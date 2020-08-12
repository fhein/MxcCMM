<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\Sql\Ddl\Column;

use MxcCommons\Db\Sql\ExpressionInterface;

/**
 * Interface ColumnInterface describes the protocol on how Column objects interact
 *
 * @package MxcCommons\Db\Sql\Ddl\Column
 */
interface ColumnInterface extends ExpressionInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isNullable();

    /**
     * @return null|string|int
     */
    public function getDefault();

    /**
     * @return array
     */
    public function getOptions();
}
