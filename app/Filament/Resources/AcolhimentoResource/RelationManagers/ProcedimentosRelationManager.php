<?php

namespace App\Filament\Resources\AcolhimentoResource\RelationManagers;

use App\Models\User;
use App\Models\PessoaAcolhida;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProcedimentosRelationManager extends RelationManager
{
    protected static string $relationship = 'procedimentos';

    protected static ?string $title = 'Procedimentos';

    public function isReadOnly(): bool
    {
        // Exibe botões também na página de Visualização do Acolhimento
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('data')->label('Data'),
                Forms\Components\Select::make('tipo')->label('Tipo')->options([
                    'atendimento' => 'Atendimento',
                    'encaminhamento' => 'Encaminhamento',
                    'visita' => 'Visita',
                    'acompanhamento' => 'Acompanhamento',
                    'outro' => 'Outro',
                ])->native(false),
                Forms\Components\TextInput::make('descricao')->label('Descrição')->required(),
                Forms\Components\Select::make('responsavel_id')
                    ->label('Responsável')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('destinatarios')
                    ->label('Destinado a')
                    ->multiple()
                    ->options(function () {
                        $owner = $this->getOwnerRecord();
                        if (! $owner) return [];
                        $options = [];
                        // Pessoa acolhida principal
                        if ($owner->pessoa) {
                            $options['P:' . $owner->pessoa->id] = 'Pessoa: ' . $owner->pessoa->nome;
                        }
                        // Familiares do acolhimento
                        foreach ($owner->familiares as $fam) {
                            $label = trim(($fam->nome ?? '') . ' ' . (($fam->parentesco ?? '') ? '(' . $fam->parentesco . ')' : ''));
                            $options['F:' . $fam->id] = 'Familiar: ' . ($label ?: ('#' . $fam->id));
                        }
                        return $options;
                    })
                    ->afterStateHydrated(function (\Filament\Forms\Components\Select $component, $state, $record) {
                        if (! $record) {
                            return;
                        }
                        $vals = [];
                        foreach ($record->destinatariosPessoas as $p) {
                            $vals[] = 'P:' . $p->id;
                        }
                        foreach ($record->destinatariosFamiliares as $f) {
                            $vals[] = 'F:' . $f->id;
                        }
                        $component->state($vals);
                    })
                    ->helperText('Selecione a(s) pessoa(s) envolvida(s): a pessoa acolhida e/ou familiares.'),
                Forms\Components\Textarea::make('observacoes')->label('Observações'),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()->sortable(),
                Tables\Columns\TextColumn::make('descricao')->label('Descrição')->wrap(),
                Tables\Columns\TextColumn::make('responsavel.name')->label('Responsável')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Adicionar procedimento')
                    ->visible(fn () => \App\Filament\Resources\AcolhimentoResource::hasActionPermission('Editar'))
                    ->after(function ($record, array $data) {
                        $this->syncDestinatarios($record, $data['destinatarios'] ?? []);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => \App\Filament\Resources\AcolhimentoResource::hasActionPermission('Editar'))
                    ->after(function ($record, array $data) {
                        $this->syncDestinatarios($record, $data['destinatarios'] ?? []);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => \App\Filament\Resources\AcolhimentoResource::hasActionPermission('Deletar')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => \App\Filament\Resources\AcolhimentoResource::hasActionPermission('Deletar')),
                ]),
            ]);
    }

    private function syncDestinatarios($record, array $values): void
    {
        $pessoaIds = [];
        $famIds = [];
        foreach ($values as $val) {
            if (str_starts_with($val, 'P:')) {
                $pessoaIds[] = (int) substr($val, 2);
            } elseif (str_starts_with($val, 'F:')) {
                $famIds[] = (int) substr($val, 2);
            }
        }

        // Validação básica: garanta que ids pertençam ao mesmo acolhimento do owner
        $owner = $this->getOwnerRecord();
        if ($owner) {
            $validFamIds = $owner->familiares()->whereIn('id', $famIds)->pluck('id')->all();
            $famIds = $validFamIds;
            $pessoaIdEsperado = $owner->pessoa?->id;
            if ($pessoaIdEsperado !== null) {
                $pessoaIds = array_values(array_intersect($pessoaIds, [$pessoaIdEsperado]));
            } else {
                $pessoaIds = [];
            }
        }

        $record->destinatariosPessoas()->sync($pessoaIds);
        $record->destinatariosFamiliares()->sync($famIds);
    }
}
