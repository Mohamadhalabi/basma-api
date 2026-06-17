<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->font('Cairo')
            ->brandLogo(asset('images/logo.webp'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('images/favicon.ico'))
            ->globalSearch(false)
            ->darkMode(false)
            ->colors([
               'primary' => Color::hex('#0F7A3D'),
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>
                    .fi-sidebar {
                        box-shadow: 2px 0 12px rgba(0,0,0,0.06);
                        border-left: 1px solid rgba(0,0,0,0.05);
                    }
                    .fi-sidebar-item-active .fi-sidebar-item-button {
                        background: rgba(15,122,61,0.08);
                        border-radius: 8px;
                    }
                    .fi-sidebar-item-button {
                        border-radius: 8px;
                        transition: background 0.15s ease;
                    }
                    .fi-sidebar-item-button:hover {
                        background: rgba(15,122,61,0.05);
                    }
                    .fi-topbar {
                        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
                    }
                    .fi-wi-stats-overview-stat, .fi-section {
                        box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
                    }
                </style>'
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}