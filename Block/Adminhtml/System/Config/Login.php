<?php
/**
 * Copyright Bridge
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author 202 ecommerce <tech@202-ecommerce.com>
 * @copyright Bridge
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 */

namespace Bridgepay\Bridge\Block\Adminhtml\System\Config;

use Magento\Store\Model\ScopeInterface;
use Bridgepay\Bridge\Helper\Config;

class Login extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        $class = "bridge_login bridge_dev";
        $mode = $this->getMode();
        $modeInput = strpos($element->getHtmlId(), '_production') !== false ? 'prod' : 'dev';
        if ($modeInput == 'prod') {
            $class = "bridge_login bridge_prod";
        }
        if ($modeInput != $mode) {
            $class .= ' hidden';
        }
        return '<tr class="' . $class . '" id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
    }

    /**
     * Return curent mode selected in order to hide prod / dev login of non selected mode
     *
     * @return string
     */
    private function getMode()
    {
        if ($this->getRequest()->getParam('store')) {
            return $this->_scopeConfig->getValue(
                Config::XML_PATH_API_DEV_MODE,
                ScopeInterface::SCOPE_STORE,
                $this->getRequest()->getParam('store')
            );
        } elseif ($this->getRequest()->getParam('website')) {
            return $this->_scopeConfig->getValue(
                Config::XML_PATH_API_DEV_MODE,
                ScopeInterface::SCOPE_WEBSITE,
                $this->getRequest()->getParam('website')
            );
        } else {
            return $this->_scopeConfig->getValue(Config::XML_PATH_API_DEV_MODE);
        }
    }
}
