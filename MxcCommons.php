<?php

namespace MxcCommons;

use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ServicesFactory;

class MxcCommons extends Plugin
{
    public const PLUGIN_DIR = __DIR__;

    private static $services;

    public static function getServices()
    {
        if (self::$services !== null) return self::$services;
        $factory = new ServicesFactory();
        self::$services = $factory->getServices(__DIR__);
        return self::$services;

    }
}


