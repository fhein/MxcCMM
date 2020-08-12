<?php

namespace MxcCommons\Toolbox\Shopware\Filter;

use MxcCommons\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;

class SetRepository
{
    protected $modelManager;
    protected $log;

    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

}