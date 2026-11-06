<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormFieldRequest;
use App\Http\Requests\Admin\StoreFormSectionRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSection;
use App\Services\ActivityLogService;
use App\Services\FormBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FormBuilderController extends Controller
{
    public function index(Form $form): View
    {
        $version = $form->latestVersion();

        if (! $version) {
            abort(404, 'Form ini belum memiliki versi.');
        }

        $version->load('sections.fields.options');

        return view('admin.forms.builder', compact('form', 'version'));
    }

    public function storeSection(StoreFormSectionRequest $request, Form $form, FormBuilderService $builder, ActivityLogService $activityLog): RedirectResponse
    {
        $version = $this->editableVersionOrFail($form);

        try {
            $section = $builder->addSection($version, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('builder.section.create', "Menambah section \"{$section->title}\" pada form \"{$form->title}\"", $form);

        return back()->with('success', 'Section berhasil ditambahkan.');
    }

    public function updateSection(StoreFormSectionRequest $request, Form $form, FormSection $section, FormBuilderService $builder, ActivityLogService $activityLog): RedirectResponse
    {
        $this->ensureSectionBelongsToForm($form, $section);

        try {
            $builder->updateSection($section, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('builder.section.update', "Memperbarui section \"{$section->title}\"", $form);

        return back()->with('success', 'Section berhasil diperbarui.');
    }

    public function destroySection(Form $form, FormSection $section, FormBuilderService $builder, ActivityLogService $activityLog): RedirectResponse
    {
        $this->ensureSectionBelongsToForm($form, $section);

        $title = $section->title;

        try {
            $builder->deleteSection($section);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('builder.section.delete', "Menghapus section \"{$title}\" beserta seluruh field di dalamnya", $form);

        return back()->with('success', 'Section berhasil dihapus.');
    }

    public function moveSection(Form $form, FormSection $section, string $direction, FormBuilderService $builder): RedirectResponse
    {
        $this->ensureSectionBelongsToForm($form, $section);

        try {
            $builder->moveSection($section, $direction);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function storeField(StoreFormFieldRequest $request, Form $form, FormSection $section, FormBuilderService $builder, ActivityLogService $activityLog): RedirectResponse
    {
        $this->ensureSectionBelongsToForm($form, $section);

        try {
            $field = $builder->addField($section, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('builder.field.create', "Menambah field \"{$field->label}\" ({$field->typeLabel()}) pada section \"{$section->title}\"", $form);

        return back()->with('success', 'Field berhasil ditambahkan.');
    }

    public function updateField(StoreFormFieldRequest $request, Form $form, FormField $field, FormBuilderService $builder, ActivityLogService $activityLog): RedirectResponse
    {
        $this->ensureFieldBelongsToForm($form, $field);

        try {
            $builder->updateField($field, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('builder.field.update', "Memperbarui field \"{$field->label}\"", $form);

        return back()->with('success', 'Field berhasil diperbarui.');
    }

    public function destroyField(Form $form, FormField $field, FormBuilderService $builder, ActivityLogService $activityLog): RedirectResponse
    {
        $this->ensureFieldBelongsToForm($form, $field);

        $label = $field->label;

        try {
            $builder->deleteField($field);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('builder.field.delete', "Menghapus field \"{$label}\"", $form);

        return back()->with('success', 'Field berhasil dihapus.');
    }

    public function moveField(Form $form, FormField $field, string $direction, FormBuilderService $builder): RedirectResponse
    {
        $this->ensureFieldBelongsToForm($form, $field);

        try {
            $builder->moveField($field, $direction);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Cegah IDOR: pastikan section benar-benar milik form yang sedang
     * dibuka, bukan sekadar ID section valid milik form lain.
     */
    private function ensureSectionBelongsToForm(Form $form, FormSection $section): void
    {
        if ($section->formVersion->form_id !== $form->id) {
            abort(404);
        }
    }

    private function ensureFieldBelongsToForm(Form $form, FormField $field): void
    {
        if ($field->section->formVersion->form_id !== $form->id) {
            abort(404);
        }
    }

    private function editableVersionOrFail(Form $form)
    {
        $version = $form->latestVersion();

        if (! $version) {
            abort(404, 'Form ini belum memiliki versi.');
        }

        return $version;
    }
}
