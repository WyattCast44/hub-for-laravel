<?php

namespace App\Commands;

use Exception;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;

class NewCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new {name?} {--installer=}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new Laravel application using composer or the laravel installer';

    protected $installCommand =
        'composer create-project laravel/laravel {NAME} --remove-vcs --prefer-dist';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(PHP_EOL.'Creating a new Laravel application...'.PHP_EOL);

        try {
            $installer = $this->option('installer');

            match ($installer) {
                'composer' => $this->installLaravelUsingComposer(),
                'laravel' => $this->installLaravelUsingLaravelInstaller(),
                default => $this->handleDefaultInstallation(),
            };
        } catch (Exception $e) {
            $this->error('Failed to create Laravel application.');
        }

    }

    protected function determineIfUserHasLaravelInstallerInstalled(): bool
    {
        $process = Process::run('composer global show laravel/installer');

        return $process->successful();
    }

    protected function installLaravelUsingComposer(): void
    {
        $this->info("
  _                               _ _    _       _     
 | |                             | | |  | |     | |    
 | |     __ _ _ __ __ ___   _____| | |__| |_   _| |__  
 | |    / _` | '__/ _` \ \ / / _ \ |  __  | | | | '_ \ 
 | |___| (_| | | | (_| |\ V /  __/ | |  | | |_| | |_) |
 |______\__,_|_|  \__,_| \_/ \___|_|_|  |_|\__,_|_.__/ 
");

        $name = $this->argument('name') ?? $this->ask('What is the name of your project?');

        if (! $name) {
            $this->error('Please provide a name for your new Laravel application.');

            return;
        }

        $command = str_replace('{NAME}', $name, $this->installCommand);

        $process = Process::run($command, function ($type, $buffer) {
            $this->info($buffer);
        });

        if (! $process->successful()) {
            $this->error('Failed to create Laravel application.');

            return;
        }
    }

    protected function installLaravelUsingLaravelInstaller(): void
    {
        $command = 'laravel new';

        if ($this->argument('name')) {
            // if the name is provided, use it
            $command .= ' '.$this->argument('name');
        }

        $descriptors = [
            0 => STDIN,  // Allow user input
            1 => STDOUT, // Output to the terminal
            2 => STDERR, // Error output to the terminal
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            // Wait for the process to finish
            $returnCode = proc_close($process);

            if (! $returnCode === 0) {
                $this->error('Failed to create Laravel application.');
            }
        }

        // todo: listen for a termsig command and kill the process, currently it
        // only kills the laravel installer process, not the laravel hub process
    }

    protected function handleDefaultInstallation(): void
    {
        if ($this->determineIfUserHasLaravelInstallerInstalled()) {
            $this->installLaravelUsingLaravelInstaller();
        } else {
            $this->installLaravelUsingComposer();
        }
    }
}
