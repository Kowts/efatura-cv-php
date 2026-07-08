<?php

declare(strict_types=1);

namespace Kowts\Efatura\Builder;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Data\FiscalDocument;
use Kowts\Efatura\Validation\DocumentValidator;

/**
 * Construtor fluente para documentos fiscais.
 */
final class DocumentBuilder
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param array<string, mixed> $defaults
     */
    public function __construct(
        private readonly DocumentValidator $validator,
        private readonly array $defaults = []
    ) {
    }

    public function type(DocumentType $type): self
    {
        $this->data['type'] = $type;
        return $this;
    }

    public function issueDate(string $date): self
    {
        $this->data['issueDate'] = $date;
        return $this;
    }

    public function issueTime(string $time): self
    {
        $this->data['issueTime'] = $time;
        return $this;
    }

    /**
     * @param array<string, mixed> $party
     */
    public function emitter(array $party): self
    {
        $this->data['emitter'] = $party;
        return $this;
    }

    /**
     * @param array<string, mixed>|null $party
     */
    public function receiver(?array $party): self
    {
        $this->data['receiver'] = $party;
        return $this;
    }

    /**
     * @param array<string, mixed> $line
     */
    public function line(array $line): self
    {
        $this->data['lines'][] = $line;
        return $this;
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    public function lines(array $lines): self
    {
        $this->data['lines'] = $lines;
        return $this;
    }

    /**
     * @param array<string, mixed> $totals
     */
    public function totals(array $totals): self
    {
        $this->data['totals'] = $totals;
        return $this;
    }

    /**
     * Permite definir campos menos comuns sem alargar continuamente a API fluente.
     */
    public function set(string $field, mixed $value): self
    {
        $this->data[$field] = $value;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_replace($this->defaults, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        return $this->validator->validate($this->toArray());
    }

    public function dto(): FiscalDocument
    {
        return FiscalDocument::fromArray($this->toArray(), $this->validator);
    }
}
