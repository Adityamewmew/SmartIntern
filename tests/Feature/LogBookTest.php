<?php

use App\Constants\DatabaseConst;
use App\Constants\UserConst;
use App\Models\User;
use App\Usecase\LogBookUsecase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

// App schema is MySQL-only (cross-schema prefix suara.*); sqlite memory used by
// phpunit.xml has no tables, so this file runs against the real mysql connection
// wrapped in a rolled-back transaction.
beforeEach(function () {
    // phpunit.xml forces DB_DATABASE=:memory: which bleeds into the mysql config;
    // restore the real schema before switching the default connection.
    config([
        'database.default' => 'mysql',
        'database.connections.mysql.database' => 'suara',
    ]);
    DB::connection('mysql')->beginTransaction();
});

afterEach(function () {
    DB::connection('mysql')->rollBack();
});

test('guest is redirected to login when opening log book index', function () {
    $this->get(route('admin.log_book.index'))
        ->assertRedirect(route('login'));
});

test('log book routes are registered', function () {
    expect(route('admin.log_book.index'))->toContain('/admin/log-book')
        ->and(route('admin.log_book.add'))->toContain('/admin/log-book/add')
        ->and(route('admin.log_book.create'))->toContain('/admin/log-book/create')
        ->and(route('admin.log_book.detail', 1))->toContain('/admin/log-book/detail/1')
        ->and(route('admin.log_book.update', 1))->toContain('/admin/log-book/update/1')
        ->and(route('admin.log_book.doUpdate', 1))->toContain('/admin/log-book/update/1')
        ->and(route('admin.log_book.delete', 1))->toContain('/admin/log-book/delete/1');
});

test('authenticated superadmin can open log book index', function () {
    $user = User::factory()->make(['id' => 1, 'access_type' => UserConst::SUPERADMIN]);

    $this->actingAs($user)
        ->get(route('admin.log_book.index'))
        ->assertOk();
});

test('authenticated superadmin can open log book add page', function () {
    $user = User::factory()->make(['id' => 1, 'access_type' => UserConst::SUPERADMIN]);

    $this->actingAs($user)
        ->get(route('admin.log_book.add'))
        ->assertOk();
});

