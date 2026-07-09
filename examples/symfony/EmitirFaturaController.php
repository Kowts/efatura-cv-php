<?php

declare(strict_types=1);

namespace App\Controller;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class EmitirFaturaController
{
    public function __construct(private readonly Efatura $efatura)
    {
    }

    #[Route('/api/faturas/exemplo', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        $document = $this->efatura->document()
            ->type(DocumentType::ElectronicInvoice)
            ->issueDate(date('Y-m-d'))
            ->issueTime(date('H:i:s'))
            ->receiver([
                'taxId' => ['countryCode' => 'CV', 'value' => '900800700'],
                'name' => 'Cliente de Demonstração',
            ])
            ->line([
                'quantity' => ['value' => 1, 'unitCode' => 'UN'],
                'price' => 1000,
                'priceExtension' => 1000,
                'netTotal' => 1000,
                'taxes' => [[
                    'taxTypeCode' => 'IVA',
                    'taxPercentage' => 15,
                    'taxTotal' => 150,
                ]],
                'item' => ['description' => 'Serviço', 'emitterIdentification' => 'SERV-1'],
            ])
            ->totals([
                'priceExtensionTotalAmount' => 1000,
                'netTotalAmount' => 1000,
                'taxTotalAmount' => 150,
                'payableAmount' => 1150,
            ])
            ->validate();

        $iud = $this->efatura->buildSequentialIud($document['issueDate'], $document['type']);

        return new JsonResponse([
            'iud' => $iud,
            'xml' => $this->efatura->buildDfeXml($iud, $document),
        ], 201);
    }
}
