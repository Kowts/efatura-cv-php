<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Bridge\Yii2\EfaturaBootstrap;
use Kowts\Efatura\Bridge\Yii2\EfaturaComponent;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Tests\Support\Yii2ApplicationStub;
use PHPUnit\Framework\TestCase;

final class Yii2BridgeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        self::loadYii2StubsWhenFrameworkIsAbsent();
    }

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

    public function testComponenteAceitaFactoryPersonalizada(): void
    {
        $calls = 0;
        $expected = new Efatura(EfaturaConfig::fromArray(self::validConfig()));

        $component = new EfaturaComponent([
            'factory' => static function (EfaturaComponent $component) use (&$calls, $expected): Efatura {
                $calls++;
                self::assertSame('100200300', $component->config['transmitter_nif']);

                return $expected;
            },
            'config' => self::validConfig(),
        ]);

        self::assertSame($expected, $component->getClient());
        self::assertSame($expected, $component->getClient());
        self::assertSame(1, $calls);
    }

    public function testComponenteRejeitaFactoryInvalida(): void
    {
        $component = new EfaturaComponent([
            'factory' => static fn (): string => 'invalido',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deve devolver uma instância de Efatura');

        $component->getClient();
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

    public function testIntegraComAplicacaoYii2RealQuandoDisponivel(): void
    {
        if (!class_exists(\yii\console\Application::class)) {
            self::markTestSkipped('Yii2 real não está instalado neste ambiente.');
        }

        $previousApp = \Yii::$app;
        $app = null;
        try {
            $app = new \yii\console\Application([
                'id' => 'efatura-cv-test',
                'basePath' => dirname(__DIR__),
            ]);
            $bootstrap = new EfaturaBootstrap();
            $bootstrap->config = ['config' => self::validConfig()];
            $bootstrap->bootstrap($app);

            self::assertInstanceOf(EfaturaComponent::class, $app->get('efatura'));
            self::assertSame($app->get('efatura'), $app->efatura);
            self::assertInstanceOf(Efatura::class, $app->efatura->client);
            self::assertSame($app->efatura->client, \Yii::$container->get(Efatura::class));
        } finally {
            if ($app instanceof \yii\console\Application && method_exists($app->errorHandler, 'unregister')) {
                $app->errorHandler->unregister();
            }
            \Yii::$app = $previousApp;
            if (method_exists(\Yii::$container, 'clear')) {
                \Yii::$container->clear(Efatura::class);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private static function validConfig(): array
    {
        return [
            'transmitter_nif' => '100200300',
            'transmitter_led' => '123',
            'software_code' => 'EFATURAPHP',
            'software_name' => 'e-Fatura PHP',
            'software_version' => '0.1.0',
        ];
    }

    private static function loadYii2StubsWhenFrameworkIsAbsent(): void
    {
        $yiiBootstrap = dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
        if (!class_exists('Yii') && is_file($yiiBootstrap)) {
            require_once $yiiBootstrap;
        }

        if (class_exists(\yii\base\Component::class) && interface_exists(\yii\base\BootstrapInterface::class)) {
            return;
        }

        require_once __DIR__ . '/Stubs/Yii2/base/Component.php';
        require_once __DIR__ . '/Stubs/Yii2/base/BootstrapInterface.php';
    }
}
