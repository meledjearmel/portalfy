<?php

namespace App\Actions;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdfAction
{
    /**
     * Génère le PDF de la facture à la demande et le met en cache sur le
     * disque privé pour éviter de le régénérer à chaque téléchargement.
     * Ce PDF n'est jamais envoyé par email, uniquement livré au client via
     * un téléchargement direct qu'il déclenche lui-même.
     */
    public function handle(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }

        $invoice->loadMissing('order.package', 'order.customer');

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        $path = "invoices/{$invoice->number}.pdf";

        Storage::disk('local')->put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }
}
