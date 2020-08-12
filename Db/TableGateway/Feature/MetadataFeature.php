<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\TableGateway\Feature;

use MxcCommons\Db\Metadata\MetadataInterface;
use MxcCommons\Db\Metadata\Object\ConstraintObject;
use MxcCommons\Db\TableGateway\Exception;
use MxcCommons\Db\Metadata\Object\TableObject;
use MxcCommons\Db\Metadata\Source\Factory as SourceFactory;
use MxcCommons\Db\Sql\TableIdentifier;

class MetadataFeature extends AbstractFeature
{
    /**
     * @var MetadataInterface
     */
    protected $metadata = null;

    /**
     * Constructor
     *
     * @param MetadataInterface $metadata
     */
    public function __construct(MetadataInterface $metadata = null)
    {
        if ($metadata) {
            $this->metadata = $metadata;
        }
        $this->sharedData['metadata'] = [
            'primaryKey' => null,
            'columns' => []
        ];
    }

    public function postInitialize()
    {
        if ($this->metadata === null) {
            $this->metadata = SourceFactory::createSourceFromAdapter($this->tableGateway->adapter);
        }

        // localize variable for brevity
        $t = $this->tableGateway;
        $m = $this->metadata;

        $tableGatewayTable = is_array($t->table) ? current($t->table) : $t->table;

        if ($tableGatewayTable instanceof TableIdentifier) {
            $table = $tableGatewayTable->getTable();
            $schema = $tableGatewayTable->getSchema();
        } else {
            $table = $tableGatewayTable;
            $schema = null;
        }

        // get column named
        $columns = $m->getColumnNames($table, $schema);
        $t->columns = $columns;

        // set locally
        $this->sharedData['metadata']['columns'] = $columns;

        // process primary key only if table is a table; there are no PK constraints on views
        if (! ($m->getTable($table, $schema) instanceof TableObject)) {
            return;
        }

        $pkc = null;

        foreach ($m->getConstraints($table, $schema) as $constraint) {
            /** @var $constraint ConstraintObject */
            if ($constraint->getType() == 'PRIMARY KEY') {
                $pkc = $constraint;
                break;
            }
        }

        if ($pkc === null) {
            throw new Exception\RuntimeException('A primary key for this column could not be found in the metadata.');
        }

        $pkcColumns = $pkc->getColumns();
        if (count($pkcColumns) === 1) {
            $primaryKey = $pkcColumns[0];
        } else {
            $primaryKey = $pkcColumns;
        }

        $this->sharedData['metadata']['primaryKey'] = $primaryKey;
    }
}
