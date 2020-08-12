<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware;

use DateTimeImmutable;
use Shopware\Models\Tax\Tax;

class TaxTool
{
    private static $vatConfig = [
        [
            'start' => '01.01.2000 00:00:00',
            'vat'   => 19.0,
        ],
        [
            'start' => '30.06.2020 00:00:00',
            'vat'   => 16.0,
        ],
        [
            'start' => '31.12.2020 00:00:00',
            'vat'   => 19.0,
        ],
    ];

    private static $currentVatPercentage = null;

    /**
     * Create a Tax object for the given tax value.
     *
     * @param float $taxValue
     * @return Tax
     */
    public static function createTax(float $taxValue)
    {
        $name = sprintf('Tax (%.2f)', $taxValue);
        $tax = new Tax();
        Shopware()->Models()->persist($tax);
        $tax->setName($name);
        $tax->setTax($taxValue);
        return $tax;
    }

    public static function getCurrentVatPercentage()
    {
        $vat = self::$currentVatPercentage;
        if ($vat !== null) return $vat;

        $currentTime = new DateTimeImmutable();
        foreach (self::$vatConfig as $vatSetting) {
            $start = DateTimeImmutable::createFromFormat('d.m.Y H:i:s', $vatSetting['start']);
            if ($start < $currentTime) {
                $vat = $vatSetting['vat'];
            }
        }
        self::$currentVatPercentage = $vat;
        return $vat;
    }

    /**
     * Returns a Tax object for the currently valid VAT. If the requested Tax object does not exist
     * and $create is true the Tax object will be created.
     *
     * @param bool $create
     * @return Tax
     */
    public static function getTax(bool $create = true)
    {
        $modelManager = Shopware()->Models();
        $taxValue = self::getCurrentVatPercentage();
        $tax = $modelManager->getRepository(Tax::class)->findOneBy(['tax' => $taxValue]);
        if (! $tax && $create) {
            $tax = self::createTax($taxValue);
        }
        return $tax;
    }
}