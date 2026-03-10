<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function users()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        return Inertia::render('Admin/Users', [
            'users' => User::select('id', 'name', 'email', 'role', 'created_at')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:user,admin',
        ]);

        $user->update($request->only('name', 'email', 'role'));

        return back();
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return back();
    }
}
