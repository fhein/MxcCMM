<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcCommons\Toolbox\Shopware;

class DatabaseTool
{
    public static function removeOrphanedDetails()
    {
        $dql = 'SELECT d FROM Shopware\Models\Article\Detail d '
            . 'LEFT JOIN Shopware\Models\Article\Article a WITH a.id = d.article '
            . 'WHERE a.id IS NULL';
        $modelManager = Shopware()->Models();
        $orphanedDetails = $modelManager->createQuery($dql)->getResult();

        foreach ($orphanedDetails as $detail) {
            $modelManager->remove($detail);
        }
        $modelManager->flush();
    }
}