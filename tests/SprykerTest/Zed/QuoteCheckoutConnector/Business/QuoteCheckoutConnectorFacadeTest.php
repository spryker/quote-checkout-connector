<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\QuoteCheckoutConnector\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\Exception\NullValueException;
use Spryker\Zed\QuoteCheckoutConnector\Business\QuoteCheckoutCondition\QuoteCheckoutCondition;
use Spryker\Zed\QuoteCheckoutConnector\Dependency\Client\QuoteCheckoutConnectorToStorageRedisClientInterface;
use Spryker\Zed\QuoteCheckoutConnector\Dependency\Service\QuoteCheckoutConnectorToUtilTextServiceInterface;
use Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorConfig;
use Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorDependencyProvider;
use SprykerTest\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group QuoteCheckoutConnector
 * @group Business
 * @group Facade
 * @group QuoteCheckoutConnectorFacadeTest
 * Add your own group annotations below this line
 */
class QuoteCheckoutConnectorFacadeTest extends Unit
{
    /**
     * @var string
     */
    protected const LOCK_STORAGE_KEY_PATTERN = 'quote:checkout:lock:%s';

    /**
     * @var string
     */
    protected const LOCK_STORAGE_KEY_UUID = 'test_uuid';

    protected QuoteCheckoutConnectorBusinessTester $tester;

    public function testDisallowCheckoutForQuoteThrowsException(): void
    {
        // Assert
        $this->expectException(NullValueException::class);

        // Act
        $this->tester->getFacade()->disallowCheckoutForQuote(new QuoteTransfer());
    }

    public function testDisallowCheckoutForQuoteAddsLockEntityToTheStorage(): void
    {
        // Arrange
        $quoteCheckoutConnectorToStorageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $this->tester->setDependency(QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS, $quoteCheckoutConnectorToStorageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())->setUuid(static::LOCK_STORAGE_KEY_UUID);

        // Act
        $resultQuoteTransfer = $this->tester->getFacade()->disallowCheckoutForQuote($quoteTransfer);

        // Assert
        $this->assertSame($quoteTransfer, $resultQuoteTransfer);
        $this->assertInstanceOf(QuoteTransfer::class, $resultQuoteTransfer);
        $quoteCheckoutConnectorToStorageRedisClientMock->method('set')->with(
            $this->equalTo(sprintf(static::LOCK_STORAGE_KEY_PATTERN, static::LOCK_STORAGE_KEY_UUID)),
        )->willReturn(null);
    }

    public function testDisallowCheckoutForQuoteAddsLockEntityToTheStorageUsesCustomerData(): void
    {
        // Arrange
        $quoteCheckoutConnectorToStorageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $this->tester->setDependency(QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS, $quoteCheckoutConnectorToStorageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())->setCustomer(
            (new CustomerTransfer())->setFirstName('Spryker')->setLastName('oscar')->setEmail('oscar@spryker.com'),
        );

        // Act
        $resultQuoteTransfer = $this->tester->getFacade()->disallowCheckoutForQuote($quoteTransfer);

        // Assert
        $this->assertSame($quoteTransfer, $resultQuoteTransfer);
        $this->assertInstanceOf(QuoteTransfer::class, $resultQuoteTransfer);
        $quoteCheckoutConnectorToStorageRedisClientMock->method('set')->with(
            $this->equalTo('quote:checkout:lock:6a5296f8c258dc97c8fa7697812fdf46'),
        )->willReturn(null);
    }

