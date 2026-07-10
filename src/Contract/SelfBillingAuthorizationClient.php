<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Fiscal\SelfBillingAuthorizationResult;

/**
 * Cliente para pedir à PE/DNRE o código de autorização de autofacturação.
 */
interface SelfBillingAuthorizationClient
{
    public function authorizeSelfBilling(
        string $sellerTaxId,
        DocumentType $documentType,
        string $mobilePhoneNumber,
        int|float|string $totalAmount,
        ?string $accessToken = null
    ): SelfBillingAuthorizationResult;
}
