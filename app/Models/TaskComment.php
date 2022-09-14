<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Xetaio\Mentions\Models\Traits\HasMentionsTrait;

class TaskComment extends Model
{
    use HasFactory;
    use HasMentionsTrait;

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

    protected $fillable = [
        'comment',
    ];
}
