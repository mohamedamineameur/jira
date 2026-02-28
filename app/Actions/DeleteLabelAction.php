<?php

namespace App\Actions;

use App\Models\Label;

class DeleteLabelAction
{
    public function execute(Label $label): Label
    {
        $label->is_deleted = true;
        $label->deleted_at = now();
        $label->save();

        return $label->refresh();
    }
}
