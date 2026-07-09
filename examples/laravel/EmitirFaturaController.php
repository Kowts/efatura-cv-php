<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;

final class EmitirFaturaController
{
    public function __construct(private readonly Efatura $efatura)
    {
    }

    /**
     * Exemplo reduzido; valide a autorização e os dados do pedido na aplicação.
     */
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
        $xml = $this->efatura->buildDfeXml($iud, $document);

        return new JsonResponse(['iud' => $iud, 'xml' => $xml], 201);
    }
}
