# Scratchpad — Fitur Log Book Harian

Dibuat dari eksekusi `plan.md` task-by-task. Tanggal: 2026-07-01.

---

## Yang sudah dikerjakan

### Task 1 — Struktur data `daily_logs`
- `db-migrator-with-drizzle/src/db/schema.ts` — tambah `dailyLogsTable` (12 kolom: id, user_id FK→users, log_date, title, description, status default `draft`, audit created/updated/deleted_by + timestamps). Index pada `user_id`, `log_date`, `status`. Import `date` drizzle ditambahkan.
- `app/Constants/DatabaseConst.php` — tambah `DAILY_LOG(): string` → `self::DB_CORE().'.daily_logs'`.
- `db-migrator-with-drizzle/drizzle/0001_daily_logs.sql` — ter-generate via `bun run db:generate` (nama `daily_logs`).
- Migration dijalankan `bun run db:migrate`. Tabel verified hidup di DB `suara`.

### Task 2 — Usecase + Const
- `app/Constants/LogBookConst.php` — konstanta status `DRAFT/IN_PROGRESS/DONE` + `getStatusOptions()` helper.
- `app/Usecase/LogBookUsecase.php` — extends `Usecase`. Method: `getAll`, `getByID`, `create`, `update`, `delete`. Filter list: `keywords` (title/desc OR), `status`, `log_date_from`, `log_date_to`. Query Builder `DB::table(DatabaseConst::DAILY_LOG())`, soft delete via `deleted_at/deleted_by`, audit trail manual `now()`. Return via `Response::build*`.
- `tests/Feature/LogBookTest.php` — 4 test: guest redirect login, route terdaftar (7 route), superadmin buka index OK, superadmin buka add OK.

### Task 3 — Controller + Route
- `app/Http/Controllers/Admin/LogBookController.php` — pola identik `UserController`: `$page`, `$baseRedirect`, inject `LogBookUsecase` constructor property promotion. Method `index/add/doCreate/detail/update/doUpdate/delete`, return type eksplisit.
- `routes/web.php` — import `LogBookController`, group baru `admin/log-book` name `log_book.`, middleware `access_type:1` (superadmin). 7 route sesuai plan.

### Task 4 — View CRUD
- `resources/views/_admin/log-book/{index,add,update,detail}.blade.php` — extend `_admin._layout.app`. Pakai `x-admin.page-header`, `x-admin.button`, `x-admin.input`, `x-admin.select`, `x-admin.table.*`, `x-admin.empty-state`. Filter GET form (keyword + status select + date from/to). Tabel: tanggal, judul, status badge (warna per status), deskripsi ringkas (Str::limit 60), aksi view/edit/delete. Delete pakai modal konfirmasi + CSRF + `@method('DELETE')`. Update pre-fill value via `old('field', $data->field)`.

### Task 5 — Sidebar + Dashboard + Verifikasi
- `resources/views/_admin/_layout/icons/sidebar/log_book.blade.php` — icon book baru (tidak ada icon existing yang cocok).
- `database/seeders/SidebarMenuSeeder.php` — menu `Log Book`, route `admin.log_book.index`, group `utama`, sort_order 60, access superadmin.
- `app/Http/Controllers/Admin/DashboardController.php` — `admin.log_book.index` ditambahkan ke `$allowedRoutes` (gate dashboard sebenarnya).
- `tests/Feature/DashboardModulesTest.php` — log_book ditambah ke mock modul + assertion `assertSee('Log Book')`.
- Re-seed `SidebarMenuSeeder` + `Cache::flush()` dijalankan. Menu verified di DB (id=4, access_type=1).

### Verifikasi
- `vendor/bin/pint --dirty --format agent` → passed.
- `php artisan test --compact` → **15 passed (42 assertions)**.
- DB: tabel `daily_logs` + menu sidebar `Log Book` ter-seed.

---

## Workflow fitur Log Book (alur request)

Pola 3-lapis starter kit: **Controller → Usecase → View**. Tanpa Eloquent, tanpa Repository. Semua query Query Builder.

