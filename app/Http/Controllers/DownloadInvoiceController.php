<?php

namespace App\Http\Controllers;

use App\Actions\GenerateInvoicePdfAction;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInvoiceController extends Controller
{
    /**
     * Télécharge le PDF d'une facture à la demande du client authentifié.
     * Jamais envoyé par email : c'est le seul point d'accès à ce document.
     */
    public function __invoke(Invoice $invoice, GenerateInvoicePdfAction $generatePdf): StreamedResponse
    {
        abort_unless(
            $invoice->order->customer_id === Auth::guard('customer')->id(),
            403,
        );

        $path = $generatePdf->handle($invoice);

        return Storage::disk('local')->download($path, "facture-{$invoice->number}.pdf");
    }
}