test('index filters by month and year', function () {
    $user = User::factory()->create(['access_type' => UserConst::SUPERADMIN]);

    foreach (['2026-01-15' => 'Log Januari', '2026-02-15' => 'Log Februari'] as $date => $title) {
        DB::table(DatabaseConst::DAILY_LOG())->insert([
            'user_id' => $user->id,
            'log_date' => $date,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->actingAs($user)
        ->get(route('admin.log_book.index', ['month' => 1, 'year' => 2026]))
        ->assertOk()
        ->assertSee('Log Januari')
        ->assertDontSee('Log Februari');
});

test('create log book with multiple images stores files and rows', function () {
    Storage::fake('public');
    $user = User::factory()->create(['access_type' => UserConst::SUPERADMIN]);

    $startCount = DB::table(DatabaseConst::DAILY_LOG_IMAGE())->count();

    $this->actingAs($user)
        ->post(route('admin.log_book.create'), [
            'log_date' => '2026-07-03',
            'title' => 'Log Multi Gambar',
            'description' => 'Deskripsi',
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])
        ->assertRedirect(route('admin.log_book.index'));

    expect(DB::table(DatabaseConst::DAILY_LOG_IMAGE())->count())->toBe($startCount + 2)
        ->and(Storage::disk('public')->exists(DB::table(DatabaseConst::DAILY_LOG_IMAGE())->orderBy('id', 'desc')->first()->path))->toBeTrue();
});

test('detail page displays uploaded images', function () {
    Storage::fake('public');
    $user = User::factory()->create(['access_type' => UserConst::SUPERADMIN]);

    $logId = DB::table(DatabaseConst::DAILY_LOG())->insertGetId([
        'user_id' => $user->id,
        'log_date' => '2026-07-03',
        'title' => 'Log Detail Gambar',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table(DatabaseConst::DAILY_LOG_IMAGE())->insert([
        'daily_log_id' => $logId,
        'path' => 'daily-logs/'.$logId.'/test.jpg',
        'original_name' => 'test.jpg',
        'mime' => 'image/jpeg',
        'size' => 100,
        'sort_order' => 0,
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.log_book.detail', $logId))
        ->assertOk()
        ->assertSee('daily-logs/'.$logId.'/test.jpg');
});

test('delete image route removes image row and file', function () {
    Storage::fake('public');
    $user = User::factory()->create(['access_type' => UserConst::SUPERADMIN]);

    $logId = DB::table(DatabaseConst::DAILY_LOG())->insertGetId([
        'user_id' => $user->id,
        'log_date' => '2026-07-03',
        'title' => 'Log Hapus Gambar',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $path = 'daily-logs/'.$logId.'/hapus.jpg';
    Storage::disk('public')->put($path, 'contents');

    $imageId = DB::table(DatabaseConst::DAILY_LOG_IMAGE())->insertGetId([
        'daily_log_id' => $logId,
        'path' => $path,
        'original_name' => 'hapus.jpg',
        'mime' => 'image/jpeg',
        'size' => 8,
        'sort_order' => 0,
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('admin.log_book.delete_image', $imageId))
        ->assertRedirect();

    expect(DB::table(DatabaseConst::DAILY_LOG_IMAGE())->where('id', $imageId)->exists())->toBeFalse()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

test('create rejects non-image file', function () {
    Storage::fake('public');
    $user = User::factory()->create(['access_type' => UserConst::SUPERADMIN]);

    $this->actingAs($user)
        ->post(route('admin.log_book.create'), [
            'log_date' => '2026-07-03',
            'title' => 'Log Invalid',
            'images' => [UploadedFile::fake()->create('doc.txt', 1, 'text/plain')],
        ])
        ->assertSessionHasErrors(['images.0']);
});

test('year options returns current year even when no logs exist', function () {
    $user = User::factory()->make(['id' => 1, 'access_type' => UserConst::SUPERADMIN]);

    $usecase = new LogBookUsecase;
    $years = $usecase->getYearOptions();

    expect($years)->toBeArray()
        ->and($years)->not->toBeEmpty()
        ->and(array_key_first($years))->toBe((int) date('Y'));
});

test('update log book with new images appends image rows', function () {
    Storage::fake('public');
    $user = User::factory()->create(['access_type' => UserConst::SUPERADMIN]);

    $logId = DB::table(DatabaseConst::DAILY_LOG())->insertGetId([
        'user_id' => $user->id,
        'log_date' => '2026-07-01',
        'title' => 'Log Awal',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $beforeCount = DB::table(DatabaseConst::DAILY_LOG_IMAGE())->count();

    $this->actingAs($user)
        ->post(route('admin.log_book.doUpdate', $logId), [
            'log_date' => '2026-07-02',
            'title' => 'Log Diupdate',
            'images' => [
                UploadedFile::fake()->image('new.jpg'),
            ],
        ])
        ->assertRedirect(route('admin.log_book.index'));

    expect(DB::table(DatabaseConst::DAILY_LOG_IMAGE())->count())->toBe($beforeCount + 1);
});

test('calendar view for anggota fills empty weekdays with dummy rows and holiday badge', function () {
    Http::fake([
        'tanggalmerah.upset.dev/*' => Http::response([
            'success' => true,
            'data' => [
                ['date' => '2026-01-01', 'name' => 'Tahun Baru', 'type' => 'holiday'],
                ['date' => '2026-01-05', 'name' => 'Cuti Bersama Palsu', 'type' => 'leave'],
            ],
            'meta' => [],
        ], 200),
    ]);

    $user = User::factory()->create(['access_type' => UserConst::ANGGOTA]);

    DB::table(DatabaseConst::DAILY_LOG())->insert([
        'user_id' => $user->id,
        'log_date' => '2026-01-15',
        'title' => 'Log Kerja Januari',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.log_book.index', ['month' => 1, 'year' => 2026]))
        ->assertOk()
        ->assertSee('Log Kerja Januari')
        ->assertSee('Belum Diisi')
        ->assertSee('Hari Libur Nasional: Tahun Baru')
        ->assertDontSee('Cuti Bersama Palsu')
        ->assertSee('Isi Logbook');
});

test('calendar view renders even when holiday api is unreachable', function () {
    Http::fake([
        'tanggalmerah.upset.dev/*' => Http::response('', 500),
    ]);

    $user = User::factory()->create(['access_type' => UserConst::ANGGOTA]);

    $this->actingAs($user)
        ->get(route('admin.log_book.index', ['month' => 1, 'year' => 2026]))
        ->assertOk()
        ->assertSee('Belum Diisi');
});
