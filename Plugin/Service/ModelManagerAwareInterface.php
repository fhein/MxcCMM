<?php

namespace MxcCommons\Plugin\Service;

use Shopware\Components\Model\ModelManager;

interface ModelManagerAwareInterface
{
    public function setModelManager(ModelManager $modelManager);
}