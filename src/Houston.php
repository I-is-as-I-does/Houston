<?php
/* This file is part of Houston | ExoProject | (c) 2021 I-is-as-I-does | MIT License */
namespace ExoProject\Houston;

class Houston implements Houston_i
{
    protected $selfLog = [];
    private $emergencyVal = [
        'configDoc'=>'config\\houston.json',
        'historyLimit'=>366,
        'dfltLvl'=>2,
        'subjectText'=>'Error report',
        'fallbackText'=>'Apologies; this page is in maintenance mode.',
        'logDir'=>'emergencyLog'
    ];
    private $dateFormat = 'Y-m-d H:i:s \G\M\TO';
    private $settings;
    private $profiles;
    private $emergPathInUse = false;


    public function __construct($datatolog = null, $origin = null, $lvl = null, $configPath = null)
    {/* @doc: 
        - Calling Houston without arguments will set up default config.

        - Passing a custom $configPath without logging something right away is also valid.
        new Houston(null,null,null,'custom/path/to/config.json'); 

        - $datatolog can also nest origin and lvl:
        $datatolog = ['data'=>"error msg", 'origin'=>__FILE__, 'lvl'=>2];
    
        - Log record will obviously be timestamped, so no need to add one. 
     */

        $this->validateAndSetConfig($configPath);

        if ($datatolog !== null) {
            $this->handle($datatolog, $origin, $lvl);
        }
    }

    protected function sendEmail($subject, $content, $sender, $recipient){
        /* @doc: 
           use your own email handler here; return true if success; false if error;
           or 
           use ExoProject\Jacks\Trinkets;
           return Trinkets::sendEmail($subject, $content, $sender, $recipient);
        */
        return false;
    }

    protected function randomLogName(){
        $randomChars = sha1(rand());
         /* @doc: or
         use ExoProject\Jacks\Token;
         $randomChars = Token::sha40char();
         */

        return $randomChars.'.json';
    }  
    
    protected function isOutOfDate($origin, $target, $limit)
    {
        $target = date_create($target);
        $origin = date_create($origin);
        $interval = $origin->diff($target);
        $interval = $interval->format('%a');
        /* @doc: or
         use ExoProject\Jacks\Time;
         $interval = Time::getInterval($origin, $target, '%a');
         */
       
        if ((int)$interval > $limit) {
            return true;
        }
        return false;
    }

    private function getEmergConfigPath()
    {
        return dirname(__DIR__).'\\'.$this->$emergencyVal['configDoc'];
    }

    private function getEmergLogPath()
    {
        $emergDir = dirname(__DIR__).'\\'.$this->$emergencyVal['logDir'];
        if(!is_dir($emergDir)){
            mkdir($emergDir);
        }
        return $emergDir.'\\'. .$this->randomLogName();
    }

    private function readConfigFile($configPath){
        $read = json_decode(file_get_contents($configPath), true);
        if(!empty($read) && is_array($read)){
            return $read;
        }
        return false;
    }

    private function getConfigContent($configPath){
        $this->selfLog = [];

        $config = [];
        if(!empty($configPath)){
            if (!file_exists($configPath)) {
                $this->selfLog[] = 'given config path is invalid';
            }
            else {
                $read = $this->readConfigFile($configPath);
                if($read !== false){
                    $config = $read;
                } else {
                    $this->selfLog[] = 'either empty or invalid config json';
                }
            }
        }

        if(empty($config)){

         $configPath = $this->getDfltConfigPath();
            if (!file_exists($configPath)) {
                $this->selfLog[] = 'default config path is invalid';
            } else {
                $read = $this->readConfigFile($configPath);
                if($read !== false){
                    $config = $read;
                } else {
                    $this->selfLog[] = 'either empty or invalid default config json';
                }
            }
        }       
            return $config;
    }

