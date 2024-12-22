<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

use function App\Helpers\uploadFile;

class UserController extends Controller
{
    //
    function register(AuthRequest $request)
    {
        // Validate the incoming data
        $validatedData = $request->validate([
            'name' => 'required|min:3|string',
            'email' => 'required|email|unique:users,email', // Email must be unique
            'password' => 'required|string|confirmed|min:6', // Password must match confirmation
        ]);

        // Check if the user already exists (optional, as unique validation is already used)
        $existingUser = User::where('email', $validatedData['email'])->first();
        if ($existingUser) {
            return ApiResponse::sendResponse('failed', 'this user already exists');
        }

        // Create a new user in the database
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']), // Using Hash facade for password
        ]);

        // If the user is created successfully, return a response
        if ($user) {
            return response()->json([
                'data' => $user, // Return the created user data
                'status' => 201, // Correct status code for created resource
                'message' => 'User created successfully'
            ], 201); // Return response with the status code
        }

        
        return ApiResponse::sendResponse('failed', 'Failed to create user', 200);
    }

    function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $token = JWTAuth::attempt($credentials);
        if (!$token) {
            return response()->json(['error' => 'Invalid credentials!', 'status' => 401], 200);
        }

        JWTAuth::setToken($token)->authenticate();
        $user = JWTAuth::user();
        $user->update(['token' => $token]);

        return response()->json(['token' => $token, 'user' => $user, 'status' => 200,], 200);
    }

    function profile()
    {
        try {
            // Try to authenticate the user with the token
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            // Token is valid and user is authenticated
            return response()->json(['status' => 'success', 'message' => 'Token is valid', 'user' => $user, 'status' => 200], 200);
        } catch (TokenExpiredException $e) {
            // Token has expired
            return response()->json(['status' => 'error', 'message' => 'Token has expired'], 401);
        } catch (TokenInvalidException $e) {
            // Token is invalid
            return response()->json(['status' => 'error', 'message' => 'Token is invalid'], 401);
        } catch (JWTException $e) {
            // Token is missing or there was another error
            return response()->json(['status' => 'error', 'message' => 'Token is missing or invalid'], 401);
        }
    }

    function logout()
    {
        // jwt
        JWTAuth::invalidate(JWTAuth::parseToken());
        JWTAuth::user()->update(['token' => null]);

        return response()->json(['message' => "log out successfully", 'status' => 200], 200);
    }



    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user =  JWTAuth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['Current password is incorrect'],
                ],
            ], 422);
        }
        

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully'], 200);
    }


    public function changeProfile(Request $request)
    {
        $request->validate([
            'profile' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $user =  JWTAuth::user();

        if ($request->hasFile('profile')) {
            if ($user->profile) {
                $image = $user->profile;
                $baseUrl = url('storage/') . '/';
                $replaceFile = str_replace($baseUrl, '', $image);
                Storage::disk('public')->delete($replaceFile);
            }
            $validatedData['profile'] = uploadFile($request->profile, 'profiles');
        }
        $user->profile = url('storage/' . $validatedData['profile']);
        $user->save();
        return response()->json(['message' => 'Profile updated successfully', 'profile' => $user->profile], 200);
    }


    public function updateUserDetails(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,',
        ]);

        $user =  JWTAuth::user();
        if ($request->name) {
            $user->name = $request->name;
        }
        if ($request->email) {
            $user->email = $request->email;
        }

        $user->save();

        return response()->json(['message' => 'User details updated successfully', 'user' => $user], 200);
    }
}
