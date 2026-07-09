<?php

declare(strict_types=1);

namespace Kowts\Efatura\Packaging;

use DOMDocument;
use DOMElement;
use Kowts\Efatura\Domain\EventId;
use Kowts\Efatura\Exception\EfaturaException;
use Kowts\Efatura\Exception\ValidationException;
use ZipArchive;

/**
 * Empacota eventos fiscais como {EventId}.xml com compressão Deflate.
 */
final class EventZip
{
    /**
     * @param list<array{eventId:string, xml:string}> $files
     */
    public function build(array $files): string
    {
        if ($files === []) {
            throw new ValidationException('files', 'É necessário indicar, pelo menos, um evento.', 'event_zip.empty');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'efatura-event-');
        if ($temporary === false) {
            throw new EfaturaException('Não foi possível criar o ficheiro ZIP temporário.');
        }

        $zip = new ZipArchive();
        $opened = false;
        try {
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new EfaturaException('Não foi possível criar o ficheiro ZIP de eventos.');
            }
            $opened = true;
            $names = [];
            foreach ($files as $file) {
                if (!EventId::isValid($file['eventId'])) {
                    throw new ValidationException('eventId', 'O identificador do evento é inválido.');
                }
                $name = $file['eventId'] . '.xml';
                if (isset($names[$name])) {
                    throw new ValidationException('files', "O evento {$name} está duplicado.", 'event_zip.duplicate');
                }
                $this->assertXmlMatchesEventId($file['xml'], $file['eventId']);
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
                throw new EfaturaException('Não foi possível ler o ficheiro ZIP de eventos.');
            }

            return $contents;
        } finally {
            if ($opened) {
                $zip->close();
            }
            @unlink($temporary);
        }
    }

    private function assertXmlMatchesEventId(string $xml, string $eventId): void
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (
                !$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)
                || !$document->documentElement instanceof DOMElement
            ) {
                throw new ValidationException('files.xml', 'O XML do evento é inválido.', 'event_zip.xml_invalid');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $root = $document->documentElement;
        if ($root->localName !== 'Event' || $root->getAttribute('Id') !== $eventId) {
            throw new ValidationException(
                'files.eventId',
                'O nome do ficheiro não corresponde ao Id do evento.',
                'event_zip.id_mismatch'
            );
        }
    }
}
