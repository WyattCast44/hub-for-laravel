<?php

namespace App\Commands;

use App\Models\Project;
use App\Pipes\UpsertEnvFilePipe;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ComposeCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'compose {script=app.yaml} {--force}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Compose your application with the given recipe file.';

    /**
     * The recipe file to compose the application with.
     */
    protected string $recipe;

    /**
     * The raw recipe file content.
     */
    protected string $rawRecipe;

    /**
     * The parsed recipe file contents.
     */
    protected array $contents;

    /**
     * The project instance.
     */
    protected Project $project;

    protected array $pipes = [
        'env' => UpsertEnvFilePipe::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->project = Project::make();

        $this
            ->determineRecipeToCompose()
            ->ensureScriptFileExists()
            ->loadRecipeFile()
            ->parseRecipeFileYaml()
            ->ensureRecipeIsNotEmpty()
            ->ensureProjectHasAName()
            ->ensureDirectoryDNE()
            ->determineInstallerToUse()
            ->installApplication();
    }

    protected function determineRecipeToCompose()
    {
        return tap($this, function () {
            $this->recipe = $this->argument('script');
        });
    }

    protected function ensureScriptFileExists()
    {
        if (file_exists('./'.$this->recipe)) {
            return $this;
        }

        throw new Exception('Unable to find the given recipe file: '.$this->recipe);
    }

    protected function loadRecipeFile()
    {
        try {
            $this->rawRecipe = file_get_contents($this->recipe);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function parseRecipeFileYaml()
    {
        try {
            $this->contents = Yaml::parse($this->rawRecipe);

            return $this;
        } catch (ParseException $e) {
            throw new Exception('Unable to parse the recipe file: '.$this->recipe);
        }
    }

    protected function ensureRecipeIsNotEmpty()
    {
        if (empty($this->contents)) {
            throw new Exception('Recipe file ('.$this->recipe.') cannot be empty.');
        }

        return $this;
    }

    protected function ensureProjectHasAName()
    {
        if (array_key_exists('name', $this->contents) && $this->contents['name'] != null) {
            $this->project->name = $this->contents['name'];

            unset($this->contents['name']);

            return $this;
        } else {
            throw new Exception('The recipe must contain a non-null name.');
        }
    }

    protected function ensureDirectoryDNE()
    {
        $path = './'.Str::slug($this->project->name);

        if (is_dir($path)) {
            if ($this->option('force')) {
                Process::quietly()->run(['rm', '-rf', $path]);
            } else {
                throw new Exception(
                    'A directory already exists at the install path. To overwrite the directory use --force, or rename the application.'
                );
            }
        }

        return $this;
    }

    protected function determineInstallerToUse()
    {
        if (array_key_exists('installer', $this->contents) && $this->contents['installer'] != null) {
            $installer = $this->contents['installer'];

            if ($installer == 'composer' || $installer == 'laravel') {
                $this->project->installer = $installer;
            } else {
                throw new Exception('Invalid installer: '.$installer.'. Valid options are: composer and laravel');
            }
        } else {
            // default to composer if no installer is specified
            // I could also see defaulting to laravel installed if it's installed
            $this->project->installer = 'composer';
        }

        return $this;
    }

    protected function installApplication()
    {
        $this->info('Installing application with '.$this->project->installer.'...');

        $this->call('new', [
            'name' => $this->project->getSluggifiedName(),
            '--installer' => $this->project->installer,
        ]);

        return $this;
    }
}
