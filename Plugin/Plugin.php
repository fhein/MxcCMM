<?php

namespace MxcCommons\Plugin;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Database\AttributeManager;
use MxcCommons\Plugin\Database\SchemaManager;
use Shopware\Components\Plugin as Base;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Kernel;
use Throwable;

class Plugin extends Base
{
    protected $installClearCache;
    protected $uninstallClearCache;
    protected $activateClearCache;
    protected $deactivateClearCache;
    protected $updateClearCache;

    /**
     * @param string $function
     * @param ContainerInterface $services
     * @return array
     */
    private function getListeners(string $function, ContainerInterface $services) {
        $config = $services->get('config');
        $listeners = is_array(@$config['doctrine']['models']) ? [SchemaManager::class]: [];
        if (is_array(@$config['doctrine']['attributes'])) {
            $listeners[] = AttributeManager::class;
        }
        $customListeners = $config['plugin'] ?? [];
        foreach ($customListeners as $listener) {
            $listeners[] = $listener;
        }
        // attach listeners in reverse order on uninstall and deactivate
        if ($function === 'uninstall' || $function === 'deactivate') {
            $listeners = array_reverse($listeners);
        }
        $pluginListeners = [];
        foreach ($listeners as $service) {
            if ($services->has($service)) {
                $pluginListeners[] = $services->get($service);
            }
        }
        return $pluginListeners;
    }

    /**
     * @param Plugin $plugin
     * @param string $function
     * @param $param
     * @return bool
     */
    private function trigger(Plugin $plugin, string $function, $param) {
        $services = $plugin->getServices();
        $result = true;
        try {
            $services->setAllowOverride(true);
            $services->setService('plugin', $plugin);
            $pluginListeners = $this->getListeners($function, $services);
            foreach ($pluginListeners as $listener) {
                if (method_exists($listener, $function)) {
                    $result = $listener->$function($param);
                    if ($result === false) break;
                }
            }
        } catch (Throwable $e) {
            $services->get('logger')->except($e);
        }
        return $result;
    }

    public function install(InstallContext $context)
    {
        $result = $this->trigger($this, __FUNCTION__, $context);
        if ($result === true && $this->installClearCache !== null) {
            $context->scheduleClearCache($this->installClearCache);
        }
        return $result;
    }

    public function uninstall(UninstallContext $context)
    {
        $result = $this->trigger($this, __FUNCTION__, $context);
        if ($result === true && $this->uninstallClearCache !== null) {
            $context->scheduleClearCache($this->uninstallClearCache);
        }
        return $result;
    }

    public function update(UpdateContext $context)
    {
        $result = $this->trigger($this, __FUNCTION__, $context);
        if ($result === true && $this->updateClearCache !== null) {
            $context->scheduleClearCache($this->updateClearCache);
        }
        return $result;
    }

    public function activate(ActivateContext $context)
    {
        $result = $this->trigger($this, __FUNCTION__, $context);
        if ($result === true && $this->activateClearCache !== null) {
            $context->scheduleClearCache($this->activateClearCache);
        }
        return $result;
    }

    public function deactivate(DeactivateContext $context)
    {
        $result = $this->trigger($this, __FUNCTION__, $context);
        if ($result === true && $this->deactivateClearCache !== null) {
            $context->scheduleClearCache($this->deactivateClearCache);
        }
        return $result;
    }
}
