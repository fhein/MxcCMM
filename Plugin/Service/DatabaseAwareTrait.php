<?php

namespace MxcCommons\Plugin\Service;

use Shopware\Components\Model\ModelManager;

trait DatabaseAwareTrait
{
    protected $db;

    public function setDatabase($db)
    {
        $this->db = $db;
    }
}