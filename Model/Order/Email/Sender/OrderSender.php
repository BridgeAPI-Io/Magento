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
namespace Bridgepay\Bridge\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;

/**
 * Sends order email to the customer.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    /**
     * Sends order email to the customer but not before payment
     *
     * @param Order $order
     * @param bool $forceSyncMode
     * @return bool
     */
    public function send(Order $order, $forceSyncMode = false)
    {
        $informations = $order->getPayment()->getAdditionalInformation();
        $paymentMethod = $order->getPayment()->getMethod();
        $bridgeNewOrder = $paymentMethod == 'bridgepayment' && isset($informations['Payment ID']) == false;

        if ($bridgeNewOrder == true) {
            return false;
        }

        return parent::send($order, $forceSyncMode);
    }
}
