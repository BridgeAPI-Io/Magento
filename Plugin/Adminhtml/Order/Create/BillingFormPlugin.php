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

namespace Bridgepay\Bridge\Plugin\Adminhtml\Order\Create;

use Magento\Catalog\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Store\Model\ScopeInterface;

class BillingFormPlugin
{
    /**
     * Remove Bridge Method from billing form
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $subject
     * @param callable $proceed
     */
    public function aroundGetMethods(\Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $subject, callable $proceed)
    {
        $methods = $proceed();

        /** @var \Magento\Payment\Model\MethodInterface $method */
        foreach ($methods as $key => $method) {
            if ($method->getCode() == 'bridgepayment') {
                unset($methods[$key]);
            }
        }

        return $methods;
    }
}
