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
            body { margin: 0; font-family: 'Manrope', sans-serif; color: #0B2B26; background: #F7FAF9; }
            .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.5rem; }
            .toolbar button { border: 0; border-radius: 9999px; background: #0F9D8C; color: #fff; font: inherit; font-weight: 600; padding: .65rem 1.4rem; cursor: pointer; }
            .sheet { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; padding: 0 1.5rem 1.5rem; }
            .ticket { border: 1px dashed #9CA3AF; padding: 1rem; background: #fff; break-inside: avoid; display: flex; flex-direction: column; gap: .35rem; text-align: center; }
            .zone { font-weight: 800; font-size: .95rem; }
            .label { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: #6B7280; }
            .code { font-family: ui-monospace, 'Courier New', monospace; font-size: 1.6rem; font-weight: 800; letter-spacing: .25em; color: #0F9D8C; }
            .package { font-weight: 600; font-size: .85rem; }
            .help { font-size: .65rem; color: #6B7280; }
            @media print {
                @page { size: A4; margin: 8mm; }
                body { background: #fff; }
                .toolbar { display: none; }
                .sheet { padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <span>{{ $accounts->count() }} code(s) à découper</span>
            <button type="button" onclick="window.print()">Imprimer</button>
        </div>

        <div class="sheet">
            @foreach ($accounts as $account)
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

        <script>
            window.addEventListener('load', () => window.print());
        </script>
    </body>
</html>
