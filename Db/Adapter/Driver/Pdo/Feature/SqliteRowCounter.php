<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\Adapter\Driver\Pdo\Feature;

use Closure;
use MxcCommons\Db\Adapter\Driver\Feature\AbstractFeature;
use MxcCommons\Db\Adapter\Driver\Pdo;
use MxcCommons\Db\Adapter\Driver\Pdo\Statement;

/**
 * SqliteRowCounter
 */
class SqliteRowCounter extends AbstractFeature
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'SqliteRowCounter';
    }

    /**
     * @param Statement $statement
     * @return int
     */
    public function getCountForStatement(Statement $statement)
    {
        $countStmt = clone $statement;
        $sql = $statement->getSql();
        if ($sql == '' || stripos($sql, 'select') === false) {
            return;
        }
        $countSql = 'SELECT COUNT(*) as "count" FROM (' . $sql . ')';
        $countStmt->prepare($countSql);
        $result = $countStmt->execute();
        $countRow = $result->getResource()->fetch(\PDO::FETCH_ASSOC);
        unset($statement, $result);
        return $countRow['count'];
    }

    /**
     * @param $sql
     * @return null|int
     */
    public function getCountForSql($sql)
    {
        if (stripos($sql, 'select') === false) {
            return;
        }
        $countSql = 'SELECT COUNT(*) as count FROM (' . $sql . ')';
        /** @var $pdo \PDO */
        $pdo = $this->driver->getConnection()->getResource();
        $result = $pdo->query($countSql);
        $countRow = $result->fetch(\PDO::FETCH_ASSOC);
        return $countRow['count'];
    }

    /**
     * @param $context
     * @return Closure
     */
    public function getRowCountClosure($context)
    {
        return function () use ($context) {
            return ($context instanceof Statement)
                ? $this->getCountForStatement($context)
                : $this->getCountForSql($context);
        };
    }
}
