<?php

namespace App\Enums;

enum UserAccountState: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';
}
