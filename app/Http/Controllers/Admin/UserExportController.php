<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportController extends Controller
{
    /**
     * Download the accounts matching the current search as CSV.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $users = User::query()
            ->search($request->string('search')->trim()->limit(100, '')->toString())
            ->with('registration:id,user_id,code,payment_status,bib_number')
            ->lazyById(500);

        return CsvExport::download(
            CsvExport::filename('users'),
            ['Name', 'Email', 'Authentication', 'Last Login', 'Account Created', 'Registration ID', 'Registration Status', 'BIB Number'],
            $users,
            fn (User $user): array => [
                CsvExport::text($user->name),
                CsvExport::text($user->email),
                $user->authenticationMethod(),
                $user->last_login_at?->format('Y-m-d H:i'),
                $user->created_at?->format('Y-m-d H:i'),
                $user->registration?->code,
                $user->registration?->payment_status->accountStatusLabel() ?? 'Not Registered',
                $user->registration?->bib_number,
            ],
        );
    }
}
