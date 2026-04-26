<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;

trait HasRelatorioListaPdf
{
    /**
     * @return array<int, array{label: string, value: string}>
     */
    protected function buildFiltrosAplicadosParaPdf(): array
    {
        $out = [];

        if (method_exists($this, 'getTableSearch')) {
            $search = $this->getTableSearch();
            if (filled($search)) {
                $out[] = ['label' => 'Busca', 'value' => (string) $search];
            }
        }

        $filters = $this->tableFilters ?? [];
        foreach ($filters as $name => $state) {
            if ($this->isEmptyFilterState($state)) {
                continue;
            }

            $label = (string) Str::of((string) $name)->replace('_', ' ')->title();
            $out[] = ['label' => $label, 'value' => $this->formatFilterState($state)];
        }

        return $out;
    }

    private function isEmptyFilterState($state): bool
    {
        if ($state === null) {
            return true;
        }

        if (is_string($state)) {
            return trim($state) === '';
        }

        if (is_array($state)) {
            foreach ($state as $v) {
                if (! $this->isEmptyFilterState($v)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function formatFilterState($state): string
    {
        if (is_bool($state)) {
            return $state ? 'Sim' : 'Não';
        }

        if (is_array($state)) {
            $parts = [];
            foreach ($state as $k => $v) {
                if ($this->isEmptyFilterState($v)) {
                    continue;
                }

                if (is_int($k)) {
                    $parts[] = $this->formatFilterState($v);

                    continue;
                }

                $kLabel = (string) Str::of((string) $k)->replace('_', ' ')->lower();
                $parts[] = $kLabel.': '.$this->formatFilterState($v);
            }

            return implode(' | ', $parts);
        }

        return (string) $state;
    }

    protected function formatMoneyBr($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $n = (float) str_replace(',', '.', (string) $value);

        return 'R$ '.number_format($n, 2, ',', '.');
    }
}
