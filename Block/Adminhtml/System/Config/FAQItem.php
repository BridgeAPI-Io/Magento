<?php
/**
 * Copyright since 2022 Younited Credit
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
 * @author     202 ecommerce <tech@202-ecommerce.com>
 * @copyright 2022 Younited Credit
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace Bridgepay\Bridge\Block\Adminhtml\System\Config;

use FuncInfo;
use Exception;
use Magento\Framework\Url;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Provides field with additional information
 */
class FAQItem extends \Magento\Config\Block\System\Config\Form\Field
{
    /** @var \Magento\Framework\Url */
    protected $urlHelper;

    /**
     * @param Url $urlHelper
     */
    public function __construct(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
        parent::_construct();
    }

    /**
     * Render the element from XML config
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $label = $element->getLabel();
        if ($label !== null && $label === 'Contact') {
            $html =  $this->getHTMLRecap();
        } else {
            $html = $label ? '<div class="config-faqitem-title"><strong>' . $label . '</strong></div>' : '';
            $html .= '<div class="config-faqitem-content">' . $this->getContent($element->getComment()) . '</div>';
        }

        return $this->decorateRowHtml($element, $html);
    }

    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    private function decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        return sprintf(
            '<tr id="row_%s"><td colspan="3"><div class="config-faqitem">%s</div></td></tr>',
            $element->getHtmlId(),
            $html
        );
    }

    /**
     * Get FAQ content
     *
     * @param string $comment - type of the comment
     */
    private function getContent($comment)
    {
        switch ($comment) {
            default:
                $content = $comment;
            case "1":
                $content = __('Integrate Bridge, a secure plug-and-play payment solution, to increase your conversion rates at reduced costs. Enjoy easy reconciliation and low fraud rates.<br />');
                $content .= '<br />';
                $content .= __('The Bridge payment solution allows you to:<br />');
                $content .= '<br />';
                $content .= __('- Offer high payment ceilings:<br />');
                $content .= __('Increase your conversion rates by allowing top carts to pay large sums instantly. <br />');
                $content .= '<br />';
                $content .= __('- Take advantage of a low-cost payment solution <br />');
                $content .= __('Both cost management and cash flow management are simplified. You can take advantage of a more competitive cost than using a credit card by paying a low percentage only on the transactions made on your site. <br />');
                $content .= '<br />';
                $content .= __('- Enjoy instant payments: <br />');
                $content .= __('Receive funds in your bank account fast.<br />');
                $content .= '<br />';
                $content .= __('- Benefit from irrevocability of payments: <br />');
                $content .= __('Payments are irrevocable. In other words, customers cannot cancel or modify their payments. <br />');
                $content .= '<br />';
                $content .= __('- Reduce fraud rates and offer secure payment<br />');
                $content .= '<br />';
                $content .= __('With account-to-account transfer, at the time of payment, your customer is authenticated directly to their online account.');
                $content .= __('No sensitive data will be entered, which makes it possible to offer a secure payment process with a limited risk of fraud.<br />');
                break;
            case "2":
                $content = __('A seamless and secure payment process: <br />');
                $content .= __('<br />');
                $content .= __('Step 1: The customer chooses to pay for their purchase by account-to-account transfer <br />');
                $content .= __('Step 2: The customer selects their bank to settle their purchase <br />');
                $content .= __('Step 3: The customer is authenticated with their bank in a smooth and secure manner<br />');
                $content .= __('Step 4: The customer verifies and confirms the payment <br />');
                $content .= __('<br />');
                $content .= __('Benefits: <br />');
                $content .= __('- No manual input or IBAN<br />');
                $content .= __('- Payment in a few seconds<br />');
                $content .= __('- No additional cost <br />');
                $content .= __('<br />');
                $content .= __('<br />');
                $content .= __('High Ceilings <br />');
                $content .= __('Your customers can pay for big carts safely and instantly - no need to increase their credit card ceilings. <br />');
                $content .= __('<br />');
                $content .= __('Security <br />');
                $content .= __('No manual input is required - banking information remains in your customer\'s hands. <br />');
                $content .= __('<br />');
                $content .= __('Simplicity <br />');
                $content .= __('Whether your customer is on a mobile device or in front of a computer, the payment process is simple and seamless. Payment is completed in a matter of seconds.');
                break;
            case "3":
                $content = __('It depends on the banks. There are two use cases as follows:<br />');
                $content .= __('- For banks that offer instant transfer, funds arrive in your account in a matter of seconds (10 seconds max).<br />');
                $content .= __('- For banks that do not offer instant transfer yet, the funds arrive in your bank account within 48 business hours.<br />');
                $content .= __('In both cases, the Magento module informs you of the status and condition of your payment.');
                break;
            case "6":
                $content = __('Test mode allows you to verify that the module is working properly with your shop and to see how the customer experience is without any funds or fees being charged.<br />');
                $content .= __('Steps: <br />');
                $content .= __('- Create an account on the Bridge dashboard<br />                      ');
                $content .= __('<a href="https://dashboard.bridgeapi.io/signup?utm_campaign=connector_magento" target="_blank">');
                $content .= __('https://dashboard.bridgeapi.io/signup?utm_campaign=connector_magento');
                $content .= __('</a><br />');
                $content .= __('- Create an app on the Bridge dashboard on Sandbox mode<br />');
                $content .= __('- Activate the Test mode on the module<br />');
                $content .= __('- Add your Sandbox client ID and client secret<br />');
                $content .= __('<br />');
                $content .= __('You can then make the first Test payment :<br />');
                $content .= __('<a href="https://docs.bridgeapi.io/docs/testing-your-payment-flow-2" target="_blank">');
                $content .= __('https://docs.bridgeapi.io/docs/testing-your-payment-flow-2</a>');
                break;
            case "7":
                $content = __('Once you are ready to bill your customers with our module, you can switch to Production mode.<br />');
                $content .= __('You will need to create a Production app on the Bridge dashboard.<br />');
                $content .= __('To do so, please contact our sales team:<br />');
                $content .= __('<a href="https://contact.bridgeapi.io/fr-contactez-nous" target="_blank">');
                $content .= __('https://contact.bridgeapi.io/fr-contactez-nous</a>');
                break;
            case "8":
                $content = __('Yes, you need to configure webhooks in order to get more details about the transactions and their status.<br />');
                $content .= __('You need to add this callback URL :');
                $content .= '<br /><u><b>' . $this->urlHelper->getUrl('rest/v1/bridge/webhook', []) . 'webhook</b></u><br />';
                $content .= __(' to your Bridge dashboard > Webhooks > Add a webhook > And select this one:<br /> ');
                $content .= __('payment.transaction.updated.<br />');
                $content .= __('You can name the webhook as you wish.');
                break;
        }
        return $content;
    }

