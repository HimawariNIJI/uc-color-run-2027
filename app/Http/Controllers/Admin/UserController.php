<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->limit(100, '')->toString();

        $users = User::query()
            ->search($search)
            ->with('registration:id,user_id,code,payment_status,bib_number')
            ->latestLogin()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Account details. Requests from the admin modal receive only the modal content.
     */
    public function show(Request $request, User $user): View|string
    {
        $user->load('registration:id,user_id,code,payment_status,bib_number');

        return view('admin.users.show', ['user' => $user])
            ->fragmentIf($request->hasHeader('X-Admin-Modal'), 'details');
    }
}
