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

namespace Bridgepay\Bridge\Controller\Order;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Contract extends \Magento\Checkout\Controller\Onepage
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \YounitedCredit\YounitedPay\Helper\Maturity
     */
    protected $maturityHelper;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Bridgepay\Bridge\Helper\Banks
     */
    protected $bankHelper;

    /**
     * Contract constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Bridgepay\Bridge\Helper\Banks $bankHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Psr\Log\LoggerInterface $logger,
        \Bridgepay\Bridge\Helper\Banks $bankHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderManagement = $orderManagement;
        $this->cartRepository = $cartRepository;
        $this->date = $date;
        $this->logger = $logger;
        $this->bankHelper = $bankHelper;

        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );
    }

    /**
     * Create the payment URL and redirect customers
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Checkout\Model\Session $session */
        $session = $this->getOnepage()->getCheckout();
        if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $orderId = $session->getLastOrderId();
        $order = $this->orderRepository->get($orderId);

        $idBank = (int) $this->getRequest()->getParam('bank');
        $amount = (float) round($order->getGrandTotal(), 2);
        $storeId = (int) $order->getStoreId();

        $response = $this->bankHelper->createPayment($idBank, $storeId, $amount, $order);

        if ($response['success'] === false) {
            $errorAPI = __('Bridge API returns an invalid response.');
            try {
                $errorAPI = __('Error during payment, please try again.') . $response['response'];
                $this->bankHelper->addLog('Error while getting Payment Link', $errorAPI, 'error');
            } catch (\Exception $ex) {
                $this->logger->debug('Exception on error response :' . $ex->getMessage());
            }
            return $this->redirectOnError($order, [
                __('Error during payment, please try again.'),
                __($response['response'])
            ]);
        }

        /** @var \BridgeSDK\Model\Payment\CreatePaymentUrl $result */
        try {
            $result = $response['response']->getModel();
            $idTransaction = $result->getId();
            $paymentURL = $result->getConsentUrl();
        } catch (\Exception $ex) {
            return $this->redirectOnError($order, [__('Error during payment, please try again.')]);
        }

        $date = $this->date->date();
        $informations = $order->getPayment()->getAdditionalInformation();
        $informations['Payment ID'] = $idTransaction;
        $informations['Payment Date'] = $date;
        $informations['Payment Status'] = __('Created');
        $informations['Updated on'] = $date;

        $order->getPayment()->setAdditionalInformation($informations)->save();

        $order->addStatusHistoryComment(
            __(
                'Bridge Bank Transfert transaction started. Reference: %1',
                $informations['Payment ID']
            )
        )
            ->setIsCustomerNotified(false)
            ->save();

        return $this->resultRedirectFactory->create()
            ->setRefererUrl($this->bankHelper->getContractUrl('canceled'))
            ->setUrl($paymentURL);
    }

    /**
     * Redirect customers on error
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Framework\Phrase[] $message
     */
    public function redirectOnError(\Magento\Sales\Api\Data\OrderInterface $order, $message)
    {
        foreach ($message as $anError) {
            $this->messageManager->addErrorMessage($anError);
        }
        $quote = $this->cartRepository->get($order->getQuoteId());
        $quote->setIsActive(true);
        $this->cartRepository->save($quote);
        $this->getOnepage()->getCheckout()->replaceQuote($quote)->unsLastRealOrderId();

        try {
            $this->orderManagement->cancel($orderId);
        } catch (\Exception $ex) {
            $this->logger->debug('Exception while cancelling order with orderManagement :' . $ex->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}
