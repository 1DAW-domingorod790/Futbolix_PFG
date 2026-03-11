<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function users()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        return Inertia::render('Admin/Users', [
            'users' => User::with('role:id,name')
                    ->select('id', 'name', 'email', 'role_id', 'created_at')
                    ->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_name' => 'required|in:user,admin',
        ]);

        $request->merge([
            'role_id' => Role::where('name', $request->role_name)->value('id')
        ]);


        $user->update($request->only('name', 'email', 'role_id'));

        return back();
    }

    public function destroy(User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return back();
    }
}
