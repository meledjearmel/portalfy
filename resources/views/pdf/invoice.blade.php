@php
    $zone = \App\Models\WifiZoneSetting::current();
    $order = $invoice->order;
@endphp
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Facture #{{ $invoice->number }}</title>
        <style>
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #0B2B26;
                font-size: 13px;
            }

            .header {
                border-bottom: 2px solid #0F9D8C;
                padding-bottom: 16px;
                margin-bottom: 24px;
                overflow: hidden;
            }

            .header h1 {
                float: left;
                font-size: 20px;
                margin: 0;
                color: #0F9D8C;
            }

            .header .meta {
                float: right;
                text-align: right;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 24px;
            }

            th, td {
                text-align: left;
                padding: 10px;
                border-bottom: 1px solid #E5E7EB;
            }

            th {
                background-color: #F7FAF9;
                color: #0B2B26;
            }

            .total-row td {
                font-weight: bold;
                border-top: 2px solid #0F9D8C;
                border-bottom: none;
            }

            .footer {
                margin-top: 48px;
                font-size: 11px;
                color: #6B7280;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>{{ $zone->name }}</h1>
            <div class="meta">
                <strong>Facture #{{ $invoice->number }}</strong><br>
                {{ $invoice->created_at?->translatedFormat('d M Y') }}
            </div>
        </div>

        <p>
            Client : {{ $order->customer?->email }}<br>
            Téléphone : {{ $order->phone }}
        </p>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Référence commande</th>
                    <th style="text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Forfait {{ $order->package->name }}</td>
                    <td>{{ $order->reference }}</td>
                    <td style="text-align: right;">{{ number_format($invoice->amount, 0, ',', ' ') }} F</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td style="text-align: right;">{{ number_format($invoice->amount, 0, ',', ' ') }} F</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            {{ $zone->name }}
            @if ($zone->address)
                — {{ $zone->address }}
            @endif
            @if ($zone->phone)
                — {{ $zone->phone }}
            @endif
        </div>
    </body>
</html>
