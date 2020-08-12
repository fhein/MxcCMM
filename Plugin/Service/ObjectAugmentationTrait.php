<?php

namespace MxcCommons\Plugin\Service;

use MxcCommons\Interop\Container\ContainerInterface;

trait ObjectAugmentationTrait {

    use ClassConfigTrait;

    public function augment(ContainerInterface $container, object $object)
    {
        if ($object instanceof LoggerAwareInterface) {
            $object->setLog($container->get('logger'));
        }
        if ($object instanceof ModelManagerAwareInterface) {
            $object->setModelManager($container->get('modelManager'));
        }
        if ($object instanceof ClassConfigAwareInterface) {
            $object->setClassConfig($this->getClassConfig($container, get_class($object)));
        }
        return $object;
    }
}