<?php

namespace App\Filament\Resources;

use App\Filament\Client\Resources\RoleResource\Pages;
use App\Filament\Client\Resources\RoleResource\RelationManagers;
use App\Models\Permission;
use App\Traits\HasDynamicPermissions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    use HasDynamicPermissions;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Papel';
    protected static ?string $pluralModelLabel = 'Papéis';
    protected static ?string $navigationLabel = 'Papéis';

    public static function form(Form $form): Form
    {
        // Agrupar permissões pelo nome da entidade (tudo após o primeiro termo de ação)
        $permissionsGroups = Permission::all()
            ->map(function ($permission) {
                $name = trim((string) $permission->name);

                // Remove o primeiro token (geralmente o verbo: Criar, Editar, Visualizar, Deletar, etc.)
                $group = preg_replace('/^\S+\s+/', '', $name) ?? '';
                $group = trim($group);

                // Fallbacks caso o formato não seja o esperado
                if ($group === '') {
                    // Se não houver espaço no nome, usa o próprio nome como grupo
                    $group = $name !== '' ? $name : 'Geral';
                }

                return [
                    'group' => $group,
                    'permission' => $permission,
                ];
            })
            ->groupBy('group')
            ->sortKeys();

        $sections = [];
        foreach ($permissionsGroups as $group => $permissions) {
            $permissionOptions = $permissions->pluck('permission')
                ->mapWithKeys(fn ($permission) => [$permission->id => $permission->name])
                ->toArray();

            $sections[] = Section::make(Str::title($group))
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->options($permissionOptions)
                        ->columns(2)
                        ->bulkToggleable()
                        ->label(false)
                ])
                ->collapsible();
        }

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nome do Papel')
                    ->required()
                    ->validationMessages([
                        'required' => 'Informe o nome do papel.',
                    ])
                    ->maxLength(255),

                TextInput::make('guard_name')
                    ->label('Guard')
                    ->required()
                    ->validationMessages([
                        'required' => 'Informe o guard.',
                    ])
                    ->hidden()
                    ->maxLength(255)
                    ->default('web'),

                Section::make('Permissões')
                    ->schema($sections)
                    ->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('permissions_count') // Conta permissões relacionadas
                ->label('Nº Permissões')
                    ->counts('permissions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RoleResource\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Resources\RoleResource\Pages\CreateRole::route('/create'),
            'edit' => \App\Filament\Resources\RoleResource\Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
