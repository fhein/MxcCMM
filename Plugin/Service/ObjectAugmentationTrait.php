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
            $object->setModelManager($container->get('models'));
        }
        if ($object instanceof DatabaseAwareInterface) {
            $object->setDatabase($container->get('db'));
        }
        if ($object instanceof ClassConfigAwareInterface) {
            $object->setClassConfig($this->getClassConfig($container, get_class($object)));
        }
        if (method_exists($object, 'init')) {
            $object->init();
        }

        return $object;
    }
}