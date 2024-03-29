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

namespace Bridgepay\Bridge\Controller\Contract;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Bridgepay\Bridge\Helper\Config as BridgeConfig;
use Bridgepay\Bridge\Model\Payment\PaymentStatuses;

class Success extends \Magento\Checkout\Controller\Onepage
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Bridgepay\Bridge\Helper\Banks
     */
    protected $bankHelper;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var string
     */
    protected $errorResponse;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Success constructor.
     *
     * @param Context $context
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
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Bridgepay\Bridge\Helper\Banks $bankHelper
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param Transaction $transaction
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
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Bridgepay\Bridge\Helper\Banks $bankHelper,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        Transaction $transaction
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->date = $date;
        $this->bankHelper = $bankHelper;
        $this->orderManagement = $orderManagement;
        $this->cartRepository = $cartRepository;

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
     * Create order and add payment informations
     *
     * Only if get Payment informations from Bridge is successfull
     */
    public function execute()
    {
        // Mettre la commande en processing
        $session = $this->getOnepage()->getCheckout();
        if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $orderId = $session->getLastOrderId();
        $order = $this->orderRepository->get($orderId);

        if ($this->isPaymentBridgeConfirmed($order, $orderId) === false) {
            return $this->redirectToCart($this->errorResponse);
        }

        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();

            if ($invoice->canCapture()) {
                $invoice->capture();
            }

            $invoice->save();
            $transactionSave = $this->transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $this->invoiceSender->send($invoice);

//            Send Invoice mail to customer
            $order->addStatusHistoryComment(
                __('Customer successfully returns from Bridge. Invoice creation #%1.', $invoice->getIncrementId())
            )
                ->setIsCustomerNotified(true);
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');

        return $resultRedirect;
    }

    /**
     * Check if payment if confirmed with an API call
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param int $orderId
     */
    private function isPaymentBridgeConfirmed($order, $orderId)
    {
        $informations = $order->getPayment()->getAdditionalInformation();
        $idTransaction = $informations['Payment ID'];
        $storeId = $order->getStoreId();

        $response = $this->bankHelper->getPaymentInformations($idTransaction, $storeId, $orderId);

        if ($response['success'] === false) {
            $this->errorResponse = __('Error in transaction response, please try again later.');
            return false;
        }

        try {
            $newStatus = $response['response']->getModel()->getStatus();
            $this->bankHelper->addLog(
                'Payment response from order ' . $orderId,
                'New status given : ' . $newStatus
            );
        } catch (\Exception $ex) {
            $this->bankHelper->addLog(
                'Get Payment confirmation and status',
                __('Error while getting status from response')
            );
            $this->errorResponse = 'Error while getting status from response';
            return false;
        }

        $date = $this->date->date();
        $informations['Updated on'] = $date;

        $isPaymentSuccess = in_array($newStatus, PaymentStatuses::SUCCESS_PAYMENTS);
        $isPaymentDone = in_array($newStatus, PaymentStatuses::DONE_PAYMENTS);

        if ($isPaymentSuccess === true || $isPaymentDone === true) {
            if ($isPaymentSuccess === true) {
                $informations['Payment Status'] = __('Transfert pending');
                $orderState = \Magento\Sales\Model\Order::STATE_NEW;
                $orderStatus = $this->scopeConfig->getValue(
                    BridgeConfig::XML_PATH_ORDER_WAITING,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                );
            }
            if ($isPaymentDone === true) {
                $informations['Payment Status'] = __('Transfert done');
                $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $orderStatus = $this->scopeConfig->getValue(
                    BridgeConfig::XML_PATH_ORDER_TRANSFERT_DONE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                );
            }
            $order->setState($orderState)->setStatus($orderStatus);
            $order->getPayment()->setAdditionalInformation($informations)->save();
            $order->save();
            return true;
        }

        if (in_array($newStatus, PaymentStatuses::CREATED_PAYMENTS) === true) {
            $this->errorResponse = __('Payment sequence not completed, verification done a created payment.');
            return false;
        }

        if (in_array($newStatus, PaymentStatuses::REJECTED_PAYMENTS) === true) {
            $this->errorResponse = __('Payment have been rejected by the bank, please try again.');
            return false;
        }

        $this->errorResponse = __('No payment status match, please try again.');

        return false;
    }

    /**
     * Redirect to cart after cancelling order
     *
     * @param string $message
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function redirectToCart($message)
    {
        // Mettre la commande en processing
        $session = $this->getOnepage()->getCheckout();
        if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $orderId = $session->getLastOrderId();
        $order = $this->orderRepository->get($orderId);

        $this->messageManager->addErrorMessage($message);
        $quote = $this->cartRepository->get($order->getQuoteId());
        $quote->setIsActive(true);
        $this->cartRepository->save($quote);
        $this->getOnepage()->getCheckout()->replaceQuote($quote)->unsLastRealOrderId();

        try {
            $this->orderManagement->cancel($orderId);
        } catch (\Exception $e) {
            
            $this->bankHelper->addLog(
                'Cancel order',
                __('Exception when order is cancelled: ' . $e->getTraceAsString())
            );
        }

        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}
