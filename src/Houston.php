<?php
/* This file is part of Houston | SSITU | (c) 2021 I-is-as-I-does */
namespace SSITU\Houston;

use \SSITU\Blueprints\Shut;
use \SSITU\Jack;

class Houston implements Shut\EnderReqInterface

{
    use Shut\EnderReqTrait;

    const LVLS = [100 => "debug", 200 => "info", 250 => "notice", 300 => "warning", 400 => "error", 500 => "critical", 550 => "alert", 600 => "emergency"];

    private $logsDir;
    private $bufferLimit;

    private $channels = [];
    private $chan;

    private $selfLogFail = false;

    public function __construct($logsDir, $bufferLimit = 10)
    {
        $this->logsDir = $logsDir;
        $this->bufferLimit = $bufferLimit;

        $this->chan = 'ssitu-houston';
        $this->setEnderProperties('processBuffer');

    }

    public function Channel($chanName)
    {
        $this->checkChan($chanName);
        if (!array_key_exists('obj', $this->channels[$chanName])) {
            $this->channels[$chanName]['obj'] = new Channel($this, $chanName);
        }
        return $this->channels[$chanName]['obj'];
    }

    public function __call($level, $argm)
    {
        if (in_array($level, self::LVLS) && is_string(current($argm))) {
            array_unshift($argm, $level);
            return $this->log(...$argm);
        }
    }

    private function snapshot($context)
    {
        if (is_null($context) || $context == '') {
            return '';
        }
        $snap = trim(json_encode($context), "\n\r\t\v\0{[]}");
        if (strlen($snap) < 24) {
            return $snap;
        }

        return substr($snap, 0, 24);
    }

    public function log($level, $message, $context = [], $chanName = 'central')
    {
        $selfLog = [];

        $level = $this->resolveLvl($level);
        if ($level === false) {
            $selfLog['invalid-lvl'] = $level;
            $level = 'warning';
        }

        $this->checkChan($chanName);

        $logindex = $level . '.' . $message . '.' . $this->snapshot($context);
        if (array_key_exists($logindex, $this->channels[$chanName]['logs'])) {
            return;
        }

        $content = $this->formatContent($level, $message, $context);

        $this->channels[$chanName]['logs'][$logindex] = $content;

        if ($this->shouldSave($this->channels[$chanName])) {

            $writelog = $this->saveLogs($this->channels[$chanName]);
            if (!$writelog) {
                $selfLog['write-error'] = $chanName;
            } else {
                $this->channels[$chanName]['logs'] = [];
            }
        }

        if (!empty($selfLog) && !$this->selfLogFail) {
            if ($chanName == $this->chan) {
                $this->selfLogFail = true;
            } else {
                $this->selfLog($selfLog);
            }
        }
    }

    public function readLogs($chanPath)
    {
        if (file_exists($chanPath)) {
            return json_decode('{' . file_get_contents($path) . '}', true);
        }
        return [];
    }

    public function searchLogs($chanName, $level = null, $message = null, $maxSample = 0, $maxDate = null, $maxRslt = false)
    {
        $matches = $this->readLogs($this->chanPath($chanName));
        if (empty($matches)) {
            return false;
        }

        if (Jack\Help::isPostvInt($maxSample)) {
            $matches = array_slice($matches, -$maxSample);
        }

        $count = count($matches);
        if (!Jack\Help::isPostvInt($maxRslt)) {
            $maxRslt = $count;
        }

        $level = $this->resolveLvl($level);
        $maxDate = Jack\Time::dateObj($maxDate);

        if (empty($level) && empty($message) && empty($maxDate)) {
            return array_slice($matches, -$maxRslt);
        }

        $rslt = [];
        $filterDate = !empty($maxDate);
        $filterLevel = !empty($level);
        $filterMessage = !empty($message);

        foreach ($matches as $log) {
            if ($filterDate && date_create($log['timestamp']) > $maxDate) {
                break;
            }
            if (($filterLevel && $level != $log['level']) || ($filterMessage && $message != $log['message'])) {
                continue;
            }
            $rslt[] = $log;
            $maxRslt--;
            if ($maxRslt === 0) {
                break;
            }
        }
        return array_slice($matches, -$maxRslt);
    }

    public function processBuffer()
    {
        $pile = [];
        if (!empty($this->channels)) {
            foreach ($this->channels as $chanName => $channel) {
                if (!empty($channel['logs'])) {
                    $writelog = $this->saveLogs($channel);
                    if ($writelog) {
                        $this->channels[$chanName] = [];
                        continue;
                    }

                    $pile[] = '{ "' . $chanName . '": "' . implode(', ' . PHP_EOL, $channel['logs']) . '" }';
                }
            }
        }
        if (!empty($pile)) {
            $pile = implode(', ' . PHP_EOL, $pile);
            $emergPath = $this->chanPath('dump-' . Jack\Random::multLetters(12));
            file_put_contents($emergPath, $pile);
        }
    }

    private function selfLog($content)
    {
        if (!$this->selfLogFail) {
            if (is_array($content)) {
                $content = implode('; ', $content);
            }
            $content = ['report' => $content];
            $this->log('error', 'logging-errors', $content, $this->chan);
        }
    }

    private function checkChan($chanName)
    {
        if (!array_key_exists($chanName, $this->channels)) {
            $this->registerChan($chanName);
        }
    }

    private function registerChan($chanName)
    {
        $this->channels[$chanName] = [];
        $this->channels[$chanName]['path'] = $this->chanPath($chanName);
        $this->channels[$chanName]['logs'] = [];
    }

    private function chanPath($chanName)
    {
        return $this->logsDir . $chanName . '.txt';
    }

    private function resolveLvl($level)
    {
        if (in_array($level, self::LVLS)) {
            return $level;
        }
        if (is_string($level)) {
            $lowerlvl = strtolower($level);
            if ($lowerlvl != $level && in_array($lowerlvl, self::LVLS)) {
                return $lowerlvl;
            }
        }
        if (array_key_exists((int) $level, self::LVLS)) {
            return self::LVLS[$level];
        }
        return false;
    }

    private function formatContent($level, $message, $context = [])
    {
        $content = [
            'timestamp' => Jack\Time::isoStamp(),
            'level' => $level,
            'message' => $message];

        if (!empty($context)) {
            $content['context'] = $context;
        }
        return Jack\File::prettyJsonEncode($content);
    }

    private function shouldSave($channel)
    {
        if (empty($this->ender) || count($channel['logs']) > $this->bufferLimit) {
            return true;
        }
        return false;
    }

    private function saveLogs($channel)
    {
        $logs = implode(', ' . PHP_EOL, $channel['logs']);
        $write = Jack\File::writeAppend($logs, $channel['path']);
        if ($write) {
            return true;
        }
        usleep(200000); #0.2s
        return Jack\File::writeAppend($logs, $channel['path']);
    }

}
