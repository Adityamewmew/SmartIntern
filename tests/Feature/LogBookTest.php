<?php

use App\Constants\UserConst;
use App\Models\User;

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
