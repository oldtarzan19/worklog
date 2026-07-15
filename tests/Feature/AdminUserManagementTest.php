<?php

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('normal users cannot access administrator routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
});

test('the last active administrator cannot be disabled or demoted', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::User->value,
        'is_active' => true,
    ])->assertSessionHasErrors('role');
    $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::Admin->value,
        'is_active' => false,
    ])->assertSessionHasErrors('role');
});

test('administrators can update important user account details', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $user), [
        'name' => 'Módosított Név',
        'email' => 'modositott@example.com',
        'role' => UserRole::Admin->value,
        'is_active' => false,
    ])->assertSessionHasNoErrors();

    expect($user->refresh())
        ->name->toBe('Módosított Név')
        ->email->toBe('modositott@example.com')
        ->role->toBe(UserRole::Admin)
        ->is_active->toBeFalse();
});

test('an administrator cannot assign an email address used by another account', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $otherUser->email,
        'role' => UserRole::User->value,
        'is_active' => true,
    ])->assertSessionHasErrors('email');
});

test('an administrator cannot assign an email address used by a pending request', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $request = RegistrationRequest::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $request->email,
        'role' => UserRole::User->value,
        'is_active' => true,
    ])->assertSessionHasErrors('email');
});

test('the administrator user list exposes paginator metadata', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(20)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Users')
            ->where('users.current_page', 2)
            ->where('users.total', 21)
            ->has('users.data', 1)
            ->has('users.links'));
});

test('inactive users cannot log in and active sessions are terminated', function () {
    $user = User::factory()->inactive()->create(['email' => 'inactive@example.com']);
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
