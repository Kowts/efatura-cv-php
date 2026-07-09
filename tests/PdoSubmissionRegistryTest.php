<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSubmissionRegistryTest extends TestCase
{
    public function testReservaDigestApenasUmaVezEntreLigacoes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'efatura-submissions-');
        self::assertNotFalse($path);

        try {
            $first = new PdoSubmissionRegistry(new PDO('sqlite:' . $path));
            $second = new PdoSubmissionRegistry(new PDO('sqlite:' . $path));
            $first->createTable();
            $digest = hash('sha256', 'pacote');

            self::assertTrue($first->claim($digest));
            self::assertFalse($second->claim($digest));
        } finally {
            @unlink($path);
        }
    }
}
