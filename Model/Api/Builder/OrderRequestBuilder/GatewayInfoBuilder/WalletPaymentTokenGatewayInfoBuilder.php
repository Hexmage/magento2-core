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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Wallet;

class WalletPaymentTokenGatewayInfoBuilder implements GatewayInfoBuilderInterface
{
    /**
     * @var Wallet
     */
    private $walletGatewayInfoBuilder;

    /**
     * WalletPaymentTokenGatewayInfoBuilder constructor.
     *
     * @param Wallet $walletGatewayInfoBuilder
     */
    public function __construct(
        Wallet $walletGatewayInfoBuilder
    ) {
        $this->walletGatewayInfoBuilder = $walletGatewayInfoBuilder;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return Wallet|null
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): ?Wallet
    {
        return $this->walletGatewayInfoBuilder->addPaymentToken(
            (string)($payment->getAdditionalInformation()['payment_token'] ?? null)
        );
    }
}
