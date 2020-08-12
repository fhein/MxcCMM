<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Validator\Db;

use MxcCommons\Db\Adapter\Adapter as DbAdapter;
use MxcCommons\Db\Adapter\AdapterAwareInterface;
use MxcCommons\Db\Adapter\AdapterAwareTrait;
use MxcCommons\Db\Sql\Select;
use MxcCommons\Db\Sql\Sql;
use MxcCommons\Db\Sql\TableIdentifier;
use MxcCommons\Stdlib\ArrayUtils;
use MxcCommons\Validator\AbstractValidator;
use MxcCommons\Validator\Exception\InvalidArgumentException;
use MxcCommons\Validator\Exception\RuntimeException;
use Traversable;

/**
 * Class for Database record validation
 */
abstract class AbstractDb extends AbstractValidator implements AdapterAwareInterface
{
    use AdapterAwareTrait;

    /**
     * Error constants
     */
    const ERROR_NO_RECORD_FOUND = 'noRecordFound';
    const ERROR_RECORD_FOUND    = 'recordFound';

    /**
     * @var array Message templates
     */
    protected $messageTemplates = [
        self::ERROR_NO_RECORD_FOUND => "No record matching the input was found",
        self::ERROR_RECORD_FOUND    => "A record matching the input was found",
    ];

    /**
     * Select object to use. can be set, or will be auto-generated
     *
     * @var Select
     */
    protected $select;

    /**
     * @var string
     */
    protected $schema = null;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $field = '';

    /**
     * @var mixed
     */
    protected $exclude = null;

    /**
     * Provides basic configuration for use with MxcCommons\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * Exclude can either be a String containing a where clause, or an array with `field` and `value` keys
     * to define the where clause added to the sql.
     * A database adapter may optionally be supplied to avoid using the registered default adapter.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'schema'  => The schema keys
     * 'field'   => The field to check for a match
     * 'exclude' => An optional where clause or field/value pair to exclude from the query
     * 'adapter' => An optional database adapter to use
     *
     * @param array|Traversable|Select $options Options to use for this validator
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($options instanceof Select) {
            $this->setSelect($options);
            return;
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (func_num_args() > 1) {
            $options       = func_get_args();
            $firstArgument = array_shift($options);
            if (is_array($firstArgument)) {
                $temp = ArrayUtils::iteratorToArray($firstArgument);
            } else {
                $temp['table'] = $firstArgument;
            }

            $temp['field'] = array_shift($options);

            if (! empty($options)) {
                $temp['exclude'] = array_shift($options);
            }

            if (! empty($options)) {
                $temp['adapter'] = array_shift($options);
            }

            $options = $temp;
        }

        if (! array_key_exists('table', $options) && ! array_key_exists('schema', $options)) {
            throw new InvalidArgumentException('Table or Schema option missing!');
        }

        if (! array_key_exists('field', $options)) {
            throw new InvalidArgumentException('Field option missing!');
        }

        if (array_key_exists('adapter', $options)) {
            $this->setAdapter($options['adapter']);
        }

        if (array_key_exists('exclude', $options)) {
            $this->setExclude($options['exclude']);
        }

        $this->setField($options['field']);
        if (array_key_exists('table', $options)) {
            $this->setTable($options['table']);
        }

        if (array_key_exists('schema', $options)) {
            $this->setSchema($options['schema']);
        }
    }

    /**
     * Returns the set adapter
     *
     * @throws RuntimeException When no database adapter is defined
     * @return DbAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets a new database adapter
     *
     * @param  DbAdapter $adapter
     * @return self Provides a fluent interface
     */
    public function setAdapter(DbAdapter $adapter)
    {
        return $this->setDbAdapter($adapter);
    }

    /**
     * Returns the set exclude clause
     *
     * @return string|array
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * Sets a new exclude clause
     *
     * @param string|array $exclude
     * @return self Provides a fluent interface
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;
        $this->select  = null;
        return $this;
    }

    /**
     * Returns the set field
     *
     * @return string|array
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets a new field
     *
     * @param string $field
     * @return AbstractDb
     */
    public function setField($field)
    {
        $this->field  = (string) $field;
        $this->select = null;
        return $this;
    }

    /**
     * Returns the set table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets a new table
     *
     * @param string $table
     * @return self Provides a fluent interface
     */
    public function setTable($table)
    {
        $this->table  = (string) $table;
        $this->select = null;
        return $this;
    }

    /**
     * Returns the set schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Sets a new schema
     *
     * @param string $schema
     * @return self Provides a fluent interface
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        $this->select = null;
        return $this;
    }

    /**
     * Sets the select object to be used by the validator
     *
     * @param  Select $select
     * @return self Provides a fluent interface
     */
    public function setSelect(Select $select)
    {
        $this->select = $select;
        return $this;
    }

    /**
     * Gets the select object to be used by the validator.
     * If no select object was supplied to the constructor,
     * then it will auto-generate one from the given table,
     * schema, field, and adapter options.
     *
     * @return Select The Select object which will be used
     */
    public function getSelect()
    {
        if ($this->select instanceof Select) {
            return $this->select;
        }

        // Build select object
        $select          = new Select();
        $tableIdentifier = new TableIdentifier($this->table, $this->schema);
        $select->from($tableIdentifier)->columns([$this->field]);
        $select->where->equalTo($this->field, null);

        if ($this->exclude !== null) {
            if (is_array($this->exclude)) {
                $select->where->notEqualTo(
                    $this->exclude['field'],
                    $this->exclude['value']
                );
            } else {
                $select->where($this->exclude);
            }
        }

        $this->select = $select;

        return $this->select;
    }

    /**
     * Run query and returns matches, or null if no matches are found.
     *
     * @param  string $value
     * @return array when matches are found.
     */
    protected function query($value)
    {
        $sql = new Sql($this->getAdapter());
        $select = $this->getSelect();
        $statement = $sql->prepareStatementForSqlObject($select);
        $parameters = $statement->getParameterContainer();
        $parameters['where1'] = $value;
        $result = $statement->execute();

        return $result->current();
    }
}
