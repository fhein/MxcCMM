<?php

namespace MxcCommons\Plugin\Database;

use Doctrine\ORM\Tools\SchemaTool;
use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class SchemaManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $models = $config['doctrine']['models'] ?? [];
        $attributes = $config['doctrine']['attributes'] ?? [];
        $attributeManager = $container->get('shopware_attribute.crud_service');
        $modelManager = $container->get('models');
        $schemaTool = new SchemaTool($modelManager);
        $metaDataCache = $modelManager->getConfiguration()->getMetadataCacheImpl();
        return new SchemaManager(
            $models,
            $attributes,
            $attributeManager,
            $schemaTool,
            $metaDataCache
        );
    }
}