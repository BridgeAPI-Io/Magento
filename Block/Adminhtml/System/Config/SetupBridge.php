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

/**
 * Provides field with additional information
 */
class SetupBridge extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var string
     */
    protected $_template = 'Bridgepay_Bridge::system/setup.phtml';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        if (is_array($data) === false) {
            $data = [];
        }
        parent::__construct($context, $data);
        $this->logger = $logger;
    }

    /**
     * Render the element from XML config
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->getContentSetup();
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
        $format = '<div class="admin__page-section-title"></div>';
        $format .= '<div id="row_%s" class="bridge-fleft bridge-col-12">%s</div>';
        $format .= '<div class="admin__page-section-title"></div>';

        try {
            return sprintf(
                $format,
                $element->getHtmlId(),
                $html
            );
        } catch (\Exception $ex) {
            return 'Exception : ' . $ex->getMessage();
        }
    }

    /**
     * Retrieve setup content (information block)
     */
    private function getContentSetup()
    {
        $html = '<div class="config-bridge-server mt-2">';
        $html .= '<h1>' . __('Accept your first payments in 10 minutes') . '</h1></div>';
        $html .= '<div class="d-flex">';

        $html .= '<div class="info-item">';
        $html .= '<h2>' . __('Test Bridge') . '</h2>';

        $html .= '<ul>';
        $link = 'https://dashboard.bridgeapi.io/signup?utm_campaign=connector_prestashop';
        $html .= '<li><a href="' . $link . '" target="_blank">';
        $html .= __('Create a Bridge account') . '</a></li>';
        $html .= '<li>' . __('Create a test application') . '</li>';
        $html .= '<li>' . __('Enable test mode below') . '</li>';
        $html .= '<li>' . __('Insert test client ID and client Secret below') . '</li>';
        
        $link = 'https://docs.bridgeapi.io/docs/testing-your-payment-flow-2';
        $html .= '<li><a href="' . $link . '" target="_blank">';
        $html .= __('Test payments') . '</a></li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '<div class="info-item">';
        $html .= '<h2>' . __('Go to production') . '</h2>';
        $html .= '<ul>';
        $link = 'https://meetings.hubspot.com/philippe-d/meeting-link-self-serve-round-robin?uuid=3cd71f68-6299-4480-8c1b-f840201fd939';
        $html .= '<li><a href="' . $link . '" target="_blank">';
        $html .= __('Schedule an appointment here') . '</a></li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '<div class="info-item">';
        $html .= '<h2>' . __('Need help ?') . '</h2>';
        $html .= '<ul>';
        $html .= '<li>' . __('As for the solution, if you want to know the coverage available ?');
        $link = 'https://addons.prestashop.com/contact-form.php?id_product=88479';
        $html .= '&nbsp;<a href="' . $link . '" target="_blank">';
        $html .= __('Visit our FAQs here') . '</a></li>';
        $html .= '</ul>';
        $html .= '<ul>';
        $html .= '<li>' . __('Having a problem setting up the solution ?');
        $html .= '&nbsp;<a href="' . $link . '" target="_blank">';
        $html .= __('Contact our technical team here') . '</a></li>';
        $html .= '</ul>';
        $html .= '<ul>';
        $html .= '<li>' . __('Having a technical issue in production ?');
        $html .= '&nbsp;<a href="' . $link . '" target="_blank">';
        $html .= __('Contact our support here') . '</a></li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Retrieve payment method model
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getBridgeImage()
    {
        $asset = $this->_assetRepo->createAsset('Bridgepay_Bridge::images/logo-payment.png');
        return $asset->getUrl();
    }
}
