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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class DirectDebitConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_directdebit';
    public const VAULT_CODE = 'multisafepay_directdebit_vault';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'image' => $this->getImage(),
                    'vaultCode' => self::VAULT_CODE,
                    'is_preselected' => $this->isPreselected(),
                    'transaction_type' => $this->getTransactionType(),
                ],
            ],
        ];
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $path = $this->getPath();
        $this->assetRepository->createAsset($path);

        return $this->assetRepository->getUrl($path);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        if ($this->localeResolver->getLocale() === 'nl_NL') {
            return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-nl.png';
        }

        return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-en.png';
    }
}
