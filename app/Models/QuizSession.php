<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_name',
        'total_questions',
        'correct_answers',
        'final_score',
        'status',
    ];

    protected $casts = [
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'final_score' => 'float',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }
}
