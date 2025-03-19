<?php

namespace App\Pipes;

use App\Models\Project;

class UpsertEnvFilePipe
{
    protected Project $project;

    public function __invoke(Project $project)
    {
        $this->project = $project;

        $this->internalHandle();

        return $this;
    }

    public function recursiveMergeEnvs($data) {
        $result = [];
    
        // If 'env' exists at the root, merge it
        if (isset($data['env']) && is_array($data['env'])) {
            $result = array_merge($result, $data['env']);
        }
    
        // If 'steps' exist, merge env from each step
        if (isset($data['steps']) && is_array($data['steps'])) {
            foreach ($data['steps'] as $step) {
                if (isset($step['env']) && is_array($step['env'])) {
                    $result = array_merge($result, $step['env']);
                }
            }
        }
    
        return $result;
    }

    public function internalHandle()
    {
        $mergedEnvs = $this->recursiveMergeEnvs($this->project->contents);
       
        return $this;
    }
}