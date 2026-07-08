<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Fiscal\RegistryResult;

interface EmitterAuthorizationClient
{
    public function checkEmitterAuthorization(
        string $transmitterNif,
        string $emitterNif,
        ?string $accessToken = null
    ): RegistryResult;
}
