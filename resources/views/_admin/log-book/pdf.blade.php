<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Log Book</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .log-item {
            border-bottom: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .holiday-row {
            background-color: #fee2e2;
        }
        .log-header {
            margin-bottom: 10px;
        }
        .log-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        .holiday-badge {
            display: inline-block;
            background-color: #ef4444;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .log-date {
            font-size: 12px;
            color: #666;
            margin: 0 0 10px 0;
        }
        .log-desc {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .images-container {
            margin-top: 10px;
        }
        .images-container img {
            max-width: 700px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

    <h2>Laporan Log Book Harian</h2>

    @foreach($data as $item)
        @php
            $isHoliday = $item->is_holiday ?? false;
            $isWeekend = $item->is_weekend ?? false;
            $isEmpty = $item->is_empty ?? false;
            $rowClass = ($isHoliday || $isWeekend) ? 'holiday-row' : '';
        @endphp
        <div class="log-item {{ $rowClass }}">
            <div class="log-header">
                <p class="log-date">{{ $item->log_date ? \Carbon\Carbon::parse($item->log_date)->translatedFormat('l, d F Y') : '-' }}</p>
                
                @if($isHoliday)
                    <div class="holiday-badge">Libur Nasional: {{ $item->holiday_name }}</div>
                @endif
                
                @if($isEmpty)
                    @if($isWeekend && !$isHoliday)
                        <h3 class="log-title" style="color:#ef4444;">Libur Akhir Pekan</h3>
                    @elseif(!$isHoliday)
                        <h3 class="log-title" style="color:#999;">Belum Diisi</h3>
                    @endif
                @else
                    <h3 class="log-title">{{ $item->title }}</h3>
                @endif
            </div>
            
            @if(!$isEmpty)
                <div class="log-desc">
                    @if (isset($item->attendance_status))
                        @if ($item->attendance_status === 'masuk')
                            <div class="holiday-badge" style="background-color: #3b82f6;">Hadir: Masuk</div>
                        @elseif ($item->attendance_status === 'izin')
                            <div class="holiday-badge" style="background-color: #eab308; color: #000;">Hadir: Izin</div>
                        @elseif ($item->attendance_status === 'izin_sakit')
                            <div class="holiday-badge" style="background-color: #f97316;">Hadir: Izin Sakit</div>
                        @endif
                    @endif
                    <br>
                    {!! \Illuminate\Support\Str::markdown($item->description) !!}
                </div>
            @endif

            @if(isset($item->images) && count($item->images) > 0)
                <div class="images-container">
                    @foreach($item->images as $img)
                        @php
                            $path = storage_path('app/public/' . $img->path);
                            // Convert file to base64 so dompdf doesn't complain about paths
                            if (file_exists($path)) {
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $dataStr = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($dataStr);
                            } else {
                                $base64 = '';
                            }
                        @endphp
                        @if($base64)
                            <img src="{{ $base64 }}" alt="Log Image">
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

</body>
</html>
