<?php
/**
 * Copyright since 2022 Younited Credit
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
 * @author     202 ecommerce <tech@202-ecommerce.com>
 * @copyright 2022 Younited Credit
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace Bridgepay\Bridge\API;

use Bridgepay\Bridge\Model\Webhook;

interface WebhookInterface
{
    /**
     * Webhook return for payments
     *
     * @api
     * @param mixed $content - Content of payment status
     * @param int $timestamp - Time of executed webhook
     * @param string $type - Type of hook catched
     * @return string - response of action done
     */
    public function getPost($content, $timestamp, $type);
}
