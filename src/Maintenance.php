<?php
/* This file is part of Onement:Heimdall | SSITU | (c) 2021 I-is-as-I-does */
namespace SSITU\Houston;

use \SSITU\Blueprints\Cron;
use \SSITU\Jack;

class Maintenance extends Cron\MaintenanceAbstract

{
    private $logsDir;
    private $logsHistoryLimit;

    public function __construct($logsDir, $logsHistoryLimit)
    {
        $this->logsDir = $logsDir;
        $this->logsHistoryLimit = $logsHistoryLimit;
    }

    public function run()
    {
        $logpaths = glob($this->logsDir . '*.json');
        if (empty($logPaths)) {
            $this->rslt = 'no-logs-found';
            $this->details['logdir'] = $this->logsDir;
        } else {
            $maxdate = Jack\Time::subTime(Jack\Time::isoStamp(), $this->logsHistoryLimit . ' days');
            foreach ($logpaths as $logpath) {
                $c = 0;
                $detK = basename($logpath);
                $logs = $this->Houston->readLogs($logpath);
                if (count($logs) > 1) {
                    foreach ($logs as $k => $log) {
                        if (!Jack\Time::isExpired($log['timestamp'], $maxdate)) {
                            unset($logs[$k]);
                            $c++;
                        }
                    }
                    if ($c > 0) {
                        $logs = trim(substr(Jack\File::prettyJsonEncode($logs), 1, -1));
                        $this->details[$detK]['saveLog'] = Jack\File::write($logs, $logpath, true);
                    }
                }
                $this->details[$detK]['cleanUpCount'] = $c;
            }
            $this->rslt = 'job-done';
        }
    }
}
