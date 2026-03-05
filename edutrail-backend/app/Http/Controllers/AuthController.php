<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'data'  => null,
                    'error' => ['message' => 'Unauthorized'],
                ], 401);
            }

            // Validate update data
            $data = $request->validate([
                'firstname'     => ['nullable', 'string', 'max:255'],
                'lastname'      => ['nullable', 'string', 'max:255'],
                'nickname'      => ['nullable', 'string', 'max:255'],
                'gender'        => ['nullable', 'string', 'in:Male,Female,Other'],
                'contact_number' => ['nullable', 'string', 'max:20'],
            ]);

            // sanity check: ensure database has required profile columns before doing any work
            $requiredColumns = ['firstname', 'lastname', 'nickname', 'gender', 'contact_number'];
            $missing = [];
            foreach ($requiredColumns as $col) {
                if (!Schema::hasColumn('users', $col)) {
                    $missing[] = $col;
                }
            }
            if (!empty($missing)) {
                // log for the developer
                \Log::error('AuthController::update missing user columns', ['missing' => $missing]);

                return response()->json([
                    'data' => null,
                    'error' => [
                        'message' => 'Database schema not up to date',
                        'details' => 'Missing columns: ' . implode(', ', $missing) . '. Run migrations.',
                    ],
                ], 500);
            }

            // Update only provided fields (skip null values)
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    // skip columns that don't exist even if validation passed
                    if (!Schema::hasColumn('users', $key)) {
                        \Log::warning('AuthController::update skipping unknown column', ['column' => $key]);
                        continue;
                    }
                    $user->$key = $value;
                }
            }

            // Update name if either firstname or lastname was supplied
            // (use safe array access to avoid undefined index errors)
            $firstnameProvided = array_key_exists('firstname', $data);
            $lastnameProvided  = array_key_exists('lastname', $data);

            if ($firstnameProvided || $lastnameProvided) {
                $firstname = $data['firstname'] ?? $user->firstname;
                $lastname  = $data['lastname'] ?? $user->lastname;
                $user->name = trim($firstname . ' ' . $lastname);
            }

            $user->save();

            return response()->json([
                'data'  => ['user' => $user],
                'error' => null,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'data'  => null,
                'error' => [
                    'message' => 'Validation failed',
                    'details' => $e->errors(),
                ],
            ], 422);

        } catch (\Throwable $e) {
            // log full exception for debugging
            \Log::error('AuthController::update error', ['exception' => $e]);

            return response()->json([
                'data'  => null,
                'error' => [
                    'message' => 'Failed to update profile',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
