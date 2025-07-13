<?php

// app/Models/Question.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'form_id',
        'question_text',
        'question_type',
        'is_required',
        'options'
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean'
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
