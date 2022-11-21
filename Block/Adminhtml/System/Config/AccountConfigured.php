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

class AccountConfigured extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->decorateRowHtml($element, '');
    }
    
    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        $format = '<div id="row_%s" class="bridge-fleft bridge-col-12">';
        $format .= '<div class="message message-warning warning">%s</div></div>';

        if ($this->isModuleConfigured() === true) {
            return '';
        }

        if ($this->getRequest()->getParam('store') === false && $this->getRequest()->getParam('website') === false) {
            return sprintf(
                $format,
                $element->getHtmlId(),
                __('<span style=\'color:red\'><b>Bridge</b> can only be configured in WEBSITE or STORE VIEW, please select in the top-left menu the WEBSITE or STORE VIEW that you want to configure.</span>')
            );
        }

        return sprintf(
            $format,
            $element->getHtmlId(),
            $element->getComment()
        );
    }

    /**
     * Return if module is correctly configured for current store view / website
     */
    private function isModuleConfigured()
    {
        $configClientId = Config::XML_PATH_API_CLIENT_ID;
        $configClientSecret = Config::XML_PATH_API_CLIENT_SECRET;
        if ($this->getConfig(Config::XML_PATH_API_DEV_MODE) === 'prod') {
            $configClientId = Config::XML_PATH_API_CLIENT_ID_PRODUCTION;
            $configClientSecret = Config::XML_PATH_API_CLIENT_SECRET_PRODUCTION;
        }
        $clientId = $this->getConfig($configClientId);
        $clientSecret = $this->getConfig($configClientSecret);

        return empty($clientId) === false && empty($clientSecret) === false;
    }

    /**
     * Return curent mode selected in order to hide prod / dev login of non selected mode
     *
     * @param string $config - Key to retrive (clientId / clientSecret)
     *
     * @return string
     */
    private function getConfig($config)
    {
        if ($this->getRequest()->getParam('store')) {
            return $this->_scopeConfig->getValue(
                $config,
                ScopeInterface::SCOPE_STORE,
                $this->getRequest()->getParam('store')
            );
        } elseif ($this->getRequest()->getParam('website')) {
            return $this->_scopeConfig->getValue(
                $config,
                ScopeInterface::SCOPE_WEBSITE,
                $this->getRequest()->getParam('website')
            );
        } else {
            return $this->_scopeConfig->getValue($config);
        }
    }
}
