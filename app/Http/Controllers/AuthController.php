<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        JWTAuth::factory()->setTTL(10080);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'type' => 'required|in:admin,client',
        ]);

        $guard = $request->type;

        $credentials = $request->only('email', 'password');

        if ($token = auth($guard)->attempt($credentials)) {
            if ($guard === 'client') {
                $user = Client::find(auth($guard)->user()->id);
                $user->update(['last_login_at' => now()]);
            }
            if ($guard === 'admin') {
                $user = Admin::find(auth($guard)->user()->id);
                $user->update(['last_login_at' => now()]);
            }
            return $this->respondWithToken($token, $guard);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function me(Request $request)
    {
        $guard = $request->header('User-Type') ?? 'client';
        return response()->json(auth($guard)->user());
    }

    public function logout(Request $request)
    {
        $guard = $request->header('User-Type') ?? 'client';
        auth($guard)->logout();
        return response()->json(['message' => 'Logged out']);
    }

    public function refresh(Request $request)
    {
        $guard = $request->header('User-Type') ?? 'client';
        return $this->respondWithToken(auth($guard)->refresh(), $guard);
    }

    protected function respondWithToken($token, $guard)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user_type' => $guard,
            'expires_in' => auth($guard)->factory()->getTTL() * 60,
        ]);
    }

    public function changePassword(Request $request)
    {
        $guard = auth()->getDefaultDriver(); // or use $request->header('User-Type') if you prefer
        $user = auth($guard)->user();

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['error' => 'Old password is incorrect'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
   
}