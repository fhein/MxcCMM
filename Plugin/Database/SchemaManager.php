<?php

namespace MxcCommons\Plugin\Database;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;


class SchemaManager implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    /**
     * @var CrudService $attributeService
     */
    protected $attributeService;

    /**
     * @var CacheProvider $metaDataCache
     */
    protected $metaDataCache;

    /**
     * @var SchemaTool $schemaTool
     */
    protected $schemaTool;

    /**
     * @var array $attributes
     */
    protected $attributes = [];
    /**
     * @var array $models
     */
    protected $models = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * @param array $models
     * @param array $attributes
     * @param CrudService $attributeService
     * @param SchemaTool $schemaTool
     * @param CacheProvider $metaDataCache
     */
    public function __construct(
        array $models,
        array $attributes,
        CrudService $attributeService,
        SchemaTool $schemaTool,
        CacheProvider $metaDataCache
    ) {
        $this->metaDataCache = $metaDataCache;
        $this->schemaTool = $schemaTool;
        $this->attributeService = $attributeService;
        $this->models = $models;
        $this->attributes = $attributes;
    }

    /**
     * Adds attributes and tables to the database schema
     *
     * @param InstallContext
     * @return bool
     * @throws Exception
     */
    public function install(/** @noinspection PhpUnusedParameterInspection */ InstallContext $c) {
        $this->create();
        return true;
    }

    /**
     * Removes attributes and tables from the database schema
     * @param UninstallContext $e
     * @return bool
     */
    public function uninstall(UninstallContext $context) {
        if (! $context->keepUserData()) $this->drop();
        return true;
    }

    public function create() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->updateSchema();
    }

    public function drop() {
        $this->schemaTool->dropSchema(
            $this->getDropSchemaMetaData()
        );
    }

    public function updateSchema()
    {
        $metaData = [];
        foreach ($this->models as $model) {
            $metaData[] = $this->modelManager->getClassMetadata($model);
        }

        $this->schemaTool->updateSchema(
            $metaData,
            true
        );
    }

    /**
     * Truncate one or more Doctrine managed tables
     * @param array $models
     * @throws DBALException
     */
    public function truncateTable(array $models) {
        $connection = $this->modelManager->getConnection();
        /** @noinspection PhpUnhandledExceptionInspection */
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $dbPlatform = $connection->getDatabasePlatform();
        foreach ($models as $class) {
            $cmd = $this->modelManager->getClassMetadata($class);
            $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
            /** @noinspection PhpUnhandledExceptionInspection */
            $connection->executeUpdate($q);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function updateModel() {
        $this->metaDataCache->deleteAll();
        $this->modelManager->generateAttributeModels(array_keys($this->attributes));
    }

    /**
     * @return array
     */
    protected function getDropSchemaMetaData() {
        if (empty($this->models)) return [];
        $metaData = [];
        $sm = $this->modelManager->getConnection()->getSchemaManager();
        foreach ($this->models as $class) {
            $meta = $this->modelManager->getClassMetadata($class);
            if ($sm->tablesExist([$meta->table['name']])) {
                $metaData[] = $meta;
            }
         }
         return $metaData;
    }
}
