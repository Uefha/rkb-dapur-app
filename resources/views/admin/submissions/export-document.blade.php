<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Submission RKB Dapur</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #24332c; font-size: 9px; }
        h1 { color: #1a5c45; font-size: 18px; margin: 0 0 4px; }
        h2 { color: #1a5c45; font-size: 13px; margin: 0 0 6px; }
        h3 { font-size: 10px; margin: 10px 0 4px; }
        .meta { color: #52635a; margin: 2px 0; }
        .submission { border-bottom: 1px solid #b7c9c0; padding: 0 0 12px; margin: 0 0 14px; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0 8px; }
        th, td { border: 1px solid #b7c9c0; text-align: left; padding: 4px; vertical-align: top; }
        th { background: #eaf1ed; }
        .field-label { font-weight: bold; margin: 7px 0 2px; }
        .empty { color: #718078; }
    </style>
</head>
<body>
    <h1>Laporan Submission RKB Dapur</h1>
    <p class="meta">Dibuat {{ now()->format('d-m-Y H:i') }} WITA · Total {{ $submissions->count() }} submission</p>

    @forelse($submissions as $submission)
        <div class="submission">
            <h2>{{ $submission->form?->title }} · #{{ $submission->id }}</h2>
            <p class="meta">Kategori: {{ $submission->form?->category?->name ?? '—' }} · Pengisi: {{ $submission->user?->name ?? '—' }} ({{ $submission->user?->email ?? '—' }})</p>
            <p class="meta">Status: {{ $submission->statusLabel() }} · Versi: {{ $submission->version?->version_number ?? '—' }} · Dikirim: {{ $submission->submitted_at ? $submission->submitted_at->format('d-m-Y H:i').' WITA' : '—' }}</p>
            @if($submission->reviewer)<p class="meta">Ditinjau oleh: {{ $submission->reviewer->name }}@if($submission->review_notes) · Catatan: {{ $submission->review_notes }}@endif</p>@endif
            @foreach($exporter->details($submission) as $detail)
                <p class="field-label">{{ $detail['label'] }}</p>
                @if($detail['table'])
                    @if(empty($detail['rows']))<p class="empty">Tidak ada data.</p>@else
                        <table><thead><tr>@foreach($detail['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr></thead><tbody>
                            @foreach($detail['rows'] as $row)<tr>@foreach($detail['columns'] as $column)<td>{{ $row[$column['label']] ?? '—' }}</td>@endforeach</tr>@endforeach
                        </tbody></table>
                    @endif
                @else
                    <p>{{ $detail['value'] !== '' ? $detail['value'] : '—' }}</p>
                @endif
            @endforeach
        </div>
    @empty
        <p class="empty">Tidak ada submission sesuai filter.</p>
    @endforelse
</body>
</html>