    public function testIsCheckoutAllowedForQuoteReturnsTrue(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->method('get')->with(
            $this->equalTo(sprintf(static::LOCK_STORAGE_KEY_PATTERN, static::LOCK_STORAGE_KEY_UUID)),
        )->willReturn(true);
        $this->tester->setDependency(QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS, $storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())->setUuid(static::LOCK_STORAGE_KEY_UUID);
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $this->tester->getFacade()->isCheckoutAllowedForQuote($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse($checkoutResponseTransfer->getIsSuccess());
    }

    public function testIsCheckoutAllowedForQuoteReturnsFalse(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->method('get')->with(
            $this->equalTo(sprintf(static::LOCK_STORAGE_KEY_PATTERN, static::LOCK_STORAGE_KEY_UUID)),
        )->willReturn(null);
        $this->tester->setDependency(QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS, $storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())->setUuid(static::LOCK_STORAGE_KEY_UUID);
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $this->tester->getFacade()->isCheckoutAllowedForQuote($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertFalse($result);
        $this->assertNull($checkoutResponseTransfer->getIsSuccess());
    }

    public function testDisallowCheckoutForQuoteLocksOnOrderCustomReferenceForExemptSource(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->expects($this->once())->method('set')->with(
            $this->equalTo('quote:checkout:lock:16a47c01eefe88e00470c53d6fac9ce6'),
        );
        $quoteCheckoutCondition = $this->createQuoteCheckoutConditionWithExemptSource($storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())
            ->setSource('api')
            ->setCustomerReference('DE--1')
            ->setOrderCustomReference('PO-123');

        // Act
        $quoteCheckoutCondition->disallowCheckoutForQuote($quoteTransfer);
    }

    public function testDisallowCheckoutForQuoteDoesNotLockExemptSourceQuoteWithNoOrderCustomReference(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->expects($this->never())->method('set');
        $quoteCheckoutCondition = $this->createQuoteCheckoutConditionWithExemptSource($storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())
            ->setSource('api')
            ->setCustomerReference('DE--1');

        // Act
        $resultQuoteTransfer = $quoteCheckoutCondition->disallowCheckoutForQuote($quoteTransfer);

        // Assert
        $this->assertSame($quoteTransfer, $resultQuoteTransfer);
    }

    public function testIsCheckoutAllowedForQuoteReturnsTrueWhenOrderCustomReferenceLockExistsForExemptSource(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->method('get')->with(
            $this->equalTo('quote:checkout:lock:16a47c01eefe88e00470c53d6fac9ce6'),
        )->willReturn(true);
        $quoteCheckoutCondition = $this->createQuoteCheckoutConditionWithExemptSource($storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())
            ->setSource('api')
            ->setCustomerReference('DE--1')
            ->setOrderCustomReference('PO-123');
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $quoteCheckoutCondition->isCheckoutAllowedForQuote($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse($checkoutResponseTransfer->getIsSuccess());
    }

    public function testIsCheckoutAllowedForQuoteReturnsFalseForExemptSourceQuoteWithNoOrderCustomReference(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->expects($this->never())->method('get');
        $quoteCheckoutCondition = $this->createQuoteCheckoutConditionWithExemptSource($storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())
            ->setSource('api')
            ->setCustomerReference('DE--1');
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        // Act
        $result = $quoteCheckoutCondition->isCheckoutAllowedForQuote($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertFalse($result);
        $this->assertNull($checkoutResponseTransfer->getIsSuccess());
    }

    public function testDisallowCheckoutForQuoteUsesGuestHashWhenSourceIsNotExempt(): void
    {
        // Arrange
        $storageRedisClientMock = $this->createMock(QuoteCheckoutConnectorToStorageRedisClientInterface::class);
        $storageRedisClientMock->expects($this->once())->method('set')->with(
            $this->equalTo('quote:checkout:lock:6a5296f8c258dc97c8fa7697812fdf46'),
        );
        $this->tester->setDependency(QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS, $storageRedisClientMock);
        $quoteTransfer = (new QuoteTransfer())
            ->setCustomerReference('DE--1')
            ->setOrderCustomReference('PO-123')
            ->setCustomer(
                (new CustomerTransfer())->setFirstName('Spryker')->setLastName('oscar')->setEmail('oscar@spryker.com'),
            );

        // Act
        $this->tester->getFacade()->disallowCheckoutForQuote($quoteTransfer);
    }

    protected function createQuoteCheckoutConditionWithExemptSource(
        QuoteCheckoutConnectorToStorageRedisClientInterface $storageRedisClientMock,
    ): QuoteCheckoutCondition {
        $configWithExemptSource = new class extends QuoteCheckoutConnectorConfig {
            public function getQuoteCheckoutLockExemptSources(): array
            {
                return ['api'];
            }
        };

        $utilTextServiceMock = $this->createMock(QuoteCheckoutConnectorToUtilTextServiceInterface::class);
        $utilTextServiceMock->method('hashValue')->willReturnCallback(
            fn (string $value, string $algorithm): string => hash($algorithm, $value),
        );

        return new QuoteCheckoutCondition($configWithExemptSource, $storageRedisClientMock, $utilTextServiceMock);
    }
}
