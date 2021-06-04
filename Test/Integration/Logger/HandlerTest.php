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

namespace MultiSafepay\ConnectCore\Test\Integration\Logger;

use Magento\Framework\App\ObjectManager;
use MultiSafepay\ConnectCore\Logger\Handler;
use MultiSafepay\ConnectCore\Logger\Logger;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    /**
     * @magentoConfigFixture default_store multisafepay/general/debug 1
     */
    public function testIsHandling()
    {
        /** @var Handler $handler */
        $handler = ObjectManager::getInstance()->get(Handler::class);

        $dummyRecord = ['level' => Logger::DEBUG];
        $this->assertTrue($handler->isHandling($dummyRecord));

        $dummyRecord = ['level' => Logger::INFO];
        $this->assertTrue($handler->isHandling($dummyRecord));
        
        $dummyRecord = ['level' => Logger::ERROR];
        $this->assertTrue($handler->isHandling($dummyRecord));
    }
    
    /**
     * @magentoConfigFixture default_store multisafepay/general/debug 0
     */
    public function testIsHandlingWhenDebugIsDisabled()
    {
        /** @var DebugHandler $handler */
        $handler = ObjectManager::getInstance()->get(Handler::class);

        $dummyRecord = ['level' => Logger::DEBUG];
        $this->assertFalse($handler->isHandling($dummyRecord));

        $dummyRecord = ['level' => Logger::INFO];
        $this->assertFalse($handler->isHandling($dummyRecord));
    }
}
