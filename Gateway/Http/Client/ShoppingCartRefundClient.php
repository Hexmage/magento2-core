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

namespace MultiSafepay\ConnectCore\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class ShoppingCartRefundClient implements ClientInterface
{
    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var Description
     */
    private $description;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ShoppingCartRefundClient constructor.
     *
     * @param Config $config
     * @param Description $description
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Description $description,
        SdkFactory $sdkFactory,
        Logger $logger
    ) {
        $this->description = $description;
        $this->sdkFactory = $sdkFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $request = $transferObject->getBody();

        try {
            $transactionManager = $this->sdkFactory->create($request[Store::STORE_ID])->getTransactionManager();
            $orderId = $request['order_id'];
            $transaction = $transactionManager->get($orderId);
            $refundRequest = $transactionManager->createRefundRequest($transaction);
            $description = $this->description->addDescription($this->config->getRefundDescription($orderId));
            $refundRequest->addDescription($description);
            $refundRequest->addMoney($request['money']);

            foreach ($request['payload'] as $refundItem) {
                $refundRequest->getCheckoutData()->refundByMerchantItemId($refundItem['sku'], $refundItem['quantity']);
            }

            return $transactionManager->refund($transaction, $refundRequest)->getResponseData();
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            $this->logger->logInvalidApiKeyException($invalidApiKeyException);

            return [];
        } catch (ApiException $apiException) {
            $this->logger->logExceptionForOrder($orderId, $apiException);

            return [];
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);

            return [];
        }
    }
}
