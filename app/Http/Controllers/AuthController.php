<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for user authentication (Register, Login, Logout)
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register
     *
     * Register a new user and return access token.
     *
     * @bodyParam name string required User full name. Example: Mokhtar Ghaleb
     * @bodyParam email string required User email. Example: mokhtar@gmail.com
     * @bodyParam password string required Password (min 6 characters). Example: 123456
     * @bodyParam password_confirmation string required Password confirmation. Example: 123456
     * @bodyParam mobile string required Mobile number. Example: 775038005
     *
     * @response 201 {
     *  "isSuccess": true,
     *  "message": "User registered successfully",
     *  "data": {
     *      "token": "1|asdasdasd...",
     *      "user": {
     *          "id": 1,
     *          "name": "Mokhtar Ghaleb",
     *          "email": "mokhtar@gmail.com",
     *          "mobile": "775038005"
     *      }
     *  }
     * }
     *
     * @response 422 {
     *  "isSuccess": false,
     *  "message": "The email has already been taken.",
     *  "data": null
     * }
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'mobile'   => 'required|string|max:20',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'mobile'   => $validated['mobile'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            'User registered successfully',
            [
                'token' => $token,
                'user'  => $user
            ],
            201
        );
    }


    /**
     * Login
     *
     * Authenticate user and return access token.
     *
     * @bodyParam email string required User email. Example: mokhtar@gmail.com
     * @bodyParam password string required User password. Example: 123456
     *
     * @response 200 {
     *  "isSuccess": true,
     *  "message": "Login successful",
     *  "data": {
     *      "token": "1|asdasdasd...",
     *      "user": {
     *          "id": 1,
     *          "name": "Mokhtar Ghaleb",
     *          "email": "mokhtar@gmail.com",
     *          "mobile": "777123456"
     *      }
     *  }
     * }
     *
     * @response 401 {
     *  "isSuccess": false,
     *  "message": "Invalid credentials",
     *  "data": null
     * }
     *
     * @response 422 {
     *  "isSuccess": false,
     *  "message": "The email field is required.",
     *  "data": null
     * }
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            'Login successful',
            [
                'token' => $token,
                'user'  => $user
            ]
        );
    }


    /**
     * Logout
     *
     * Logout authenticated user and revoke current token.
     *
     * @authenticated
     *
     * @response 200 {
     *  "isSuccess": true,
     *  "message": "Logged out successfully",
     *  "data": null
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logged out successfully');
    }
}