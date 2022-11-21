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

namespace Bridgepay\Bridge\Model;

use Bridgepay\Bridge\API\WebhookInterface;
use Bridgepay\Bridge\Model\WebHookIP;
use Bridgepay\Bridge\Helper\Config as BridgeConfig;
use Bridgepay\Bridge\Model\Payment\PaymentStatuses;
use Magento\Framework\Controller\Result\JsonFactory;

class Webhook implements WebhookInterface
{
    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    protected $configWriter;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $responseCode;

    /**
     * Withdrawn constructor.
     *
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Webhook return for payments
     *
     * @api
     * @param mixed $content - Content of payment status
     * @param int $timestamp - Time of executed webhook
     * @param string $type - Type of hook catched
     * @return string - response of action done
     */
    public function getPost($content, $timestamp, $type)
    {
        if ($this->isRemoteServerValid() === false) {
            return json_encode([
                'response_code' => 401,
                'message' => __('Unauthorized'),
                'success' => false,
            ]);
        }

        $webHookContent = $content;

        $this->logger->notice('WebHook Bridge catched :' . json_encode($webHookContent));

        if ($type === 'TEST_EVENT') {
            $this->configWriter->save(BridgeConfig::XML_PATH_IS_WEBHOOK_CONTACTED, true);
            return json_encode([
                'response_code' => 200,
                'message' => __('Test webhook succeeded'),
                'success' => true,
            ]);
        }

        if (isset($webHookContent['end_to_end_id']) === false) {
            return json_encode([
                'response_code' => 400,
                'message' => __('Bad webhook content'),
                'success' => false,
            ]);
        }

        $orderId = (int) $webHookContent['end_to_end_id'];
        $order = $this->orderRepository->get($orderId);
        $status = $webHookContent['status'];
        $success = $this->checkResponse($order, $orderId, $status);

        $this->logger->info(sprintf(
            'WebHook Bridge order %s - %s',
            $orderId,
            $this->message
        ));

        return json_encode([
            'response_code' => $this->responseCode,
            'message' => $this->message,
            'success' => $success,
        ]);
    }

    /**
     * Check if response is good
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param int $orderId
     * @param string $status
     */
    private function checkResponse($order, $orderId, $status)
    {
        $isPaymentSuccess = in_array($status, PaymentStatuses::SUCCESS_PAYMENTS);
        $isPaymentDone = in_array($status, PaymentStatuses::DONE_PAYMENTS);
        $allPaymentTypes = array_merge(
            PaymentStatuses::SUCCESS_PAYMENTS,
            PaymentStatuses::DONE_PAYMENTS,
            PaymentStatuses::REJECTED_PAYMENTS,
            PaymentStatuses::CREATED_PAYMENTS
        );
        $isPaymentOk = $isPaymentSuccess === true || $isPaymentDone === true;

        $success = false;
        $this->message = __('Nothing done');
        $this->responseCode = 200;

        $orderExists = $order->getEntityId() == $orderId;

        if ($isPaymentOk && $orderExists) {
            $this->updateOrder($order, $isPaymentSuccess, $isPaymentDone, $status);
            $success = true;
            $this->responseCode = 201;
        } else {
            if ($orderExists === false) {
                $this->message = __('Order with id %s not found', $orderId);
                $this->responseCode = 404;
            }

            if (in_array($status, PaymentStatuses::CREATED_PAYMENTS) === true) {
                $this->message = __('Payment sequence not completed, verification done a created payment.');
            }
    
            if (in_array($status, PaymentStatuses::REJECTED_PAYMENTS) === true) {
                $this->message = __('Payment have been rejected by the bank, please try again.');
            }
    
            if (in_array($status, $allPaymentTypes) === false) {
                $this->message = __('Payment status not found.');
            }
        }

        return $success;
    }

    /**
     * Check if response is good
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param bool $isPaymentSuccess
     * @param bool $isPaymentDone
     * @param string $status
     */
    private function updateOrder($order, $isPaymentSuccess, $isPaymentDone, $status)
    {
        $informations = $order->getPayment()->getAdditionalInformation();
        $date = $this->date->date();
        $informations['Updated on'] = $date;
        if ($isPaymentDone === true) {
            $informations['Payment Status'] = __('Transfert done');
            $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $orderStatus = $this->scopeConfig->getValue(
                BridgeConfig::XML_PATH_ORDER_TRANSFERT_DONE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );
            $this->message = __('Order state updated with transfert done');
        } elseif ($isPaymentSuccess === true) {
            $informations['Payment Status'] = __('Transfert pending');
            $orderState = \Magento\Sales\Model\Order::STATE_NEW;
            $orderStatus = $this->scopeConfig->getValue(
                BridgeConfig::XML_PATH_ORDER_WAITING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );
            $this->message = __('Order state updated with transfert pending');
        }
        $order->setState($orderState)->setStatus($orderStatus);
        $order->getPayment()->setAdditionalInformation($informations)->save();

        $order->addStatusHistoryComment(
            __(
                'Bridge Bank Transfert transaction updated (%1). Reference: %1',
                $status,
                $informations['Payment ID']
            )
        )
            ->setIsCustomerNotified(false);
            
        $order->save();
    }

    /**
     * Return is remote server valid
     *
     * @return bool
     */
    private function isRemoteServerValid()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $remote = $objectManager->get(Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);
        $IpAddress = $remote->getRemoteAddress();

        if (in_array($IpAddress, WebHookIP::AUTHORIZED_IP) === false) {
            $this->logger->info(sprintf(
                'Access not allowed with this IP Address: %s',
                $IpAddress
            ));
            return false;
        }

        return true;
    }
}
