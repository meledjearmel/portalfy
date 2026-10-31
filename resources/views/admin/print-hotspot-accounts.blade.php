<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Codes WiFi à imprimer — {{ $zone->name }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; font-family: 'Manrope', sans-serif; color: #0B2B26; background: #E5ECEA; }
            .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; max-width: 210mm; margin: 0 auto; padding: 1rem 0; }
            .toolbar button { border: 0; border-radius: 9999px; background: #0F9D8C; color: #fff; font: inherit; font-weight: 600; padding: .65rem 1.4rem; cursor: pointer; }

            /* Une feuille A4 = 15 tickets, 3 colonnes × 5 lignes. */
            .sheet {
                width: 210mm;
                height: 297mm;
                margin: 0 auto 1rem;
                padding: 8mm;
                background: #fff;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(5, 1fr);
                break-after: page;
            }
            .sheet:last-child { break-after: auto; }
            .ticket { border: 1px dashed #9CA3AF; padding: 4mm; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.5mm; text-align: center; overflow: hidden; }
            .zone { font-weight: 800; font-size: 11pt; }
            .label { font-size: 7pt; text-transform: uppercase; letter-spacing: .08em; color: #6B7280; }
            .code { font-family: ui-monospace, 'Courier New', monospace; font-size: 20pt; font-weight: 800; letter-spacing: .2em; color: #0F9D8C; }
            .package { font-weight: 600; font-size: 8.5pt; }
            .help { font-size: 6.5pt; color: #6B7280; }

            @media print {
                @page { size: A4; margin: 0; }
                body { background: #fff; }
                .toolbar { display: none; }
                .sheet { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <span>{{ $total }} code(s) · {{ $sheets->count() }} feuille(s)</span>
            <button type="button" onclick="window.print()">Imprimer</button>
        </div>

        @foreach ($sheets as $sheet)
            <div class="sheet">
                @foreach ($sheet as $account)
                    <div class="ticket">
                        <div class="zone">{{ $zone->name }}</div>
                        <div class="label">Code WiFi</div>
                        <div class="code">{{ $account->code }}</div>
                        @if ($account->package)
                            <div class="package">
                                {{ $account->package->name }} · {{ $account->package->duration_label }}
                                · {{ number_format($account->package->price, 0, ',', ' ') }} F
                            </div>
                        @endif
                        <div class="help">Connectez-vous au WiFi puis saisissez ce code sur la page qui s'ouvre.</div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <script>
            window.addEventListener('load', () => window.print());
        </script>
    </body>
</html>
