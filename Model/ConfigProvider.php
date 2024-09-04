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
use Bridgepay\Bridge\Model\Payment\LegalMentions;
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
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryHelper;

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
            self::$countryCode = $countryCode;
            $banks = [];
            $bankResponse = $this->bankHelper->getBanks(false, $this->getStore()->getId());
            if ($bankResponse['success'] === true) {
                $errorResponse = !($bankResponse['response'] instanceof ListBanksResponse);

                if ($errorResponse === false) {
                    $banks = $bankResponse['response']->getModel()->getBanks();
                }

                while ($errorResponse === false && $bankResponse['response']->getModel()->getAfter() !== '') {
                    $bankResponse = $this->bankHelper->getBanks(false, $this->getStore()->getId(), false, $bankResponse['response']->getModel()->getAfter());
                    $errorResponse = !($bankResponse['response'] instanceof ListBanksResponse);

                    if ($errorResponse === true) {
                        break;
                    }

                    $banks = array_merge(
                        $banks,
                        $bankResponse['response']->getModel()->getBanks()
                    );
                }

                $bankLists = new TreeBuilder(empty($countryCode) ? 'FR' : $countryCode);
                self::$banks = $bankLists->build($banks);
            }
        }

        $translations = [
            'search' => __('Search a bank'),
            'choose' => __('Choose my bank'),
            'noresult' => __('No results to display'),
            'back' => __('Back'),
            'legal_mentions_text_part_1' => __('By continuing, you agree to the'),
            'legal_mentions_link_part_2' => __('ToS'),
            'legal_mentions_text_part_3' => __('and the'),
            'legal_mentions_link_part_4' => __('information statement'),
            'legal_mentions_text_part_5' => __('of Bridge, a licensed payment institution.'),
        ];
        
        return [
            'payment' => [
                'bridgepayment' => [
                    'contractUrl' => $this->urlInterface->getUrl('bridge/order/contract'),
                    'url' => $this->urlInterface->getUrl('bridge/order/contract'),
                    'store' => $this->getStore()->getId(),
                    'banks' => self::$banks,
                    'translations' => $translations,
                    'logo' => $this->getAsset('images/logo-payment.png'),
                    'images' => [
                        'bank' => $this->getAsset('images/bank.png'),
                        'auth' => $this->getAsset('images/auth.png'),
                        'done' => $this->getAsset('images/done.png'),
                        'arrow' => $this->getAsset('images/arrow.png'),
                        'valid' => $this->getAsset('images/valid.png'),
                    ],
                    'terms_conditions_link' => $this->getTermsAndConditionsLink(),
                    'privacy_policy_link' => $this->getPrivacyPolicyLink(),
                ]
            ]
        ];
    }

    /**
     * Get asset URL
     * 
     * @param string
     * 
     * @return string
     */
    private function getAsset($asset)
    {
        return $this->assetRepository->createAsset('Bridgepay_Bridge::' . $asset)->getUrl();
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

    /**
     * Get terms & conditions link from language context
     *
     * @return string
     */
    public function getTermsAndConditionsLink()
    {
        $langContext = strtolower(empty(self::$countryCode) === false ? self::$countryCode : 'default');

        if (false === array_key_exists($langContext, LegalMentions::TERMS_CONDITIONS)) {
            $langContext = 'default';
        }

        return LegalMentions::TERMS_CONDITIONS[$langContext];
    }

    /**
     * Get privacy policy link from language context
     *
     * @return string
     */
    public function getPrivacyPolicyLink()
    {
        $langContext = strtolower(empty(self::$countryCode) === false ? self::$countryCode : 'default');

        if (false === array_key_exists($langContext, LegalMentions::PRIVACY_POLICY)) {
            $langContext = 'default';
        }

        return LegalMentions::PRIVACY_POLICY[$langContext];
    }
}
