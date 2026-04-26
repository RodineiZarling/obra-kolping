<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 18mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; }
        .muted { color: #6b7280; }
        .row { width: 100%; }
        .col { display: inline-block; vertical-align: top; }
        .col-6 { width: 49%; }
        .h1 { font-size: 16px; font-weight: 700; margin: 0 0 4px; }
        .small { font-size: 10px; }
        .header { border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 12px; }
        .filters { margin: 10px 0 14px; }
        .filters h3 { font-size: 11px; margin: 0 0 6px; }
        .filters ul { margin: 0; padding-left: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 6px; vertical-align: top; }
        th { background: #f9fafb; font-weight: 700; font-size: 10px; }
        td { font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

<div class="header">
    @php
        /** @var \App\Models\Empresa|null $empresa */

        $empresaNome = null;
        $empresaCreci = null;
        if (!empty($empresa)) {
            $empresaNome = $empresa->fantasia ?: $empresa->nome;
            $empresaCreci = !empty($empresa->creci) ? trim((string) $empresa->creci) : null;
        }

        $logoPath = null;
        $logoSrc = null;
        if (!empty($empresa) && !empty($empresa->logo)) {
            $logoValue = str_replace('\\', '/', (string) $empresa->logo);
            $logoValue = ltrim($logoValue, '/');

            // Pode vir como caminho absoluto (Windows/Linux) ou como caminho relativo no disk `public`.
            $candidates = [];

            // 1) Se vier absoluto (ex.: C:/...)
            if (preg_match('/^[A-Za-z]:\//', $logoValue) === 1) {
                $candidates[] = $logoValue;
            }

            // Algumas instalações salvam algo como "pacher/storage/...". Tentamos base_path.
            $candidates[] = base_path($logoValue);

            // 2) Se vier apontando para storage/app/public/... (sem passar pelo symlink public/storage)
            if (str_contains($logoValue, 'storage/app/public/')) {
                $pos = strpos($logoValue, 'storage/app/public/');
                $tail = substr($logoValue, $pos + strlen('storage/app/public/'));
                $candidates[] = storage_path('app/public/'.$tail);
            }

            // 3) Caso comum: valor salvo como path relativo dentro do disk `public` (ex.: "imagens/logo.png").
            $candidates[] = storage_path('app/public/'.$logoValue);

            // Normaliza casos em que o campo já vem com prefixo "storage/".
            if (str_starts_with($logoValue, 'storage/')) {
                $logoValue = substr($logoValue, strlen('storage/'));
            }

            // Prioriza o symlink padrão "public/storage" (disk public).
            $candidates[] = public_path('storage/'.$logoValue);

            // Fallback: se já veio um path relativo apontando diretamente para public.
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

            // Dompdf pode falhar ao carregar paths locais diretamente. Usamos data URI para garantir renderização.
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
    @endphp

    <div class="row">
        <div class="col col-6">
            <div class="h1">{{ $titulo }}</div>
            <div class="muted small">Gerado em {{ $emitidoEm->format('d/m/Y H:i') }}</div>
        </div>
        <div class="col col-6" style="text-align: right;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" style="height:60px; max-width: 220px;" alt="Logo">
            @elseif($empresaNome)
                <div style="font-weight: 700; font-size: 16px;">
                    {{ $empresaNome }}@if($empresaCreci) · CRECI {{ $empresaCreci }}@endif
                </div>
            @else
                <div style="font-weight: 700;">{{ config('app.name') }}</div>
            @endif

            <div class="muted small" style="margin-top: 4px;">
                @if($empresaNome)
                    <div>{{ $empresaNome }}@if($empresaCreci) · CRECI {{ $empresaCreci }}@endif</div>
                @endif
                @if(!empty($empresa) && (!empty($empresa->cidade) || !empty($empresa->uf)))
                    <div>{{ $empresa->cidade }}@if(!empty($empresa->uf)) - {{ $empresa->uf }}@endif</div>
                @endif
                @if(!empty($empresa) && (!empty($empresa->telefone) || !empty($empresa->email)))
                    <div>
                        {{ $empresa->telefone ?? '' }}@if(!empty($empresa->telefone) && !empty($empresa->email)) · @endif{{ $empresa->email ?? '' }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="filters">
    <h3>Filtros aplicados</h3>
    @if(empty($filtros))
        <div>Nenhum filtro.</div>
    @else
        <ul>
            @foreach($filtros as $f)
                <li><strong>{{ $f['label'] }}</strong>: {{ $f['value'] }}</li>
            @endforeach
        </ul>
    @endif
</div>

<table>
    <thead>
    <tr>
        @foreach($colunas as $col)
            <th>{{ $col['label'] }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($registros as $record)
        <tr>
            @foreach($colunas as $col)
                @php($value = $col['value']($record))
                <td>{{ $value }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
