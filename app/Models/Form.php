<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'category_id',
        'code',
        'title',
        'description',
        'purpose',
        'instructions',
        'status',
        'visibility',
        'current_version_id',
        'created_by',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class)->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'current_version_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->visibility === 'all' || $this->assignedUsers()->whereKey($user->id)->exists();
    }

    /**
     * Versi terbaru (bukan berarti published) -- ini yang dipakai Builder
     * untuk menentukan apakah masih bisa diedit langsung atau harus
     * membuat versi baru dulu.
     */
    public function latestVersion(): ?FormVersion
    {
        return $this->versions()->first();
    }

    public function isEditable(): bool
    {
        $latest = $this->latestVersion();

        return $latest !== null && $latest->status === FormVersion::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
            default => 'Draft',
        };
    }
}
