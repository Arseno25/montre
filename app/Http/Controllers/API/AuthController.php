<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
  public function register(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'message' => 'Validation Error',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      'password' => Hash::make($request->password),
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
      'status' => true,
      'message' => 'User registered successfully',
      'data' => [
        'user' => $user,
        'token' => $token
      ]
    ], 201);
  }

  public function login(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|string|email',
      'password' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'message' => 'Validation Error',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json([
        'status' => false,
        'message' => 'Invalid credentials'
      ], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
      'status' => true,
      'message' => 'Login successful',
      'data' => [
        'user' => $user,
        'token' => $token
      ]
    ]);
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
      'status' => true,
      'message' => 'Successfully logged out'
    ]);
  }

  public function profile(Request $request)
  {
    return response()->json([
      'status' => true,
      'data' => [
        'user' => $request->user()
      ]
    ]);
  }
}
