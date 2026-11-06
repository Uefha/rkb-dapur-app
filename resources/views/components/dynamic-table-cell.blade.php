@props(['column', 'name', 'value' => null, 'readonly' => false])

@switch($column['type'] ?? 'text')
    @case('select')
        {{-- $column['options'] adalah array string polos (mis. ['Bersih', 'Kotor']),
             BUKAN array {label, value} -- format ini harus persis sama dengan yang
             dihasilkan FormBuilderService::buildFieldConfig(), satu-satunya sumber
             kebenaran struktur config, dipakai baik oleh Builder HTTP maupun
             FormSeeder. Setiap string sekaligus jadi label dan value opsinya. --}}
        <select name="{{ $name }}" data-col="{{ $column['key'] }}" class="form-select form-select-sm" @disabled($readonly)>
            <option value=""></option>
            @foreach ($column['options'] ?? [] as $option)
                <option value="{{ $option }}" @selected((string) $value === (string) $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        @break

    @case('number')
        <input type="number" name="{{ $name }}" data-col="{{ $column['key'] }}" value="{{ $value }}" class="form-control form-control-sm" @readonly($readonly)>
        @break

    @case('date')
        <input type="date" name="{{ $name }}" data-col="{{ $column['key'] }}" value="{{ $value }}" class="form-control form-control-sm" @readonly($readonly)>
        @break

    @case('time')
        <input type="time" name="{{ $name }}" data-col="{{ $column['key'] }}" value="{{ $value }}" class="form-control form-control-sm" @readonly($readonly)>
        @break

    @default
        <input type="text" name="{{ $name }}" data-col="{{ $column['key'] }}" value="{{ $value }}" class="form-control form-control-sm" @readonly($readonly)>
@endswitch
