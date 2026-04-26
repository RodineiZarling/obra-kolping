<?php

namespace App\Enums;

enum DocumentType: string
{
    case IDENTIDADE = 'identidade';
    case CPF = 'cpf';
    case COMPROVANTE_RENDA = 'comprovante_renda';
    case COMPROVANTE_ENDERECO = 'comprovante_endereco';
    case CONTRATO = 'contrato';
    case APOLICE_SEGURO = 'apolice_seguro';
    case OUTRO = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIDADE => 'Documento de identidade',
            self::CPF => 'CPF',
            self::COMPROVANTE_RENDA => 'Comprovante de renda',
            self::COMPROVANTE_ENDERECO => 'Comprovante de endereço',
            self::CONTRATO => 'Contrato',
            self::APOLICE_SEGURO => 'Apólice de Seguro',
            self::OUTRO => 'Outro',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
