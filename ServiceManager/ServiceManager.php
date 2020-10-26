<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */

/**
 * @link      https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @copyright Copyright (c) 2018 maxence operations gmbh, Germany
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\ServiceManager;

use Exception;
use MxcCommons\EventManager\EventManager;
use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Interop\Container\Exception\ContainerException;
use MxcCommons\MxcCommons;
use ProxyManager\Configuration as ProxyConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use MxcCommons\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use MxcCommons\ServiceManager\Exception\CyclicAliasException;
use MxcCommons\ServiceManager\Exception\InvalidArgumentException;
use MxcCommons\ServiceManager\Exception\ServiceNotCreatedException;
use MxcCommons\ServiceManager\Exception\ServiceNotFoundException;
use MxcCommons\ServiceManager\Factory\DelegatorFactoryInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcCommons\ServiceManager\Initializer\InitializerInterface;
use function array_merge_recursive;
use function class_exists;
use function in_array;
use function is_callable;
use function is_string;
use function spl_autoload_register;
use function spl_object_hash;
use function trigger_error;
use ReflectionClass;

/**
 * Service Manager.
 *
 * Default implementation of the ServiceLocatorInterface, providing capabilities
 * for object creation via:
 *
 * - factories
 * - abstract factories
 * - delegator factories
 * - lazy service factories (generated proxies)
 * - initializers (interface injection)
 *
 * It also provides the ability to inject specific service instances and to
 * define aliases.
 *
 * 2020-08-26 object augmentation and classConfig added, FHE
 */
class ServiceManager implements ServiceLocatorInterface
{
    /**
     * @var Factory\AbstractFactoryInterface|string[]
     */
    protected $abstractFactories = [];

    /**
     * @var boolean[]
     */
    private $abstractFactoryTags = [];

    /**
     * A list of aliases
     *
     * Should map one alias to a service name, or another alias (aliases are recursively resolved)
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Whether or not changes may be made to this instance.
     *
     * @param bool
     */
    protected $allowOverride = false;

    /**
     * @var ContainerInterface
     */
    protected $creationContext;

    /**
     * @var string[][]|Factory\DelegatorFactoryInterface[][]
     */
    protected $delegators = [];

    /**
     * @var callable[]
     */
    protected $delegatorCallbackCache = [];

    /**
     * A list of factories (either as string name or callable)
     *
     * @var string[]|callable[]
     */
    protected $factories = [];

    /**
     * A list of invokable classes
     *
     * @var string[]|callable[]
     */
    protected $invokables = [];

    /**
     * @var Initializer\InitializerInterface[]|callable[]
     */
    protected $initializers = [];

    /**
     * @var array
     */
    protected $lazyServices = [];

    /**
     * @var array
     */
    protected $lazyServicesClassMap = [];

    /**
     * @var null|Proxy\LazyServiceFactory
     */
    private $lazyServicesDelegator;

    /**
     * A list of already loaded services (this act as a local cache)
     *
     * @var array
     */
    protected $services = [];

    /**
     * Enable/disable shared instances by service name.
     *
     * Example configuration:
     *
     * 'shared' => [
     *     MyService::class => true, // will be shared, even if "sharedByDefault" is false
     *     MyOtherService::class => false // won't be shared, even if "sharedByDefault" is true
     * ]
     *
     * @var boolean[]
     */
    protected $shared = [];

    /**
     * Should the services be shared by default?
     *
     * @var bool
     */
    protected $sharedByDefault = true;

    /**
     * ServiceManager was already configured?
     * Maintened for bc also we don't rely on it
     * any more
     *
     * @var bool
     */
    protected $configured = false;

