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
    // Tipos genéricos para contexto Kolping
    case DOCUMENTO_PESSOAL = 'documento_pessoal';
    case COMPROVANTE_RESIDENCIA = 'comprovante_residencia';
    case RELATORIO = 'relatorio';
    case TERMO = 'termo';
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
            self::DOCUMENTO_PESSOAL => 'Documento pessoal',
            self::COMPROVANTE_RESIDENCIA => 'Comprovante de residência',
            self::RELATORIO => 'Relatório',
            self::TERMO => 'Termo',
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
