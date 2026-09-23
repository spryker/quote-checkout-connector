<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteCheckoutConnector\Business\QuoteCheckoutCondition;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Service\UtilText\Model\Hash;
use Spryker\Zed\QuoteCheckoutConnector\Dependency\Client\QuoteCheckoutConnectorToStorageRedisClientInterface;
use Spryker\Zed\QuoteCheckoutConnector\Dependency\Service\QuoteCheckoutConnectorToUtilTextServiceInterface;
use Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorConfig;

class QuoteCheckoutCondition implements QuoteCheckoutConditionInterface
{
    protected const string GLOSSARY_KEY_DUPLICATE_ORDER_PROCESSING = 'checkout.error.duplicate-order-processing';

    protected const string DUPLICATE_ORDER_LOCKED_QUOTE_ID_PARAMETER = '%quote-uid%';

    protected const string LOCK_KEY_PLACEHOLDER = '%s:%s';

    protected const string GUEST_HASH_VALUE_PLACEHOLDER = '%s-%s-%s';

    protected const string ORDER_CUSTOM_REFERENCE_HASH_VALUE_PLACEHOLDER = '%s-%s';

    protected QuoteCheckoutConnectorConfig $config;

    protected QuoteCheckoutConnectorToStorageRedisClientInterface $storageRedisClient;

    protected QuoteCheckoutConnectorToUtilTextServiceInterface $utilTextService;

    public function __construct(
        QuoteCheckoutConnectorConfig $config,
        QuoteCheckoutConnectorToStorageRedisClientInterface $storageRedisClient,
        QuoteCheckoutConnectorToUtilTextServiceInterface $utilTextService
    ) {
        $this->config = $config;
        $this->storageRedisClient = $storageRedisClient;
        $this->utilTextService = $utilTextService;
    }

    public function disallowCheckoutForQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $lockKey = $this->getLockKey($quoteTransfer);

        if ($lockKey === null) {
            return $quoteTransfer;
        }

        $this->storageRedisClient->set($lockKey, 'true', $this->config->getTtlQuoteCheckoutLock());

        return $quoteTransfer;
    }

    public function isCheckoutAllowedForQuote(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): bool
    {
        $lockKey = $this->getLockKey($quoteTransfer);

        if ($lockKey === null) {
            return false;
        }

        if ((bool)$this->storageRedisClient->get($lockKey)) {
            $this->addErrorToCheckoutResponseTransfer($quoteTransfer, $checkoutResponseTransfer);

            return true;
        }

        return false;
    }

    protected function getLockKey(QuoteTransfer $quoteTransfer): ?string
    {
        $discriminator = $this->buildLockDiscriminator($quoteTransfer);

        if ($discriminator === null) {
            return null;
        }

        return sprintf(
            static::LOCK_KEY_PLACEHOLDER,
            $this->config->getQuoteCheckoutLockStorageNamespace(),
            $discriminator,
        );
    }

    protected function buildLockDiscriminator(QuoteTransfer $quoteTransfer): ?string
    {
        if ($quoteTransfer->getUuid() !== null) {
            return $quoteTransfer->getUuid();
        }

        if ($this->isLockExemptSource($quoteTransfer)) {
            return $this->buildOrderCustomReferenceUniqueId($quoteTransfer);
        }

        return $this->buildGuestUniqueId($quoteTransfer);
    }

    protected function isLockExemptSource(QuoteTransfer $quoteTransfer): bool
    {
        return $quoteTransfer->getSource() !== null
            && in_array($quoteTransfer->getSource(), $this->config->getQuoteCheckoutLockExemptSources(), true);
    }

    protected function buildGuestUniqueId(QuoteTransfer $quoteTransfer): string
    {
        $customerTransfer = $quoteTransfer->getCustomerOrFail();

        $hashValue = sprintf(
            static::GUEST_HASH_VALUE_PLACEHOLDER,
            $customerTransfer->getFirstName(),
            $customerTransfer->getLastName(),
            $customerTransfer->getEmail(),
        );

        return $this->utilTextService->hashValue($hashValue, Hash::MD5);
    }

    protected function buildOrderCustomReferenceUniqueId(QuoteTransfer $quoteTransfer): ?string
    {
        $orderCustomReference = $quoteTransfer->getOrderCustomReference();

        if ($orderCustomReference === null || $orderCustomReference === '') {
            return null;
        }

        $hashValue = sprintf(
            static::ORDER_CUSTOM_REFERENCE_HASH_VALUE_PLACEHOLDER,
            $quoteTransfer->getCustomerReference(),
            $orderCustomReference,
        );

        return $this->utilTextService->hashValue($hashValue, Hash::MD5);
    }

    protected function addErrorToCheckoutResponseTransfer(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $checkoutErrorTransfer = $this->createCheckoutErrorTransfer($quoteTransfer);
        $checkoutResponseTransfer->addError($checkoutErrorTransfer)->setIsSuccess(false);
    }

    protected function createCheckoutErrorTransfer(QuoteTransfer $quoteTransfer): CheckoutErrorTransfer
    {
        $checkoutErrorTransfer = new CheckoutErrorTransfer();
        $checkoutErrorTransfer->setMessage(static::GLOSSARY_KEY_DUPLICATE_ORDER_PROCESSING);
        $checkoutErrorTransfer->setParameters([static::DUPLICATE_ORDER_LOCKED_QUOTE_ID_PARAMETER => $quoteTransfer->getUuid()]);

        return $checkoutErrorTransfer;
    }
}
