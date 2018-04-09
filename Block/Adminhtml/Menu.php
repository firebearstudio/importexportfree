<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Block\Adminhtml;

class Menu extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Firebear_ImportExport::menu.phtml';

    /**
     * @var \Firebear\ImportExport\Model\Source\Menu\Config
     */
    protected $config;

    protected $moduleResource;


    /**
     * Menu constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Firebear\ImportExport\Model\Source\Menu\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Firebear\ImportExport\Model\Source\Menu\Config $config,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        array $data = []
    ) {
        $this->config = $config;
        $this->moduleResource = $moduleResource;
        parent::__construct($context, $data);
    }

    /**
     * @return array|mixed|null
     */
    public function getItems()
    {
        return $this->config->get();
    }
}
