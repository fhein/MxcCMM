<?php

namespace MxcCommons\Plugin\Database;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class AttributeManager implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /**
     * @var CrudService $attributeService
     */
    private $attributeService;

    /**
     * @var CacheProvider $metaDataCache
     */
    private $metaDataCache;

    /**
     * @var SchemaTool $schemaTool
     */
    private $schemaTool;

    /**
     * @var array $attributes
     */
    protected $attributes = [];
    /**
     * @var array $models
     */
    protected $models = [];

    /**
     * @param array $attributes
     * @param CrudService $attributeService
     * @param SchemaTool $schemaTool
     * @param CacheProvider $metaDataCache
     */
    public function __construct(
        array $attributes,
        CrudService $attributeService,
        SchemaTool $schemaTool,
        CacheProvider $metaDataCache
    ) {
        $this->metaDataCache = $metaDataCache;
        $this->schemaTool = $schemaTool;
        $this->attributeService = $attributeService;
        $this->attributes = $attributes;
    }

    /**
     * Adds attributes and tables to the database schema
     *
     * @param InstallContext $c
     * @return bool
     */
    public function install(/** @noinspection PhpUnusedParameterInspection */ InstallContext $c) {
        $this->create();
        return true;
    }

    /**
     * Removes attributes and tables from the database schema
     * @param UninstallContext $context
     * @return bool
     */
    public function uninstall(UninstallContext $context) {
        if (! $context->keepUserData()) $this->drop();
        return true;
    }

    public function create() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->updateAttributes();
    }

    public function drop() {
        $this->dropAttributes();
    }

    /**
     * Delete the attributes defined in the §attributes member from the database schema
     */
    protected function dropAttributes() {
        foreach ($this->attributes as $table => $attributes) {
            $names = array_keys($attributes);
            foreach ($names as $name) {
                try {
                    $this->attributeService->delete($table, $name);
                } catch (Exception $e) {
                    // ignore attribute did not exist
                }
            }
        }
        $this->updateModel();
        return true;
    }


    /**
     * Add/Update the attributes defined in the §attributes member to the database schema
     *
     *  'settings' => [
     *      'label'            => '',
     *      'supportText'      => '',
     *      'helpText'         => '',
     *      'translatable'     => false,
     *      'displayInBackend' => false,
     *      'position'         => 10000,
     *      'custom'           => false
     *   ]
     */
    protected function updateAttributes() {
        foreach ($this->attributes as $table => $attributes) {
            foreach ($attributes as $name => $config) {
                try {
                    $this->attributeService->update(
                        $table,
                        $name,
                        $config['type'],
                        $config['settings'] ?? [],
                        $config['newColumnName'] ?? null,
                        $config['updateDependingTables'] ?? false,
                        $config['defaultValue'] ?? null);
                } catch (Exception $e) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    throw new Exception('Attribute service failed to create attributes: ' . $e->getMessage());
                }
            }
        }
        $this->updateModel();
    }

    private function updateModel() {
        $this->metaDataCache->deleteAll();
        $this->modelManager->generateAttributeModels(array_keys($this->attributes));
    }
}