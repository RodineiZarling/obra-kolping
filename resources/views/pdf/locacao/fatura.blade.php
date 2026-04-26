<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fatura de Locação</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        .muted { color: #6b7280; }
        .row { width: 100%; }
        .col { display: inline-block; vertical-align: top; }
        .col-6 { width: 49%; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .h2 { font-size: 12px; font-weight: 700; margin: 16px 0 6px; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px; }
        .spacer { height: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; }
        th { text-align: left; background: #f9fafb; font-weight: 700; }
        td.num, th.num { text-align: right; }
        .totais td { border-bottom: none; padding: 4px 6px; }
        .totais tr:last-child td { font-weight: 700; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
@php
    /** @var array $imobiliaria */
    /** @var \App\Models\Empresa|null $empresa */
    /** @var array $cobranca */
    /** @var array|null $locatario */
    /** @var array|null $unidade */
    /** @var array|null $imovel */
    /** @var array $contrato */
    /** @var array $itens */

    $fmt = static fn ($v): string => 'R$ '.number_format((float) $v, 2, ',', '.');
    $dataVenc = $cobranca['vencimento'] ? \Carbon\Carbon::parse($cobranca['vencimento'])->format('d/m/Y') : '—';
@endphp

<div class="row">
    <div class="col col-6">
        <div class="h1">Fatura de Locação</div>
        <div class="muted small">Gerada em {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <div class="col col-6" style="text-align: right;">
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

                // Pode vir como caminho absoluto (Windows/Linux) ou como caminho relativo no disk `public`.
                $candidates = [];

                // 1) Se vier absoluto (ex.: C:/... ou /...)
                if (preg_match('/^[A-Za-z]:\//', $logoValue) === 1) {
                    $candidates[] = $logoValue;
                }

                // Se veio com barra inicial (ex.: /pacher/storage/...) após normalização/ltrim, não teremos aqui.
                // Ainda assim, algumas instalações salvam algo como "pacher/storage/...". Tentamos base_path.
                $candidates[] = base_path($logoValue);

                // 2) Se vier apontando para storage/app/public/... (sem passar pelo symlink public/storage)
                if (str_contains($logoValue, 'storage/app/public/')) {
                    $pos = strpos($logoValue, 'storage/app/public/');
                    $tail = substr($logoValue, $pos + strlen('storage/app/public/'));
                    $candidates[] = storage_path('app/public/'.$tail);
                }

                // 3) Caso comum: valor salvo como path relativo dentro do disk `public` (ex.: "imagens/logo.png").
                // Nesse caso, o arquivo real fica em storage/app/public/...
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
                        // Se o campo estiver apontando para uma pasta (ex.: .../imagens), tenta achar a primeira imagem.
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

        @if($logoSrc)
            <img src="{{ $logoSrc }}" style="height:60px; max-width: 220px;" alt="Logo">
        @elseif($empresaNome)
            <div style="font-weight: 700; font-size: 16px;">
                {{ $empresaNome }}@if($empresaCreci) · CRECI {{ $empresaCreci }}@endif
            </div>
        @else
            <div style="font-weight: 700;">{{ $imobiliaria['nome'] }}</div>
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

            {{-- Fallback: mantém a exibição anterior caso não haja empresa cadastrada --}}
            @if(!$empresa)
                @if(!empty($imobiliaria['documento']))
                    <div>{{ $imobiliaria['documento'] }}</div>
                @endif
                @if(!empty($imobiliaria['endereco_linha']))
                    <div>{{ $imobiliaria['endereco_linha'] }}</div>
                @endif
                @if(!empty($imobiliaria['telefone']) || !empty($imobiliaria['email']))
                    <div>
                        {{ $imobiliaria['telefone'] ?? '' }}@if(!empty($imobiliaria['telefone']) && !empty($imobiliaria['email'])) · @endif{{ $imobiliaria['email'] ?? '' }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<div class="spacer"></div>

<div class="box">
    <div class="row">
        <div class="col col-6">
            <div class="h2">Locatário</div>
            @if($locatario)
                <div><strong>{{ $locatario['nome'] }}</strong></div>
                @if(!empty($locatario['documento']))
                    <div class="muted small">{{ $locatario['documento'] }}</div>
                @endif
                @if(!empty($locatario['email']))
                    <div class="muted small">{{ $locatario['email'] }}</div>
                @endif
            @else
                <div class="muted">—</div>
            @endif
        </div>

        <div class="col col-6" style="text-align: right;">
            <div class="h2">Dados da cobrança</div>
            <div><strong>Competência:</strong> {{ $cobranca['competencia_formatada'] ?? $cobranca['competencia'] }}</div>
            <div><strong>Vencimento:</strong> {{ $dataVenc }}</div>
            <div class="muted small"><strong>Contrato:</strong> #{{ $contrato['id'] ?? '—' }} · <strong>Cobrança:</strong> #{{ $cobranca['id'] }}</div>
        </div>
    </div>
</div>

<div class="spacer"></div>

<div class="box">
    <div class="h2">Unidade / Imóvel</div>
    <div>
        <strong>{{ $imovel['titulo'] ?? '—' }}</strong>
        @if(!empty($unidade['identificador']))
            <span class="muted">· Unidade {{ $unidade['identificador'] }}</span>
        @endif
    </div>
    @php
        $endereco = null;
        if (!empty($imovel['rua']) || !empty($imovel['numero']) || !empty($imovel['bairro'])) {
            $endereco = trim(($imovel['rua'] ?? '').', '.($imovel['numero'] ?? '').' '.($imovel['bairro'] ?? ''));
        } elseif (!empty($imovel['endereco'])) {
            $endereco = $imovel['endereco'];
        }

        $cidadeUf = trim((string) ($imovel['cidade'] ?? ''));
        $uf = $imovel['uf'] ?? $imovel['estado'] ?? null;
        if ($uf) {
            $cidadeUf = $cidadeUf !== '' ? ($cidadeUf.' - '.$uf) : (string) $uf;
        }
        $cep = $imovel['postal_code'] ?? $imovel['cep'] ?? null;
    @endphp
    @if($endereco)
        <div class="muted small">{{ $endereco }}</div>
    @endif
    @if($cidadeUf)
        <div class="muted small">{{ $cidadeUf }}@if($cep) · CEP {{ $cep }}@endif</div>
    @endif
</div>

<div class="spacer"></div>

<div class="box">
    <div class="h2">Itens da cobrança</div>
    <table>
        <thead>
        <tr>
            <th>Item</th>
            <th>Descrição</th>
            <th class="num">Qtd.</th>
            <th class="num">Valor</th>
        </tr>
        </thead>
        <tbody>
        @forelse($itens as $item)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', (string) ($item['tipo'] ?? ''))) }}</td>
                <td class="muted">{{ $item['descricao'] ?? '—' }}</td>
                <td class="num">{{ $item['quantidade'] ?? '—' }}</td>
                <td class="num">{{ $fmt($item['valor_total'] ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="muted">Nenhum item encontrado para esta cobrança.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <table class="totais" style="margin-top: 10px;">
        <tr>
            <td style="width: 70%;"></td>
            <td class="num" style="width: 15%;">Subtotal</td>
            <td class="num" style="width: 15%;">{{ $fmt($cobranca['valor_subtotal_itens'] ?? 0) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="num">Multa</td>
            <td class="num">{{ $fmt($cobranca['valor_multa'] ?? 0) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="num">Juros</td>
            <td class="num">{{ $fmt($cobranca['valor_juros'] ?? 0) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="num">Total</td>
            <td class="num">{{ $fmt($cobranca['valor_total'] ?? 0) }}</td>
        </tr>
    </table>
</div>

<div class="spacer"></div>

<div class="box">
    <div class="h2">Observações / Instruções de pagamento</div>
    <div class="small">{{ $instrucoes_pagamento }}</div>
    @if(!empty($cobranca['observacoes']))
        <div class="spacer"></div>
        <div class="small muted">{{ $cobranca['observacoes'] }}</div>
    @endif
</div>

</body>
</html>
