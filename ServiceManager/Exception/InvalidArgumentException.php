<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\ServiceManager\Exception;

use InvalidArgumentException as SplInvalidArgumentException;
use MxcCommons\ServiceManager\Factory\AbstractFactoryInterface;
use MxcCommons\ServiceManager\Initializer\InitializerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcCommons\ServiceManager\Factory\DelegatorFactoryInterface;

class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
    /**
     * @param mixed $initializer
     * @return self
     */
    public static function fromInvalidInitializer($initializer)
    {
        return new self(sprintf(
            'An invalid initializer was registered. Expected a valid function name, '
            . 'class name, a callable or an instance of "%s", but "%s" was received.',
            InitializerInterface::class,
            is_object($initializer) ? get_class($initializer) : gettype($initializer)
        ));
    }

    /**
     * @param mixed $factory
     * @return self
     */
    public static function fromInvalidFactory($factory)
    {
        return new self(sprintf(
            'An invalid factory was registered. Expected a valid function name, '
            . 'class name, a callable or an instance of "%s", but "%s" was received.',
            FactoryInterface::class,
            is_object($factory) ? get_class($factory) : gettype($factory)
        ));
    }

    /**
     * @param mixed $abstractFactory
     * @return self
     */
    public static function fromInvalidAbstractFactory($abstractFactory)
    {
        return new self(sprintf(
            'An invalid abstract factory was registered. Expected an instance of or a valid'
            . ' class name resolving to an implementation of "%s", but "%s" was received.',
            AbstractFactoryInterface::class,
            is_object($abstractFactory) ? get_class($abstractFactory) : gettype($abstractFactory)
        ));
    }

    public static function fromInvalidDelegatorFactoryClass($name)
    {
        return new self(sprintf(
            'An invalid delegator factory was registered; resolved to class or function "%s" '
            . 'which does not exist; please provide a valid function name or class name resolving '
            . 'to an implementation of %s',
            $name,
            DelegatorFactoryInterface::class
        ));
    }

    public static function fromInvalidDelegatorFactoryInstance($name)
    {
        return new self(sprintf(
            'A non-callable delegator, "%s", was provided; expected a callable or instance of "%s"',
            is_object($name) ? get_class($name) : gettype($name),
            DelegatorFactoryInterface::class
        ));
    }
}
