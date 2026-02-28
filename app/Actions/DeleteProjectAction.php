<?php

namespace App\Actions;

use App\Models\Project;

class DeleteProjectAction
{
    public function execute(Project $project): Project
    {
        $project->is_deleted = true;
        $project->deleted_at = now();
        $project->save();

        return $project->refresh();
    }
}