    /**
     * Constructor.
     *
     * See {@see \MxcCommons\ServiceManager\ServiceManager::configure()} for details
     * on what $config accepts.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->creationContext = $this;

        if (! empty($this->aliases)) {
            $this->mapAliasesToTargets();
        }

        $abstractFactories = $this->abstractFactories;
        $this->abstractFactories = [];
        $this->registerAbstractFactories($abstractFactories);

        $initializers = $this->initializers;
        $this->initializers = [];
        $this->registerInitializers($initializers);

        $this->configure($config);
    }

    /**
     * Configure the service manager
     *
     * Valid top keys are:
     *
     * - services: service name => service instance pairs
     * - invokables: service name => class name pairs for classes that do not
     *   have required constructor arguments; internally, maps the class to an
     *   InvokableFactory instance, and creates an alias if the service name
     *   and class name do not match.
     * - factories: service name => factory pairs; factories may be any
     *   callable, string name resolving to an invokable class, or string name
     *   resolving to a FactoryInterface instance.
     * - abstract_factories: an array of abstract factories; these may be
     *   instances of AbstractFactoryInterface, or string names resolving to
     *   classes that implement that interface.
     * - delegators: service name => list of delegator factories for the given
     *   service; each item in the list may be a callable, a string name
     *   resolving to an invokable class, or a string name resolving to a class
     *   implementing DelegatorFactoryInterface.
     * - shared: service name => flag pairs; the flag is a boolean indicating
     *   whether or not the service is shared.
     * - aliases: alias => service name pairs.
     * - lazy_services: lazy service configuration; can contain the keys:
     *   - class_map: service name => class name pairs.
     *   - proxies_namespace: string namespace to use for generated proxy
     *     classes.
     *   - proxies_target_dir: directory in which to write generated proxy
     *     classes; uses system temporary by default.
     *   - write_proxy_files: boolean indicating whether generated proxy
     *     classes should be written; defaults to boolean false.
     * - shared_by_default: boolean, indicating if services in this instance
     *   should be shared by default.
     *
     * @param  array $config
     * @return self
     * @throws ContainerModificationsNotAllowedException if the allow
     *     override flag has been toggled off, and a service instance
     *     exists for a given service.
     */
    public function configure(array $config)
    {
        $allowOverride = empty($this->services) || $this->allowOverride;

        if (isset($config['magicals'])) {
            $config = $this->transformMagicals($config);
        }
        if ($allowOverride) {
            // This is the fast track. We can just merge.
            if (! empty($config['services'])) {
                $this->services = empty($this->services) ? $config['services'] : $config['services'] + $this->services;
            }
            if (! empty($config['aliases'])) {
                $this->aliases = empty($this->aliases) ? $config['aliases'] : $config['aliases'] + $this->aliases;
                $this->mapAliasesToTargets();
            }
            if (! empty($config['factories'])) {
                $this->factories =
                    empty($this->factories) ? $config['factories'] : $config['factories'] + $this->factories;
            }
            if (! empty($config['invokables'])) {
                $this->invokables =
                    empty($this->invokables) ? $config['invokables'] : $config['invokables'] + $this->invokables;
            }
            if (! empty($config['shared'])) {
                $this->shared = empty($this->shared) ? $config['shared'] : $config['shared'] + $this->shared;
            }
            if (! empty($config['delegators'])) {
                $this->delegators = empty($this->delegators)
                    ? $config['delegators'] : array_merge_recursive($this->delegators, $config['delegators']);
                unset($this->delegatorCallbackCache);
            }
            if (! empty($config['lazy_services']['class_map'])) {
                $this->lazyServicesClassMap = empty($this->lazyServicesClassMap)
                    ? $config['lazy_services']['class_map']
                    : $config['lazy_services']['class_map'] + $this->lazyServicesClassMap;
                $this->lazyServicesDelegator = null;
                // we merge the rest of lazy_services later
                unset($config['lazy_services']['class_map']);
            }
        } else {
            if (! empty($config['services'])) {
                foreach ($config['services'] as $name => $service) {
                    if (isset($this->services[$name])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($name);
                    }
                    $this->services[$name] = $service;
                }
            }
            if (! empty($config['aliases'])) {
                foreach ($config['aliases'] as $alias => $target) {
                    if (isset($this->services[$alias])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($alias);
                    }
                    $this->aliases[$alias] = $target;
                }
                $this->mapAliasesToTargets();
            }
            if (! empty($config['delegators'])) {
                foreach ($config['delegators'] as $name => $delegator) {
                    if (isset($this->services[$name])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($name);
                    }
                    $this->delegators[$name] = $delegator + $this->delegators[$name];
                }
            }
            if (! empty($config['factories'])) {
                foreach ($config['factories'] as $name => $factory) {
                    if (isset($this->services[$name])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($name);
                    }
                    $this->factories[$name] = $factory;
                }
            }
            if (! empty($config['invokables'])) {
                foreach ($config['invokables'] as $name => $service) {
                    if (isset($this->services[$name])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($name);
                    }
                    $this->invokables[$name] = $service;
                }
            }
            if (! empty($config['shared'])) {
                foreach ($config['shared'] as $name => $shared) {
                    if (isset($this->services[$name])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($name);
                    }
                    $this->shared[$name] = $shared;
                }
            }
            if (! empty($config['lazy_services']['class_map'])) {
                foreach ($config['lazy_services']['class_map'] as $name => $service) {
                    if (isset($this->services[$name])) {
                        throw ContainerModificationsNotAllowedException::fromExistingService($name);
                    }
                    $this->lazyServicesClassMap[$name] = $service;
                }
                $this->lazyServicesDelegator = null;
                unset($config['lazy_services']['class_map']);
            }
        }
        // we merge the rest of supplied lazy_services if present
        if (! empty($config['lazy_services'])) {
            if (! empty($this->lazyServices)) {
                $this->lazyServices = $config['lazy_services'] + $this->lazyServices;
            } else {
                $this->lazyServices = $config['lazy_services'];
            }
        }

        if (isset($config['shared_by_default'])) {
            $this->sharedByDefault = $config['shared_by_default'];
        }

        if (! empty($config['abstract_factories'])) {
            $this->registerAbstractFactories($config['abstract_factories']);
        }

        if (! empty($config['initializers'])) {
            $this->registerInitializers($config['initializers']);
        }

        $this->configured = true;
        return $this;
    }

