<?php

namespace MxcCommons\Plugin\Service;

use Enlight_Components_Db_Adapter_Pdo_Mysql;

interface DatabaseAwareInterface
{
    public function setDatabase(Enlight_Components_Db_Adapter_Pdo_Mysql $db);
}