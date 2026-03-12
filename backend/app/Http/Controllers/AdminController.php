<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function listUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

        public function makeAdmin($id)
    {
        $user = User::findOrFail($id);
    
    // Verifica se já é admin
    if ($user->is_admin) {
        return response()->json([
            'message' => 'Usuário já é administrador'
        ], 400);
    }
    
        $user->is_admin = true;
        $user->save();
    
        return response()->json([
            'message' => 'Usuário promovido a administrador com sucesso',
            'user' => $user
        ]);
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'is_admin' => 'sometimes|boolean'
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('is_admin')) $user->is_admin = $request->is_admin;

        $user->save();

        return response()->json(['message' => 'Usuário atualizado', 'user' => $user]);
    }

    public function stats()
{
    $totalUsers = User::count();
    $totalAdmins = User::where('is_admin', true)->count();
    $newToday = User::whereDate('created_at', today())->count();

    return response()->json([
        'totalUsers' => $totalUsers,
        'totalAdmins' => $totalAdmins,
        'newToday' => $newToday
    ]);
}

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuário deletado']);
    }
}