<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'title',
        'code',
        'level',
        'semester',
        'unit',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'unit' => 'integer',
            'level' => 'string',
        ];
    }
}
