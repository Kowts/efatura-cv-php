<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Fiscal\RegistryResult;

interface DocumentStatusClient
{
    public function lookupDocument(string $iud, ?string $accessToken = null): RegistryResult;
}
