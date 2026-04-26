<?php

namespace App\Filament\Resources;

use App\Filament\Client\Resources\EmpresaResource\Pages;
use App\Filament\Client\Resources\EmpresaResource\RelationManagers;
use App\Models\Empresa;
use App\Traits\HasDynamicPermissions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Leandrocfe\FilamentPtbrFormFields\Cep;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;

class EmpresaResource extends Resource
{
    use HasDynamicPermissions;
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $navigationLabel = 'Empresas';
    protected static ?string $model = Empresa::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    /**
     * Determina se o usuário pode acessar este recurso
     */
    public static function canAccess(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Apenas usuários com nível de acesso 0 podem gerenciar unidades
        return $user->nivel_acesso == 0;
    }

    public static function form(Form $form): Form
    {
        return $form
                ->schema([
                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                            'image/svg+xml',
                            'image/heic',
                            'image/heif',
                        ])
                        ->directory('imagens')
                        ->disk('public')
                        ->preserveFilenames()
                        ->nullable(),
                    TextInput::make('nome')
                        ->label('Razão Social')
                        ->required()
                        ->validationMessages([
                            'required' => 'Informe a razão social.',
                        ])
                        ->maxLength(255),
                    Document::make('cpfcnpj')
                        ->label('CPF/CNPJ')
                        ->required()
                        ->validationMessages([
                            'required' => 'Informe o CPF/CNPJ.',
                        ])
                        ->dynamic(),
                    TextInput::make('rgie')
                        ->label('RG/IE')
                        ->maxLength(255),
                    TextInput::make('fantasia')
                        ->label('Nome Fantasia')
                        ->maxLength(255),
                    textInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->validationMessages([
                            'required' => 'Informe o e-mail.',
                        ]),
                    PhoneNumber::make('telefone')
                        ->label('Telefone')
                        ->mask('(99) 9999-9999')
                        ->stripCharacters([',', '.', '-', '/', '(', ')', ' ']),
                    PhoneNumber::make('celular')
                        ->label('Celular/Whatsapp')
                        ->mask('(99) 99999-9999')
                        ->stripCharacters([',', '.', '-', '/', '(', ')', ' ']),
                    Cep::make('postal_code')->label('CEP')
                        ->viaCep(
                            mode: 'suffix', // Determines whether the action should be appended to (suffix) or prepended to (prefix) the cep field, or not included at all (none).
                            errorMessage: 'CEP inválido.', // Error message to display if the CEP is invalid.

                            /**
                             * Other form fields that can be filled by ViaCep.
                             * The key is the name of the Filament input, and the value is the ViaCep attribute that corresponds to it.
                             * More information: https://viacep.com.br/
                             */
                            setFields: [
                                'rua' => 'logradouro',
                                'numero' => 'numero',
                                'complemento' => 'complemento',
                                'bairro' => 'bairro',
                                'cidade' => 'localidade',
                                'uf' => 'uf'
                            ]
                        ),
                    TextInput::make('rua')
                        ,
                    TextInput::make('numero')
                        ,
                    TextInput::make('complemento')
                        ,
                    TextInput::make('bairro')
                        ,
                    TextInput::make('cidade')
                        ,
                    TextInput::make('uf')
                        ,
                    Select::make('status')
                    ->options([
                        '1' => 'Ativo',
                        '0' => 'Inativo',
                    ])
                    ->selectablePlaceholder(false)
                    ->default('1') ,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fantasia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cpfcnpj')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->searchable()
                    ->disabled(fn () => !self::hasActionPermission('Editar')),
                Tables\Columns\TextColumn::make('cadastro')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('modificado')
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
            'index' => \App\Filament\Resources\EmpresaResource\Pages\ListEmpresas::route('/'),
            'create' => \App\Filament\Resources\EmpresaResource\Pages\CreateEmpresa::route('/create'),
            'edit' => \App\Filament\Resources\EmpresaResource\Pages\EditEmpresa::route('/{record}/edit'),
        ];
    }
}