    public function validateAndSetConfig($configPath = null) {
        $config = $this->getConfigContent($configPath);

        foreach(['settings','profiles'] as $branch){
            if(!isset($config[$branch])){
                $config[$branch] = [];
                $this->selfLog[] = "'$branch' are not set";
            }
        }

        $settings = $config['settings'];
        $profiles = $config['profiles'];

        $validSenderEmail = false;
        if(!empty($settings["senderEmail"])){
            if(filter_var($settings["senderEmail"], FILTER_VALIDATE_EMAIL)){
                $validSenderEmail = true;
            } else {
                $this->selfLog[] = $settings["senderEmail"].' is not a valid email';
            }
        } 
       
        if (!isset($settings["dlftLvl"])) {
            $settings["dlftLvl"] = $this->emergencyVal["dlftLvl"];
            $this->selfLog[] = 'default lvl is not set';
        }

        $dfltLvl = $settings["dlftLvl"];
        if(!isset($profiles[$dfltLvl])){
            $profiles[$dfltLvl] = [];
            $this->selfLog[] = 'profile of default lvl is not set';
        }

        $this->emergPathInUse = false;
        if(empty($profiles[$dfltLvl]['logPath']) ||
           !is_writable(dirname($profiles[$dfltLvl]['logPath']))){
            $profiles[$dfltLvl]['logPath'] = $this->getEmergLogPath();
            $this->emergPathInUse = true;
            $this->selfLog[] = 'default lvl log path is either not set or invalid';
        }

         foreach($profiles as $k => $profile) {
            if($k != $dfltLvl && (empty($profile['logPath'] || !is_writable(dirname($profile['logPath']))))){
                $profiles[$k]['logPath'] = $profiles[$dfltLvl]['logPath'];
                $this->selfLog[] = "profile '$k': log path is either not set or invalid";
                }
            if(empty($profile['historyLimit']) || $profile['historyLimit'] < 1 || is_float($profile['historyLimit']){
                $profiles[$k]['historyLimit'] = $this->emergencyVal['historyLimit'];
                $this->selfLog[] = "profile '$k': history limit is either not set or invalid";
            }

            if (isset($profile['sendMail']) && !empty($profile['sendMail']["isActive"])) {
                if(!$validSenderEmail || empty($profile['sendMail']['recipientEmail']) || !filter_var($profile['sendMail']['recipientEmail'], FILTER_VALIDATE_EMAIL)){
                    $profiles[$k]['sendMail'] = false;
                    if($validSenderEmail){
                        $this->selfLog[] = "profile '$k': recipient email is either not set or invalid";
                    }
                } elseif(empty($profile['sendMail']['subjectText']){
                    $profiles[$k]['sendMail']['subjectText'] = $_SERVER['HTTP_HOST'].': '.$this->emergencyVal['subjectText'];
                    $this->selfLog[] = "profile '$k': email subject is not set";
                }
            }        
            if(!empty($profile['exitAfterLog'])){
                if(empty($profile['exitAfterLog']["isActive"])){
                    $profile['exitAfterLog'] = false;
                } else {
                if(empty($profile['exitAfterLog']["fallbackText"])){
                    $profiles[$k]['exitAfterLog']["fallbackText"] = $this->emergencyVal['fallbackText'];
                    $this->selfLog[] = "profile '$k': fallback text is not set";
                }
                if(!empty($profile['exitAfterLog']["pagePath"]) && !file_exists($profile['exitAfterLog']["pagePath"])){
                    $profiles[$k]['exitAfterLog']["pagePath"] = false;
                    $this->selfLog[] = "profile '$k': exit page path is invalid";
                }
            }           
            }
            $this->profiles = $profiles;
            $this->settings = $settings;
            $this->handleSelfLog();
    }

    public function handle($datatolog, $origin = null, $lvl = null)
    {
        if (!isset($this->profiles) || !isset($this->settings)) {
            $this->validateAndSetConfig();
        }
        if ($lvl === null) {
            if (isset($datatolog['lvl'])) {
                $lvl = $datatolog['lvl'];
                unset($datatolog['lvl']);
            } else {
                $lvl = $this->settings["dlftLvl"];
            }
        }

        if (empty($this->profiles[$lvl])) {
            $this->selfLog[] = "specified level '".$lvl."' does not exist";
            $lvl = $this->settings["dlftLvl"];
        }

        $lvldata = $this->profiles[$lvl];

        if (empty($origin)) {
            if (isset($datatolog['origin'])) {
                $origin = $datatolog['origin'];
                unset($datatolog['origin']);
            } else {
                $origin = debug_backtrace()[0]['file'];
            }
        }

        if (isset($datatolog['data'])) {
            $datatolog = $datatolog['data'];
        }

        $content = ['origin' => $origin, 'data' => $datatolog];

        if (!empty($lvldata["logPath"])) {
            $writelog = $this->recordLog($lvldata["logPath"], $content, $lvldata["historyLimit"]);
            if ($writelog === false) {
                $this->selfLog[] = 'an error occured while trying to record log';
                $content['HoustonLog'] = $this->selfLog;
                $this->profiles[$lvl]["logPath"] = false;
            }
        }

        if (!empty($lvldata["sendMail"])) {
            
            $sendemail = $this->sendEmail($lvldata["subjectText"], json_encode($content, JSON_PRETTY_PRINT), $this->settings["senderEmail"], $lvldata["sendMail"]["recipientEmail"]);
            if ($sendemail === false) {
                $this->profiles[$lvl]["sendMail"] = false;
                $this->selfLog = "sending email notification failed";
            }
        }
            $this->handleSelfLog();

        if(!empty($lvldata["exitAfterLog"])){
            $exitContent = $this->getExitContent($lvldata["exitAfterLog"]);
            $this->outputExitContent($exitContent);
        }

    }

    private function cleanSelfLog(){
        $this->selfLog = [];
        return true;
    }
   
    protected function handleSelfLog(){
        if (empty($this->selfLog)){
            return true;
        }
        if (!isset($this->profiles) || !isset($this->settings)) {
            return $this->cleanSelfLog();
        }
   
        if(!empty($this->profiles['1']) && !empty($this->profiles[1]["logPath"])){
                $record = $this->recordLog($this->profiles[1]["logPath"], $this->selfLog, $this->profiles[1]["historyLimit"]);
                if($record !== false){
                    return $this->cleanSelfLog();
                }
            } 
            if($this->settings["dlftLvl"] !== 1){
                $record = $this->recordLog($this->profiles[$this->settings["dlftLvl"]]["logPath"], $this->selfLog, $this->profiles[$this->settings["dlftLvl"]]["historyLimit"]);
                if($record !== false){
                    return $this->cleanSelfLog();
                }
            }
            if ($this->emergPathInUse !== true) {
                $record = $this->recordLog($this->getEmergLogPath(), $this->selfLog, $this->emergencyVal['historyLimit']);
                if($record !== false){
                    return $this->cleanSelfLog();
                }
            }
            $this->emergPathInUse = true;
            return false;
    }
    
    protected function recordLog($logpath, $content, $historyLimit)
    {
        if (!is_writable(dirname($logpath))) {
            return false;
        }
        $timestamp = date($this->$dateFormat);
        $log = [];
        if (file_exists($logpath)) {
            $decodlog = json_decode(file_get_contents($logpath), true);
            if (!empty($decodlog)) {
                //@todo: test
                $timestamps = array_keys($decodlog);
                $c = 0;
                $historyLimit;
                while ($this->isOutOfDate(($timestamps[$c], $timestamp, $historyLimit)) {
                    unset($decodlog[$timestamps[$c]]);
                    $c++;
                }
                $log = $decodlog;
            }
        }
        $log[$timestamp] = $content;
        return file_put_contents($logpath, json_encode($log, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function getExitContent($exitData)
    {
        if (!empty($exitData["exitPagePath"])) {
                ob_start();
                include($exitData["exitPagePath"]);
                $exitContent = ob_get_clean();
                if(!empty($exitContent)){
                    return $exitContent;
                }
            }
        return $exitData["exitFallback"];
    }

    private function outputExitContent($exitContent)
    {
        exit($exitContent);
    }

}
