<?php

namespace MxcCommons\Plugin\Database;

use Doctrine\ORM\Tools\SchemaTool;
use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class AttributeManagerFactory extends AugmentedObjectFactory
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $attributes = $config['doctrine']['attributes'] ?? [];
        $attributeManager = $container->get('shopware_attribute.crud_service');
        $models = $container->get('models');
        $schemaTool = new SchemaTool($models);
        $metaDataCache = $models->getConfiguration()->getMetadataCacheImpl();
        return $this->augment($container, new AttributeManager(
            $attributes,
            $attributeManager,
            $schemaTool,
            $metaDataCache));
    }
}