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

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use MultiSafepay\ConnectCore\Model\Api\Validator\AccountNumberValidator;

class DirectDebitValidator extends AbstractValidator
{

    /**
     * @var AccountNumberValidator
     */
    private $accountNumberValidator;

    /**
     * DirectDebitValidator constructor.
     *
     * @param AccountNumberValidator $accountNumberValidator
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        AccountNumberValidator $accountNumberValidator,
        ResultInterfaceFactory $resultFactory
    ) {
        $this->accountNumberValidator = $accountNumberValidator;
        parent::__construct($resultFactory);
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'] ?? null;

        if (!$payment) {
            return $this->createResult(false, [__('Can\'t get a payment information')]);
        }

        if ($paymentAdditionalInformation = $payment->getAdditionalInformation()) {
            if (empty($paymentAdditionalInformation['account_holder_name'])) {
                return $this->createResult(false, [__('The account holder name can not be empty')]);
            }

            $accountNumber = $paymentAdditionalInformation['account_holder_iban'] ?? null;

            if (!$accountNumber || !$this->accountNumberValidator->validate($accountNumber)) {
                return $this->createResult(false, [$accountNumber . __(' is not a valid IBAN number')]);
            }

        }

        return $this->createResult(true);
    }
}
