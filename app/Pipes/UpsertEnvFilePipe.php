<?php

namespace App\Pipes;

use App\Models\Project;

class UpsertEnvFilePipe
{
    protected Project $project;

    public function __invoke(Project $project)
    {
        $this->project = $project;

        return $this;
    }

    // okay in this file we will shell out to the upsert env file command
    // but we will scrape the compose file recipe for any env calls
}