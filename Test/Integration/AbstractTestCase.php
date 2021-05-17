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

namespace MultiSafepay\ConnectCore\Test\Integration;

use Exception;
use Magento\Config\Model\ResourceModel\Config as ConfigResourceModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\Sales\Total\Quote\SetupUtil;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase to serve as parent for other tests
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @param string $expectedClass
     * @param string $classIdentifier
     */
    protected function assertDiInstanceEquals(string $expectedClass, string $classIdentifier)
    {
        $this->assertInstanceOf($expectedClass, $this->getObjectManager()->get($classIdentifier));
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        return Bootstrap::getObjectManager();
    }

    /**
     * @return OrderInterface
     * @throws LocalizedException
     */
    protected function getOrder(): OrderInterface
    {
        static $order = null;
        if ($order instanceof OrderInterface) {
            return $order;
        }

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->getObjectManager()->get(OrderRepositoryInterface::class);

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->getObjectManager()->create(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->create();
        $searchResults = $orderRepository->getList($searchCriteria);
        $items = $searchResults->getItems();

        if (count($items) === 0) {
            throw new NotFoundException(__('No orders found'));
        }

        $order = array_shift($items);
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('transaction_type', 'direct');
        $order->setPayment($payment);

        return $order;
    }

    /**
     * @return PaymentDataObjectInterface
     * @throws LocalizedException
     */
    protected function getPaymentDataObject(string $type = 'direct'): PaymentDataObjectInterface
    {
        $order = $this->getOrder();

        /** @var PaymentDataObjectFactoryInterface $paymentDataObjectFactory */
        $paymentDataObjectFactory = $this->getObjectManager()->get(PaymentDataObjectFactoryInterface::class);
        $paymentDataObject = $paymentDataObjectFactory->create($order->getPayment());
        $paymentDataObject->getPayment()->setAdditionalInformation('transaction_type', $type);

        return $paymentDataObject;
    }

    /**
     * @param string $reservedOrderId
     * @return Quote
     * @throws Exception
     */
    protected function getQuote(string $reservedOrderId): Quote
    {
        $this->includeFixtureFile('quote_with_multiple_products');
        $configResource = $this->getObjectManager()->get(ConfigResourceModel::class);
        $configResource->saveConfig(
            'general/country/allow',
            'US',
            ScopeInterface::SCOPE_WEBSITES,
            1
        );

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->getObjectManager()->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->getObjectManager()->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function dump(array $data): string
    {
        return $this->getObjectManager()->get(SerializerInterface::class)->serialize(
            $data
        );
    }

    /**
     * @param string $fixtureFile
     * @throws Exception
     */
    protected function includeFixtureFile(string $fixtureFile)
    {
        /** @var ComponentRegistrar $componentRegistrar */
        $componentRegistrar = $this->getObjectManager()->get(ComponentRegistrar::class);
        $modulePath = $componentRegistrar->getPath('module', 'MultiSafepay_ConnectCore');
        $fixturePath = $modulePath . '/Test/Integration/_files/' . $fixtureFile . '.php';
        if (!is_file($fixturePath)) {
            throw new Exception('Fixture file "' . $fixturePath . '" could not be found');
        }

        $cwd = getcwd();
        $directoryList = $this->getObjectManager()->get(DirectoryList::class);
        $rootPath = $directoryList->getRoot();
        chdir($rootPath . '/dev/tests/integration/testsuite/');
        require($fixturePath);
        chdir($cwd);
    }

    /**
     * @param array $taxAdditionalData
     * @return CartInterface
     */
    protected function getQuoteWithTaxesAndDiscount(array $taxAdditionalData = []): CartInterface
    {
        $setupUtil = new SetupUtil($this->getObjectManager());
        $taxData = $taxAdditionalData ? array_merge_recursive($taxAdditionalData, $this->getSampleTaxDataWithDiscount())
            : $this->getSampleTaxDataWithDiscount();
        $setupUtil->setupTax($taxData['config_data']);
        $quote = $setupUtil->setupQuote($taxData['quote_data']);
        $quote->collectTotals();

        return $quote;
    }

    /**
     * @return array[]
     */
    protected function getSampleTaxDataWithDiscount(): array
    {
        return [
            'config_data' => [
                'config_overrides' => [
                    Config::XML_PATH_ALGORITHM => TaxCalculationInterface::CALC_ROW_BASE,
                    Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS => SetupUtil::SHIPPING_TAX_CLASS,
                ],
                'tax_rate_overrides' => [
                    SetupUtil::TAX_RATE_TX => 18,
                    SetupUtil::TAX_RATE_SHIPPING => 0,
                ],
                'tax_rule_overrides' => [
                    [
                        'code' => 'Product Tax Rule',
                        'product_tax_class_ids' => [
                            SetupUtil::PRODUCT_TAX_CLASS_1,
                        ],
                    ],
                    [
                        'code' => 'Shipping Tax Rule',
                        'product_tax_class_ids' => [
                            SetupUtil::SHIPPING_TAX_CLASS,
                        ],
                        'tax_rate_ids' => [
                            SetupUtil::TAX_RATE_SHIPPING,
                        ],
                    ],
                ],
            ],
            'quote_data' => [
                'billing_address' => [
                    'region_id' => SetupUtil::REGION_TX,
                ],
                'shipping_address' => [
                    'region_id' => SetupUtil::REGION_TX,
                ],
                'items' => [
                    [
                        'sku' => 'simple1',
                        'price' => 2542.37,
                        'qty' => 2,
                    ],
                ],
                'shipping_method' => 'flatrate_flatrate',
                'shopping_cart_rules' => [
                    [
                        'discount_amount' => 20,
                    ],
                ],
            ],
        ];
    }
}
