<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubmissionExportService
{
    public function query(Request $request): Builder
    {
        return FormSubmission::query()
            ->select('form_submissions.*')
            ->selectRaw('(SELECT COUNT(*) FROM form_submissions AS daily_items WHERE daily_items.deleted_at IS NULL AND DATE(COALESCE(daily_items.submitted_at, daily_items.created_at)) = DATE(COALESCE(form_submissions.submitted_at, form_submissions.created_at)) AND daily_items.id <= form_submissions.id) AS daily_sequence')
            ->with([
                'form.category', 'user', 'reviewer',
                'version.sections.fields.options',
                'answers', 'rows.answers',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')),
                fn ($q) => $q->where('status', '!=', FormSubmission::STATUS_DRAFT))
            ->when($request->filled('form_id'), fn ($q) => $q->where('form_id', $request->query('form_id')))
            ->when($request->filled('submission_numbers'), function ($q) use ($request) {
                $numbers = collect(explode(',', (string) $request->query('submission_numbers')))
                    ->map(fn ($number) => (int) trim($number))->filter(fn ($number) => $number > 0)->unique()->values();
                if ($numbers->isNotEmpty()) {
                    $placeholders = implode(',', array_fill(0, $numbers->count(), '?'));
                    $q->whereRaw("(SELECT COUNT(*) FROM form_submissions AS daily_items WHERE daily_items.deleted_at IS NULL AND DATE(COALESCE(daily_items.submitted_at, daily_items.created_at)) = DATE(COALESCE(form_submissions.submitted_at, form_submissions.created_at)) AND daily_items.id <= form_submissions.id) IN ({$placeholders})", $numbers->all());
                }
            })
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->query('user_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas('form', fn ($form) => $form->where('category_id', $request->query('category_id'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('submitted_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('submitted_at', '<=', $request->query('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->query('q'));
                $q->where(function ($query) use ($term) {
                    $query->where('id', is_numeric($term) ? (int) $term : 0)
                        ->orWhereHas('form', fn ($form) => $form->where('title', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->when($request->query('sort_by', 'id') === 'date', fn ($q) => $q->orderByRaw('COALESCE(submitted_at, created_at) '.($request->query('sort_order', 'desc') === 'asc' ? 'asc' : 'desc'))->orderBy('id', $request->query('sort_order', 'desc')),
                fn ($q) => $q->orderBy('id', $request->query('sort_order', 'desc') === 'asc' ? 'asc' : 'desc'));
    }

    /** @return Collection<int, array{label:string,value:string,table:bool,columns:array,rows:array}> */
    public function details(FormSubmission $submission): Collection
    {
        $directAnswers = $submission->answers->whereNull('form_submission_row_id')->keyBy('form_field_id');
        $rowsByField = $submission->rows->sortBy('row_index')->groupBy('form_field_id');
        $details = collect();

        foreach ($submission->version?->sections ?? [] as $section) {
            foreach ($section->fields as $field) {
                if ($field->isDisplayOnly()) continue;
                $label = ($submission->form?->title ?? 'Form').' / '.$section->title.' / '.$field->label;
                if ($field->isDynamicTable()) {
                    $columns = $field->tableColumns();
                    $tableRows = $rowsByField->get($field->id, collect())->map(function ($row) use ($columns) {
                        $cells = $row->answers->keyBy('column_key');
                        return collect($columns)->mapWithKeys(fn ($column) => [
                            $column['label'] => (string) (optional($cells->get($column['key']))->value ?? ''),
                        ])->all();
                    })->values()->all();
                    $details->push(['label' => $label, 'value' => '', 'table' => true, 'columns' => $columns, 'rows' => $tableRows]);
                    continue;
                }

                $value = $directAnswers->get($field->id)?->value;
                if ($value !== null && $field->field_type === FormField::TYPE_CHECKBOX) {
                    $selected = json_decode($value, true);
                    $value = is_array($selected) ? collect($selected)->map(fn ($optionValue) => $field->options->firstWhere('value', $optionValue)?->label ?? $optionValue)->implode(', ') : $value;
                } elseif ($value !== null && in_array($field->field_type, [FormField::TYPE_SELECT, FormField::TYPE_RADIO], true)) {
                    $value = $field->options->firstWhere('value', $value)?->label ?? $value;
                } elseif ($field->field_type === FormField::TYPE_YESNO && $value !== null) {
                    $value = (string) $value === '1' ? 'Ya' : 'Tidak';
                }
                $details->push(['label' => $label, 'value' => (string) ($value ?? ''), 'table' => false, 'columns' => [], 'rows' => []]);
            }
        }

        return $details;
    }

    public function spreadsheet(Collection $submissions): array
    {
        $records = $submissions->map(fn ($submission) => $this->details($submission));
        $fieldLabels = $records->flatten(1)->pluck('label')->unique()->values();
        $headings = ['ID Submission', 'Form', 'Kategori', 'Pengisi', 'Email Pengisi', 'Status', 'Dikirim Pada', 'Ditinjau Oleh', 'Catatan Review', ...$fieldLabels->all()];
        $rows = $submissions->values()->map(function ($submission, $index) use ($records, $fieldLabels) {
            $details = $records[$index]->keyBy('label');
            $row = [
                $submission->id, $submission->form?->title, $submission->form?->category?->name,
                $submission->user?->name, $submission->user?->email, $submission->statusLabel(),
                $submission->submitted_at?->format('Y-m-d H:i'), $submission->reviewer?->name, $submission->review_notes,
            ];
            foreach ($fieldLabels as $label) {
                $detail = $details->get($label);
                if (! $detail) { $row[] = ''; continue; }
                if (! $detail['table']) { $row[] = $detail['value']; continue; }
                $row[] = collect($detail['rows'])->map(fn ($cells, $i) => 'Baris '.($i + 1).': '.collect($cells)->map(fn ($value, $key) => $key.': '.$value)->implode(' | '))->implode("\n");
            }
            return $row;
        })->all();

        return [$headings, $rows];
    }
}
