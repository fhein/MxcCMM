<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\Sql;

use MxcCommons\Db\Adapter\AdapterInterface;
use MxcCommons\Db\Adapter\StatementContainerInterface;

interface PreparableSqlInterface
{
    /**
     * @param AdapterInterface            $adapter
     * @param StatementContainerInterface $statementContainer
     *
     * @return void
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer);
}
