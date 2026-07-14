<?php

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the first registrant becomes an active administrator and later registrations stay pending', function () {
    $this->post(route('register'), [
        'name' => 'Első Admin', 'email' => 'admin@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(User::first()->role)->toBe(UserRole::Admin)
        ->and(User::first()->is_active)->toBeTrue();

    $this->post(route('logout'));
    $this->post(route('register'), [
        'name' => 'Jelentkező', 'email' => 'user@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('registration.pending'));

    $this->assertGuest();
    $this->assertDatabaseHas('registration_requests', ['email' => 'user@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'user@example.com']);
});

test('email addresses must be unique across users and pending requests', function () {
    User::factory()->create(['email' => 'used@example.com']);
    RegistrationRequest::factory()->create(['email' => 'pending@example.com', 'password' => Hash::make('password')]);

    foreach (['used@example.com', 'pending@example.com'] as $email) {
        $this->post(route('register'), ['name' => 'Teszt', 'email' => $email, 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('email');
    }
});

test('an administrator can approve and reject registrations', function () {
    $admin = User::factory()->admin()->create();
    $approved = RegistrationRequest::factory()->create(['password' => Hash::make('password')]);
    $rejected = RegistrationRequest::factory()->create(['password' => Hash::make('password')]);

    $this->actingAs($admin)->post(route('admin.registrations.approve', $approved))->assertRedirect();
    $user = User::query()->where('email', $approved->email)->firstOrFail();
    expect($user->is_active)->toBeTrue();
    $this->assertDatabaseMissing('registration_requests', ['id' => $approved->id]);

    $this->actingAs($admin)->delete(route('admin.registrations.reject', $rejected))->assertRedirect();
    $this->assertDatabaseMissing('registration_requests', ['id' => $rejected->id]);
});

test('pending applicants with the correct password receive the waiting message', function () {
    $pending = RegistrationRequest::factory()->create(['email' => 'wait@example.com', 'password' => Hash::make('password')]);

    $this->post(route('login'), ['email' => $pending->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'A regisztrációd adminisztrátori jóváhagyásra vár.']);
});
