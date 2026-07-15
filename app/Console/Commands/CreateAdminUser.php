<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worklog:create-admin
        {--name= : Az adminisztrátor neve}
        {--email= : Az adminisztrátor e-mail-címe}
        {--password= : Az adminisztrátor jelszava}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Biztonságosan létrehozza az első Worklog adminisztrátort';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: text('Név', required: true));
        $email = (string) ($this->option('email') ?: text('E-mail-cím', required: true));
        $plainPassword = (string) ($this->option('password') ?: password('Jelszó', required: true));
        $passwordConfirmation = (string) ($this->option('password') ?: password('Jelszó megerősítése', required: true));

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $plainPassword,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(User::class),
                    Rule::unique(RegistrationRequest::class),
                ],
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
            [
                'required' => 'A(z) :attribute megadása kötelező.',
                'string' => 'A(z) :attribute csak szöveg lehet.',
                'max.string' => 'A(z) :attribute legfeljebb :max karakter lehet.',
                'min.string' => 'A(z) :attribute legalább :min karakter hosszú legyen.',
                'email' => 'Az e-mail-cím formátuma nem megfelelő.',
                'lowercase' => 'Az e-mail-cím csak kisbetűket tartalmazhat.',
                'unique' => 'Ezzel az e-mail-címmel már létezik felhasználó vagy függő regisztráció.',
                'confirmed' => 'A jelszó és a megerősítése nem egyezik.',
            ],
            [
                'name' => 'név',
                'email' => 'e-mail-cím',
                'password' => 'jelszó',
            ],
        );

        if ($validator->fails()) {
            $this->error('Az adminisztrátor nem jött létre. Javítsd a következő hibákat:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => Str::lower($email),
            'password' => $plainPassword,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info('Az adminisztrátori fiók létrejött.');

        return self::SUCCESS;
    }
}
