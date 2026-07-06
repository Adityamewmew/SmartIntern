@extends('_admin._layout.app')

@section('title', 'Detail Log Book')


@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white overflow-hidden shadow-lg rounded-2xl dark:bg-neutral-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700 flex items-center">
                <a href="{{ route('admin.log_book.index') }}"
                    class="py-3 px-3 inline-flex items-center gap-x-2 text-xl rounded-xl border border-gray-200 bg-white text-gray-800 shadow-md hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700 cursor-pointer">
                    <svg class="shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="90" height="90"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7" />
                        <path d="M19 12H5" />
                    </svg>
                </a>
                <div class="ms-3">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                        Detail Log Book
                    </h2>
                </div>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $data->title }}</h3>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-neutral-400">
                        <span>
                            {{ $data->log_date ? \Carbon\Carbon::parse($data->log_date)->translatedFormat('d F Y') : '-' }}
                        </span>
                    </div>
                </div>

                @if (!empty($data->description))
                    <div class="mb-6">
                        <p class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wide font-semibold mb-2">
                            Deskripsi
                        </p>
                        <p class="text-sm text-gray-700 dark:text-neutral-200 whitespace-pre-line">{{ $data->description }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @if (!empty($data->created_at))
                        <div class="p-4 bg-gray-50 rounded-xl dark:bg-neutral-700/50 border border-gray-100 dark:border-neutral-700">
                            <p class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wide font-semibold mb-1">
                                Dibuat Pada</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">
                                {{ \Carbon\Carbon::parse($data->created_at)->translatedFormat('d F Y, H:i') }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-neutral-500 mt-0.5">
                                {{ \Carbon\Carbon::parse($data->created_at)->diffForHumans() }}
                            </p>
                        </div>
                    @endif

                    @if (!empty($data->updated_at))
                        <div class="p-4 bg-gray-50 rounded-xl dark:bg-neutral-700/50 border border-gray-100 dark:border-neutral-700">
                            <p class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wide font-semibold mb-1">
                                Terakhir Diupdate</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">
                                {{ \Carbon\Carbon::parse($data->updated_at)->translatedFormat('d F Y, H:i') }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-neutral-500 mt-0.5">
                                {{ \Carbon\Carbon::parse($data->updated_at)->diffForHumans() }}
                            </p>
                        </div>
                    @endif
                </div>

                @if (!empty($images))
                    <div class="mb-6">
                        <p class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wide font-semibold mb-2">
                            Gambar
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            @foreach ($images as $img)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($img->path) }}"
                                    target="_blank" class="block">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($img->path) }}"
                                        alt="{{ $img->original_name }}"
                                        class="w-full aspect-square object-cover rounded-xl border border-gray-200 dark:border-neutral-700 hover:opacity-80 transition">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-start gap-x-2">
                    <x-admin.button href="{{ route('admin.log_book.update', $data->id) }}" color="primary">
                        @include('_admin._layout.icons.pencil')
                        Edit Data
                    </x-admin.button>
                </div>
            </div>
        </div>
    </div>
@endsection
