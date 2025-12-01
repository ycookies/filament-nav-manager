<?php

namespace Ycookies\FilamentNavManager\Resources;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Ycookies\FilamentNavManager\Models\NavManager;
use Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages\CreateNavManager;
use Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages\EditNavManager;
use Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages\ListNavManagers;
use Ycookies\FilamentNavManager\Resources\NavManagerResource\Schemas\NavManagerForm;
use Ycookies\FilamentNavManager\Resources\NavManagerResource\Tables\NavManagersTable;


class NavManagerResource extends Resource
{
    protected static ?string $model = NavManager::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static string | UnitEnum | null $navigationGroup = null;

    protected static ?int $navigationSort = null;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('nav-manager::nav-manager.nav.label') ?? 'Navigation Manager';
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? __('nav-manager::nav-manager.resource.singular_label') ?? 'Navigation Item';
    }

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? __('nav-manager::nav-manager.resource.plural_label') ?? 'Navigation Items';
    }

    public static function getNavigationGroup(): ?string
    {
        // First check if static property is set
        if (static::$navigationGroup !== null) {
            return static::$navigationGroup;
        }

        // Then check config file
        $configGroup = config('nav-manager.navigation_group');
        if ($configGroup !== null) {
            return $configGroup;
        }

        // Finally fall back to translation or default
        return __('nav-manager::nav-manager.nav.group') ?? 'System';
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return static::$navigationIcon ?? Heroicon::OutlinedBars3;
    }

    /**
     * Check if the current user can access this resource.
     * This controls actual access permissions based on allowed_roles config.
     */
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    /**
     * Check if the current user can view any records.
     * Based on allowed_roles config.
     */
    public static function canViewAny(): bool
    {
        // Check if user is authenticated
        if (!Filament::auth()?->check()) {
            return false;
        }

        $allowedRoles = config('nav-manager.allowed_roles');

        // If no roles restriction, allow all authenticated users
        if (empty($allowedRoles) || $allowedRoles === null) {
            return true;
        }

        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        // Check if user has any of the allowed roles
        // Support both Spatie Permission package and simple role checking
        if (method_exists($user, 'hasAnyRole')) {
            // @phpstan-ignore-next-line
            return $user->hasAnyRole($allowedRoles);
        }

        if (method_exists($user, 'hasRole')) {
            foreach ((array) $allowedRoles as $role) {
                // @phpstan-ignore-next-line
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        // Check role attribute if exists
        if (property_exists($user, 'role') || method_exists($user, 'getRole')) {
            // @phpstan-ignore-next-line
            $userRole = method_exists($user, 'getRole') ? $user->getRole() : $user->role;
            if (in_array($userRole, (array) $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return NavManagerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NavManagersTable::configure($table);
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
            'index'  => ListNavManagers::route('/'),
            'create' => CreateNavManager::route('/create'),
            'edit'   => EditNavManager::route('/{record}/edit'),
        ];
    }
}

