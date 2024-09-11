/*
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

require([
    'jquery'
], function ($) {
    $(document).on('readystatechange', function() {
        $('#bridge_setup_general_mode').change(function(e) {
            e.preventDefault();
            bridgeChangeMode();
        });
        $('#copy-clip-bridge').click(function(e) {
            e.preventDefault();
            copyToClipboard();
        });
        $('#bridge_setup_general_payment_account').change(function(e) {
            e.preventDefault();
            bridgeDisplayPaymentLabel();
        });
        bridgeDisplayPaymentLabel();
    });

    function copyToClipboard() {
        var text = $('#copy-clip-bridge').attr('data-clipboard-copy');
        try {
            jQueryCopy(text);
            showConfZone();
            return true;
        } catch (errorjQuery) {
            console.log('Error copy jQuery' + errorjQuery);
        }
        try {
            navigator.clipboard.writeText(text);
            showConfZone();
            return true;
        } catch(error) {
            console.log('Error writeText' + error);
        }
        try {
            navigator.clipboard.write(text);
            showConfZone();
            return true;
        } catch(errorWrite) {
            console.log('Error write' + errorWrite);
        }
    }

    function showConfZone() {
        console.log($('#bridge_webhook_copied'));
        $('#bridge_webhook_copied').show();
        setTimeout(function() {
            $('#bridge_webhook_copied').hide(250);
        }, 3500);
    }

    function jQueryCopy(text) {
        var copyTextAreaBridge = document.createElement("textarea");
        document.body.appendChild(copyTextAreaBridge);
        copyTextAreaBridge.value = text;
        copyTextAreaBridge.select();
        document.execCommand("copy");
        document.body.removeChild(copyTextAreaBridge);
    }

    function bridgeChangeMode() {
        var modeBridge = $('#bridge_setup_general_mode').val();
        console.log(modeBridge);

        $('.bridge_login').addClass('hidden');
        if (modeBridge == 'dev') {
            $('.bridge_dev').removeClass('hidden');
        } else {
            $('.bridge_prod').removeClass('hidden');
        }
    }

    function bridgeDisplayPaymentLabel() {
        var paymentAccountBridge = $('#bridge_setup_general_payment_account').val();

        if (paymentAccountBridge == 1) {
            $('#row_bridge_setup_general_payment_label').addClass('hidden');
        } else {
            $('#row_bridge_setup_general_payment_label').removeClass('hidden');
        }
    }
});