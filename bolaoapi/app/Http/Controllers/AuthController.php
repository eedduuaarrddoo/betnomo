<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
   public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required',
    ]);

    $credentials = $request->only('username', 'password');

    if (!$token = auth()->attempt($credentials)) {
        return response()->json(['error' => 'Credenciais inválidas'], 401);
    }

    return response()->json(['token' => $token]);
}
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }
}
