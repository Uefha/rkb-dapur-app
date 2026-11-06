<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Mengambil struktur form (section + field + opsi) yang siap dirender ke
 * User, selalu dari `current_version_id` milik form -- bukan versi terakhir
 * secara umum -- supaya konsisten dengan aturan immutability versi: begitu
 * form baru dipublish di versi lain, User otomatis melihat struktur terbaru,
 * sementara submission LAMA (Phase 5) tetap terikat ke version_id saat
 * submission itu dibuat.
 */
class FormRendererService
{
    /**
     * @throws NotFoundHttpException jika form tidak published atau tidak
     *   punya versi published sama sekali (mis. form baru dibuat, belum
     *   pernah di-publish satu kalipun).
     */
    public function renderableVersion(Form $form): FormVersion
    {
        if ($form->status !== 'published') {
            throw new NotFoundHttpException('Form ini tidak tersedia untuk diisi.');
        }

        $version = $form->currentVersion;

        if (! $version || $version->status !== 'published') {
            throw new NotFoundHttpException('Form ini belum memiliki versi yang dipublikasikan.');
        }

        return $version->load([
            'sections' => fn ($q) => $q->orderBy('order'),
            'sections.fields' => fn ($q) => $q->orderBy('order'),
            'sections.fields.options' => fn ($q) => $q->orderBy('order'),
        ]);
    }
}
