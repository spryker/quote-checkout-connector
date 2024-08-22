<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\QuoteCheckoutConnector\Dependency\Service;

interface QuoteCheckoutConnectorToUtilTextServiceInterface
{
    /**
     * @param string $value
     * @param string $algorithm
     *
     * @return string
     */
    public function hashValue(string $value, string $algorithm): string;
}
