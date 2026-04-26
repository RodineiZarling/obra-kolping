<?php

namespace App\Filament\Resources;

use App\Filament\Client\Resources\UserResource\Pages;
use App\Filament\Client\Resources\UserResource\RelationManagers;
use App\Models\Empresa;
use App\Models\User;
use App\Traits\HasDynamicPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    use HasDynamicPermissions;
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $navigationLabel = 'Usuários';
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->validationMessages([
                        'required' => 'Informe o nome.',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->validationMessages([
                        'required' => 'Informe o e-mail.',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\UserResource\Pages\CreateUser)
                    ->validationMessages([
                        'required' => 'Informe a senha.',
                    ]),

                // Campos adicionais do model User::$fillable
                Forms\Components\TextInput::make('cpfcnpj')
                    ->label('CPF/CNPJ')
                    ->maxLength(255),
                // Unidades de acesso (para nível 2)
                Forms\Components\Select::make('empresas')
                    ->label('Unidades de acesso')
                    ->multiple()
                    ->relationship('empresas', 'nome')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => (int) ($get('nivel_acesso') ?? 2) === 2)
                    ->helperText('Selecione as unidades às quais o usuário terá acesso.'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        '1' => 'Ativo',
                        '0' => 'Inativo',
                    ])
                    ->selectablePlaceholder(false)
                    ->disabled(fn () => ! (auth()->user() && ((auth()->user()->nivel_acesso ?? 99) <= 1 || self::hasActionPermission('Editar'))))
                    ->dehydrated(fn () => auth()->user() && ((auth()->user()->nivel_acesso ?? 99) <= 1 || self::hasActionPermission('Editar'))),

                Forms\Components\Select::make('role')
                    ->label('Papel (Role)')
                    ->relationship('roles', 'name') // Relaciona o User com as Roles
                    ->placeholder('Selecione um papel')
                    ->options(Role::where('name', '!=', 'admin')->pluck('name', 'id')) // ID como key, nome como label
                    ->required()
                    ->validationMessages([
                        'required' => 'Selecione o papel (role).',
                    ])
                    ->searchable()
                    ->disabled(fn () => ! (auth()->user() && ((auth()->user()->nivel_acesso ?? 99) <= 1 || self::hasActionPermission('Editar'))))
                    ->dehydrated(fn () => auth()->user() && ((auth()->user()->nivel_acesso ?? 99) <= 1 || self::hasActionPermission('Editar'))),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cpfcnpj')
                    ->label('CPF/CNPJ')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo_caixa')
                    ->label('Código Caixa')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('empresa')
                    ->label('Empresa')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => (string)$state === '1' ? 'Ativo' : 'Inativo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Papel')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('E-mail verificado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->visible(fn () => self::hasActionPermission('Visualizar')),
                    Tables\Actions\EditAction::make()
                        ->visible(fn () => self::hasActionPermission('Editar')),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn () => self::hasActionPermission('Deletar')),
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
                        ->visible(fn () => self::hasActionPermission('Deletar')),
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
            'index' => \App\Filament\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Resources\UserResource\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
