@php
    $columns = $field->tableColumns();
    $rows = $rowsByField->get($field->id, collect())->sortBy('row_index');
@endphp

<div class="mb-4">
    <label class="form-label small fw-semibold d-block">{{ $field->label }}</label>

    @if ($rows->isEmpty())
        <p class="text-muted small mb-0">Tidak ada data.</p>
    @else
        <div class="table-responsive border rounded">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach ($columns as $column)
                            <th class="small">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php($cells = $row->answers->keyBy('column_key'))
                        <tr>
                            @foreach ($columns as $column)
                                @php($cellValue = optional($cells->get($column['key']))->value)
                                <td class="small">{{ $cellValue !== null && $cellValue !== '' ? $cellValue : '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
