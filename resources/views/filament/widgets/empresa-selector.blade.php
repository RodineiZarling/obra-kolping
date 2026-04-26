<x-filament::section>
    <div class="flex items-center gap-3">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="empresaAtiva">
                <option value="">Todas as unidades</option>
                @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
</x-filament::section>
