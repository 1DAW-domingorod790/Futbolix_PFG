<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{

    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'avatar_path' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg'],
            'role_name' => 'required|in:user,admin',
        ]);

        $avatarPath = $request->file('avatar_path') 
            ? $request->file('avatar_path')->store('avatars', 'public')
            : null;

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => Role::where('name', $request->role_name)->value('id'),
            'avatar_path' => $avatarPath ?? "https://api.dicebear.com/9.x/micah/svg" 
                . "?seed=". urlencode($request->name ?? 'User')
                . "&backgroundColor=" . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT) . "," .  str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)
                . "&backgroundType=gradientLinear"
                . "&glassesProbability=50" 
        ]);

        return back();
    }

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
