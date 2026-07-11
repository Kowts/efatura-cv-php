<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Bridge\Yii2\EfaturaBootstrap;
use Kowts\Efatura\Bridge\Yii2\EfaturaComponent;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Tests\Support\Yii2ApplicationStub;
use PHPUnit\Framework\TestCase;

final class Yii2BridgeTest extends TestCase
{
    public function testComponenteCriaClienteEEncaminhaChamadas(): void
    {
        $component = new EfaturaComponent([
            'config' => [
                'transmitter_nif' => '100200300',
                'transmitter_led' => '123',
                'software_code' => 'EFATURAPHP',
                'software_name' => 'e-Fatura PHP',
                'software_version' => '0.1.0',
            ],
        ]);

        self::assertInstanceOf(Efatura::class, $component->getClient());
        self::assertSame($component->getClient(), $component->getEfatura());

        $iud = $component->buildIud('2026-02-08', DocumentType::ElectronicInvoice, 1, 12345678);

        self::assertTrue(Iud::isValid($iud));
    }

    public function testBootstrapRegistaComponenteSemSobrescreverExistente(): void
    {
        $app = new Yii2ApplicationStub();
        $bootstrap = new EfaturaBootstrap();
        $bootstrap->config = [
            'config' => [
                'transmitter_nif' => '100200300',
                'transmitter_led' => '123',
                'software_code' => 'EFATURAPHP',
                'software_name' => 'e-Fatura PHP',
                'software_version' => '0.1.0',
            ],
        ];

        $bootstrap->bootstrap($app);
        $bootstrap->bootstrap($app);

        self::assertCount(1, $app->components);
        self::assertSame(EfaturaComponent::class, $app->components['efatura']['class']);
        self::assertSame('100200300', $app->components['efatura']['config']['transmitter_nif']);
    }
}
