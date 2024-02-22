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
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Bridge
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace Bridgepay\Bridge\Model\Payment;

class LegalMentions
{
    public const TERMS_CONDITIONS = [
        'default' => 'https://pay.bridgeapi.io/pdf/cgs-en.pdf',
        'de' => 'https://pay.bridgeapi.io/pdf/cgs-en.pdf',
        'en' => 'https://pay.bridgeapi.io/pdf/cgs-en.pdf',
        'es' => 'https://pay.bridgeapi.io/pdf/cgs-en.pdf',
        'fr' => 'https://pay.bridgeapi.io/pdf/cgs-fr.pdf',
        'it' => 'https://pay.bridgeapi.io/pdf/cgs-en.pdf',
        'pt' => 'https://pay.bridgeapi.io/pdf/cgs-en.pdf',
    ];

    public const PRIVACY_POLICY = [
        'default' => 'https://pay.bridgeapi.io/pdf/information_statement_EN.pdf',
        'de' => 'https://pay.bridgeapi.io/pdf/information_statement_EN.pdf',
        'en' => 'https://pay.bridgeapi.io/pdf/information_statement_EN.pdf',
        'es' => 'https://pay.bridgeapi.io/pdf/information_statement_EN.pdf',
        'fr' => 'https://pay.bridgeapi.io/pdf/information_statement_FR.pdf',
        'it' => 'https://pay.bridgeapi.io/pdf/information_statement_EN.pdf',
        'pt' => 'https://pay.bridgeapi.io/pdf/information_statement_EN.pdf',
    ];
}
