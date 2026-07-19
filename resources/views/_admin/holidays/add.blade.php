@extends('_admin._layout.app')

@section('title', 'Tambah Hari Libur')

@section('content')
    <div class="max-w-5xl">
        <div class="bg-white overflow-hidden shadow-lg rounded-2xl dark:bg-neutral-800 border-2 border-gray-100 dark:border-neutral-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700 flex items-center">
                <a href="{{ route('admin.holidays.index') }}"
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
                        Tambah Hari Libur
                    </h2>
                </div>
            </div>

            <form class="p-6" navigate-form action="{{ route('admin.holidays.create') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <x-admin.input type="text" id="holiday_date" name="holiday_date" label="Tanggal" class="datepicker"
                        placeholder="Pilih tanggal" required autocomplete="off" value="{{ old('holiday_date') }}"
                        error="{{ $errors->first('holiday_date') }}" />

                    <x-admin.input type="text" id="holiday_name" name="holiday_name" label="Nama Hari Libur"
                        placeholder="Contoh: Tahun Baru Masehi" required value="{{ old('holiday_name') }}"
                        error="{{ $errors->first('holiday_name') }}" />
                </div>

                <div class="mt-4 flex justify-start gap-x-2">
                    <x-admin.button href="{{ route('admin.holidays.index') }}" color="outline-secondary">
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
@endsection
