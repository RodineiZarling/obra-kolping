<?php

namespace App\Filament\Resources;

use App\Models\Empresa;
use App\Models\PessoaAcolhida;
use Leandrocfe\FilamentPtbrFormFields\Cep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PessoaAcolhidaResource extends Resource
{
    protected static ?string $model = PessoaAcolhida::class;

    protected static ?string $navigationGroup = 'Acolhimentos';
    protected static ?string $navigationLabel = 'Pessoas Acolhidas';
    protected static ?string $modelLabel = 'Pessoa Acolhida';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('nome')->label('Nome')->required()->maxLength(255),
                        Forms\Components\TextInput::make('cpf')->label('CPF')->maxLength(20),
                        Forms\Components\TextInput::make('rg')->label('RG')->maxLength(30),
                        Forms\Components\DatePicker::make('data_nascimento')->label('Data de nascimento'),
                        Forms\Components\Select::make('sexo')->label('Sexo')->options([
                            'masculino' => 'Masculino',
                            'feminino' => 'Feminino',
                            'outro' => 'Outro',
                        ])->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Contato')
                    ->schema([
                        Forms\Components\TextInput::make('telefone')->label('Telefone')->maxLength(50),
                        Forms\Components\TextInput::make('email')->label('E-mail')->email(),
                    ])->columns(2),

                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Cep::make('cep')->label('CEP')
                            ->viaCep(
                                mode: 'suffix',
                                errorMessage: 'CEP inválido.',
                                setFields: [
                                    'endereco' => 'logradouro',
                                    'numero' => 'numero',
                                    'complemento' => 'complemento',
                                    'bairro' => 'bairro',
                                    'cidade' => 'localidade',
                                    'uf' => 'uf',
                                ]
                            ),
                        Forms\Components\TextInput::make('endereco')->label('Endereço'),
                        Forms\Components\TextInput::make('numero')->label('Número')->maxLength(30),
                        Forms\Components\TextInput::make('complemento')->label('Complemento'),
                        Forms\Components\TextInput::make('bairro')->label('Bairro'),
                        Forms\Components\TextInput::make('cidade')->label('Cidade'),
                        Forms\Components\TextInput::make('uf')->label('UF')->maxLength(2),
                    ])->columns(3),

                Forms\Components\Section::make('Informações gerais')
                    ->schema([
                        Forms\Components\Select::make('empresa_id')
                            ->label('Unidade')
                            ->required()
                            ->options(function () {
                                $user = auth()->user();
                                if (! $user) return [];
                                $nivel = (int) ($user->nivel_acesso ?? 2);
                                $query = in_array($nivel, [0,1], true)
                                    ? Empresa::query()
                                    : $user->empresas()->getQuery();
                                return $query->orderBy('id')->pluck('nome', 'id');
                            })
                            ->default(fn () => session('empresa_ativa_id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('situacao')
                            ->label('Situação')
                            ->options([
                                'ativo' => 'Ativo',
                                'inativo' => 'Inativo',
                                'encerrado' => 'Encerrado',
                            ])->default('ativo')->native(false),
                        Forms\Components\Textarea::make('observacoes')->label('Observações')->rows(4),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cpf')->label('CPF')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('telefone')->label('Telefone')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cidade')->label('Cidade/UF')->formatStateUsing(fn ($record) => trim(($record->cidade ?? '') . '/' . ($record->uf ?? ''), '/')),
                Tables\Columns\TextColumn::make('empresa.nome')->label('Empresa')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('situacao')->label('Situação')->colors([
                    'success' => 'ativo',
                    'warning' => 'inativo',
                    'gray' => 'encerrado',
                ])->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->empresaFilter();
    }

    public static function getPages(): array
    {
        return [
            'index' => PessoaAcolhidaResource\Pages\ListPessoaAcolhidas::route('/'),
            'create' => PessoaAcolhidaResource\Pages\CreatePessoaAcolhida::route('/create'),
            'edit' => PessoaAcolhidaResource\Pages\EditPessoaAcolhida::route('/{record}/edit'),
            'view' => PessoaAcolhidaResource\Pages\ViewPessoaAcolhida::route('/{record}'),
        ];
    }
}
