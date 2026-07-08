<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Validation\DocumentValidator;

/**
 * Documento fiscal imutável com acesso tipado e conversão sem perdas para array.
 */
final class FiscalDocument
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param list<DocumentLine> $lines
     * @param array<string, mixed> $data
     */
    private function __construct(
        public readonly DocumentType $type,
        public readonly string $issueDate,
        public readonly ?string $issueTime,
        public readonly Party $emitter,
        public readonly ?Party $receiver,
        public readonly array $lines,
        public readonly ?DocumentTotals $totals,
        array $data
    ) {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?DocumentValidator $validator = null): self
    {
        $normalised = ($validator ?? new DocumentValidator())->validate($data);
        /** @var DocumentType $type */
        $type = $normalised['type'];
        /** @var array<string, mixed> $emitter */
        $emitter = $normalised['emitter'];
        $receiver = is_array($normalised['receiver']) ? Party::fromArray($normalised['receiver']) : null;
        $lines = array_values(array_map(
            static fn (array $line): DocumentLine => DocumentLine::fromArray($line),
            $normalised['lines']
        ));
        $totals = is_array($normalised['totals']) ? DocumentTotals::fromArray($normalised['totals']) : null;

        return new self(
            $type,
            (string) $normalised['issueDate'],
            isset($normalised['issueTime']) ? (string) $normalised['issueTime'] : null,
            Party::fromArray($emitter),
            $receiver,
            $lines,
            $totals,
            $normalised
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
