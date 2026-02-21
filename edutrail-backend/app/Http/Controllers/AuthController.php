<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'session' => Auth::check() ? true : null,
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => Auth::user(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        try {
            // ✅ Standard Laravel fields (expects password_confirmation automatically)
            $data = $request->validate([
                'firstname' => ['required', 'string', 'max:255'],
                'lastname'  => ['required', 'string', 'max:255'],
                'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
                'password'  => ['required', 'string', 'min:6', 'confirmed'],
            ]);

            $user = User::create([
                'name'     => $data['firstname'] . ' ' . $data['lastname'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return response()->json([
                'data'  => ['user' => $user],
                'error' => null,
            ], 201);

        } catch (ValidationException $e) {
            // ✅ THIS WILL SHOW YOU EXACTLY:
            // - which field is missing
            // - and what payload Laravel actually received
            return response()->json([
                'data'  => null,
                'error' => [
                    'message'  => 'Validation failed',
                    'details'  => $e->errors(),
                    'received' => $request->all(),
                ],
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'data'  => null,
                'error' => [
                    'message' => 'Unexpected error during registration',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'data'  => null,
                'error' => ['message' => 'Invalid email or password'],
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'data'  => ['user' => Auth::user()],
            'error' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            if (Auth::check()) {
                Auth::logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'data'  => ['success' => true],
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'data'  => null,
                'error' => ['message' => 'Logout failed'],
            ], 500);
        }
    }
}
