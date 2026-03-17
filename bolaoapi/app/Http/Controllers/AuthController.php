<?php
namespace App\Http\Controllers;

use App\Models\User;           
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;      
use Tymon\JWTAuth\Facades\JWTAuth; 
use Illuminate\Support\Str;
use App\Jobs\SendVerificationEmail;

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
        'username'                 => $request->username,
        'email'                    => $request->email,
        'password'                 => Hash::make($request->password),
        'email_verified_at'        => null,
        'email_verification_token' => Str::random(64),
    ]);

    SendVerificationEmail::dispatch($user);

    return response()->json([
        'message' => 'Cadastro realizado! Verifique seu e-mail para ativar a conta.',
        'user'    => $user->only(['id', 'username', 'email']),
    ], 201);
}

public function verifyEmail(string $token)
{
    $user = User::where('email_verification_token', $token)->first();

    if (!$user) {
        return response()->json(['message' => 'Token inválido ou já utilizado.'], 404);
    }

    $user->update([
        'email_verified_at'        => now(),
        'email_verification_token' => null,
    ]);

    $jwtToken = JWTAuth::fromUser($user);

    return response()->json([
        'message' => 'E-mail verificado com sucesso!',
        'token'   => $jwtToken,
    ]);
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

        $user = auth()->user();

        return response()->json([
            'token'    => $token,
            'is_admin' => (bool) $user->is_admin,   
            'user'     => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'is_admin' => (bool) $user->is_admin,
            ],
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ]);
    }
}