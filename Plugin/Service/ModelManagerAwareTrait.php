<?php

namespace MxcCommons\Plugin\Service;

use Shopware\Components\Model\ModelManager;

trait ModelManagerAwareTrait
{
    /** @var ModelManager */
    protected $modelManager;

    public function setModelManager(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }
}