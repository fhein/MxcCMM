<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\RowGateway;

use MxcCommons\Db\Adapter\AdapterInterface;
use MxcCommons\Db\Sql\Sql;
use MxcCommons\Db\Sql\TableIdentifier;

class RowGateway extends AbstractRowGateway
{
    /**
     * Constructor
     *
     * @param string $primaryKeyColumn
     * @param string|TableIdentifier $table
     * @param AdapterInterface|Sql $adapterOrSql
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($primaryKeyColumn, $table, $adapterOrSql = null)
    {
        // setup primary key
        $this->primaryKeyColumn = empty($primaryKeyColumn) ? null : (array) $primaryKeyColumn;

        // set table
        $this->table = $table;

        // set Sql object
        if ($adapterOrSql instanceof Sql) {
            $this->sql = $adapterOrSql;
        } elseif ($adapterOrSql instanceof AdapterInterface) {
            $this->sql = new Sql($adapterOrSql, $this->table);
        } else {
            throw new Exception\InvalidArgumentException('A valid Sql object was not provided.');
        }

        if ($this->sql->getTable() !== $this->table) {
            throw new Exception\InvalidArgumentException(
                'The Sql object provided does not have a table that matches this row object'
            );
        }

        $this->initialize();
    }
}
