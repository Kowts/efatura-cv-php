<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

/**
 * Códigos de imposto aceites pelo formato DFE.
 */
enum TaxType: string
{
    case NotApplicable = 'NA';
    case Iva = 'IVA';
    case StampTax = 'IS';
    case IncomeTax = 'IR';
}
