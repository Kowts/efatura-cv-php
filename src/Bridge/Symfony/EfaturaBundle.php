<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle de integração automática com o contentor Symfony.
 */
final class EfaturaBundle extends Bundle
{
    public function getContainerExtension(): EfaturaExtension
    {
        return new EfaturaExtension();
    }
}
