<?php

namespace MxcCommons\Toolbox\Shopware;

class DatabaseConsistency
{
    public static function checkArticlesDetailsAttributes() : array
    {
        $db = Shopware()->Db();

        $report = [];
        // Find all articles without details
        $sql = 'SELECT a.id, a.name FROM s_articles a LEFT JOIN s_articles_details ad ON ad.articleID = a.id WHERE ad.id IS NULL';
        $result = $db->fetchAll($sql);
        $result = empty($result) ? 'none' : var_export($result, true);
        $report[] = 'Defective articles (no details): ' . $result;

        // Find all details without article
        $sql = 'SELECT d.id FROM s_articles_details d LEFT JOIN s_articles a ON d.articleID = a.id WHERE a.id IS NULL';
        $result = $db->fetchAll($sql);
        $result = empty($result) ? 'none' : var_export($result, true);
        $report[] = 'Orphaned details (no article): ' . $result;

        // Find all details without attributes
        $sql = 'SELECT d.id FROM s_articles_details d LEFT JOIN s_articles_attributes a ON a.articledetailsID = d.id WHERE a.id IS NULL';
        $result = $db->fetchAll($sql);
        $result = empty($result) ? 'none' : var_export($result, true);
        $report[] = 'Details without attributes: ' . $result;

        // Find all attributes without details
        $sql = 'SELECT a.articledetailsID FROM s_articles_attributes a LEFT JOIN s_articles_details d ON a.articledetailsID = d.id WHERE a.id IS NULL';
        $result = $db->fetchAll($sql);
        $result = empty($result) ? 'none' : var_export($result, true);
        $report[] = 'Orphaned attributes (no details): ' . $result;

        // Find all article relations where article articleID does not exist
        $sql = 'SELECT ar.articleID FROM s_articles_relationships ar LEFT JOIN s_articles a ON ar.articleID = a.id WHERE a.id IS NULL ';
        $result = $db->fetchAll($sql);
        $result = empty($result) ? 'none' : var_export($result, true);
        $report[] = 'Related Article does not exist:' . $result;

        // Find all article relations where similar article of articleID does not exist
        $sql = 'SELECT ar.articleID FROM s_articles_similar ar LEFT JOIN s_articles a ON ar.articleID = a.id WHERE a.id IS NULL ';
        $label = 'Similar article does not exist: ';
        $result = $db->fetchAll($sql);
        $result = empty($result) ? 'none' : var_export($result, true);
        $report[] = $label . $result;

        return $report;
    }
}