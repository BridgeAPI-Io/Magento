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

define([
    'ko',
    'jquery',
    'mage/translate',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader'
], function (ko, $, $t, Component, quote, totals, url, fullScreenLoader) {
    'use strict';

    return Component.extend({
        banks: [],
        redirectAfterPlaceOrder: false,
        defaults: {
            template: 'Bridgepay_Bridge/payment/bridgepayment'
        },

        getLogo: function() {
            return window.checkoutConfig.payment.bridgepayment.logo;
        },

        getImages: function() {
            return window.checkoutConfig.payment.bridgepayment.images;
        },

        getT: function(toTranslate) {
            return window.checkoutConfig.payment.bridgepayment.translations[toTranslate];
        },

        isThereBanks: function() {
            return window.checkoutConfig.payment.bridgepayment.banks.length > 0;
        },

        /**
         * Display Bank List
         */
        getBanks: function () {
            var banks = [];
            $.each(window.checkoutConfig.payment.bridgepayment.banks, (key, bank) => {
                bank.id_parent = key;                
                bank.is_children = 0;
                var removedBanks = ['SG', 'LCL'];
                if (removedBanks.indexOf(bank.name) === -1) {
                    if (typeof bank.id_bank == 'undefined') {
                        bank.id_bank = 'parent_' + key;
                    }
                    if (bank.children && bank.children.length > 0) {
                        bank.has_children = true;
                        banks.push(bank);
                        $.each(bank.children, (keyChild, childBank) => {
                            childBank.id_parent = key;
                            childBank.is_children = 1;
                            childBank.has_children = false;
                            banks.push(childBank);
                        });
                    } else {
                        bank.has_children = false;
                        banks.push(bank);
                    }
                }
            });
            this.banks = banks;
            return banks;
        },

        getTermsAndConditionsLink: function () {
            return window.checkoutConfig.payment.bridgepayment.terms_conditions_link;
        },

        getPrivacyPolicyLink: function () {
            return window.checkoutConfig.payment.bridgepayment.privacy_policy_link;
        },

        searchBank: function(reset) {
            $('.bank-wrapper').removeClass('bridge-selected');

            var search = $('#bridge_search').val();            
            var keySelectedBank = $('#bridge-key-bank-selected').val();
            var idSelectedBank = parseInt($('#bridge-id-bank-selected').val());
            var idSelectedParent = parseInt($('#bridge-id-bank-parent-selected').val());
            var levelBanks = parseInt($('#bridge-bank-level').val());

            if (reset === true) {
                $('.banks-wrapper [data-is-children="0"]').css('display', 'flex');
                $('.banks-wrapper [data-is-children="1"]').css('display', 'none');                
                $('#bridge_search').val('');
                $('#bridge-bank-level').val(0);
                $('#bridge-id-bank-parent-selected').val(-1);
                $('#bridge-id-bank-selected').val(-1);
                $('#bridge-key-bank-selected').val('');
                $('#bridge-back-button').css('display', 'none');
                return;
            }

            if (!search || search == '' || search.length < 2) {
                $('.bank-wrapper').css('display', 'none');
                if (levelBanks === 0) {               
                    $('.banks-wrapper [data-is-children="0"]').css('display', 'flex');
                } else {                    
                    var selectedBank = '#bridge_bank_parent_' + keySelectedBank;
                    var childrenBanks = $('.banks-wrapper [data-id-parent="' + keySelectedBank + '"]');
                    childrenBanks.css('display', 'flex');
                    $(selectedBank).css('display', 'none');
                }
                return;
            }

            $('.bank-wrapper').css('display', 'none');

            var filteredBanks = this.banks.filter(
                bank => this.filterBank(bank, search, idSelectedParent)
            );

            

            if (filteredBanks.length <= 0) {
                $('#bridge_no_banks').css('display', 'block');
                return;
            }

            $.each(filteredBanks, (index, bank) => {
                if (parseInt(bank.is_children) === levelBanks) {
                    $('#bridge_bank_' + bank.id_bank).css('display', 'flex');
                }
            });
        },

        filterBank: function(bank, search, idSelectedParent) {
            var isBankFound = bank.name.toLowerCase().includes(search.toLowerCase());
            return isBankFound && (idSelectedParent === -1 || bank.id_parent === idSelectedParent);
        },

        selectBank: function(idBank, hasChildren, key) {
            var selectedBank = '#bridge_bank_' + idBank;
            $('#bridge-key-bank-selected').val(key);
            $('#bridge-bank-level').val(hasChildren === false ? 0 : 1);
            $('#bridge-id-bank-parent-selected').val(hasChildren === false ? -1 : key);
            $('#bridge-id-bank-selected').val(hasChildren === false ? idBank : -1);

            if (hasChildren === false) {
                $('.bank-wrapper').removeClass('bridge-selected');
                $(selectedBank).addClass('bridge-selected');
                $('#bridgepayment-checkout').removeAttr('disabled');
            } else {  
                $('.bank-wrapper').css('display', 'none');
                var childrenBanks = $('.banks-wrapper [data-id-parent="' + key + '"]');
                childrenBanks.css('display', 'flex');
                $(selectedBank).css('display', 'none');
                $('#bridge-back-button').css('display', 'inline-block');
                $('#bridgepayment-checkout').attr('disabled', true);
            }
        },

        /**
         * After place order callback
         */
        afterPlaceOrder: function () {
            fullScreenLoader.startLoader();
            var idSelectedBank = $('#bridge-id-bank-selected').val();
            var placeOrderUrl = window.checkoutConfig.payment.bridgepayment.contractUrl
                + 'bank/' + idSelectedBank + '/';
            window.location.replace(url.build(placeOrderUrl));
        }
    });
});
