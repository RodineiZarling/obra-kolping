<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasDynamicPermissions
{
    /**
     * Verifica se o usuário tem permissão específica para este resource
     *
     * @param string $action Ação como "Criar", "Editar", "Visualizar", "Deletar"
     * @return bool
     */
    public static function hasActionPermission(string $action): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Usuários com nivel_acesso = 0 não têm restrição de visualização/ações
        if (isset($user->nivel_acesso) && (int) $user->nivel_acesso === 0) {
            return true;
        }

        $modelName = static::getPermissionModelName();

        return $user->can("$action $modelName");
    }

    /**
     * Controla a exibição do item no menu de navegação do Filament.
     * Só registra o item se o usuário tiver permissão de "Visualizar {Recurso}".
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasActionPermission('Visualizar');
    }

    /**
     * Retorna o nome do modelo para usar em permissões
     *
     * @return string
     */
    public static function getPermissionModelName(): string
    {
        // Tenta obter o nome do modelo a partir do label do modelo, se definido
        if (static::$modelLabel) {
            return static::$modelLabel;
        }

        // Caso contrário, extrai do nome da classe model
        $modelClass = static::$model;
        $modelBaseName = class_basename($modelClass);

        // Você pode personalizar como o nome deve aparecer aqui
        // Por exemplo, para usar nomes plurais:
        return Str::of($modelBaseName)->studly()->toString();
    }
}
