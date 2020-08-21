<?php

namespace MxcCommons\Plugin\Service;

use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

trait DatabaseAwareTrait
{
    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected  $db;

    public function setDatabase(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }
}