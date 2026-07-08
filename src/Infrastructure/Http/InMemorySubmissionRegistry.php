<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use Kowts\Efatura\Contract\SubmissionRegistry;

/**
 * Protecção por processo; aplicações distribuídas devem injectar persistência partilhada.
 */
final class InMemorySubmissionRegistry implements SubmissionRegistry
{
    /** @var array<string, true> */
    private array $digests = [];

    public function claim(string $digest): bool
    {
        if (isset($this->digests[$digest])) {
            return false;
        }
        $this->digests[$digest] = true;

        return true;
    }
}
