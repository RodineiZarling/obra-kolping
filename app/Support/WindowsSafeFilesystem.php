<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Mitiga falhas intermitentes de "Access denied (code: 5)" no Windows durante
 * operações atômicas de escrita (rename), comuns na compilação de views Blade.
 *
 * Mantém comportamento padrão em outros SOs.
 */
class WindowsSafeFilesystem extends Filesystem
{
    /**
     * @param  string  $path
     * @param  string  $content
     * @param  int|null  $mode
     */
    public function replace($path, $content, $mode = null): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            parent::replace($path, $content, $mode);
            return;
        }

        $attempts = 5;
        $delayMs = 40;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                parent::replace($path, $content, $mode);
                return;
            } catch (\Throwable $e) {
                // Em Windows é comum o arquivo/diretório ser bloqueado momentaneamente
                // por antivírus/indexadores/concorrência; tentamos novamente.
                if ($i < $attempts - 1) {
                    usleep($delayMs * 1000);
                    $delayMs *= 2;
                    continue;
                }

                // Último recurso: tenta escrever direto no destino (sem rename atômico).
                // Isso não altera o conteúdo; apenas evita o rename que falhou.
                $this->put($path, $content);
                if (is_int($mode)) {
                    @chmod($path, $mode);
                }
                return;
            }
        }
    }
}
