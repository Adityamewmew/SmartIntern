@extends('_admin._layout.app')

@section('title', 'Tambah Log Book')

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
@endpush

@section('content')
    <div class="max-w-5xl">
        <div class="bg-white overflow-hidden shadow-lg rounded-2xl dark:bg-neutral-800 border-2 border-gray-100 dark:border-neutral-700">
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
                        Tambah Log Book
                    </h2>
                </div>
            </div>

            <form id="add-form" class="p-6" navigate-form action="{{ route('admin.log_book.create') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="space-y-4">
                    {{-- Date --}}
                    <x-admin.input type="text" id="log_date" name="log_date" label="Tanggal" class="datepicker"
                        placeholder="Pilih tanggal" required autocomplete="off" value="{{ old('log_date', request('date', date('Y-m-d'))) }}"
                        error="{{ $errors->first('log_date') }}" />

                    {{-- Attendance Status --}}
                    <div>
                        <label class="block text-sm font-medium mb-2 dark:text-white">Kehadiran</label>
                        <div class="flex gap-x-6">
                            <div class="flex items-center">
                                <input type="radio" name="attendance_status" id="attendance_masuk" value="masuk" class="shrink-0 mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" {{ old('attendance_status', 'masuk') === 'masuk' ? 'checked' : '' }}>
                                <label for="attendance_masuk" class="text-sm text-gray-500 ms-2 dark:text-neutral-400">Masuk</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" name="attendance_status" id="attendance_izin" value="izin" class="shrink-0 mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" {{ old('attendance_status') === 'izin' ? 'checked' : '' }}>
                                <label for="attendance_izin" class="text-sm text-gray-500 ms-2 dark:text-neutral-400">Izin</label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" name="attendance_status" id="attendance_izin_sakit" value="izin_sakit" class="shrink-0 mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" {{ old('attendance_status') === 'izin_sakit' ? 'checked' : '' }}>
                                <label for="attendance_izin_sakit" class="text-sm text-gray-500 ms-2 dark:text-neutral-400">Izin Sakit</label>
                            </div>
                        </div>
                        @error('attendance_status')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Title --}}
                    <x-admin.input type="text" id="title" name="title" label="Judul"
                        placeholder="Contoh: Meeting dengan tim" required
                        error="{{ $errors->first('title') }}" />

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium mb-2 dark:text-white">Deskripsi</label>
                        <textarea id="description" name="description" rows="4"
                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 placeholder-neutral-300 dark:placeholder-neutral-500 dark:focus:ring-neutral-600 @error('description') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror"
                            placeholder="Rincian aktivitas harian">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Images --}}
                    <div>
                        <label for="images" class="block text-sm font-medium mb-2 dark:text-white">Gambar</label>
                        <div
                            class="relative rounded-2xl border border-dashed border-gray-300 bg-gray-50 transition hover:border-blue-400 hover:bg-blue-50/40 dark:border-neutral-700 dark:bg-neutral-900/40 dark:hover:border-blue-500 dark:hover:bg-blue-900/10 @error('images') border-red-500 @enderror">
                            <input type="file" id="images" name="images[]" multiple accept="image/png,image/jpeg"
                                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                            <div class="flex flex-col items-center justify-center px-6 py-8 text-center">
                                <svg class="mb-3 size-8 text-gray-400 dark:text-neutral-500" xmlns="http://www.w3.org/2000/svg"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" x2="12" y1="3" y2="15" />
                                </svg>
                                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">Klik untuk pilih foto atau seret ke sini</span>
                                <span id="images-helper-text" class="mt-1 text-xs text-gray-500 dark:text-neutral-400">Belum ada foto dipilih</span>
                            </div>
                        </div>
                        <div id="images-preview" class="mt-3 hidden grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5"></div>
                        <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1">Bisa pilih lebih dari 1 foto. Maksimal 10 foto, masing-masing 2MB.</p>
                        @error('images')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                        @error('images.*')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 flex justify-start gap-x-2">
                    <x-admin.button href="{{ route('admin.log_book.index') }}" color="outline-secondary">
                        Batal
                    </x-admin.button>
                    <x-admin.button type="submit" color="primary">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        Simpan Data
                    </x-admin.button>
                </div>
            </form>
        </div>
    </div>

    <!-- EasyMDE -->
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new EasyMDE({
                element: document.getElementById('description'),
                spellChecker: false,
                placeholder: "Rincian aktivitas harian...",
                hideIcons: ["guide", "fullscreen", "side-by-side"]
            });
        });

        const addImagesInput = document.getElementById('images');
        const addImagesPreview = document.getElementById('images-preview');
        const addImagesHelperText = document.getElementById('images-helper-text');
        
        let accumulatedFiles = [];

        function renderPreview() {
            const dt = new DataTransfer();
            accumulatedFiles.forEach(file => dt.items.add(file));
            addImagesInput.files = dt.files;

            addImagesPreview.innerHTML = '';

            if (!accumulatedFiles.length) {
                addImagesPreview.classList.add('hidden');
                addImagesHelperText.textContent = 'Belum ada foto dipilih';
                return;
            }

            addImagesHelperText.textContent = accumulatedFiles.length + ' foto dipilih';
            addImagesPreview.classList.remove('hidden');

            accumulatedFiles.forEach(function(file, index) {
                const reader = new FileReader();

                reader.onload = function(loadEvent) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'relative overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-neutral-700 dark:bg-neutral-800';
                    previewItem.innerHTML =
                        '<img src="' + loadEvent.target.result + '" alt="' + file.name + '" class="h-28 w-full object-cover">' +
                        '<div class="px-3 py-2 text-xs text-gray-500 dark:text-neutral-400 truncate">' + file.name + '</div>' +
                        '<button type="button" class="absolute top-1 end-1 inline-flex items-center justify-center size-6 text-xs font-semibold rounded-full bg-red-600 text-white hover:bg-red-700 focus:outline-none cursor-pointer" onclick="removeAddedImage(' + index + ')" title="Hapus gambar dari antrean">' +
                        '<svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>';
                    addImagesPreview.appendChild(previewItem);
                };

                reader.readAsDataURL(file);
            });
        }

        window.removeAddedImage = function(index) {
            accumulatedFiles.splice(index, 1);
            renderPreview();
        };

        if (addImagesInput && addImagesPreview && addImagesHelperText) {
            addImagesInput.addEventListener('change', function(event) {
                const newFiles = Array.from(event.target.files || []);
                
                newFiles.forEach(file => {
                    const exists = accumulatedFiles.some(f => f.name === file.name && f.size === file.size);
                    if (!exists) {
                        accumulatedFiles.push(file);
                    }
                });

                renderPreview();
            });
        }
    </script>
@endsection

@push('scripts')
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <script>
        (function() {
            function initEasyMDE() {
                var el = document.getElementById('description');
                if (el && typeof EasyMDE !== 'undefined') {
                    // Prevent multiple initializations
                    if (el.nextElementSibling && el.nextElementSibling.classList.contains('EasyMDEContainer')) {
                        return;
                    }
                    new EasyMDE({
                        element: el,
                        spellChecker: false,
                        placeholder: "Rincian aktivitas harian (mendukung Markdown)...",
                        toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview", "guide"]
                    });
                }
            }

            // Run immediately if loaded
            if (typeof EasyMDE !== 'undefined') {
                initEasyMDE();
            } else {
                // Check periodically if script was loaded asynchronously
                var checkInterval = setInterval(function() {
                    if (typeof EasyMDE !== 'undefined') {
                        clearInterval(checkInterval);
                        initEasyMDE();
                    }
                }, 100);
                
                // Clear interval after 5 seconds just in case
                setTimeout(function() {
                    clearInterval(checkInterval);
                }, 5000);
            }
        })();
    </script>
@endpush
