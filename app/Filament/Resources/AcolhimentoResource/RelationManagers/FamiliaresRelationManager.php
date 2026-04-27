<?php

namespace App\Filament\Resources\AcolhimentoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FamiliaresRelationManager extends RelationManager
{
    protected static string $relationship = 'familiares';

    protected static ?string $title = 'Composição familiar';

    public function isReadOnly(): bool
    {
        // Exibe botões também na página de Visualização do Acolhimento
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')->label('Nome')->required(),
                Forms\Components\TextInput::make('parentesco')->label('Parentesco'),
                Forms\Components\TextInput::make('idade')->label('Idade')->numeric(),
                Forms\Components\TextInput::make('telefone')->label('Telefone'),
                Forms\Components\TextInput::make('ocupacao')->label('Ocupação'),
                Forms\Components\Textarea::make('observacoes')->label('Observações'),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parentesco')->label('Parentesco')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('idade')->label('Idade')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('telefone')->label('Telefone')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ocupacao')->label('Ocupação')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Adicionar familiar')
                    ->visible(fn () => \App\Filament\Resources\AcolhimentoResource::hasActionPermission('Editar')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => \App\Filament\Resources\AcolhimentoResource::hasActionPermission('Editar')),
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
}
