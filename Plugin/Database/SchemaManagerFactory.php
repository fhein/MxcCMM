<?php

namespace MxcCommons\Plugin\Database;

use Doctrine\ORM\Tools\SchemaTool;
use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class SchemaManagerFactory extends AugmentedObjectFactory
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
        $models = $config['doctrine']['models'] ?? [];
        $attributes = $config['doctrine']['attributes'] ?? [];
        $attributeManager = $container->get('shopware_attribute.crud_service');
        $modelManager = $container->get('models');
        $schemaTool = new SchemaTool($modelManager);
        $metaDataCache = $modelManager->getConfiguration()->getMetadataCacheImpl();
        return $this->augment($container, new SchemaManager(
            $models,
            $attributes,
            $attributeManager,
            $schemaTool,
            $metaDataCache)
        );
    }
}