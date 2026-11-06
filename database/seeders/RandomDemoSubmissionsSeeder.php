<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\FormSubmissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class RandomDemoSubmissionsSeeder extends Seeder
{
    private const MARKER = 'random_demo_submissions_100_created';

    public function run(FormSubmissionService $submissionService): void
    {
        if (AppSetting::where('key', self::MARKER)->exists()) {
            $this->command?->warn('Batch 100 data dummy sudah pernah dibuat. Tidak ada data tambahan.');
            return;
        }

        $user = User::where('email', 'petugas@rkb-dapur.test')->first();
        if (! $user) {
            throw new RuntimeException('Akun petugas contoh belum ada. Jalankan DemoUserSeeder terlebih dahulu.');
        }

        $forms = Form::query()
            ->where('status', Form::STATUS_PUBLISHED)
            ->with('currentVersion.sections.fields.options')
            ->get()
            ->filter(fn (Form $form) => $form->currentVersion !== null)
            ->values();

        if ($forms->isEmpty()) {
            throw new RuntimeException('Tidak ada form terbit untuk diisi dengan data dummy.');
        }

        for ($index = 1; $index <= 100; $index++) {
            $form = $forms->random();
            $version = $form->currentVersion;
            $submittedAt = Carbon::now()
                ->subDays(random_int(0, 180))
                ->setTime(random_int(6, 22), random_int(0, 59), random_int(0, 59));
            $answers = $this->answersFor($version, $index, $submittedAt);

            $submission = FormSubmission::create([
                'form_id' => $form->id,
                'form_version_id' => $version->id,
                'user_id' => $user->id,
                'status' => FormSubmission::STATUS_DRAFT,
            ]);
            $submissionService->save($submission, $version, $answers, 'submit');
            $submission->refresh();
            $submission->forceFill([
                'status' => FormSubmission::STATUS_SUBMITTED,
                'submitted_at' => $submittedAt,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ])->saveQuietly();
        }

        AppSetting::updateOrCreate(['key' => self::MARKER], ['value' => now()->toDateTimeString()]);
        $this->command?->info('Berhasil membuat 100 submission dummy dengan tanggal acak dalam 180 hari terakhir.');
    }

    private function answersFor($version, int $index, Carbon $submittedAt): array
    {
        $answers = [];

        foreach ($version->sections as $section) {
            foreach ($section->fields as $field) {
                if ($field->isDisplayOnly()) continue;

                if ($field->isDynamicTable()) {
                    $columns = $field->tableColumns();
                    $rows = [];
                    $firstNewRow = count($field->tableDefaultRows());
                    $rowCount = random_int(1, 3);
                    for ($rowIndex = $firstNewRow; $rowIndex < $firstNewRow + $rowCount; $rowIndex++) {
                        foreach ($columns as $column) {
                            $rows[$rowIndex][$column['key']] = $this->valueFor(
                                $column['type'] ?? 'text',
                                $column['options'] ?? [],
                                $index,
                                $submittedAt,
                            );
                        }
                    }
                    $answers[$field->id] = ['rows' => $rows];
                    continue;
                }

                $options = $field->options->map(fn ($option) => $option->value ?? $option->label)->all();
                $answers[$field->id] = $this->valueFor($field->field_type, $options, $index, $submittedAt);
            }
        }

        return $answers;
    }

    private function valueFor(string $type, array $options, int $index, Carbon $submittedAt): mixed
    {
        $option = $options === [] ? 'Contoh' : $options[array_rand($options)];

        return match ($type) {
            FormField::TYPE_NUMBER, 'number' => (string) random_int(1, 100),
            FormField::TYPE_DATE, 'date' => $submittedAt->copy()->subDays(random_int(0, 7))->toDateString(),
            FormField::TYPE_TIME, 'time' => sprintf('%02d:%02d', random_int(6, 21), random_int(0, 59)),
            FormField::TYPE_DATETIME, 'datetime' => $submittedAt->format('Y-m-d\\TH:i'),
            FormField::TYPE_EMAIL, 'email' => 'petugas@rkb-dapur.test',
            FormField::TYPE_SELECT, FormField::TYPE_RADIO, 'select' => $option,
            FormField::TYPE_CHECKBOX => array_slice(array_values(array_unique($options ?: ['Contoh'])), 0, random_int(1, max(1, min(2, count($options))))),
            FormField::TYPE_YESNO => (string) random_int(0, 1),
            default => 'Data percobaan '.$index.' - '.fake()->words(3, true),
        };
    }
}
