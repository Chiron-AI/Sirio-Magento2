<?php
/**
 * @category Chiron
 * @package Chiron_Sirio
 * @version 0.1.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Chiron\Sirio\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Chiron\Sirio\Model\Config as SirioConfig;

class Iframe extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Chiron_Sirio::system/config/iframe.phtml';

    /**
     * @var SirioConfig
     */
    private $sirioConfig;

    /**
     * @param  Context     $context
     * @param  SirioConfig $sirioConfig
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        SirioConfig $sirioConfig,
        array $data = []
    ) {
        $this->sirioConfig = $sirioConfig;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }


}
