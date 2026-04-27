<?php

namespace App\Filament\Resources;

use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\AcolhimentoResource\RelationManagers\DiariosRelationManager;
use App\Filament\Resources\AcolhimentoResource\RelationManagers\FamiliaresRelationManager;
use App\Filament\Resources\AcolhimentoResource\RelationManagers\ProcedimentosRelationManager;
use App\Models\Acolhimento;
use App\Models\Empresa;
use App\Models\PessoaAcolhida;
use App\Models\User;
use App\Traits\HasDynamicPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AcolhimentoResource extends Resource
{
    use HasDynamicPermissions;
    protected static ?string $model = Acolhimento::class;

    protected static ?string $navigationGroup = 'Acolhimentos';
    protected static ?string $navigationLabel = 'Acolhimentos';
    protected static ?string $modelLabel = 'Acolhimento';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Acolhimento')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Dados básicos')
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
                                Forms\Components\Select::make('pessoa_acolhida_id')
                                    ->label('Pessoa acolhida')
                                    ->required()
                                    ->options(function () {
                                        $user = auth()->user();
                                        if (! $user) {
                                            return [];
                                        }

                                        $nivel = (int) ($user->nivel_acesso ?? 2);
                                        $ativa = session('empresa_ativa_id');

                                        $query = PessoaAcolhida::query();

                                        if (in_array($nivel, [0, 1], true)) {
                                            if ($ativa !== null && $ativa !== '') {
                                                $query->where('empresa_id', (int) $ativa);
                                            }
                                        } else {
                                            $empresaIds = $user->empresas()->pluck('empresas.id')->all();
                                            if ($ativa !== null && $ativa !== '') {
                                                $ativaInt = (int) $ativa;
                                                if (in_array($ativaInt, $empresaIds, true)) {
                                                    $query->where('empresa_id', $ativaInt);
                                                } else {
                                                    // ativa inválida para o usuário nível 2: restringe ao conjunto permitido
                                                    $query->whereIn('empresa_id', $empresaIds);
                                                }
                                            } else {
                                                $query->whereIn('empresa_id', $empresaIds);
                                            }
                                        }

                                        return $query->orderBy('nome')->pluck('nome', 'id');
                                    })
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('responsavel_id')
                                    ->label('Responsável')
                                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                                    ->default(fn () => auth()->id())
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\DatePicker::make('data_acolhimento')
                                    ->label('Data do acolhimento')
                                    ->required(),
                                Forms\Components\TextInput::make('origem_encaminhamento')->label('Origem do encaminhamento'),
                                Forms\Components\TextInput::make('motivo_acolhimento')->label('Motivo do acolhimento'),
                                Forms\Components\Textarea::make('descricao_situacao')->label('Descrição da situação'),
                                Forms\Components\Select::make('status')->label('Status')->options([
                                    'em_andamento' => 'Em andamento',
                                    'encerrado' => 'Encerrado',
                                    'suspenso' => 'Suspenso',
                                ])->default('em_andamento')->native(false),
                                Forms\Components\Textarea::make('observacoes')->label('Observações')->rows(4),
                            ])->columns(2),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pessoa.nome')->label('Pessoa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('empresa.nome')->label('Unidade')->sortable(),
                Tables\Columns\TextColumn::make('responsavel.name')->label('Responsável')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('data_acolhimento')->label('Data')->date()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('Status')->colors([
                    'warning' => 'em_andamento',
                    'success' => 'encerrado',
                    'gray' => 'suspenso',
                ])->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FamiliaresRelationManager::class,
            ProcedimentosRelationManager::class,
            DiariosRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->empresaFilter();
    }

    public static function getPages(): array
    {
        return [
            'index' => AcolhimentoResource\Pages\ListAcolhimentos::route('/'),
            'create' => AcolhimentoResource\Pages\CreateAcolhimento::route('/create'),
            'edit' => AcolhimentoResource\Pages\EditAcolhimento::route('/{record}/edit'),
            'view' => AcolhimentoResource\Pages\ViewAcolhimento::route('/{record}'),
        ];
    }
}
