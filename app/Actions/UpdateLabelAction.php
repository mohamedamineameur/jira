<?php

namespace App\Actions;

use App\Models\Label;

class UpdateLabelAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Label $label, array $data): Label
    {
        $label->fill($data);
        $label->save();

        return $label->refresh();
    }
}
