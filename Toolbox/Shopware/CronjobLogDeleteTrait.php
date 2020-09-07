<?php

namespace MxcCommons\Toolbox\Shopware;

use MxcCommons\MxcCommons;

trait CronjobLogDeleteTrait
{
    // Usage:
    // Implement a shopware cronjob which uses this trait
    // Iegister onDeleteLogs() method as cronjob action
    // Configure cronjob to run once a day

    // identifier of files to look for (e.g. mxc_dropship, mxc_commons)
    protected $logIds = [];

    // keep log files with last modification younger than $maxAge days
    // 14 days is the default, change it in your cronjob if not suitable
    protected $maxAge = 14;

    // will match files with name containing any of the $logIds in the Shopware log directory
    // does not scan subdirectories
    public function onDeleteLogs()
    {
        if (empty($this->logIds)) return false;
        $logPath = Shopware()->DocPath() . 'var/log';
        $maxAge = $this->maxAge * 3600 * 24;
        $files = scandir($logPath);
        foreach ($files as $file) {
            if ($file == '..' || $file == '.') continue;
            foreach ($this->logIds as $id) {
                if (strpos($file, $id) === false) {
                    continue;
                }
                $file = $logPath . '/' . $file;
                if (is_dir($file)) continue;

                // delete file if last modification is older than $maxAge
                $age = time() - filemtime($file);
                if ($age > $maxAge) {
                    unlink($file);
                }
            }
        }
        return true;
    }
}