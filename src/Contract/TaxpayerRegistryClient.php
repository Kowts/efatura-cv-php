<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Fiscal\RegistryResult;

interface TaxpayerRegistryClient
{
    public function lookupTaxpayer(string $nif, ?string $accessToken = null): RegistryResult;
}
