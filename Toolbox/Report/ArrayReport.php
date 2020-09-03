<?php

namespace MxcCommons\Toolbox\Report;

use MxcCommons\Plugin\Utility\StringUtility;
use MxcCommons\Toolbox\Config\Config;

class ArrayReport
{
    protected $reportDir;

    public function __construct(string $class = null)
    {
        $this->reportDir = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs';
        if (null !== $class) {
            $this->reportDir .= '/' . $this->getFileName($class);
        }
        if (file_exists($this->reportDir) && ! is_dir($this->reportDir)) {
            unlink($this->reportDir);
        }
        if (! is_dir($this->reportDir)) {
            mkdir($this->reportDir, 0777, true);
        }
    }

    public function __invoke(array $topics) {
        if (! $topics || empty($topics)) return;

        foreach ($topics as $what => $topic) {
            if (! is_string($what)) continue;
            $fn = $this->getFileName($what);
            $this->writeFiles($topic, $fn);
        }
    }

    /**
     * @param array $topic
     * @param string $fn
     */
    protected function writeFiles(array $topic, string $fn)
    {
        $dir = $this->reportDir . '/';
        $actFile = $dir . $fn . '.php';
        /** @noinspection PhpUnusedLocalVariableInspection */
        $diffFile = $dir . $fn . '.diff.php';

        // @todo: There is a bug in here which can be seen running console import
//        if (file_exists($actFile)) {
//            /** @noinspection PhpIncludeInspection */
//            $old = include $actFile;
//            $diff = array_diff($topic, $old);
//            if (empty($diff)) {
//                if (file_exists($diffFile)) {
//                    unlink($diffFile);
//                }
//            } else {
//                Config::toFile($diffFile, array_diff($topic, $old));
//            }
//        }
        Config::toFile($actFile, $topic);
    }

    protected function getFileName(string $value) {
        return StringUtility::toLowerCase(StringUtility::camelCaseToUnderscore($value));
    }
}