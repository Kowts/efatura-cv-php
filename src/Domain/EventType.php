<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

/**
 * Eventos fiscais suportados.
 */
enum EventType: string
{
    case FiscalDocumentCancellation = 'FDC';
    case UnusedDocumentNumber = 'UDN';
}
