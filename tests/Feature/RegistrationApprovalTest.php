<?php

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('public registrations stay pending and an administrator can be created from the command line', function () {
    $this->post(route('register'), [
        'name' => 'Jelentkező', 'email' => 'user@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('registration.pending'));

    $this->assertGuest();
    $this->assertDatabaseHas('registration_requests', ['email' => 'user@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'user@example.com']);

    $this->artisan('worklog:create-admin', [
        '--name' => 'Első Admin',
        '--email' => 'admin@example.com',
        '--password' => 'password',
    ])
        ->expectsOutput('Az adminisztrátori fiók létrejött.')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue();
});

test('admin creation clearly reports mismatched password confirmation', function () {
    $this->artisan('worklog:create-admin')
        ->expectsQuestion('Név', 'Teszt Admin')
        ->expectsQuestion('E-mail-cím', 'admin@example.com')
        ->expectsQuestion('Jelszó', 'password')
        ->expectsQuestion('Jelszó megerősítése', 'different-password')
        ->expectsOutputToContain('Az adminisztrátor nem jött létre.')
        ->expectsOutputToContain('A jelszó és a megerősítése nem egyezik.')
        ->doesntExpectOutputToContain('validation.confirmed')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
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

test('approval reports an email collision instead of failing with a server error', function () {
    $admin = User::factory()->admin()->create();
    $request = RegistrationRequest::factory()->create([
        'email' => 'collision@example.com',
        'password' => Hash::make('password'),
    ]);
    User::factory()->create(['email' => 'collision@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.registrations.approve', $request))
        ->assertSessionHasErrors('email');

    $this->assertDatabaseHas('registration_requests', ['id' => $request->id]);
});

test('registration requests are rate limited per email address', function () {
    $payload = [
        'name' => 'Rate Limited',
        'email' => 'rate-limit@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $this->post(route('register'), $payload)->assertRedirect(route('registration.pending'));
    $this->post(route('register'), $payload)->assertSessionHasErrors('email');
    $this->post(route('register'), $payload)->assertSessionHasErrors('email');
    $this->post(route('register'), $payload)->assertTooManyRequests();
});

test('the registration request list exposes paginator metadata', function () {
    $admin = User::factory()->admin()->create();
    RegistrationRequest::factory()->count(21)->create();

    $this->actingAs($admin)
        ->get(route('admin.registrations.index', ['page' => 2]))
        ->assertInertia(fn ($page) => $page
            ->component('admin/Registrations')
            ->where('requests.current_page', 2)
            ->where('requests.total', 21)
            ->has('requests.data', 1)
            ->has('requests.links'));
});

test('pending applicants with the correct password receive the waiting message', function () {
    $pending = RegistrationRequest::factory()->create(['email' => 'wait@example.com', 'password' => Hash::make('password')]);

    $this->post(route('login'), ['email' => $pending->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'A regisztrációd adminisztrátori jóváhagyásra vár.']);
});
