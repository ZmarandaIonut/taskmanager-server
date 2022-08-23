<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardMembers extends Model
{
    use HasFactory;
    const ADMIN = 1;
    const MEMBER = 0;
}
