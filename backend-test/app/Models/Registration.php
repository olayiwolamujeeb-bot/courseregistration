<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'student_id',
        'course_ids',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'course_ids' => 'array',
            'created_at' => 'datetime:c',
            'updated_at' => 'datetime:c',
        ];
    }

    protected $appends = [
        'studentId',
        'courseIds',
        'createdAt',
    ];

    protected $hidden = [
        'student_id',
        'course_ids',
        'updated_at',
    ];

    public function getStudentIdAttribute(): int
    {
        return (int) ($this->attributes['student_id'] ?? 0);
    }

    public function getCourseIdsAttribute(): array
    {
        $courseIds = $this->getAttributeFromArray('course_ids');

        if (is_array($courseIds)) {
            return array_map('intval', $courseIds);
        }

        $decoded = json_decode((string) $courseIds, true);

        return is_array($decoded)
            ? array_map('intval', $decoded)
            : [];
    }

    public function getCreatedAtAttribute($value): ?string
    {
        return $value ? $this->asDateTime($value)->toISOString() : null;
    }
}
