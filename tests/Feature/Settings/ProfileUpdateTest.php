<?php

use App\Models\RegistrationRequest;
use App\Models\User;

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('user cannot delete their account so worklog data is retained', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/settings/profile', [
            'password' => 'password',
        ]);

    $response->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});

test('profile email cannot use an address reserved by a pending registration', function () {
    $user = User::factory()->create();
    $request = RegistrationRequest::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $request->email,
        ])
        ->assertSessionHasErrors('email');

    expect($user->refresh()->email)->not->toBe($request->email);
});
