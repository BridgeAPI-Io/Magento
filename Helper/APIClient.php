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

use BridgeSDK\Client;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Banks manipulation helper
 */
class APIClient
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Maturity constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Retrive the current store
     *
     * @return \Magento\Store\Api\Data\StoreInterface|Store
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        if (!$this->store) {
            $this->store = $this->storeManager->getStore();
        }

        return $this->store;
    }

    /**
     * Get Credential for current store / website
     *
     * @param int|bool $storeId
     * @param int|bool $websiteId
     *
     * @return array|false
     *
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApiCredentials($storeId = false, $websiteId = false)
    {
        if (($storeId === false || empty($storeId)) && $websiteId === false) {
            $storeId = $this->getStore()->getId();
        }

        if (!$storeId && !$websiteId) {
            return false;
        }

        $scope = ScopeInterface::SCOPE_STORE;
        $elemId = $storeId;
        if (!$storeId && $websiteId !== false) {
            $scope = ScopeInterface::SCOPE_WEBSITE;
            $elemId = $websiteId;
        }

        $apiMode = $this->scopeConfig->getValue(
            Config::XML_PATH_API_DEV_MODE,
            $scope,
            $elemId
        );

        $suffix = ''; // DEV
        if ($apiMode == 'prod') {
            $suffix = '_production';
        }

        $clientId = $this->scopeConfig->getValue(
            Config::XML_PATH_API_CLIENT_ID . $suffix,
            $scope,
            $elemId
        );

        $clientSecret = $this->scopeConfig->getValue(
            Config::XML_PATH_API_CLIENT_SECRET . $suffix,
            $scope,
            $elemId
        );

        if (empty($clientId) || $clientId === null) {
            return false;
        }

        if (empty($clientSecret) || $clientSecret === null) {
            return false;
        }

        return [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
        ];
    }

    /**
     * Send API Request
     *
     * @param \BridgeSDK\Request\AbstractRequest $request - Request to send
     * @param bool $logger - Do we add informations in logs ?
     * @param int|bool $storeId - Id of concerned store
     *
     * @return array
     */
    public function sendRequest($request, $logger = false, $storeId = false)
    {
        $credentials = $this->getApiCredentials($storeId);

        if ($credentials === false) {
            return [
                'response' => 'Error in client ID / Secret (not saved)',
                'success' => false,
            ];
        }

        $clientId = $credentials['clientId'];
        $clientSecret = $credentials['clientSecret'];

        $client = new Client();
        if ($logger === true) {
            $client->setLogger($this->logger);
        }

        $success = false;
        $client->setCredentials($clientId, $clientSecret);
        $errors = [];

        try {
            $responseCall = $client->sendRequest($request);

            if ((int) $responseCall->getStatusCode() !== 200) {
                $typeRequest = \get_class($request);
                $this->logger->info('Error in response - ' . $typeRequest . ' - ' . $responseCall->getReasonPhrase());
                $this->logger->info('Status code ' . $responseCall->getStatusCode());
                $this->logger->info(json_encode([
                    'Error request ' . $typeRequest => [
                        'clientId' => $clientId,
                        'clientSecret' => $clientSecret,
                        'response' => $responseCall,
                    ],
                ]));

                $response = __('Error in response');
                if ($this->isDeveloperMode() === true) {
                    $response .= '(details only in mode dev):';
                    $errors = 0;
                    foreach ($responseCall->getError() as $oneError) {
                        $response .= $errors > 0 ? ', ' : '';
                        $response .= $oneError;
                        ++$errors;
                    }
                } else {
                    $response .= '.';
                }
            } else {
                $response = $responseCall;
                $success = true;
            }
        } catch (\Exception $e) {
            $response = ($e->getMessage() . $e->getFile() . ':' . $e->getLine() . $e->getTraceAsString());
            $this->logger->critical('Bridge API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return [
            'response' => $response,
            'success' => $success,
        ];
    }

    /**
     * Return if in developer mode or not
     *
     * @return bool - Developer mode or not
     */
    private function isDeveloperMode()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\App\State $state */
        $state = $om->get(Magento\Framework\App\State::class);
        return \Magento\Framework\App\State::MODE_DEVELOPER === $state->getMode();
    }
}
