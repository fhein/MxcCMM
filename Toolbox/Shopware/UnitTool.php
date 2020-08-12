<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware;

use Shopware\Models\Article\Unit;

class UnitTool
{
    public static function createUnit($name)
    {
        $unit = new Unit();
        Shopware()->Models()->persist($unit);
        $unit->setName($name);
        $unit->setUnit($name);
        return $unit;
    }

    /**
     * Returns a Unit object for a given name. If the Unit Object is not available
     * it will be created.
     *
     * @param string $name
     * @param bool $create
     * @return object|Unit|null
     */
    public static function getUnit(string $name, bool $create = true)
    {
        $unit = Shopware()->Models()->getRepository(Unit::class)->findOneBy(['name' => $name]);
        if (! $unit && $create) {
            $unit = self::createUnit($name);
        }
        return $unit;
    }
}