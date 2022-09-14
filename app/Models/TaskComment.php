<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use HasFactory;

    /**
     * Get the User that owns the TaskComment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function User()
    {
        return $this->belongsTo(User::class, 'id');
    }

    /**
     * Get the Task that owns the TaskComment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Task()
    {
        return $this->belongsTo(Task::class, 'id');
    }
}