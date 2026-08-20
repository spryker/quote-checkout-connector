<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\QuoteCheckoutConnector\Helper\Storage;

use Spryker\Zed\QuoteCheckoutConnector\Dependency\Client\QuoteCheckoutConnectorToStorageRedisClientInterface;

/**
 * The storage the duplicate-order guard keeps its lock flags in, held in the process instead of in
 * Redis. Time-to-live is accepted and ignored: nothing in a single request outlives it.
 */
class InMemoryQuoteCheckoutLockStorage implements QuoteCheckoutConnectorToStorageRedisClientInterface
{
    /**
     * @var array<string, string>
     */
    protected array $values = [];

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->values[$key] ?? null;
    }

    /**
     * @return mixed
     */
    public function set(string $key, string $value, ?int $ttl = null)
    {
        $this->values[$key] = $value;

        return true;
    }
}
