<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAssignedTo extends Model
{
    use HasFactory;

    public function getUser()
    {
        return $this->belongsTo(User::class, "assigned_to", "id");
    }
}
