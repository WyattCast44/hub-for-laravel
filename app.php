<?php

/**
 * Immediate benefits:
 * - Intellisense
 * - Type safety (at least better than yaml, still lots of magic keys)
 * - Developer flexability
 * - PHP Code Gen: https://github.com/nette/php-generator
 */

function step(string $name): object
{
    return new Class {
        public function composer(array $commands)
        {
            return $commands;
        }
        public function npm(array $commands)
        {
            return $commands;
        }
        public function artisan(array $commands)
        {
            return $commands;
        }
        public function git(array $commands)
        {
            return $commands;
        }
        public function migrate(bool $fresh = false, bool $seed = false)
        {
            return $this;
        }
    };
}

function compose(string $name): object
{
    return new Class {
        public function description(string $description)
        {
            return $this;
        }
        public function extend(string $recipe)
        {
            return $this;
        }
        public function env(array $env)
        {
            return $this;
        }
        public function steps(array $steps)
        {
            return $this;
        }
    };
}

compose('Laravel Hub')
    ->description('Laravel Hub CLI')
    ->extend('laravel/livewire-volt') // should we extend starter kits, or recipes, or both?
    ->env([
        'APP_NAME' => 'Laravel Hub',
        'APP_ENV' => 'local',
        'APP_DEBUG' => 'true',
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
            ->phpGen([
                
            ])
            ->git([
                'add .',
                'commit -m "Configure Team Model"',
            ]),
    ]);
