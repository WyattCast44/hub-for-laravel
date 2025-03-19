<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class Project
{
    /**
     * The name of the project.
     *
     * This will be set in the env file of the project.
     */
    public string $name;

    /**
     * The path to the project.
     *
     * This will be made available to the recipe file.
     */
    public string $path;

    /**
     * The raw contents of the YAML recipe file.
     */
    public string $rawRecipe;

    /**
     * The parsed contents of the YAML recipe file.
     */
    public array $contents;

    public Command $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public static function make($command)
    {
        return new self($command);
    }

    public function getInstallationMethod(): string
    {
        if (array_key_exists('installer', $this->contents) && $this->contents['installer'] != null) {
            $installer = $this->contents['installer'];

            if (!$installer == 'composer' || $installer == 'laravel') {
                throw new Exception('Invalid installer: '.$installer.'. Valid options are: composer and laravel');
            }

            return $installer;
        } 

        if($this->determineIfUserHasLaravelInstallerInstalled()) {
            return 'laravel';
        }

        return 'composer';
    }

    private function determineIfUserHasLaravelInstallerInstalled(): bool
    {
        $process = Process::run('composer global show laravel/installer');

        return $process->successful();
    }

    public function getSluggifiedName(): string
    {
        return Str::slug($this->name);
    }
}
