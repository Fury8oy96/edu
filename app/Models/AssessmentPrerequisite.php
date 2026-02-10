<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentPrerequisite extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'prerequisite_type',
        'prerequisite_data',
    ];

    protected $casts = [
        'prerequisite_data' => 'array',
    ];

    /**
     * Get the assessment that this prerequisite belongs to
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Validate prerequisite type enum
     * 
     * @param string $type
     * @return bool
     */
    public static function isValidPrerequisiteType(string $type): bool
    {
        return in_array($type, ['quiz_completion', 'minimum_progress', 'lesson_completion']);
    }
}
