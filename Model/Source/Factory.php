<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Firebear\ImportExport\Model\Source;

use Magento\Framework\ObjectManagerInterface;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;

/**
 * Import source factory
 */
class Factory
{
    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param string $className
     *
     * @return AbstractBehavior
     * @throws \InvalidArgumentException
     */
    public function create($className)
    {
        if(!$className) {
            throw new \InvalidArgumentException('Incorrect class name');
        }

        return $this->_objectManager->create($className);
    }
}
