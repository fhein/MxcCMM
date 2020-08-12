<?php

namespace MxcCommons\Plugin\Service;

use MxcCommons\Interop\Container\ContainerInterface;

trait ClassConfigTrait
{
    protected function getClassConfig(ContainerInterface $container, string $class): array
    {
        $config = $container->get('config');
        $config = $config['class_config'][$class] ?? [];

        if (is_string($config)) {
            $pluginConfigPath = $container->get('config')['plugin_config_path'];
            $configFile = $pluginConfigPath . '/' . $config;
            if (! file_exists($configFile)) return [];
            /** @noinspection PhpIncludeInspection */
            $config = include $configFile;
        }

        if (! is_array($config)) return [];

        return $config;
    }
}