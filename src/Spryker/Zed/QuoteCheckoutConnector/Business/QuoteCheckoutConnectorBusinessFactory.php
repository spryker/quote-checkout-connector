<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteCheckoutConnector\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\QuoteCheckoutConnector\Business\QuoteCheckoutCondition\QuoteCheckoutCondition;
use Spryker\Zed\QuoteCheckoutConnector\Business\QuoteCheckoutCondition\QuoteCheckoutConditionInterface;
use Spryker\Zed\QuoteCheckoutConnector\Dependency\Client\QuoteCheckoutConnectorToStorageRedisClientInterface;
use Spryker\Zed\QuoteCheckoutConnector\Dependency\Service\QuoteCheckoutConnectorToUtilTextServiceInterface;
use Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorDependencyProvider;

/**
 * @method \Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorConfig getConfig()
 */
class QuoteCheckoutConnectorBusinessFactory extends AbstractBusinessFactory
{
    public function createQuoteCheckoutCondition(): QuoteCheckoutConditionInterface
    {
        return new QuoteCheckoutCondition(
            $this->getConfig(),
            $this->getStorageRedisClient(),
            $this->getUtilTextService(),
        );
    }

    public function getStorageRedisClient(): QuoteCheckoutConnectorToStorageRedisClientInterface
    {
        return $this->getProvidedDependency(QuoteCheckoutConnectorDependencyProvider::CLIENT_STORAGE_REDIS);
    }

    public function getUtilTextService(): QuoteCheckoutConnectorToUtilTextServiceInterface
    {
        return $this->getProvidedDependency(QuoteCheckoutConnectorDependencyProvider::SERVICE_UTIL_TEXT);
    }
}
