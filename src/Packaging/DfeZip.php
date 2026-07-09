<?php

declare(strict_types=1);

namespace Kowts\Efatura\Packaging;

use DOMDocument;
use DOMElement;
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
            $names = [];
            foreach ($files as $file) {
                if (!Iud::isValid($file['iud'])) {
                    throw new ValidationException('iud', 'O IUD é inválido.', 'zip.iud_invalid');
                }
                $name = $file['iud'] . '.xml';
                if (isset($names[$name])) {
                    throw new ValidationException(
                        'files',
                        "O ficheiro {$name} está duplicado no pacote.",
                        'zip.duplicate_file'
                    );
                }
                $this->assertXmlMatchesIud($file['xml'], $file['iud']);
                $names[$name] = true;
                if (
                    !$zip->addFromString($name, $file['xml'])
                    || !$zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 9)
                ) {
                    throw new EfaturaException("Não foi possível adicionar {$name} ao pacote ZIP.");
                }
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

    private function assertXmlMatchesIud(string $xml, string $iud): void
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (
                !$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)
                || !$document->documentElement instanceof DOMElement
            ) {
                throw new ValidationException('files.xml', 'O XML do pacote é inválido.', 'zip.xml_invalid');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $root = $document->documentElement;
        if ($root->localName !== 'Dfe' || $root->getAttribute('Id') !== $iud) {
            throw new ValidationException(
                'files.iud',
                'O IUD do nome do ficheiro não corresponde ao Id do DFE.',
                'zip.iud_xml_mismatch'
            );
        }
    }
}