### 1. Akses & gate
```
Request → middleware auth (login) → middleware access_type:1 (superadmin only) → LogBookController
```
Sidebar menu `Log Book` hanya tampil untuk superadmin (filter `sidebar_menu_accesses`). Dashboard menampilkan kartu modul lewat `getDashboardModules()` LALU difilter hardcoded `$allowedRoutes` di `DashboardController`.

### 2. Baca list (GET /admin/log-book)
```
UserController::index
  → kirim filter (keywords/status/log_date_from/log_date_to) ke LogBookUsecase::getAll
  → Usecase: DB::table(daily_logs)->whereNull('deleted_at')->when(filter...)->paginate(20)
  → return ['success'=>true,'data'=>['list'=>paginator]]
  → controller ekstrak list, kirim ke view _admin/log-book/index.blade.php
  → view render tabel + filter + pagination + empty-state
```

### 3. Buat (POST /admin/log-book/create)
```
Form add.blade.php → POST → LogBookController::doCreate
  → LogBookUsecase::create(data: $request)
     • Validator::make (log_date required|date, title required, status in options)
     • DB::beginTransaction → insert (user_id dari Auth, audit created_by, now()) → commit
     • return Response::buildSuccessCreated()
  → success? redirect index + flash success : redirect back + flash error + withInput
```

### 4. Detail / Edit (GET /admin/log-book/detail/{id} | update/{id})
```
LogBookController::detail|update
  → LogBookUsecase::getByID(id) → DB::table(daily_logs)->whereNull('deleted_at')->where('id',id)->first()
  → empty? redirect intended(baseRedirect) + error
  → else view (detail read-only | update pre-fill form)
```

### 5. Update (POST /admin/log-book/update/{id})
```
LogBookController::doUpdate(id, request)
  → LogBookUsecase::update(data, id) → validasi → DB transaction update (audit updated_by, now()) → commit
  → redirect index + flash
```

### 6. Hapus soft delete (DELETE /admin/log-book/delete/{id})
```
Modal index.blade.php → form DELETE + CSRF → LogBookController::delete(id)
  → LogBookUsecase::delete(id) → DB transaction update deleted_at+deleted_by → commit
  → data fisik TIDAK hilang, hanya di-skip oleh whereNull('deleted_at')
  → redirect index + flash
```

### Konvensi yang dipakai (match starter kit)
- `DatabaseConst::DAILY_LOG()` untuk nama tabel (cross-db prefix via `DB_CORE()`).
- `Response::buildSuccess/buildSuccessCreated/buildErrorService` format return konsisten.
- Soft delete: `deleted_at` + `deleted_by`, bukan hapus fisik.
- Audit trail: `created_by/updated_by/deleted_by` dari `Auth::user()?->id`, timestamp manual `now()`.
- `ResponseConst` untuk pesan sukses/error.
- Flash message `->with('success'/'error')` → auto-render layout.
- Komponen Blade `x-admin.*` + `navigate-form`/`navigate` (Inertia-like navigation).

---

## Catatan
- Tidak dibuat (sesuai scope plan): approval, upload lampiran, notifikasi, export PDF/Excel.
- Untuk role baru (selain superadmin): tambah `access_type` di `UserConst`, daftarkan menu di `SidebarMenuSeeder::assignAccess`, dan buka route via middleware `access_type:N`.
- Setiap menu baru WAJIB didaftarkan di `$allowedRoutes` `DashboardController` agar muncul sebagai kartu modul.
- **Perbaikan Bug Local Domain (FlyEnv)**:
  - Form delete pada Users dan Log Book sebelumnya menggunakan generator absolute URL `{{ url(...) }}`. Ketika diakses via custom local domain seperti `http://smartintern.local`, form action salah mengarah ke `http://localhost/...`, memicu kegagalan session & CSRF token mismatch (error 419).
  - Diperbaiki dengan mengubah URL action di JavaScript menjadi path relatif (seperti pada Sidebar Menu): `/admin/users/delete/${id}` dan `/admin/log-book/delete/${id}`.
  - Atribut `navigate-form` (AJAX SPA) pada form delete Log Book juga dihapus agar disubmit secara native (full reload) untuk mencegah overlay/backdrop modal dari Preline UI macet.
  - Konfigurasi `APP_URL` di `.env` disesuaikan menjadi `http://smartintern.local`.
