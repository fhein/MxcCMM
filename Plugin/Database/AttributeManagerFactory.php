<?php

namespace MxcCommons\Plugin\Database;

use Doctrine\ORM\Tools\SchemaTool;
use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class AttributeManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $attributes = $config['doctrine']['attributes'] ?? [];
        $attributeManager = $container->get('shopware_attribute.crud_service');
        $models = $container->get('models');
        $schemaTool = new SchemaTool($models);
        $metaDataCache = $models->getConfiguration()->getMetadataCacheImpl();
        return new AttributeManager(
            $attributes,
            $attributeManager,
            $schemaTool,
            $metaDataCache
        );
    }
}