<?php

namespace App\Actions;

use App\Models\Project;

class CreateProjectAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): Project
    {
        return Project::query()->create($data)->refresh();
    }
}
