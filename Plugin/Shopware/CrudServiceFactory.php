<?php

namespace MxcCommons\Plugin\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class CrudServiceFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return Shopware()->Container()->get('shopware_attribute.crud_service');
    }
}