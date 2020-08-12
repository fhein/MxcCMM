<?php


namespace MxcCommons\Toolbox\Shopware;

use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Order\Order;

class Attributes
{
    const ARTICLE = 0;
    const ARTICLEDETAIL = 1;
    const ORDER = 2;
    const ORDERDETAIL = 3;

    protected $tables = [
        Article::class => 's_articles_attributes',
        Detail::class => 's_articles_attributes',
    ];

    public static function get($attr = null)
    {
        switch (getType($attr)) {
            case 'NULL':
                $selector = '*';
                break;
            case 'string':
                $selector = $attr;
                break;
            case 'array':
                $selector = implode(', ', $attr);
                break;
            default:
                return false;
        }
    }


}