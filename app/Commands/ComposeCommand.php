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
use Illuminate\Pipeline\Pipeline;

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
            ->ensureRecipeFileExists()
            ->loadRecipeFile()
            ->parseRecipeFileYaml()
            ->ensureRecipeIsNotEmpty()
            ->ensureProjectHasAName()
            ->ensureDirectoryDNE()
            ->installApplication()
            ->runPipes();
    }

    protected function determineRecipeToCompose()
    {
        return tap($this, function () {
            $this->recipe = $this->argument('script');
        });
    }

    protected function ensureRecipeFileExists()
    {
        if (file_exists('./'.$this->recipe)) {
            return $this;
        }

        throw new Exception('Unable to find the given recipe file: '.$this->recipe);
    }

    protected function loadRecipeFile()
    {
        try {
            $this->project->rawRecipe = file_get_contents($this->recipe);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function parseRecipeFileYaml()
    {
        try {
            $this->project->contents = Yaml::parse($this->project->rawRecipe);

            return $this;
        } catch (ParseException $e) {
            throw new Exception('Unable to parse the recipe file: '.$this->recipe);
        }
    }

    protected function ensureRecipeIsNotEmpty()
    {
        if (empty($this->project->contents)) {
            throw new Exception('Recipe file ('.$this->recipe.') cannot be empty.');
        }

        return $this;
    }

    protected function ensureProjectHasAName()
    {
        $status = array_key_exists('name', $this->project->contents) && $this->project->contents['name'] != null;

        if(!$status) {
            throw new Exception('The recipe must contain a non-null name.');
        }

        $this->project->name = $this->project->contents['name'];

        return $this;
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

    protected function installApplication()
    {
        $method = $this->project->getInstallationMethod();

        $this->info('Installing application with '.$method.'...');

        /* $this->call('new', [
            'name' => $this->project->getSluggifiedName(),
            '--installer' => $method,
        ]);
 */
        return $this;
    }

    protected function runPipes()
    {
        $pipeline = app()->make(Pipeline::class);

        $project = $pipeline->send($this->project)->through($this->pipes)->thenReturn();

        dd($project);

        return $this;
    }
}
