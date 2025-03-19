<?php

namespace App\Pipes;

use App\Models\Project;
use Closure;

class UpsertComposerDescription
{
    protected Project $project;

    protected array $env = [];

    public function __invoke(Project $project, Closure $next)
    {
        $this->project = $project;

        if (!$this->shouldHandle()) {
            return $this;
        }

        $this->handleUpsert();

        $next($this);
    }

    protected function shouldHandle(): bool
    {
        return array_key_exists('description', $this->project->contents);
    }

    protected function handleUpsert()
    {
        $this->project->command->task("Updating composer.json description", function () {
            sleep(0.5);
        });
       
        return $this;
    }
}