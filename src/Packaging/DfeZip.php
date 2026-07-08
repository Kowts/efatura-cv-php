<?php

declare(strict_types=1);

namespace Kowts\Efatura\Packaging;

use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\EfaturaException;
use Kowts\Efatura\Exception\ValidationException;
use ZipArchive;

/**
 * Empacota um ou mais DFE como {IUD}.xml com compressão Deflate.
 */
final class DfeZip
{
    /**
     * @param list<array{iud:string, xml:string}> $files
     */
    public function build(array $files): string
    {
        if ($files === []) {
            throw new ValidationException('files', 'É necessário indicar, pelo menos, um DFE.', 'zip.empty');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'efatura-');
        if ($temporary === false) {
            throw new EfaturaException('Não foi possível criar o ficheiro ZIP temporário.');
        }

        $zip = new ZipArchive();
        $opened = false;
        try {
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new EfaturaException('Não foi possível criar o ficheiro ZIP.');
            }
            $opened = true;
            foreach ($files as $file) {
                if (!Iud::isValid($file['iud'])) {
                    throw new ValidationException('iud', 'O IUD é inválido.', 'zip.iud_invalid');
                }
                $name = $file['iud'] . '.xml';
                $zip->addFromString($name, $file['xml']);
                $zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 9);
            }
            $zip->close();
            $opened = false;
            $contents = file_get_contents($temporary);
            if ($contents === false) {
                throw new EfaturaException('Não foi possível ler o ficheiro ZIP criado.');
            }
            return $contents;
        } finally {
            if ($opened) {
                $zip->close();
            }
            @unlink($temporary);
        }
    }
}
