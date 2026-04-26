<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; }
        .row-head { width: 100%; }
        .col { display: inline-block; vertical-align: top; }
        .col-6 { width: 49%; }
        .header { margin-bottom: 18px; border-bottom: 1px solid #d1d5db; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: 700; margin: 0; }
        .muted { color: #6b7280; }
        .small { font-size: 11px; }
        .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; margin-bottom: 10px; }
        .row { margin-bottom: 4px; }
        .label { font-weight: 700; }
    </style>
</head>
<body>
@php
    $locatario = $cobranca->locatario?->nome ?? $cobranca->contrato?->locatario?->nome ?? '—';
    $unidade = $cobranca->unidade?->identificador ?? '—';
    $forma = $formaPagamentoNome
        ?? $cobranca->contasReceber?->formaPagamento?->nome
        ?? '—';
    $valorPago = (float) ($dadosPagamento['valor_pago'] ?? $cobranca->valor_total ?? 0);
    $dataPagamento = $dadosPagamento['data_pagamento'] ?? null;
@endphp

<div class="header">
    <div class="row-head">
        <div class="col col-6">
            <p class="title">Recibo de Pagamento de Cobrança de Locação</p>
            <p class="muted small" style="margin: 2px 0 0 0;">Gerado em {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="col col-6" style="text-align: right;">
            @php
                $empresaNome = null;
                $empresaCreci = null;
                if ($empresa) {
                    $empresaNome = $empresa->fantasia ?: $empresa->nome;
                    $empresaCreci = !empty($empresa->creci) ? trim((string) $empresa->creci) : null;
                }
            @endphp

            @if($empresaNome)
                <div style="font-weight: 700; font-size: 16px;">
                    {{ $empresaNome }}@if($empresaCreci) · CRECI {{ $empresaCreci }}@endif
                </div>
            @else
                <div style="font-weight: 700; font-size: 16px;">Imobiliária</div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="row"><span class="label">Cobrança:</span> #{{ $cobranca->id }}</div>
    <div class="row"><span class="label">Contrato:</span> #{{ $cobranca->contrato_locacao_id }}</div>
    <div class="row"><span class="label">Locatário:</span> {{ $locatario }}</div>
    <div class="row"><span class="label">Unidade:</span> {{ $unidade }}</div>
    <div class="row"><span class="label">Competência:</span> {{ $cobranca->competencia_formatada ?? \App\Models\LocacaoCobranca::competenciaDbToBr($cobranca->competencia) }}</div>
</div>

<div class="card">
    <div class="row"><span class="label">Data do pagamento:</span> {{ $dataPagamento ? \Carbon\Carbon::parse($dataPagamento)->format('d/m/Y') : '—' }}</div>
    <div class="row"><span class="label">Forma de pagamento:</span> {{ $forma }}</div>
    <div class="row"><span class="label">Valor pago:</span> R$ {{ number_format($valorPago, 2, ',', '.') }}</div>
    <div class="row"><span class="label">Observação:</span> {{ (string) ($dadosPagamento['observacao'] ?? '') !== '' ? $dadosPagamento['observacao'] : '—' }}</div>
</div>
</body>
</html>
