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

namespace Bridgepay\Bridge\Helper;

use BridgeSDK\Model\Payment\CreatePayment;
use BridgeSDK\Model\Payment\CreatePaymentTransaction;
use BridgeSDK\Model\Payment\Payment;
use BridgeSDK\Model\Payment\PaymentUser;
use BridgeSDK\Request\CreatePaymentRequest;
use BridgeSDK\Request\ListBanksRequest;
use BridgeSDK\Request\PaymentRequest;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\UrlInterface;

/**
 * Banks manipulation helper
 */
class Banks
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Bridgepay\Bridge\Helper\APIClient
     */
    protected $apiClient;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Banks Helper constructor.
     *
     * @param UrlInterface $urlInterface
     * @param APIClient $apiClient
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        UrlInterface $urlInterface,
        APIClient $apiClient,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->apiClient = $apiClient;
        $this->urlInterface = $urlInterface;
        $this->_storeManager = $storeManager;
    }

    /**
     * Return URL of controller for contract
     *
     * @param string $controller
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getContractUrl($controller)
    {
        return $this->urlInterface->getUrl('bridge/contract/' . $controller);
    }

    /**
     * Create payment for the bank provided
     *
     * @param int $idBank - Id bank selected by user
     * @param int $storeId - Id of store used for the configuration
     * @param float $amount - Amount to pay
     * @param \Magento\Sales\Api\Data\OrderInterface $order - Order object
     *
     * @return array|null
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createPayment(
        int $idBank,
        int $storeId,
        float $amount,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $remote = $objectManager->get(RemoteAddress::class);
        $IpAddress = $remote->getRemoteAddress();
        $orderId = empty($order->getEntityId()) === false ? $order->getEntityId() : $order->getExtOrderId();
        $clientRef = empty($order->getCustomerId()) === false ? $order->getCustomerId()
            : $order->getCustomerIsGuest();
        $clientRef = \mb_strlen($clientRef) <= 100 ? $clientRef : \substr($clientRef, 0, 99);
        $storeName = $this->_storeManager->getStore()->getName();
        $webSiteName = $this->_storeManager->getWebsite()->getName();
        $webSiteName .= $storeName != '' ? '-' . $storeName : '';
        $storeNameCut = \mb_strlen($webSiteName) <= 40 ? $webSiteName : \substr($webSiteName, 0, 39);

        $body = (new CreatePayment())
            ->setBankId((int) $idBank)
            ->setSuccessfulCallbackUrl($this->urlInterface->getUrl('bridge/contract/success'))
            ->setUnsuccessfulCallbackUrl($this->urlInterface->getUrl('bridge/contract/failed'))
            ->setTransactions([
                (new CreatePaymentTransaction())
                    ->setCurrency('EUR')
                    ->setLabel($storeNameCut)
                    ->setAmount($amount)
                    ->setClientReference($clientRef)
                    ->setEndToEndId($orderId),
            ])
            ->setUser(
                (new PaymentUser())
                    ->setFirstName($order->getCustomerFirstname())
                    ->setLastName($order->getCustomerLastname())
                    ->setIpAddress($IpAddress)
            );

        $this->addLog('Bridge API Create payment request', [json_encode($body)]);

        $request = (new CreatePaymentRequest())->setModel($body);

        return $this->apiClient->sendRequest($request, true);
    }

    /**
     * Add logs informations for errors / info calls
     *
     * @param string $title - Title (line start with this)
     * @param array $informations - Information(s) to add
     * @param array $type - Optionnal - success | critical | error | info (default)
     */
    public function addLog($title, $informations = [], $type = 'info')
    {
        if (is_array($informations) === false) {
            $informations = [$informations];
        }

        switch ($type) {
            case 'success':
                $this->logger->notice($title, $informations);
                break;

            case 'critical':
                $this->logger->critical($title, $informations);
                break;

            case 'error':
                $this->logger->error($title, $informations);
                break;

            default:
            case 'info':
                $this->logger->info($title, $informations);
                break;
        }
    }

    /**
     * Get information for the transaction / order
     *
     * @param string $idTransaction - id of the transaction initiated bith Bridge
     * @param int $storeId - Id of store used for the configuration
     * @param int $orderId - Id of the order concerned for logging
     *
     * @return array|null
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentInformations($idTransaction, int $storeId, int $orderId)
    {
        $payment = (new Payment())->setId($idTransaction);
        $request = (new PaymentRequest())->setModel($payment);

        $this->logger->info(
            'Bridge API Get Payment Informations request',
            [
                'Transaction Id : ' . $idTransaction,
                'For Order : ' . $idTransaction,
            ]
        );

        return $this->apiClient->sendRequest($request, true);
    }

    /**
     * Get Banks linked to the Bridge Account
     *
     * @param bool $count - Return count only if true, if false listing of banks (for configuration)
     * @param int $storeId - Id of store used for the configuration
     * @param int $websiteId - Id of website used for the configuration
     *
     * @return array|null
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBanks($count = false, $storeId = false, $websiteId = false)
    {
        $request = new ListBanksRequest();

        $responseCall = $this->apiClient->sendRequest($request, false, $storeId);

        if ($responseCall['success'] === true) {
            $countBanks = count($responseCall['response']->getModel()->getBanks());
            if ($count) {
                $response = $countBanks . __(' banks');
                $this->logger->info($countBanks . ' banks in response');
            } else {
                $response = $responseCall['response'];
                $this->logger->info($countBanks . ' banks in response (getting for front)');
            }

            return [
                'response' => $response,
                'success' => true,
            ];
        } else {
            return $responseCall;
        }
    }
}