    protected function getClassConfig($object): array
    {
        $config = $this->get('config');
        $configPath = $config['plugin_config_path'];
        // interesting: Reflexion class is faster than string operations in retrieving the class name w/o namespace
        $configFile = (new ReflectionClass($object))->getShortName() . '.config.php';
        $filename = $configPath . '/' . $configFile;
        // we support the phpx extesnion also, temporarily to exclude some config files form code inspection
        if (! file_exists($filename))
        {
            $filename .= 'x';
            if (! file_exists($filename)) return [];
        }

        $classConfig = include $filename;
        if (! is_array($classConfig)) return [];
        return $classConfig;
    }

    protected function augment($object)
    {
        if (! $object instanceof AugmentedObject) return;

        if (method_exists($object, 'setServices')) {
            $object->setServices($this);
        }
        if (method_exists($object, 'setLog')) {
            $object->setLog($this->get('logger'));
        }
        if (method_exists($object, 'setModelManager')) {
            $object->setModelManager($this->get('models'));
        }
        if (method_exists($object, 'setDatabase')) {
            $object->setDatabase($this->get('db'));
        }
        if (method_exists($object, 'setClassConfig')) {
            $object->setClassConfig($this->getClassConfig($object));
        }
        if (method_exists($object, 'setEventManager')) {
            // make sure that the SharedEventManager comes from MxcCommons
            // so the SharedEventManager is the same object for all plugins
            $mxcCommons = MxcCommons::getServices();
            $object->setEventManager(new EventManager($mxcCommons->get('shared_events')));
        }
        if (method_exists($object, 'init')) {
            $object->init();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        // We start by checking if we have cached the requested service;
        // this is the fastest method.
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        // We achieve better performance if we can let all alias
        // considerations out.
        $sharedService = $this->shared[$name] ?? $this->sharedByDefault;
        if (! isset($this->aliases[$name])) {
            $object = $this->createService($name, null);

            // Cache the object for later, if it is supposed to be shared.
            if ($sharedService) {
                $this->services[$name] = $object;
            }
            return $object;
        }

        $resolvedName = $this->aliases[$name];

        // If the alias is registered as a shared service, we are done.
        if ($sharedService && isset($this->services[$resolvedName])) {
            $this->services[$name] = $this->services[$resolvedName];
            return $this->services[$name];
        }

        $object = $this->createService($resolvedName, null);

        // Cache the object for later, if it is supposed to be shared.
        if ($this->shared[$resolvedName] ?? $this->sharedByDefault) {
            $this->services[$resolvedName] = $object;
        }

        // Also do so for aliases
        if ($sharedService) {
            $this->services[$name] = $object;
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function build($name, array $options = null)
    {
        // We never cache when using "build".
        $name = $this->aliases[$name] ?? $name;
        return $this->createService($name, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function has($name)
    {
        if (isset($this->services[$name])) {
            return true;
        }
        if (isset($this->factories[$name])) {
            return true;
        }
        if (isset($this->invokables[$name])) {
            return true;
        }

        $resolvedName = $this->aliases[$name] ?? $name;

        if ($resolvedName !== $name) {
            if (isset($this->services[$resolvedName])) {
                return true;
            }

            if (isset($this->factories[$resolvedName])) {
                return true;
            }
            if (isset($this->invokables[$resolvedName])) {
                return true;
            }
        }

        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $resolvedName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create a new instance with an already resolved name
     *
     * @param  string     $resolvedName
     * @param  null|array $options
     * @return mixed
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    private function createService(string $resolvedName, array $options = null)
    {
        try {
            if (isset($this->delegators[$resolvedName])) {
                $object = $this->createServiceFromDelegator($resolvedName, $options);
            } else {
                $object = $this->createServiceFromFactory($resolvedName, $options);
            }
        } catch (ContainerException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw ServiceNotCreatedException::fromException($resolvedName, $exception);
        }

        foreach ($this->initializers as $initializer) {
            $initializer($this->creationContext, $object);
        }
        $this->augment($object);

        return $object;
    }

    /**
     * Get a factory for the given service name and create an object using
     * that factory or create invokable if service is invokable
     *
     * @param  string $name
     * @return object
     * @throws ServiceNotFoundException
     */
    private function createServiceFromFactory(string $name, array $options = null)
    {
        $factory = $this->factories[$name] ?? null;

        if (is_string($factory) && class_exists($factory)) {
            $factory = new $factory();
            if ($factory instanceof FactoryInterface) {
                $this->factories[$name] = $factory;
                return $factory($this->creationContext, $name, $options);
            }
            throw InvalidArgumentException::fromInvalidFactory($factory);
        }

        if (is_callable($factory)) {
            return $factory($this->creationContext, $name, $options);
        } elseif (isset($this->invokables[$name])) {
            $invokable = $this->invokables[$name];
            return $options === null ? new $invokable() : new $invokable($options);
        }

        foreach ($this->abstractFactories as $idx => $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return $abstractFactory($this->creationContext, $name, $options);
            }
        }

        throw ServiceNotFoundException::fromUnknownService($name);
    }

    /**
     * Create a service from delegator definition
     *
     * Delegator nested callback is cached for consecutive requests.
     *
     * @param  string     $name
     * @param  null|array $options
     * @return object
     */
    private function createServiceFromDelegator(string $name, array $options = null)
    {
        // @todo: This selection is necessary only because lazy services
        // configuration currently requires an explicit Delegator definition
        if ($this->delegators[$name][0] === Proxy\LazyServiceFactory::class) {
            return $this->createServiceFromLazyServiceDelegator($name, $options);
        }

        $creationCallback = $this->delegatorCallbackCache[$name] ?? null;
        if ($creationCallback) {
            return $creationCallback($this->creationContext, $name, $creationCallback, $options);
        }

        $creationCallback = function () use ($name, $options) {
            return $this->createServiceFromFactory($name, $options);
        };

        foreach ($this->delegators[$name] as $index => $delegatorFactory) {
            if (is_string($delegatorFactory) && class_exists($delegatorFactory)) {
                $delegatorFactory = new $delegatorFactory();
                if ($delegatorFactory instanceof DelegatorFactoryInterface) {
                    $this->delegators[$name][$index] = $delegatorFactory;
                    $creationCallback = function () use ($delegatorFactory, $name, $creationCallback, $options) {
                        return $delegatorFactory($this->creationContext, $name, $creationCallback, $options);
                    };
                    continue;
                }
            }
            if (is_callable($delegatorFactory)) {
                $creationCallback = function () use ($delegatorFactory, $name, $creationCallback, $options) {
                    return $delegatorFactory($this->creationContext, $name, $creationCallback, $options);
                };
                continue;
            }
            if (is_string($delegatorFactory)) {
                throw InvalidArgumentException::fromInvalidDelegatorFactoryClass($delegatorFactory);
            }
            throw InvalidArgumentException::fromInvalidDelegatorFactoryInstance($delegatorFactory);
        }

        //cache the callback for later requests
        $this->delegatorCallbackCache[$name] = $creationCallback;
        return $creationCallback($this->creationContext, $name, $creationCallback, $options);
    }

    /**
     * Specialized version of createServiceFromDelegator, where delegator is fixed
     *
     * Delegator nested callback is cached for consecutive requests.
     *
     * @param  string     $name
     * @param  null|array $options
     * @return object
     */
    private function createServiceFromLazyServiceDelegator(string $name, array $options = null)
    {

        $creationCallback = $this->delegatorCallbackCache[$name] ?? null;
        if ($creationCallback) {
            return $creationCallback($this->creationContext, $name, $creationCallback, $options);
        }
        $creationCallback = function () use ($name, $options) {
            return $this->createServiceFromFactory($name, $options);
        };

        $delegatorFactory = $this->delegators[$name][0];
        if (is_string($delegatorFactory)) {
            $delegatorFactory = $this->createLazyServiceDelegatorFactory();
            $this->delegators[$name][0] = $delegatorFactory;
        }

        $creationCallback = function () use ($delegatorFactory, $name, $creationCallback, $options) {
            return $delegatorFactory($this->creationContext, $name, $creationCallback, $options);
        };

        //cache the callback for later requests
        $this->delegatorCallbackCache[$name] = $creationCallback;
        return $creationCallback($this->creationContext, $name, $creationCallback, $options);
    }

    /**
     * Checks if an abstract factory can deliver a service with name provided
     *
     * Abstract factories get instantiated and cached for later use as necessary
     *
     * $this->abstractFactories contains all registered distinct abstractFactories,
     * which can be registered as callable implementing Factory\AbstractFactory or
     * FQCN of a class implementing Factory\AbstractFactoryInterface.
     *
     * The caching mechanismus works as follows:
     *
     * 1. Registered callables do not need caching
     * 2. Registered classes are instantiated and checked for compliance
     * 4. Their abstractFactories entry gets replaced with the object
     * 5. If abstract factory can create service of name $name, it gets
     *    registered as a factory for that name.
     *
     * @param string $name
     */
    private function registerAbstractFactories(array $abstractFactories)
    {
        foreach ($abstractFactories as $abstractFactory) {
            if (is_string($abstractFactory) && class_exists($abstractFactory)) {
                if (isset($this->abstractFactoryTags[$abstractFactory])) {
                    continue;
                }
                $this->abstractFactoryTags[$abstractFactory] = true;
                $abstractFactory = new $abstractFactory;
            }
            if ($abstractFactory instanceof Factory\AbstractFactoryInterface) {
                $this->abstractFactories[spl_object_hash($abstractFactory)] = $abstractFactory;
            } else {
                throw InvalidArgumentException::fromInvalidAbstractFactory($abstractFactory);
            }
        }
    }

    /**
     * Rewrite config by transforming magicals to factories and invokables
     *
     * @param array $config
     * @return array
     */
    public function transformMagicals(array $config)
    {
        if (isset($config['magicals'])) {
            foreach ($config['magicals'] as $magical) {
                if (isset($config['services'][$magical])) {
                    continue;
                }
                if (! isset($config['factories'][$magical])) {
                    $factory = $magical . 'Factory';
                    if (class_exists($factory)) {
                        $config['factories'][$magical] = $factory;
                        continue;
                    }
                    if (! isset($config['invokables'][$magical]) && class_exists($magical)) {
                        $config['invokables'][$magical] = $magical;
                    }
                }
            }
        }
        return $config;
    }

    /**
     * Applies initializers to a supplied object
     *
     * Initializers are instantiated and cached for later use as necessary
     *
     * @param object
     */
    private function registerInitializers(array $initializers)
    {
        foreach ($initializers as $initializer) {
            if (is_string($initializer) && class_exists($initializer)) {
                $initializer = new $initializer();
                if (! ($initializer instanceof InitializerInterface)) {
                    throw InvalidArgumentException::fromInvalidInitializer($initializer);
                }
            }
            if (is_callable($initializer)) {
                $this->initializers[] = $initializer;
            } else {
                throw InvalidArgumentException::fromInvalidInitializer($initializer);
            }
        }
    }

    /**
     * Indicate whether or not the instance is immutable.
     *
     * @param bool $flag
     */
    public function setAllowOverride($flag)
    {
        $this->allowOverride = (bool) $flag;
    }

    /**
     * Retrieve the flag indicating immutability status.
     *
     * @return bool
     */
    public function getAllowOverride()
    {
        return $this->allowOverride;
    }

    /**
     * Add an alias.
     *
     * @param string $alias
     * @param string $target
     * @throws ContainerModificationsNotAllowedException if $alias already
     *     exists as a service and overrides are disallowed.
     */
    public function setAlias($name, $target)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->mapAliasToTarget($name, $target);
    }

    /**
     * Add an invokable class mapping.
     *
     * @param string $name Service name
     * @param null|string $class Class to which to map; if omitted, $name is
     *     assumed.
     * @throws ContainerModificationsNotAllowedException if $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setInvokableClass(string $name, string $class = null)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->invokables[$name] = $class ?? $name;
    }

    /**
     * Specify a factory for a given service name.
     *
     * @param string $name Service name
     * @param string|callable|Factory\FactoryInterface $factory Factory to which
     *     to map.
     * @throws ContainerModificationsNotAllowedException if $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setFactory(string $name, $factory)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->factories[$name] = $factory;
    }

    public function setMagical(string $name)
    {
        $factory = $name . 'Factory';
        if (class_exists($factory)) {
            $this->setFactory($name, $factory);
            return;
        }
        $this->setInvokableClass($name);
    }

    /**
     * Create a lazy service mapping to a class.
     *
     * @param string $name Service name to map
     * @param null|string $class Class to which to map; if not provided, $name
     *     will be used for the mapping.
     */
    public function mapLazyService(string $name, $class = null)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->lazyServicesClassMap[$name] = $class ?? $name;
        $this->lazyServicesDelegator = null;
    }

    /**
     * Add an abstract factory for resolving services.
     *
     * @param string|Factory\AbstractFactoryInterface $factory Abstract factory
     *     instance or class name.
     */
    public function addAbstractFactory($factory)
    {
        $this->registerAbstractFactories([$factory]);
    }

    /**
     * Add a delegator or an array of delegators for a given service.
     *
     * @param string $name Service name
     * @param (string|callable|Factory\DelegatorFactoryInterface[]) $factory
     *     Delegator factory (array) to assign
     */
    public function addDelegator(string $name, $factory)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $factory = is_array($factory) ? $factory : [ $factory];
        if (isset($this->delegators[$name])) {
            $this->delegators[$name] = $factory + $this->delegators[$name];
        } else {
            $this->delegators[$name] = $factory;
        }
    }

    /**
     * Add an initializer.
     *
     * @param string|callable|Initializer\InitializerInterface $initializer
     */
    public function addInitializer($initializer)
    {
        $this->registerInitializers([$initializer]);
    }

    /**
     * Map a service.
     *
     * @param string $name Service name
     * @param array|object $service
     * @throws ContainerModificationsNotAllowedException if $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setService(string $name, $service)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->services[$name] = $service;
    }

    /**
     * Add a service sharing rule.
     *
     * @param string $name Service name
     * @param boolean $flag Whether or not the service should be shared.
     * @throws ContainerModificationsNotAllowedException if $name already
     *     exists as a service and overrides are disallowed.
     */
    public function setShared(string $name, bool $flag)
    {
        if (isset($this->services[$name]) && ! $this->allowOverride) {
            throw ContainerModificationsNotAllowedException::fromExistingService($name);
        }
        $this->shared[$name] = $flag;
    }

    /**
     * Assuming that the alias name is valid (see above) resolve/add it.
     *
     * This is done differently from bulk mapping aliases for performance reasons, as the
     * algorithms for mapping a single item efficiently are different from those of mapping
     * many.
     *
     * @see mapAliasesToTargets() below
     *
     * @param string $alias
     * @param string $target
     */
    private function mapAliasToTarget(string $alias, string $target)
    {
        // $target is either an alias or something else
        // if it is an alias, resolve it
        $this->aliases[$alias] = $this->aliases[$target] ?? $target;

        // a self-referencing alias indicates a cycle
        if ($alias === $this->aliases[$alias]) {
            throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
        }

        // finally we have to check if existing incomplete alias definitions
        // exist which can get resolved by the new alias
        if (in_array($alias, $this->aliases)) {
            $r = array_intersect($this->aliases, [ $alias ]);
            // found some, resolve them
            foreach ($r as $name => $_) {
                $this->aliases[$name] = $target;
            }
        }
    }

    /**
     * Assuming that all provided alias keys are valid resolve them.
     *
     * This function maps $this->aliases in place.
     *
     * This algorithm is an adaptated version of Tarjans Strongly
     * Connected Components. Instead of returning the strongly
     * connected components (i.e. cycles in our case), we throw.
     * If nodes are not strongly connected (i.e. resolvable in
     * our case), they get resolved.
     *
     * This algorithm is fast for mass updates through configure().
     * It is not appropriate if just a single alias is added.
     *
     * @see mapAliasToTarget above
     *
     */
    private function mapAliasesToTargets()
    {
        $tagged = [];

        foreach ($this->aliases as $alias => $target) {
            if (isset($tagged[$alias])) {
                continue;
            }

            if ($alias === $target) {
                throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
            }

            $tCursor = $target;
            $aCursor = $alias;
            $stack = [];

            while (isset($this->aliases[$tCursor])) {
                $stack[] = $aCursor;
                if ($aCursor === $this->aliases[$tCursor]) {
                    throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
                }
                $aCursor = $tCursor;
                $tCursor = $this->aliases[$tCursor];
            }

            $tagged[$aCursor] = true;

            foreach ($stack as $alias) {
                if ($alias === $tCursor) {
                    throw CyclicAliasException::fromCyclicAlias($alias, $this->aliases);
                }
                $this->aliases[$alias] = $tCursor;
                $tagged[$alias] = true;
            }
        }
    }

    /**
     * Create the lazy services delegator factory.
     *
     * Creates the lazy services delegator factory based on the lazy_services
     * configuration present.
     *
     * @return Proxy\LazyServiceFactory
     * @throws InvalidArgumentException when the lazy service class_map
     *     configuration is missing
     */
    private function createLazyServiceDelegatorFactory()
    {
        // @todo: As a matter of separation of concerns this factory
        // was better be implemented in a separate class
        if ($this->lazyServicesDelegator) {
            return $this->lazyServicesDelegator;
        }

        if (empty($this->lazyServicesClassMap)) {
            throw new InvalidArgumentException('Missing "class_map" config key in "lazy_services"');
        }

        $factoryConfig = new ProxyConfiguration();

        if (isset($this->lazyServices['proxies_namespace'])) {
            $factoryConfig->setProxiesNamespace($this->lazyServices['proxies_namespace']);
        }

        if (isset($this->lazyServices['proxies_target_dir'])) {
            $factoryConfig->setProxiesTargetDir($this->lazyServices['proxies_target_dir']);
        }

        if (! isset($this->lazyServices['write_proxy_files']) || ! $this->lazyServices['write_proxy_files']) {
            $factoryConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        } else {
            $factoryConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy(
                new FileLocator($factoryConfig->getProxiesTargetDir())
            ));
        }

        spl_autoload_register($factoryConfig->getProxyAutoloader());

        $this->lazyServicesDelegator = new Proxy\LazyServiceFactory(
            new LazyLoadingValueHolderFactory($factoryConfig),
            $this->lazyServicesClassMap
        );

        return $this->lazyServicesDelegator;
    }

    /**
     * Implemented for backwards compatibility with previous plugin managers only.
     *
     * Returns the creation context.
     *
     * @deprecated since 3.0.0. Factories using 3.0 should use the container
     *     instance passed to the factory instead.
     * @return ContainerInterface
     */
    public function getServiceLocator()
    {
        trigger_error(sprintf(
            'Usage of %s is deprecated since v3.0.0; please use the container passed to the factory instead',
            __METHOD__
        ), E_USER_DEPRECATED);
        return $this->creationContext;
    }
}
