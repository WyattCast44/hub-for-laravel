<?php

namespace App\Pipes;

use App\Models\Project;
use Closure;
use Illuminate\Support\Facades\Artisan;

class UpsertEnvFilePipe
{
    protected Project $project;

    protected array $env = [];

    public function __invoke(Project $project, Closure $next)
    {
        $this->project = $project;

        if (!$this->shouldHandle()) {
            return $this;
        }

        $this->handleUpserts();

        $next($project);
    }

    protected function shouldHandle(): bool
    {
        $env = $this->project->contents['env'] ?? [];

        // I think we should actually just handle the top level env and 
        // then default to the steps env if it exists
        // that way the steps can have their own env and not conflict with the top level
        // and the progress output will be have content specific to the step
        // and if there is a step-order-dependant flow, this does not highjack that process
        $steps = $this->project->contents['steps'] ?? [];

        foreach ($steps as $step) {
            if (isset($step['env']) && is_array($step['env'])) {
                $env = array_merge($env, $step['env']);
            }
        }

        $this->env = $env;

        return count($this->env) > 0;
    }

    protected function handleUpserts()
    {
        $this->project->command->task("Upserting env file", function () {
            
            foreach ($this->env as $key => $value) {
                Artisan::call('env:set', [
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        });
       
        return $this;
    }
}