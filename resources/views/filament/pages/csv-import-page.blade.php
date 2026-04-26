<x-filament-panels::page>
    {{ $this->form }}

    {{-- As ações agora são renderizadas automaticamente pelo Filament via getHeaderActions() --}}

    @if (!empty($this->lastSummary))
        <div class="mt-6 space-y-4">
            <x-filament::section>
                <x-slot name="heading">Resumo</x-slot>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                    <div><strong>Total linhas:</strong> {{ $this->lastSummary['total_linhas'] ?? 0 }}</div>
                    <div><strong>Criados:</strong> {{ $this->lastSummary['total_criados'] ?? 0 }}</div>
                    <div><strong>Atualizados:</strong> {{ $this->lastSummary['total_atualizados'] ?? 0 }}</div>
                    <div><strong>Erros:</strong> {{ $this->lastSummary['total_erros'] ?? 0 }}</div>
                </div>
            </x-filament::section>

            @if (!empty($this->lastRows))
                <x-filament::section>
                    <x-slot name="heading">Prévia (até 50 linhas)</x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left">
                                <th class="py-2 pr-4">Linha</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Ação</th>
                                <th class="py-2 pr-4">Mensagem</th>
                                <th class="py-2 pr-4">Erros</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($this->lastRows as $row)
                                <tr class="border-t">
                                    <td class="py-2 pr-4">{{ $row['linha'] }}</td>
                                    <td class="py-2 pr-4">{{ $row['status'] }}</td>
                                    <td class="py-2 pr-4">{{ $row['acao'] }}</td>
                                    <td class="py-2 pr-4">{{ $row['mensagem'] }}</td>
                                    <td class="py-2 pr-4">
                                        @if (!empty($row['erros']))
                                            <ul class="list-disc pl-4">
                                                @foreach ($row['erros'] as $err)
                                                    <li>{{ $err }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
