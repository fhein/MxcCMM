<?php

namespace MxcCommons\Plugin\Service;

use Shopware\Components\Model\ModelManager;

interface DatabaseAwareInterface
{
    public function setDatabase($db);
}