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
use Shopware\Components\Plugin\XmlReader\XmlPluginReader;

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
        $customListeners = $config['plugin_listeners'] ?? [];
        foreach ($customListeners as $listener) {
            $listeners[] = $listener;
        }
        // attach listeners in reverse order on uninstall and deactivate
        if ($function === 'uninstall' || $function === 'deactivate') {
            $listeners = array_reverse($listeners);
        }
        return $listeners;
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
                if (! $services->has($listener)) continue;
                $listener = $services->get($listener);
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
        // uninstall only if there is no dependent plugin installed
        // will throw if dependent plugins are installed
        $this->checkDependencies(false);
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
        // deactivate only if there is no dependent plugin active
        // will throw if dependent plugins are active
        $this->checkDependencies(true);

        $result = $this->trigger($this, __FUNCTION__, $context);
        if ($result === true && $this->deactivateClearCache !== null) {
            $context->scheduleClearCache($this->deactivateClearCache);
        }
        return $result;
    }

    private function checkDependencies(bool $checkActiveOnly)
    {
        $task = $checkActiveOnly ? 'deactivated' : 'uninstalled';

        $xmlConfigReader = new XmlPluginReader();

        // get all plugins (i use this for '$plugin->getPath()')
        /** @var Kernel $kernel */
        $kernel = Shopware()->Container()->get('kernel');
        $plugins = $kernel->getPlugins();

        // get name of this plugin
        $thisPluginName = $this->getName();

        // get installed plugins
        $db = Shopware()->Container()->get('db');
        $installedPlugins = $db->fetchAll(
            'SELECT name 
            FROM s_core_plugins 
            WHERE namespace = "ShopwarePlugins" AND installation_date IS NOT NULL'
        );

        // create new array with the plugin names only
        $installedPluginNames = [];
        foreach($installedPlugins as $singlePlugin){
            $installedPluginNames[] = $singlePlugin['name'];
        }

        // check every plugin if it requires this plugin
        foreach ($plugins as $plugin){
            // skip if the plugin isn't installed
            if (!in_array($plugin->getName(),$installedPluginNames)){
                continue;
            }
            // check for plugin.xml
            $pluginXmlFile = $plugin->getPath() . '/plugin.xml';
            if (!is_file($pluginXmlFile)) {
                continue;
            }
            // check for required plugins
            $info = $xmlConfigReader->read($pluginXmlFile);
            if (!isset($info['requiredPlugins'])) {
                continue;
            }
            // check every required plugin if it is this plugin
            foreach ($info['requiredPlugins'] as $requiredPlugin){
                if ($requiredPlugin['pluginName'] == $thisPluginName) {
                    if ($task == 'deactivated' && $plugin->isActive() == false ){
                        continue;
                    }
                    throw new \Exception(sprintf('Plugin "%s" requires this plugin and has to be %s first.', $plugin->getName(), $task));
                }
            }
        }
    }
}
