@php
    // جمع‌آوری همه یادداشت‌ها برای نمایش در جدول جداگانه
    $allNotes = [];
    foreach ($rows as $row) {
        foreach ($row['periods'] as $p => $data) {
            $note = is_array($data) ? ($data['note'] ?? null) : null;
            if (!empty($note)) {
                $allNotes[] = [
                    'date'   => $row['date'],
                    'period' => $p,
                    'note'   => $note,
                ];
            }
        }
    }
@endphp

{{-- جدول اصلی حضور و غیاب --}}
<table class="table table-sm table-bordered align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th style="width: 145px;">تاریخ</th>
            <th class="text-center" style="width: 45px;">ز۱</th>
            <th class="text-center" style="width: 45px;">ز۲</th>
            <th class="text-center" style="width: 45px;">ز۳</th>
            <th class="text-center" style="width: 45px;">ز۴</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td class="small text-nowrap">
                    {{ jdate($row['date'], 'EEEE yyyy/MM/dd') }}
                </td>
                @for($p = 1; $p <= 4; $p++)
                    @php
                        $periodData = $row['periods'][$p] ?? null;
                        $status     = is_array($periodData) ? $periodData['status'] : $periodData;
                    @endphp
                    <td class="text-center" style="font-size: 1.05rem;">
                        {{ \App\Models\Attendance::statusEmoji($status) }}
                    </td>
                @endfor
            </tr>
        @endforeach
    </tbody>
</table>

{{-- جدول یادداشت‌ها (فقط اگر یادداشتی وجود دارد) --}}
@if(!empty($allNotes))
    <table class="table table-sm table-bordered align-middle mt-2 mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 145px;">تاریخ</th>
                <th class="text-center" style="width: 45px;">زنگ</th>
                <th>یادداشت</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allNotes as $n)
                <tr>
                    <td class="small text-nowrap">{{ jdate($n['date'], 'EEEE yyyy/MM/dd') }}</td>
                    <td class="text-center small">{{ $n['period'] }}</td>
                    <td class="small">{{ $n['note'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
