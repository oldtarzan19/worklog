<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('email delivery recovery and verification infrastructure is unavailable', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/verify-email')->assertNotFound();

    expect(Schema::hasTable('password_reset_tokens'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'email_verified_at'))->toBeFalse();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password and receive a Hungarian error', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'A megadott e-mail-cím vagy jelszó hibás.']);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
