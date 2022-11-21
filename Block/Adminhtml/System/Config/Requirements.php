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

use Bridgepay\Bridge\Helper\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

/**
 * Provides field with additional information
 */
class Requirements extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    public const CUSTOM_STATUS_CODE = 'bridge_waiting';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status
     */
    protected $ressourceStatus;
    
    /**
     * @var string
     */
    protected $_template = 'Bridgepay_Bridge::system/config/js.phtml';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Bridgepay\Bridge\Helper\Banks
     */
    protected $bankHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Url
     */
    protected $urlHelper;
 
    /**
     * @var StatusResourceFactory
     */
    protected $statusResourceFactory;

    /**
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context                $context
     * @param \Bridgepay\Bridge\Helper\Banks                         $bankHelper
     * @param \Psr\Log\LoggerInterface                               $logger
     * @param \Magento\Framework\App\RequestInterface                $httpRequest
     * @param \Magento\Framework\Url                                 $urlHelper
     * @param StatusFactory                                          $statusFactory
     * @param StatusResourceFactory                                  $statusResourceFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Status        $ressourceStatus
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Bridgepay\Bridge\Helper\Banks $bankHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $httpRequest,
        \Magento\Framework\Url $urlHelper,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status $ressourceStatus
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->bankHelper = $bankHelper;
        $this->_request = $httpRequest;
        $this->urlHelper = $urlHelper;
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
        $this->ressourceStatus = $ressourceStatus;
        $this->addBridgeOrderStatus();
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<div class="config-additional-comment-title"><h2><b>' . $element->getLabel() . '</b></h2></div>';

        return $this->decorateRowHtml($element, $html);
    }

    /**
     * Decorate HTML Row
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     *
     * @return string
     */
    private function decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        $asset = $this->_assetRepo->createAsset('Bridgepay_Bridge::images/image-marketing.png');
        $imageMarketing = '<img src="' . $asset->getUrl() . '" alt="Bridge Marketing Image" />';

        $format = '<div id="row_%s">';

        $format .= '<div class="col3-config-blocks first"><div class="config-bridge-comment">';
        $format .= $imageMarketing . '</div></div>';

        $format .= '<div class="col3-config-blocks last">';
        $format .= $html;
        $format .= $this->isCurlActivated();
        $format .= $this->isSSLActivated();
        $format .= $this->isApiConnected();
        $format .= $this->isProductionMode();
        $format .= $this->isWebhookConnected();
        $format .= $this->webHookConfiguration();
        $format .= '</div></div>';

        try {
            return sprintf(
                $format,
                $element->getHtmlId()
            );
        } catch (\Exception $ex) {
            return 'Exception : ' . $ex->getMessage();
        }
    }

    /**
     * Return cURL section
     *
     * @return string
     */
    private function isCurlActivated()
    {
        $isValid = 'invalid';
        $curl_version = 'Not installed';
        $ssl_version = '';
        if (function_exists('curl_version')) {
            $isValid = 'valid';
            $curl_info = curl_version();
            $curl_version = 'version v' . $curl_info['version'];
            $ssl_version = $curl_info['ssl_version'];

            $ch = curl_init('https://www.howsmyssl.com/a/check');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_close($ch);
        }

        $format = '<div class="config-bridge-server"><span class="' . $isValid . '"></span> CURL - '
            . $curl_version . ' ' . $ssl_version . '</div>';

        return $format;
    }

    /**
     * Return SSL section
     *
     * @return string
     */
    private function isSSLActivated()
    {
        /**
         * @see https://stackoverflow.com/questions/27904854/verify-if-curl-is-using-tls
         */
        $isValid = 'invalid';
        $isEnabled = __('Not enabled');

        $serverHTTPS = $this->_request->getServer('HTTPS');
        if (isset($serverHTTPS) && $serverHTTPS != 'off') {
            $isValid = 'valid';
            $isEnabled = __('Enabled');
        }
        $format = '<div class="config-bridge-server"><span class="' . $isValid . '"></span>';
        $format .= ' SSL & TLS1.2 - ' . $isEnabled . '</div>';

        return $format;
    }

    /**
     * Return is Production / SandBox section
     *
     * @return string
     */
    private function isProductionMode()
    {
        if ($this->getRequest()->getParam('store')) {
            $mode = $this->_scopeConfig->getValue(
                Config::XML_PATH_API_DEV_MODE,
                ScopeInterface::SCOPE_STORE,
                $this->getRequest()->getParam('store')
            );
        } elseif ($this->getRequest()->getParam('website')) {
            $mode = $this->_scopeConfig->getValue(
                Config::XML_PATH_API_DEV_MODE,
                ScopeInterface::SCOPE_WEBSITE,
                $this->getRequest()->getParam('website')
            );
        } else {
            $mode = $this->_scopeConfig->getValue(Config::XML_PATH_API_DEV_MODE);
        }
        $isValid = $mode == 'prod' ? 'valid' : 'invalid';

        $format = '<div class="config-bridge-server">';
        $format .= '<span class="' . $isValid . '"></span> ' . __('Production enviroment') . '</div>';

        return $format;
    }

    /**
     * Check WebHook connected or not
     *
     * @return string
     */
    private function isWebhookConnected()
    {
        $webHookValue = $this->_scopeConfig->getValue(Config::XML_PATH_IS_WEBHOOK_CONTACTED);
        $isValid = (bool) $webHookValue === true ? 'valid' : 'invalid';

        $format = '<div class="config-bridge-server"><span class="' . $isValid . '"></span> ';
        $format .= __('WebHook contacted') . '</div>';

        return $format;
    }

    /**
     * Check WebHook configuration
     *
     * @return string
     */
    private function webHookConfiguration()
    {
        $format = '<div class="config-additional-comment-title">';
        $format .= '<h2><b>' . __('WebHook configuration') . '</b></h2></div>';

        $webHookUrl = $this->urlHelper->getUrl('rest/V1/bridge/webhook', []) . 'webhook';

        $format .= '<div class="config-additional-comment-content">';
        $format .= '<span class="inline" style="font-size:14px;color:#00aff0;">';
        $format .= '<a href="#copy-clip-bridge" id="copy-clip-bridge" data-clipboard-copy="' . $webHookUrl . '"';
        $format .= ' title="' . $webHookUrl . '">';
        $format .= '<span class="truncate">' . $webHookUrl . '</span>';
        $format .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 24 24">';
        $format .= '<path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>';
        $format .= '<path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>';
        $format .= '</svg></a></span></div>';

        $format .= '<div id="bridge_webhook_copied" style="display:none;"';
        $format .= ' class="config-additional-comment-content message message-success success">';
        $format .= __('WebHook URL copied to clipboard');
        $format .= '</div>';

        $format .= '<div class="config-additional-comment-content">';
        $format .= __('This URL is needed to connect Bridge to your website. ');
        $format .= __('It must be entered in the WebHook section of your Bridge account ');
        $format .= __('for the module to work properly.');
        $format .= '</div>';

        return $format;
    }

    /**
     * Check API connection
     */
    private function isApiConnected()
    {
        $storeId = (int) $this->getRequest()->getParam('store');
        $websiteId = $this->getRequest()->getParam('website');
        $isValid = 'invalid';

        if (!$storeId && !$websiteId) {
            $message = __('Error not in website or store context');
        } else {
            try {
                $isApiConnected = $this->bankHelper->getBanks(true, $storeId, $websiteId);
                $isValid = $isApiConnected['success'] === true ? 'valid' : 'invalid';
                $message = $isApiConnected['response'];
            } catch (\Exception $e) {
                $message = sprintf(__('Please Check SDK installation: %s'), $e->getMessage());
            }
        }
        $format = '<span class="' . $isValid . '"></span> ';
        $format .= __('Connected to bridge API') . ' - ' . $message;

        return '<div class="config-bridge-server">' . $format . '</div>';
    }

    /**
     * Add default Bridge waiting status
     */
    public function addBridgeOrderStatus()
    {
        $getStatus = $this->ressourceStatus->getConnection()->fetchOne(
            $this->ressourceStatus->getConnection()->select()
            ->from($this->ressourceStatus->getTable('sales_order_status_state'), [new \Zend_Db_Expr(1)])
            ->where('status = ?', self::CUSTOM_STATUS_CODE));

        if (empty($getStatus) === false) {            
            return;
        }

        $statusResource = $this->statusResourceFactory->create();

        /** @var Magento\Sales\Model\ResourceModel\Order\StatusFactory $status */
        $status = $this->statusFactory->create();

        $status->setData([
            'status' => self::CUSTOM_STATUS_CODE,
            'label' => __('Waiting for Bridge transfert'),
        ]);

        try {
            $statusResource->save($status);
        } catch (\Magento\Framework\Exception\AlreadyExistsException $exception) {
            return;
        }

        $status->assignState(\Magento\Sales\Model\Order::STATE_NEW, false, true);
    }
}
