<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'name'
    ];

    protected $visible = [
        'id', 'board_id', 'name'
    ];

    protected $hidden = [
        'timestamps'
    ];
}
