<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Gateway\Validator;

use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;

class CurrencyValidator
{
    /**
     * @param CartInterface $quote
     * @param Config $config
     * @param string $methodCode
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(CartInterface $quote, Config $config, string $methodCode): bool
    {
        $storeId = $quote->getStoreId();

        if ((int)$config->getValue('allow_specific_currency', $storeId) === 1) {
            $availableCurrencies = explode(
                ',',
                (string)$config->getValue('allowed_currency', $storeId)
            );

            if (!in_array($quote->getStore()->getCurrentCurrencyCode(), $availableCurrencies, true)) {
                return true;
            }
        }

        return false;
    }
}
