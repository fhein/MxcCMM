<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcCommons\Db\Sql\Platform\IbmDb2;

use MxcCommons\Db\Sql\Platform\AbstractPlatform;

class IbmDb2 extends AbstractPlatform
{
    /**
     * @param SelectDecorator $selectDecorator
     */
    public function __construct(SelectDecorator $selectDecorator = null)
    {
        $this->setTypeDecorator('MxcCommons\Db\Sql\Select', ($selectDecorator) ?: new SelectDecorator());
    }
}