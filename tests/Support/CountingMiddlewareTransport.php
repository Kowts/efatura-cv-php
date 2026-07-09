<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

use Kowts\Efatura\Contract\MiddlewareTransport;
use Kowts\Efatura\Http\SubmissionResult;

final class CountingMiddlewareTransport implements MiddlewareTransport
{
    public int $calls = 0;

    public function submit(
        string $baseUrl,
        string $transmitterKey,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult {
        ++$this->calls;
        return new SubmissionResult(true, 202, 'Accepted', [], '', []);
    }
}
