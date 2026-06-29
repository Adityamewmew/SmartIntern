@extends('_admin._layout.auth')

@section('title', 'Login')

@section('content')
    <div class="w-full max-w-md">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('images/logo-light.png') }}" alt="Logo" class="w-auto h-15">
        </div>
        <div class="bg-white shadow-2xl dark:bg-neutral-900 dark:border-neutral-700 rounded-2xl p-6 ">
            <div class="text-center mb-8">
                <div>
                    <h1 class="block text-3xl font-bold text-gray-800 dark:text-white">Login Aplikasi</h1>
                    <p class="mt-2 text-sm text-gray-400 dark:text-neutral-400">
                        Silahkan login menggunakan Akun anda
                    </p>
                </div>
            </div>

            <form id="login-form" action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="grid gap-y-4">
                    @error('login_error')
                        <div class="bg-red-50 border border-red-200 text-sm text-red-600 rounded-lg p-4 mb-4 dark:bg-red-800/10 dark:border-red-900 dark:text-red-500"
                            role="alert" tabindex="-1" aria-labelledby="hs-soft-color-danger-label">
                            <span id="hs-soft-color-danger-label" class="font-bold"></span> {{ $message }}
                        </div>
                    @enderror

                    <!-- Form Group -->
                    <div>
                        <label for="email" class="block text-sm mb-2 dark:text-white">Akun (Email / NPSN)</label>
                        <div class="relative">
                            <input type="text" id="email" name="email"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                required aria-describedby="email-error">
                            <div class="hidden absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor"
                                    viewBox="0 0 16 16" aria-hidden="true">
                                    <path
                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- End Form Group -->

                    <!-- Form Group -->
                    <div>
                        <div class="flex justify-between items-center">
                            <label for="password" class="block text-sm mb-2 dark:text-white">Password</label>
                        </div>
                        <div class="relative">
                            <input type="password" id="password" name="password"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                required aria-describedby="password-error">
                            <div class="hidden absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor"
                                    viewBox="0 0 16 16" aria-hidden="true">
                                    <path
                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- End Form Group -->

                    <button type="submit" id="login-btn"
                        class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-lg font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 cursor-pointer">
                        <span id="btn-text">M A S U K</span>
                        <span id="btn-spinner"
                            class="animate-spin size-4 border-[3px] border-current border-t-transparent text-white rounded-full hidden"
                            role="status" aria-label="loading">
                            <span class="sr-only">Loading...</span>
                        </span>
                        <span id="btn-loading-text" class="hidden">Loading...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('login-btn');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const btnLoadingText = document.getElementById('btn-loading-text');

            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
            btnText.classList.add('hidden');
            btnSpinner.classList.remove('hidden');
            btnSpinner.classList.add('inline-block');
            btnLoadingText.classList.remove('hidden');
        });
    </script>
    </div>
@endsection
