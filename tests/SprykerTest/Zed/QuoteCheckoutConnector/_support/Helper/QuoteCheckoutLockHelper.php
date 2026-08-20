<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\QuoteCheckoutConnector\Helper;

use Spryker\Zed\QuoteCheckoutConnector\Business\QuoteCheckoutConnectorFacade;
use Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorDependencyProvider;
use SprykerTest\Shared\Testify\Helper\AbstractHelper;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Zed\QuoteCheckoutConnector\Helper\Storage\InMemoryQuoteCheckoutLockStorage;
use SprykerTest\Zed\Testify\Helper\ResolvedBusinessFactoryTrait;

/**
 * Keeps the duplicate-order guard of the checkout on the host lane, where there is no Redis.
 *
 * {@see \Spryker\Zed\QuoteCheckoutConnector\Business\QuoteCheckoutCondition} writes and reads its
 * lock flag through the StorageRedis client directly rather than through the Storage client, so it
 * has no database-backed mode to fall back to the way OMS locking has. Swapping the storage behind
 * the guard leaves the guard itself running: the pre-condition plugin still executes, still builds
 * its key and still refuses a quote that is already being checked out.
 *
 * The substitute lives for the length of the request, which is all the guard's own time-to-live
 * would give it anyway.
 */
class QuoteCheckoutLockHelper extends AbstractHelper
{
    use DependencyHelperTrait;
    use ResolvedBusinessFactoryTrait;

    public function useInMemoryQuoteCheckoutLock(): void
    {
        $this->getDependencyHelper()->setDependency(
            QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS,
            new InMemoryQuoteCheckoutLockStorage(),
            $this->getBusinessFactoryClassNameFor(QuoteCheckoutConnectorFacade::class),
        );
    }
}
