<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\FormSubmissionService;
use Illuminate\Database\Seeder;

class DemoSubmissionSeeder extends Seeder
{
    public function run(FormSubmissionService $submissions): void
    {
        $user = User::where('email', 'petugas@rkb-dapur.test')->first();
        if (! $user) return;

        $forms = Form::query()->where('status', 'published')->with('currentVersion.sections.fields.options')->get();
        foreach ($forms as $form) {
            $version = $form->currentVersion;
            if (! $version || FormSubmission::where('form_id', $form->id)->where('user_id', $user->id)->exists()) continue;

            $answers = [];
            foreach ($version->sections as $section) {
                foreach ($section->fields as $field) {
                    if ($field->isDisplayOnly()) continue;
                    if ($field->isDynamicTable()) {
                        $rows = [];
                        foreach ($field->tableColumns() as $column) {
                            $rows[0][$column['key']] = $this->sampleValue($column['type'] ?? 'text', $column['options'] ?? []);
                        }
                        $answers[$field->id] = ['rows' => $rows];
                    } else {
                        $answers[$field->id] = $this->sampleValue($field->field_type, $field->options->map(fn ($option) => $option->value ?? $option->label)->all());
                    }
                }
            }

            $submissions->saveSubmission($form, $version, $user, $answers, 'submit');
        }
    }

    private function sampleValue(string $type, array $options = []): mixed
    {
        return match ($type) {
            FormField::TYPE_NUMBER, 'number' => '1',
            FormField::TYPE_DATE => now()->toDateString(),
            FormField::TYPE_TIME => '08:00',
            FormField::TYPE_DATETIME => now()->format('Y-m-d\\TH:i'),
            FormField::TYPE_EMAIL => 'petugas@rkb-dapur.test',
            FormField::TYPE_SELECT, FormField::TYPE_RADIO, 'select' => $options[0] ?? 'Contoh',
            FormField::TYPE_CHECKBOX => [$options[0] ?? 'Contoh'],
            FormField::TYPE_YESNO => '1',
            default => 'Data contoh untuk uji coba',
        };
    }
}
