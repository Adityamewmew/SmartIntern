@props(['status'])

@if (isset($status))
    @if ($status === 'masuk')
        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500">Masuk</span>
    @elseif ($status === 'izin')
        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">Izin</span>
    @elseif ($status === 'izin_sakit')
        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-800/30 dark:text-orange-500">Izin Sakit</span>
    @endif
@else
    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-500">-</span>
@endif
