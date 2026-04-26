<?php

return [
    // Percentual padrão (técnico) de honorários para cálculo do repasse.
    // Pode ser sobrescrito via env, e pode ser nulo para manter contratos antigos sem preenchimento.
    'honorarios_percentual' => env('LOCACAO_HONORARIOS_PERCENTUAL', '0.00'),

    // Configurações opcionais para geração de fatura PDF (somente exibição; não altera regras).
    'fatura' => [
        'imobiliaria_nome' => env('LOCACAO_FATURA_IMOBILIARIA_NOME', env('APP_NAME')),
        'imobiliaria_documento' => env('LOCACAO_FATURA_IMOBILIARIA_DOCUMENTO'),
        'imobiliaria_email' => env('LOCACAO_FATURA_IMOBILIARIA_EMAIL'),
        'imobiliaria_telefone' => env('LOCACAO_FATURA_IMOBILIARIA_TELEFONE'),
        'imobiliaria_endereco_linha' => env('LOCACAO_FATURA_IMOBILIARIA_ENDERECO_LINHA'),
        'instrucoes_pagamento' => env('LOCACAO_FATURA_INSTRUCOES_PAGAMENTO'),
    ],
];
