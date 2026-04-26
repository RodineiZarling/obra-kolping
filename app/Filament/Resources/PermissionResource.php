<?php

namespace App\Filament\Resources;

use App\Filament\Client\Resources\PermissionResource\Pages;
use App\Filament\Client\Resources\PermissionResource\RelationManagers;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Permissão';
    protected static ?string $pluralModelLabel = 'Permissões';
    protected static ?string $navigationLabel = 'Permissões';

    /**
     * Determina se o usuário pode acessar este recurso
     */
    public static function canAccess(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Apenas usuários com nível de acesso 0 podem gerenciar papéis/permissões
        return (int) $user->nivel_acesso === 0;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('generate_multiple')
                    ->label('Gerar múltiplas permissões')
                    ->helperText('Ative para criar as 4 permissões padrão de uma vez')
                    ->default(false)
                    ->reactive(),

                Section::make('Permissão Individual')
                    ->description('Crie uma permissão única')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da Permissão')
                            ->required(fn ($get) => !$get('generate_multiple'))
                            ->maxLength(255),

                        Hidden::make('guard_name')
                            ->default('web'),
                    ])
                    ->visible(fn ($get) => !$get('generate_multiple')),

                Section::make('Gerador de Permissões')
                    ->description('Gere várias permissões usando um padrão')
                    ->schema([
                        TextInput::make('resource_name')
                            ->label('Nome do Recurso')
                            ->placeholder('Ex: Grupos, Usuários, Produtos')
                            ->helperText('Informe apenas o nome do recurso. Serão geradas permissões com os padrões Criar, Editar, Visualizar e Deletar.')
                            ->required(fn ($get) => $get('generate_multiple')),

                        Grid::make(2)
                            ->schema([
                                Checkbox::make('create_permission')
                                    ->label('Criar [Recurso]')
                                    ->default(true),

                                Checkbox::make('view_permission')
                                    ->label('Visualizar [Recurso]')
                                    ->default(true),

                                Checkbox::make('edit_permission')
                                    ->label('Editar [Recurso]')
                                    ->default(true),

                                Checkbox::make('delete_permission')
                                    ->label('Deletar [Recurso]')
                                    ->default(true),
                            ]),
                    ])
                    ->visible(fn ($get) => $get('generate_multiple')),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime(),
            ])
            ->filters([
                // Adicione filtros, se necessário
            ])

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->visible(fn () => auth()->check() && auth()->user()->nivel_acesso == 0),
                    Tables\Actions\EditAction::make()
                        ->visible(fn () => auth()->check() && auth()->user()->nivel_acesso == 0),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn () => auth()->check() && auth()->user()->nivel_acesso == 0),
                ])
                    ->label('Ações')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->check() && auth()->user()->nivel_acesso == 0),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\PermissionResource\Pages\ListPermissions::route('/'),
            'create' => \App\Filament\Resources\PermissionResource\Pages\CreatePermission::route('/create'),
            'edit' => \App\Filament\Resources\PermissionResource\Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
