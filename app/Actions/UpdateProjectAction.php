<?php

namespace App\Actions;

use App\Models\Project;

class UpdateProjectAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Project $project, array $data): Project
    {
        $project->fill($data);
        $project->save();

        return $project->refresh();
    }
}
