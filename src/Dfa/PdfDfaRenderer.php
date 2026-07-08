<?php

declare(strict_types=1);

namespace Kowts\Efatura\Dfa;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Kowts\Efatura\Domain\Data\FiscalDocument;
use Kowts\Efatura\Exception\EfaturaException;

/**
 * Renderiza um DFA A4 com resumo fiscal, contingência e QR Code.
 */
final class PdfDfaRenderer
{
    public function render(
        string $iud,
        FiscalDocument $document,
        string $qrCodeUrl,
        string $currency = 'CVE'
    ): DfaDocument {
        if (!class_exists(Dompdf::class) || !class_exists(QrCode::class) || !extension_loaded('gd')) {
            throw new EfaturaException(
                'Instale dompdf/dompdf, endroid/qr-code e a extensão GD para gerar o DFA em PDF.'
            );
        }

        $qr = (new PngWriter())->write(new QrCode(data: $qrCodeUrl));
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(
            $this->html($iud, $document, $qr->getDataUri(), $qrCodeUrl, $currency),
            'UTF-8'
        );
        $pdf->render();

        return new DfaDocument($pdf->output(), 'application/pdf', $iud . '.pdf');
    }

    private function html(
        string $iud,
        FiscalDocument $document,
        string $qrDataUri,
        string $qrCodeUrl,
        string $currency
    ): string {
        $data = $document->toArray();
        $rows = '';
        foreach ($document->lines as $index => $line) {
            $description = (string) ($line->item['description'] ?? '');
            $rows .= '<tr><td>' . ($index + 1) . '</td><td>' . $this->e($description) . '</td>'
                . '<td class="n">' . $this->money($line->quantity) . '</td>'
                . '<td class="n">' . $this->money($line->price ?? 0) . '</td>'
                . '<td class="n">' . $this->money($line->netTotal ?? 0) . '</td></tr>';
        }
        $receiver = $document->receiver === null
            ? 'Consumidor final'
            : ($document->receiver->name ?? 'Consumidor final');
        $totals = $document->totals;
        $net = $totals === null ? 0.0 : $totals->net;
        $tax = $totals === null ? 0.0 : $totals->tax;
        $payable = $totals === null ? 0.0 : $totals->payable;
        $contingency = is_array($data['contingency'] ?? null)
            ? '<div class="warning">Emitido em contingência — '
                . $this->e((string) ($data['contingency']['reasonDescription'] ?? '')) . '</div>'
            : '';

        return '<!doctype html><html lang="pt"><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#172033}'
            . 'h1{font-size:20px;margin:0}h2{font-size:12px;margin:18px 0 6px}'
            . '.top{display:table;width:100%}.main,.qr{display:table-cell;vertical-align:top}.qr{width:140px;text-align:right}'
            . '.qr img{width:120px}.muted{color:#64748b}.warning{padding:9px;background:#fff3cd;margin:12px 0}'
            . 'table{width:100%;border-collapse:collapse}th,td{padding:6px;border-bottom:1px solid #d8dee9;text-align:left}'
            . '.n{text-align:right}.totals{margin-left:auto;width:260px}.iud{font-family:monospace;font-size:9px}'
            . '</style></head><body><div class="top"><div class="main"><h1>Documento Fiscal Auxiliar</h1>'
            . '<div class="muted">' . $this->e($document->type->value) . ' · '
            . $this->e($document->issueDate) . '</div><h2>Emitente</h2><strong>'
            . $this->e((string) $document->emitter->name) . '</strong><br>NIF '
            . $this->e((string) $document->emitter->taxId?->value) . '<h2>Adquirente</h2>'
            . $this->e((string) $receiver) . '</div><div class="qr"><img src="' . $qrDataUri
            . '"></div></div>' . $contingency
            . '<h2>Linhas</h2><table><thead><tr><th>#</th><th>Descrição</th><th class="n">Qtd.</th>'
            . '<th class="n">Preço</th><th class="n">Total</th></tr></thead><tbody>' . $rows
            . '</tbody></table><table class="totals"><tr><th>Líquido</th><td class="n">'
            . $this->money($net) . ' ' . $this->e($currency)
            . '</td></tr><tr><th>Imposto</th><td class="n">' . $this->money($tax)
            . ' ' . $this->e($currency) . '</td></tr><tr><th>A pagar</th><td class="n"><strong>'
            . $this->money($payable) . ' ' . $this->e($currency)
            . '</strong></td></tr></table><h2>IUD</h2><div class="iud">' . $this->e($iud)
            . '</div><div class="muted">Consulte: ' . $this->e($qrCodeUrl) . '</div></body></html>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ');
    }
}
