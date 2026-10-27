<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = collect([
            (object) [
                'id' => 1,
                'name' => 'Andi Pratama',
                'email' => 'andi.pratama@example.com',
                'last_login' => '2026-10-27 09:15:00',
                'bib_number' => 'UC27001',
            ],
            (object) [
                'id' => 2,
                'name' => 'Jessica Tan',
                'email' => 'jessica.tan@example.com',
                'last_login' => '2026-10-26 18:30:00',
                'bib_number' => 'UC27002',
            ],
            (object) [
                'id' => 3,
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'last_login' => null,
                'bib_number' => 'UC27003',
            ],
        ]);

        // Search berdasarkan nama
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $users = $users->filter(function ($user) use ($search) {
                return str_contains(strtolower($user->name), $search);
            });
        }

        // Pagination sederhana untuk dummy data
        $users = new \Illuminate\Pagination\LengthAwarePaginator(
            $users->values(),
            $users->count(),
            10,
            1,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.user', compact('users'));
    }
}
