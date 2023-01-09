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

namespace Bridgepay\Bridge\Model;

use Bridgepay\Bridge\Model\Bank\TreeBuilder;
use BridgeSDK\Response\ListBanksResponse;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    protected $store;

    /**
     * @var string|int
     */
    protected $storeCode;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Repository
     */
    protected $assetRepository;

    /**
     * @var \Bridgepay\Bridge\Helper\Banks
     */
    protected $bankHelper;

    /**
     * @var array
     */
    protected static $banks;

    /**
     * @var string
     */
    protected static $countryCode;

    /**
     * ConfigProvider constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlInterface
     * @param Repository $assetRepository
     * @param \Bridgepay\Bridge\Helper\Banks $bankHelper
     * @param \Magento\Directory\Helper\Data $directoryHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartTotalRepositoryInterface $cartTotalRepository,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface,
        Repository $assetRepository,
        \Bridgepay\Bridge\Helper\Banks $bankHelper,
        \Magento\Directory\Helper\Data $directoryHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->storeManager = $storeManager;
        $this->urlInterface = $urlInterface;
        $this->assetRepository = $assetRepository;
        $this->bankHelper = $bankHelper;
        $this->directoryHelper = $directoryHelper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $countryCode = $this->directoryHelper->getDefaultCountry($quote->getStoreId());

        if (empty(self::$banks) === true || self::$countryCode !== $countryCode) {
            $bankResponse = $this->bankHelper->getBanks(false, $this->getStore()->getId());
            self::$banks = [];
            if ($bankResponse['success'] === true) {
                $bankLists = new TreeBuilder(empty($countryCode) ? 'FR' : $countryCode);
                if ($bankResponse['response'] instanceof ListBanksResponse) {
                    self::$banks = $bankLists->build($bankResponse['response']);
                    self::$countryCode = $countryCode;
                }
            }
        }

        $translations = [
            'search' => __('Search a bank'),
            'choose' => __('Choose my bank'),
            'noresult' => __('No results to display'),
            'back' => __('Back'),
        ];
        
        return [
            'payment' => [
                'bridgepayment' => [
                    'contractUrl' => $this->urlInterface->getUrl('bridge/order/contract'),
                    'url' => $this->urlInterface->getUrl('bridge/order/contract'),
                    'store' => $this->getStore()->getId(),
                    'banks' => self::$banks,
                    'translations' => $translations,
                    'logo' => $this->assetRepository
                                ->createAsset('Bridgepay_Bridge::images/logo-payment.png')
                                ->getUrl()
                ]
            ]
        ];
    }

    /**
     * Get Store
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        if (!$this->store) {
            try {
                $this->store = $this->storeManager->getStore();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->store = $this->storeManager->getStores()[0];
            }
        }
        return $this->store;
    }

    /**
     * Get Store code
     *
     * @return int|string
     */
    public function getStoreCode()
    {
        if (!$this->storeCode) {
            $this->storeCode = $this->getStore()->getCode();
        }
        return $this->storeCode;
    }
}
