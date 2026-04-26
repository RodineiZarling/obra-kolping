@php
    $empresaNome = null;
    $empresaCreci = null;
    if ($empresa) {
        $empresaNome = $empresa->fantasia ?: $empresa->nome;
        $empresaCreci = !empty($empresa->creci) ? trim((string) $empresa->creci) : null;
    }

    $logoPath = null;
    $logoSrc = null;
    if ($empresa && !empty($empresa->logo)) {
        $logoValue = str_replace('\\', '/', (string) $empresa->logo);
        $logoValue = ltrim($logoValue, '/');

        $candidates = [];
        if (preg_match('/^[A-Za-z]:\//', $logoValue) === 1) {
            $candidates[] = $logoValue;
        }

        $candidates[] = base_path($logoValue);

        if (str_contains($logoValue, 'storage/app/public/')) {
            $pos = strpos($logoValue, 'storage/app/public/');
            $tail = substr($logoValue, $pos + strlen('storage/app/public/'));
            $candidates[] = storage_path('app/public/'.$tail);
        }

        $candidates[] = storage_path('app/public/'.$logoValue);

        if (str_starts_with($logoValue, 'storage/')) {
            $logoValue = substr($logoValue, strlen('storage/'));
        }

        $candidates[] = public_path('storage/'.$logoValue);
        $candidates[] = public_path($logoValue);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                $logoPath = $candidate;
                break;
            }
            if (is_string($candidate) && is_dir($candidate)) {
                $files = glob(rtrim($candidate, '/')."/*.{png,jpg,jpeg,gif,webp,svg}", GLOB_BRACE) ?: [];
                sort($files);
                if (!empty($files) && is_file($files[0])) {
                    $logoPath = $files[0];
                    break;
                }
            }
        }

        if (is_string($logoPath) && is_file($logoPath) && is_readable($logoPath)) {
            $ext = strtolower((string) pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'image/png',
            };
            $logoSrc = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
        }
    }

    $locadorNome = $repasse->locador?->nome ?: '—';
    $locadorDocumento = $repasse->locador?->documento ?: '—';
    $unidade = $repasse->contrato?->unidade?->identificador ?: ('#'.$repasse->contrato?->unidade_imovel_id);
    $imovel = $repasse->contrato?->unidade?->imovel?->titulo ?: '—';
@endphp

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante de Pagamento de Repasse</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .muted { color: #6b7280; }
        .row-header { width: 100%; margin-bottom: 16px; }
        .col { display: inline-block; vertical-align: top; }
        .col-6 { width: 49%; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .small { font-size: 11px; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
        .row { margin: 4px 0; }
        .label { font-weight: 700; }
    </style>
</head>
<body>
    <div class="row-header">
        <div class="col col-6">
            <div class="h1">Comprovante de Pagamento de Repasse</div>
            <div class="muted small">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <div class="col col-6" style="text-align: right;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" style="height:60px; max-width: 220px;" alt="Logo">
            @elseif($empresaNome)
                <div style="font-weight: 700; font-size: 16px;">
                    {{ $empresaNome }}@if($empresaCreci) · CRECI {{ $empresaCreci }}@endif
                </div>
            @else
                <div style="font-weight: 700;">Imobiliária</div>
            @endif

            <div class="muted small" style="margin-top: 4px;">
                @if($empresaNome)
                    <div>{{ $empresaNome }}@if($empresaCreci) · CRECI {{ $empresaCreci }}@endif</div>
                @endif
                @if($empresa && (!empty($empresa->cidade) || !empty($empresa->uf)))
                    <div>{{ $empresa->cidade }}@if(!empty($empresa->uf)) - {{ $empresa->uf }}@endif</div>
                @endif
                @if($empresa && (!empty($empresa->telefone) || !empty($empresa->email)))
                    <div>
                        {{ $empresa->telefone ?? '' }}@if(!empty($empresa->telefone) && !empty($empresa->email)) · @endif{{ $empresa->email ?? '' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="box">
        <div class="row"><span class="label">Locador:</span> {{ $locadorNome }}</div>
        <div class="row"><span class="label">Documento:</span> {{ $locadorDocumento }}</div>
        <div class="row"><span class="label">Contrato:</span> #{{ $repasse->contrato_locacao_id }}</div>
        <div class="row"><span class="label">Unidade:</span> {{ $imovel }} - {{ $unidade }}</div>
        <div class="row"><span class="label">Competência:</span> {{ $repasse->competencia_formatada ?? $repasse->competencia }}</div>
    </div>

    <div class="box">
        <div class="row"><span class="label">Data do pagamento:</span> {{ $dataPagamento->format('d/m/Y') }}</div>
        <div class="row"><span class="label">Forma de pagamento:</span> {{ $formaPagamentoNome ?: ($repasse->contasPagar?->formaPagamento?->nome ?: '—') }}</div>
        <div class="row"><span class="label">Valor pago:</span> R$ {{ number_format((float) $valorPago, 2, ',', '.') }}</div>
        <div class="row"><span class="label">Valor líquido repassar:</span> R$ {{ number_format((float) $repasse->valor_liquido_repassar, 2, ',', '.') }}</div>
        <div class="row"><span class="label">Status do repasse:</span> {{ ucfirst((string) ($repasse->status_efetivo ?? $repasse->status)) }}</div>
    </div>

    @if(!empty($observacao))
        <div class="box">
            <div class="row"><span class="label">Observação:</span> {{ $observacao }}</div>
        </div>
    @endif

    <div class="muted" style="margin-top: 18px; font-size: 12px;">
        Emitido em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
