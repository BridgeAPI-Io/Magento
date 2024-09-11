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

class Config
{
    /**
     * Config keys
     */
    const XML_PATH_IS_ACTIVE = 'payment/bridgepayment/active';
    const XML_PATH_PAYMENT_TITLE = 'payment/bridgepayment/title';
    const XML_PATH_IS_WEBHOOK_CONTACTED = 'payment/bridgepayment/webhook_contacted';
    const XML_PATH_IS_IP_WHITELIST = 'bridge_setup/general/enable_ip_whitelist';
    const XML_PATH_IP_WHITELIST = 'bridge_setup/general/ip_whitelist';

    const XML_PATH_API_DEV_MODE = 'bridge_setup/general/mode';
    
    const XML_PATH_API_CLIENT_ID = 'bridge_setup/general/client_id';
    const XML_PATH_API_CLIENT_SECRET = 'bridge_setup/general/client_secret';

    const XML_PATH_API_CLIENT_ID_PRODUCTION = 'bridge_setup/general/client_id_production';
    const XML_PATH_API_CLIENT_SECRET_PRODUCTION = 'bridge_setup/general/client_secret_production';

    public const XML_PATH_PAYMENT_ACCOUNT = 'bridge_setup/general/payment_account';
    public const XML_PATH_PAYMENT_LABEL = 'bridge_setup/general/payment_label';

    public const XML_PATH_ORDER_WAITING = 'payment/bridge/order_status_waiting';
    public const XML_PATH_ORDER_TRANSFERT_DONE = 'payment/bridge/order_status_processing';
}
