<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware;

use Shopware\Models\Article\Supplier;

class SupplierTool
{

    public static function createSupplier(string $name) {
        $supplier = new Supplier();
        Shopware()->Models()->persist($supplier);
        $supplier->setName($name);
        return $supplier;
    }

    /**
     * If supplied $article has a supplier then get it by name from Shopware or create it if necessary.
     * Otherwise do the same with default supplier name 'unknown'
     *
     * @param string $name
     * @param bool $create
     * @return Supplier
     */
    public static function getSupplier(string $name, bool $create = true)
    {
        $modelManager = Shopware()->Models();
        $supplier = $modelManager->getRepository(Supplier::class)->findOneBy(['name' => $name]);
        if (! $supplier && $create) {
            $supplier = self::createSupplier($name);
        }
        return $supplier;
    }

    public static function setSupplierMetaInfo(
        Supplier $supplier,
        string $metaTitle = null,
        string $metaDescription = null,
        string $metaKeywords = null)
    {
        if ($metaTitle !== null) {
            $supplier->setMetaTitle($metaTitle);
        }
        if ($metaDescription !== null) {
            $supplier->setMetaDescription($metaDescription);
        }
        if ($metaKeywords !== null) {
            $supplier->setMetaKeywords($metaKeywords);
        }
    }
}