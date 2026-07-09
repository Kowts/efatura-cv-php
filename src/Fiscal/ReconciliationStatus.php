<?php

declare(strict_types=1);

namespace Kowts\Efatura\Fiscal;

/**
 * Estado obtido ao reconciliar uma submissão com a autoridade fiscal.
 */
enum ReconciliationStatus: string
{
    case Confirmed = 'confirmed';
    case NotFound = 'not_found';
    case Indeterminate = 'indeterminate';
}
