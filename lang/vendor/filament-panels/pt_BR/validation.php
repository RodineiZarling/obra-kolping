<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mensagens padrão de validação
    |--------------------------------------------------------------------------
    */

    'accepted' => 'O campo :attribute deve ser aceito.',
    'active_url' => 'O campo :attribute não é uma URL válida.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'alpha' => 'O campo :attribute deve conter apenas letras.',
    'alpha_num' => 'O campo :attribute deve conter apenas letras e números.',
    'array' => 'O campo :attribute deve ser um array.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'date' => 'O campo :attribute não é uma data válida.',
    'date_format' => 'O campo :attribute não corresponde ao formato :format.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'email' => 'Informe um e-mail válido.',
    'exists' => 'O valor selecionado para :attribute é inválido.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'filled' => 'O campo :attribute deve ser preenchido.',
    'gt' => 'O campo :attribute deve ser maior que :value.',
    'gte' => 'O campo :attribute deve ser maior ou igual a :value.',
    'image' => 'O arquivo deve ser uma imagem válida.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'ip' => 'O campo :attribute deve ser um IP válido.',
    'json' => 'O campo :attribute deve ser um JSON válido.',
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'file' => 'O arquivo não pode ser maior que :max KB.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'mimes' => 'O arquivo deve ser do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'file' => 'O arquivo deve ter no mínimo :min KB.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'not_in' => 'O valor selecionado para :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'required' => 'Informe :attribute.',
    'required_if' => 'Informe :attribute.',
    'same' => 'Os campos :attribute e :other devem ser iguais.',
    'size' => [
        'numeric' => 'O campo :attribute deve ser :size.',
        'file' => 'O arquivo deve ter :size KB.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',
    'url' => 'Informe uma URL válida.',

    /*
    |--------------------------------------------------------------------------
    | Nomes amigáveis dos campos
    |--------------------------------------------------------------------------
    */

    'attributes' => [

        // Contrato
        'data_rescisao' => 'a data de rescisão',
        'motivo_rescisao' => 'o motivo da rescisão',
        'valor_aluguel' => 'o valor do aluguel',

        // Cobrança
        'competencia' => 'a competência',
        'vencimento' => 'o vencimento',
        'valor_total' => 'o valor total',

        // Pagamentos
        'forma_pagamento' => 'a forma de pagamento',
        'valor_liquido_final' => 'o valor final',
        'valor' => 'o valor',

        // Locação
        'locatario_id' => 'o locatário',
        'unidade_imovel_id' => 'a unidade',
        'contrato_locacao_id' => 'o contrato',

        // Repasse
        'valor_liquido_repassar' => 'o valor do repasse',
        'motivo_ajuste_manual' => 'o motivo do ajuste',

        // Documentos
        'arquivo' => 'o arquivo',

    ],

];
