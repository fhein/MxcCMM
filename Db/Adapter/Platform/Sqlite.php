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
use MxcCommons\Db\Adapter\Exception;
use MxcCommons\Db\Adapter\Exception\InvalidArgumentException;

class Sqlite extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifier = ['"','"'];

    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifierTo = '\'';

    /**
     * @var \PDO
     */
    protected $resource = null;

    /**
     * @param null|\MxcCommons\Db\Adapter\Driver\Pdo\Pdo||\PDO $driver
     */
    public function __construct($driver = null)
    {
        if ($driver) {
            $this->setDriver($driver);
        }
    }

    /**
     * @param \MxcCommons\Db\Adapter\Driver\Pdo\Pdo|\PDO $driver
     * @return self Provides a fluent interface
     * @throws InvalidArgumentException
     */
    public function setDriver($driver)
    {
        if (($driver instanceof \PDO && $driver->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'sqlite')
            || ($driver instanceof Pdo\Pdo && $driver->getDatabasePlatformName() == 'Sqlite')
        ) {
            $this->resource = $driver;
            return $this;
        }

        throw new InvalidArgumentException(
            '$driver must be a Sqlite PDO MxcCommons\Db\Adapter\Driver, Sqlite PDO instance'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'SQLite';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteValue($value)
    {
        $resource = $this->resource;

        if ($resource instanceof DriverInterface) {
            $resource = $resource->getConnection()->getResource();
        }

        if ($resource instanceof \PDO) {
            return $resource->quote($value);
        }

        return parent::quoteValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function quoteTrustedValue($value)
    {
        $resource = $this->resource;

        if ($resource instanceof DriverInterface) {
            $resource = $resource->getConnection()->getResource();
        }

        if ($resource instanceof \PDO) {
            return $resource->quote($value);
        }

        return parent::quoteTrustedValue($value);
    }
}
