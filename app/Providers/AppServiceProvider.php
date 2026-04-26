<?php

namespace App\Providers;

use App\Support\WindowsSafeFilesystem;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Mitiga falhas intermitentes de rename() no Windows ao compilar Blade.
        // Widgets/Services não devem depender disso; é infra.
        $this->app->singleton('files', function () {
            return new WindowsSafeFilesystem();
        });

        $this->app->singleton(Filesystem::class, function () {
            return new WindowsSafeFilesystem();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $compiledViewsPath = config('view.compiled');
        if (is_string($compiledViewsPath) && $compiledViewsPath !== '') {
            if (! is_dir($compiledViewsPath)) {
                @mkdir($compiledViewsPath, 0755, true);
            }
        }

        $logPath = storage_path('logs/laravel.log');
        if (is_file($logPath)) {
            $handle = @fopen($logPath, 'rb');
            if ($handle) {
                $bom = fread($handle, 3) ?: '';
                fclose($handle);

                $skip = 0;
                if (str_starts_with($bom, "\xEF\xBB\xBF")) {
                    $skip = 3;
                } elseif (str_starts_with($bom, "\xFF\xFE") || str_starts_with($bom, "\xFE\xFF")) {
                    $skip = 2;
                }

                if ($skip > 0) {
                    $source = fopen($logPath, 'rb');
                    $tempPath = $logPath . '.tmp';
                    $dest = fopen($tempPath, 'wb');
                    if ($source && $dest) {
                        fseek($source, $skip);
                        stream_copy_to_stream($source, $dest);
                    }
                    if ($source) {
                        fclose($source);
                    }
                    if ($dest) {
                        fclose($dest);
                    }
                    if (is_file($tempPath)) {
                        @rename($tempPath, $logPath);
                    }
                }
            }
        }
    }
}
