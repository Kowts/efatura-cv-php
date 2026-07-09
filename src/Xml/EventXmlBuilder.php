<?php

declare(strict_types=1);

namespace Kowts\Efatura\Xml;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\EmissionMode;
use Kowts\Efatura\Domain\EventId;
use Kowts\Efatura\Domain\EventType;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Validation\EventValidator;

/**
 * Gera eventos de anulação e inutilização de numeração.
 */
final class EventXmlBuilder
{
    public function __construct(
        private readonly EfaturaConfig $config,
        private readonly EventValidator $validator = new EventValidator()
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function build(string $eventId, array $event, EmissionMode $mode = EmissionMode::Online): string
    {
        if (!EventId::isValid($eventId)) {
            throw new ValidationException('eventId', 'O identificador do evento é inválido.', 'event.id_invalid');
        }
        $event = $this->validator->validate($event);
        $eventIdData = EventId::parse($eventId);
        /** @var EventType $type */
        $type = $event['type'];
        $issueDateTime = (string) $event['issueDateTime'];
        $eventDate = new \DateTimeImmutable($issueDateTime);
        if (
            $eventIdData['repositoryCode'] !== $this->config->repositoryCode()
            || $eventIdData['transmitterNif'] !== $this->config->transmitterNif
            || $eventIdData['issueDateTime'] !== $eventDate->format('Y-m-d\TH:i:s')
        ) {
            throw new ValidationException(
                'eventId',
                'O identificador do evento não corresponde à transmissão.',
                'event.id_mismatch'
            );
        }

        $target = '';
        if (is_array($event['iuds'] ?? null) && $event['iuds'] !== []) {
            foreach ($event['iuds'] as $iud) {
                $target .= Xml::element('IUD', (string) $iud);
            }
        } elseif (is_array($event['range'] ?? null)) {
            $range = $event['range'];
            $documentType = $range['documentType'] ?? null;
            if (!$documentType instanceof DocumentType) {
                $documentType = is_string($documentType)
                    ? DocumentType::tryFrom($documentType)
                    : DocumentType::fromCode((int) $documentType);
            }
            if (!$documentType instanceof DocumentType) {
                throw new ValidationException('event.range.documentType', 'O tipo de documento do intervalo é inválido.');
            }
            $target = Xml::element('Year', $range['year'] ?? null)
                . Xml::element('LedCode', $range['ledCode'] ?? null)
                . Xml::element('Serie', $range['serie'] ?? null)
                . Xml::element('DocumentTypeCode', $documentType->code())
                . Xml::element('DocumentNumberStart', $range['documentNumberStart'] ?? null)
                . Xml::element('DocumentNumberEnd', $range['documentNumberEnd'] ?? null);
        } else {
            throw new ValidationException('event.target', 'O evento deve indicar IUDs ou um intervalo documental.');
        }

        $emitter = $this->config->emitter;
        $taxId = is_array($emitter['taxId'] ?? null)
            ? $emitter['taxId'] : ['countryCode' => 'CV', 'value' => $this->config->transmitterNif];

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Event xmlns="' . DfeXmlBuilder::XML_NAMESPACE . '" Version="' . DfeXmlBuilder::XML_VERSION
            . '" Id="' . Xml::escape($eventId) . '" EventTypeCode="' . $type->value . '">'
            . '<EmitterTaxId CountryCode="' . Xml::escape((string) $taxId['countryCode']) . '">'
            . Xml::escape((string) $taxId['value']) . '</EmitterTaxId>'
            . Xml::element('IssueDateTime', $issueDateTime)
            . Xml::element('IssueReasonDescription', $event['issueReasonDescription'] ?? null)
            . $target
            . '<Transmission>' . Xml::element('IssueMode', $mode->code())
            . '<TransmitterTaxId CountryCode="CV">' . Xml::escape($this->config->transmitterNif)
            . '</TransmitterTaxId><Software>'
            . Xml::element('Code', $this->config->softwareCode)
            . Xml::element('Name', $this->config->softwareName)
            . Xml::element('Version', $this->config->softwareVersion)
            . '</Software></Transmission>'
            . Xml::element('RepositoryCode', $this->config->repositoryCode())
            . '</Event>';
    }
}