    /**
     * Retrieve module informations to contact support
     */
    private function getModuleInformations()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $moduleInfo =  $objectManager->get(ModuleList::class)->getOne('Bridgepay_Bridge');
                 
        /**
         * @var \Magento\Framework\App\ProductMetadataInterface $magentoInfo Magento Core
         */
        $magentoInfo = $objectManager->get(ProductMetadataInterface::class);
        $versionMagento = '';
        try {
            $versionMagento = $magentoInfo->getVersion();
        } catch (Exception $ex) {
            // No version detected
            $versionMagento = 'Non identifiée';
        }
        $moduleInfo['versionMagento'] = $versionMagento;

        return $moduleInfo;
    }

    /**
     * Get HTML recap in order to contact support (PHP version, Magento and Module)
     */
    private function getHTMLRecap()
    {
        $moduleInfo = $this->getModuleInformations();
        $URL = $this->urlHelper->getBaseUrl();
        
        $html = '<div class="config-faqitem-content">';
        // $html .= __('To contact the support you must go to the developer page of the module on Magento Addons');
        $html .= __('Please ');
        $html .= '<a href="https://contact.bridgeapi.io/fr-contactez-nous" target="_blank">' . __('contact us here') . '</a>';
        $html .= '<br />';
        $html .= __('Send a message with the following information:<br />');
        $html.= sprintf(
            __('- URL: %s<br />- PHP: %s<br />- Module version: %s<br />- Magento version: %s<br />'),
            $URL,
            phpversion(),
            isset($moduleInfo['setup_version']) ? $moduleInfo['setup_version'] : 'Non identifiée',
            $moduleInfo['versionMagento']
        );
        $html .= __('- Multishop: yes / no');
        $html .= '</div>';

        return $html;
    }
}
