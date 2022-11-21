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

 define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        if (window.checkoutConfig.payment.bridgepayment.banks.length > 0) {
            console.log('push bridgepayment in rendererlist');
            rendererList.push(
                {
                    type: 'bridgepayment',
                    component: 'Bridgepay_Bridge/js/view/payment/method-renderer/bridgepayment-method'
                }
            );
        }
        return Component.extend({});
    }
);
