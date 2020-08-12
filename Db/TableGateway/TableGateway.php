<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\TableGateway;

use MxcCommons\Db\Adapter\AdapterInterface;
use MxcCommons\Db\ResultSet\ResultSet;
use MxcCommons\Db\ResultSet\ResultSetInterface;
use MxcCommons\Db\Sql\Sql;
use MxcCommons\Db\Sql\TableIdentifier;

class TableGateway extends AbstractTableGateway
{
    /**
     * Constructor
     *
     * @param string|TableIdentifier|array                                              $table
     * @param AdapterInterface                                                          $adapter
     * @param Feature\AbstractFeature|Feature\FeatureSet|Feature\AbstractFeature[]|null $features
     * @param ResultSetInterface|null                                                   $resultSetPrototype
     * @param Sql|null                                                                  $sql
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        $table,
        AdapterInterface $adapter,
        $features = null,
        ResultSetInterface $resultSetPrototype = null,
        Sql $sql = null
    ) {
        // table
        if (! (is_string($table) || $table instanceof TableIdentifier || is_array($table))) {
            throw new Exception\InvalidArgumentException(
                'Table name must be a string or an instance of MxcCommons\Db\Sql\TableIdentifier'
            );
        }
        $this->table = $table;

        // adapter
        $this->adapter = $adapter;

        // process features
        if ($features !== null) {
            if ($features instanceof Feature\AbstractFeature) {
                $features = [$features];
            }
            if (is_array($features)) {
                $this->featureSet = new Feature\FeatureSet($features);
            } elseif ($features instanceof Feature\FeatureSet) {
                $this->featureSet = $features;
            } else {
                throw new Exception\InvalidArgumentException(
                    'TableGateway expects $feature to be an instance of an AbstractFeature or a FeatureSet, or an '
                    . 'array of AbstractFeatures'
                );
            }
        } else {
            $this->featureSet = new Feature\FeatureSet();
        }

        // result prototype
        $this->resultSetPrototype = ($resultSetPrototype) ?: new ResultSet;

        // Sql object (factory for select, insert, update, delete)
        $this->sql = ($sql) ?: new Sql($this->adapter, $this->table);

        // check sql object bound to same table
        if ($this->sql->getTable() != $this->table) {
            throw new Exception\InvalidArgumentException(
                'The table inside the provided Sql object must match the table of this TableGateway'
            );
        }

        $this->initialize();
    }
}
