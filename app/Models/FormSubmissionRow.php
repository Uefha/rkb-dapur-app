<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmissionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'row_index',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class, 'form_submission_row_id');
    }
}
