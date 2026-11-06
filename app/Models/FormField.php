<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasFactory;

    // Daftar lengkap tipe field sesuai bagian 8 spesifikasi. Disimpan sebagai
    // string (bukan DB enum) supaya tipe baru di fase lanjutan tidak perlu
    // ALTER TABLE -- cukup tambah entri di sini.
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER = 'number';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_EMAIL = 'email';
    public const TYPE_SELECT = 'select';
    public const TYPE_RADIO = 'radio';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_YESNO = 'yesno';
    public const TYPE_STATIC_TEXT = 'static_text';
    public const TYPE_HEADING = 'heading';

    // Menyusul di fase lanjutan (kolom field_type sudah menerima nilai ini
    // dari sisi database, hanya belum diaktifkan di dropdown Builder):
    public const TYPE_FILE = 'file';               // Phase 8
    public const TYPE_IMAGE = 'image';              // Phase 8
    public const TYPE_SIGNATURE = 'signature';      // Phase 8
    public const TYPE_REPEATER = 'repeater';        // Phase 3
    public const TYPE_DYNAMIC_TABLE = 'dynamic_table'; // Phase 3
    public const TYPE_CALCULATED = 'calculated';    // Phase 8

    /** Tipe yang tersedia di UI Builder dasar (Phase 2). */
    public const PHASE2_TYPES = [
        self::TYPE_TEXT => 'Text',
        self::TYPE_TEXTAREA => 'Textarea',
        self::TYPE_NUMBER => 'Number',
        self::TYPE_DATE => 'Date',
        self::TYPE_TIME => 'Time',
        self::TYPE_DATETIME => 'DateTime',
        self::TYPE_EMAIL => 'Email',
        self::TYPE_SELECT => 'Select (Dropdown)',
        self::TYPE_RADIO => 'Radio',
        self::TYPE_CHECKBOX => 'Checkbox',
        self::TYPE_YESNO => 'Yes / No',
        self::TYPE_STATIC_TEXT => 'Static Text',
        self::TYPE_HEADING => 'Heading',
    ];

    /** Tipe yang tersedia di UI Builder mulai Phase 3 (menambah Dynamic Table). */
    public const PHASE3_TYPES = self::PHASE2_TYPES + [
        self::TYPE_DYNAMIC_TABLE => 'Dynamic Table',
    ];

    /** Tipe kolom yang valid di dalam config sebuah field Dynamic Table. */
    public const TABLE_COLUMN_TYPES = [
        'text' => 'Text',
        'number' => 'Number',
        'date' => 'Date',
        'time' => 'Time',
        'select' => 'Select (Dropdown)',
    ];

    /** Tipe yang butuh daftar opsi (form_field_options). */
    public const TYPES_WITH_OPTIONS = [
        self::TYPE_SELECT,
        self::TYPE_RADIO,
        self::TYPE_CHECKBOX,
    ];

    /** Tipe display-only: tidak mengumpulkan jawaban, tidak butuh Required/Nama. */
    public const DISPLAY_ONLY_TYPES = [
        self::TYPE_STATIC_TEXT,
        self::TYPE_HEADING,
    ];

    protected $fillable = [
        'form_section_id',
        'parent_field_id',
        'field_type',
        'name',
        'label',
        'placeholder',
        'help_text',
        'is_required',
        'order',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'config' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'parent_field_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FormField::class, 'parent_field_id')->orderBy('order');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('order');
    }

    public function hasOptions(): bool
    {
        return in_array($this->field_type, self::TYPES_WITH_OPTIONS, true);
    }

    public function isDisplayOnly(): bool
    {
        return in_array($this->field_type, self::DISPLAY_ONLY_TYPES, true);
    }

    public function typeLabel(): string
    {
        return self::PHASE3_TYPES[$this->field_type] ?? $this->field_type;
    }

    public function isDynamicTable(): bool
    {
        return $this->field_type === self::TYPE_DYNAMIC_TABLE;
    }

    /** Definisi kolom Dynamic Table, dari config JSON. Array kosong untuk tipe field lain. */
    public function tableColumns(): array
    {
        return $this->config['columns'] ?? [];
    }

    /** Baris awal (opsional) yang sudah diisi otomatis, mis. daftar aspek checklist yang tetap. */
    public function tableDefaultRows(): array
    {
        return $this->config['default_rows'] ?? [];
    }
}
