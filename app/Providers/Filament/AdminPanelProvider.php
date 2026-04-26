<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\EmpresaSelectorWidget;
use App\Filament\Widgets\FullWidthAccountWidget;
use Filament\Navigation\MenuItem;
use Illuminate\Session\Middleware\StartSession;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->userMenuItems([
                // Abre diretamente a página de edição do próprio usuário (UserResource) sem passar pela tabela
                'edita-profile' => MenuItem::make()
                    ->label('Editar perfil')
                    ->url(fn (): string => UserResource::getUrl('edit', ['record' => auth()->id()]))
                    ->icon('heroicon-o-user'),
            ])
            ->navigationGroups([
                'Financeiro',
                'Operações',
                'Cadastros',
                'Configurações',
            ])
            ->colors([
                'primary' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            //->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                FullWidthAccountWidget::class,
            ])
            // Renderiza o seletor de empresa imediatamente antes do menu/ícone do usuário
            ->renderHook('panels::user-menu.before', fn () => view('filament.partials.empresa-topbar-selector'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                AuthenticateSession::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandName(function() {
                return config('app.name', 'Kolping');
            });
    }
}
