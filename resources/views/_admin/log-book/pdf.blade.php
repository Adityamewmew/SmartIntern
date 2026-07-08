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
            padding-bottom: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .log-header {
            margin-bottom: 10px;
        }
        .log-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 5px 0;
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
        <div class="log-item">
            <div class="log-header">
                <h3 class="log-title">{{ $item->title }}</h3>
                <p class="log-date">{{ $item->log_date ? \Carbon\Carbon::parse($item->log_date)->translatedFormat('l, d F Y') : '-' }}</p>
            </div>
            
            <div class="log-desc">
                {{ $item->description }}
            </div>

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
