<?php

namespace MxcCommons\Plugin\Service;

use Shopware\Components\Model\ModelManager;

trait ModelManagerAwareTrait
{
    protected $modelManager;

    public function setModelManager(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }
}