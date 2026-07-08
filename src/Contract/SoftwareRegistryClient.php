<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Fiscal\RegistryResult;

interface SoftwareRegistryClient
{
    public function lookupSoftware(string $code, ?string $accessToken = null): RegistryResult;
}
