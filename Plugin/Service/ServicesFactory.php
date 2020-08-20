<?php /** @noinspection PhpIncludeInspection */

namespace MxcCommons\Plugin\Service;

use MxcCommons\Plugin\Database\AttributeManager;
use MxcCommons\Plugin\Database\BulkOperation;
use MxcCommons\Plugin\Database\SchemaManager;
use MxcCommons\Plugin\Mail\MailManager;
use MxcCommons\Plugin\Shopware\AuthServiceFactory;
use MxcCommons\Plugin\Shopware\ConfigurationFactory;
use MxcCommons\Plugin\Shopware\CrudServiceFactory;
use MxcCommons\Plugin\Shopware\dbal_connectionFactory;
use MxcCommons\Plugin\Shopware\DbFactory;
use MxcCommons\Plugin\Shopware\MediaServiceFactory;
use MxcCommons\Plugin\Shopware\ModelManagerFactory;
use MxcCommons\Plugin\Shopware\ShopwareServicesFactory;
use MxcCommons\Plugin\Utility\StringUtility;
use MxcCommons\Log\Formatter\Simple;
use MxcCommons\Log\Logger;
use MxcCommons\Log\LoggerServiceFactory;
use MxcCommons\ServiceManager\ServiceManager;

class ServicesFactory
{
    private $serviceConfig = [
        'factories' => [
            // because we have our own config service, we can not use config to access the Shopware config
            'shopwareConfig'            => ConfigurationFactory::class,

            // services
            Logger::class               => LoggerServiceFactory::class,
            BulkOperation::class        => AugmentedObjectFactory::class,

        ],
        'magicals' => [
            SchemaManager::class,
            AttributeManager::class,
            MailManager::class,
        ],
        'delegators' => [
            Logger::class => [
                LoggerDelegatorFactory::class,
            ],
        ],
        'aliases' => [
            'logger' => Logger::class,
        ],
        'abstract_factories' => [
            ShopwareServicesFactory::class,
        ],
    ];

    protected function getLogFileName(string $pluginClass) {
        return strtolower(StringUtility::camelCaseToUnderscore($pluginClass));
    }

    protected function getLoggerConfig(string $pluginName) {
        return [
            'writers' => [
                'stream' => [
                    'name' => 'stream',
                    'priority'  => Logger::ALERT,
                    'options'   => [
                        'stream'    => Shopware()->DocPath() . 'var/log/' . $this->getLogFileName($pluginName) . '-' . date('Y-m-d') . '.log',
                        'formatter' => [
                            'name'      => Simple::class,
                            'options'   => [
                                'format'            => '%timestamp% %priorityName%: %message% %extra%',
                                'dateTimeFormat'    => 'H:i:s',
                            ],
                        ],
                        'filters' => [
                            'priority' => [
                                'name' => 'priority',
                                'options' => [
                                    'operator' => '<=',
                                    'priority' => Logger::DEBUG,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getServices(string $pluginDir) {
        $pluginName = substr(strrchr($pluginDir, '/'), 1);
        $configDir = $pluginDir . '/Config';
        $configFile = $configDir . '/plugin.config.php';
        $services = new ServiceManager($this->serviceConfig);
        $config = [];
        if (file_exists($configFile)) {
            $config = include $configFile;
        }
        if (! isset($config['log'])) {
            $config['log'] = $this->getLoggerConfig($pluginName);
        }
        $services->setAllowOverride(true);
        $services->configure($config['services'] ?? []);
        $config['plugin_config_path'] = $configDir;
        $services->setService('config', $config);
        $services->setService('services', $services);
        $services->setAllowOverride(false);

        return $services;

    }
}