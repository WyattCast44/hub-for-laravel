<?php

require_once 'vendor/autoload.php';

compose('Laravel Hub')
    ->in('.') // basically allow the user to specify the path to the project
    ->description('Laravel Hub CLI')
    ->extend('laravel/livewire-volt') // should we extend starter kits, or recipes, or both?
    ->env([
        'APP_NAME' => 'Laravel Hub',
        'APP_MY_KEY' => 'true',
    ])
    ->steps([
        step('Initialize Git repository')
            ->git([
                'init',
                'add .',
                'commit -m "Initial commit"',
            ]),
        step('Install Laravel Fortify')
            ->composer([
                'require' => [
                    'laravel/fortify:^1.0',
                ],
            ])
            ->npm([
                'install' => [
                    'tailwindcss',
                    'alpinejs',
                ],
                'dev' => [
                    'vitest',
                ],
            ])
            ->git([
                'add .',
                'commit -m "Install composer dependencies for Laravel Fortify"',
            ]),
        step('Install Laravel Pint')
            ->composer([
                'require-dev' => [
                    'laravel/pint'
                ],
                'scripts' => [
                    'lint' => 'pint',
                ],
            ]),            
        step('Configure Laravel Fortify')
            ->artisan([
                'vendor:publish',
                '--provider' => 'Laravel\Fortify\FortifyServiceProvider',
            ])
            ->git([
                'add .',
                'commit -m "Configure Laravel Fortify"',
            ]),
        step('Configure Team Model')
            ->artisan([
                'make:model',
                'Team',
                '--migration',
            ])
            ->migrate(fresh: true, seed: true)
            ->git([
                'add .',
                'commit -m "Configure Team Model"',
            ]),
        step('Configure Roles & Permissions')
            ->composer([
                'require' => [
                    'spatie/laravel-permission',
                ],
            ])
            ->artisan([
                'vendor:publish',
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            ])
            ->config([
                'permissions' => [
                    'teams' => true,
                ],
            ])
            ->migrate(fresh: true)
            ->addTrait(
                'Spatie\Permission\Traits\HasRoles::class',
                'App\Models\User',
            ) // under the hood this is using PHP Code Gen
            ->closure(function ($app) {
                $role = Spatie\Permission\Models\Role::create(['name' => 'writer']);
                $permission = Spatie\Permission\Models\Permission::create(['name' => 'edit articles']);
                $role->givePermissionTo($permission);
            })
            ->git([
                'add .',
                'commit -m "Configure Roles & Permissions"',
            ]),
            
    ]);
