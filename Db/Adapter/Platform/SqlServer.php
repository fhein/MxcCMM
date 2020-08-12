<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\Adapter\Platform;

use MxcCommons\Db\Adapter\Driver\DriverInterface;
use MxcCommons\Db\Adapter\Driver\Pdo;
use MxcCommons\Db\Adapter\Driver\Sqlsrv\Sqlsrv;
use MxcCommons\Db\Adapter\Exception;
use MxcCommons\Db\Adapter\Exception\InvalidArgumentException;

class SqlServer extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifier = ['[',']'];

    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifierTo = '\\';

    /**
     * @var resource|\PDO
     */
    protected $resource = null;

    /**
     * @param null|Sqlsrv|\MxcCommons\Db\Adapter\Driver\Pdo\Pdo|resource|\PDO $driver
     */
    public function __construct($driver = null)
    {
        if ($driver) {
            $this->setDriver($driver);
        }
    }

    /**
     * @param Sqlsrv|\MxcCommons\Db\Adapter\Driver\Pdo\Pdo|resource|\PDO $driver
     * @return self Provides a fluent interface
     * @throws InvalidArgumentException
     */
    public function setDriver($driver)
    {
        // handle MxcCommons\Db drivers
        if (($driver instanceof Pdo\Pdo && in_array($driver->getDatabasePlatformName(), ['SqlServer', 'Dblib']))
            || ($driver instanceof \PDO && in_array($driver->getAttribute(\PDO::ATTR_DRIVER_NAME), ['sqlsrv', 'dblib']))
        ) {
            $this->resource = $driver;
            return $this;
        }

        throw new InvalidArgumentException(
            '$driver must be a Sqlsrv PDO MxcCommons\Db\Adapter\Driver or Sqlsrv PDO instance'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'SQLServer';
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteIdentifierSymbol()
    {
        return $this->quoteIdentifier;
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifierChain($identifierChain)
    {
        return '[' . implode('].[', (array) $identifierChain) . ']';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteValue($value)
    {
        if ($this->resource instanceof DriverInterface) {
            $this->resource = $this->resource->getConnection()->getResource();
        }
        if ($this->resource instanceof \PDO) {
            return $this->resource->quote($value);
        }
        trigger_error(
            'Attempting to quote a value in ' . __CLASS__ . ' without extension/driver support '
                . 'can introduce security vulnerabilities in a production environment.'
        );

        return '\'' . str_replace('\'', '\'\'', addcslashes($value, "\000\032")) . '\'';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteTrustedValue($value)
    {
        if ($this->resource instanceof DriverInterface) {
            $this->resource = $this->resource->getConnection()->getResource();
        }
        if ($this->resource instanceof \PDO) {
            return $this->resource->quote($value);
        }
        return '\'' . str_replace('\'', '\'\'', $value) . '\'';
    }
}
