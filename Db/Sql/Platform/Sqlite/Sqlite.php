<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\Sql\Platform\Sqlite;

use MxcCommons\Db\Sql\Platform\AbstractPlatform;

class Sqlite extends AbstractPlatform
{
    /**
     * Constructor
     *
     * Registers the type decorator.
     */
    public function __construct()
    {
        $this->setTypeDecorator('MxcCommons\Db\Sql\Select', new SelectDecorator());
    }
}
