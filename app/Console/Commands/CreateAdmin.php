<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create
    {--name= : Name shown in the admin console}
    {--username= : Username used to sign in}
    {--password= : Password (prompted securely when omitted)}')]
#[Description('Create an account for the UC Color Run 2027 admin console')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $input = [
            'name' => $this->option('name') ?? text(label: 'Name', default: 'Administrator', required: true),
            'username' => Str::lower(trim($this->option('username') ?? text(label: 'Username', required: true))),
            'password' => $this->option('password') ?? password(label: 'Password', required: true, hint: 'At least 8 characters.'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:50', Rule::unique(Admin::class, 'username')],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $admin = Admin::create($validator->validated());

        $this->components->info("Admin account [{$admin->username}] created. Sign in at ".route('admin.login'));

        return self::SUCCESS;
    }
}
