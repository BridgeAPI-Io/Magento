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

namespace Bridgepay\Bridge\Model\Bank;

use BridgeSDK\Response\BankResponse;
use BridgeSDK\Response\ListBanksResponse;

class TreeBuilder
{
    /**
     * @var string
     */
    protected $preferredIsoCode = 'FR';

    /**
     * List banks
     *
     * @param ListBanksResponse $banks
     */
    public function build(ListBanksResponse $banks)
    {
        $banksTree = [];
        /** @var BankResponse $bank */
        foreach ($banks->getModel()->getBanks() as $bank) {
            if (empty($bank->getParentName())) {
                $banksTree[$bank->getName()] = [
                    'children' => [],
                    'name' => $bank->getName(),
                    'logo' => $bank->getLogoUrl(),
                    'id_bank' => $bank->getId(),
                    'country' => $bank->getCountryCode(),
                ];
            } else {
                if (!isset($banksTree[$bank->getParentName()])) {
                    $banksTree[$bank->getParentName()] = [
                        'children' => [],
                        'logo' => $bank->getLogoUrl(),
                        'name' => $bank->getParentName(),
                        'country' => $bank->getCountryCode(),
                    ];
                }
                $banksTree[$bank->getParentName()]['children'][$bank->getName()] = [
                    'children' => [],
                    'logo' => $bank->getLogoUrl(),
                    'id_bank' => $bank->getId(),
                    'name' => $bank->getName(),
                    'country' => $bank->getCountryCode(),
                ];
            }
        }
        $self = $this;
        usort($banksTree, function ($a, $b) use ($self) {
            return $self->sortByNameCountry($a, $b);
        });

        foreach ($banksTree as &$bankItem) {
            usort($bankItem['children'], function ($a, $b) use ($self) {
                return $self->sortByNameCountry($a, $b);
            });
        }

        return $banksTree;
    }

    /**
     * Sort by name and country banks list
     *
     * @param array $a
     * @param array $b
     */
    protected function sortByNameCountry($a, $b)
    {
        $preferedIsoCode = $this->getPreferredIsoCode();
        if (!($a['country'] == $preferedIsoCode && $b['country'] == $preferedIsoCode)
            && $a['country'] != $b['country']
        ) {
            if ($a['country'] == $preferedIsoCode) {
                return -1;
            } elseif ($b['country'] == $preferedIsoCode) {
                return 1;
            } else {
                return $a['country'] > $b['country'];
            }
        }

        return $a['name'] > $b['name'];
    }

    /**
     * Get Preferred iso code
     *
     * @return string
     */
    public function getPreferredIsoCode()
    {
        return $this->preferredIsoCode;
    }

    /**
     * Set Preferred iso code
     *
     * @param string $preferredIsoCode
     *
     * @return TreeBuilder
     */
    public function setPreferredIsoCode($preferredIsoCode)
    {
        $this->preferredIsoCode = $preferredIsoCode;

        return $this;
    }
}
