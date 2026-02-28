<?php

namespace App\Actions;

use App\Models\Label;

class CreateLabelAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): Label
    {
        return Label::query()->create($data)->refresh();
    }
}
