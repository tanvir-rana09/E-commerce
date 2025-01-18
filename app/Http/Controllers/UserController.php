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
use Illuminate\Support\Facades\Validator;
use function App\Helpers\uploadFile;

class UserController extends Controller
{

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

    public function login(Request $request)
    {
        try {
            // Validate the request input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            $credentials = $request->only('email', 'password');
    
            // Attempt to authenticate the user and get the token
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials!', 'status' => 401], 401);
            }
    
            // Retrieve the authenticated user
            $user = JWTAuth::user();
    
            $user->update(['token' => $token]);
    
            return response()->json([
                'token' => $token,
                'user' => $user,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed', 'message' => $e->getMessage()], 500);
        }
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
        $user->profile = $validatedData['profile'];
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

    public function getAllUsers(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $role = $request->query('role');
        $search = $request->query('search');

        // Query builder with filters
        $usersQuery = User::query()
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%");
                });
            })
            ->orderBy('created_at', 'desc');

        // Use paginate to handle pagination and metadata
        $perPage = $request->query('per_page', 10); // Default to 10 if not provided
        $users = $usersQuery->paginate($perPage);

        // Check if data is empty
        if ($users->isEmpty()) {
            return response()->json([
                "status" => "success",
                "message" => "No users found",
                "data" => [],
            ], 200);
        }

        return response()->json([
            "status" => "success",
            "total" => $users->total(),
            "current_page" => $users->currentPage(),
            "per_page" => $users->perPage(),
            "total_pages" => $users->lastPage(),
            "data" => $users->items(),
        ], 200);
    }

   
    public function updateUserRole(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:admin,user,moderator,reader'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the role
        $user->role = $request->input('role');
        $user->save();

        return response()->json(['message' => 'User role updated successfully', 'user' => $user,'status'=>200], 200);
    }

    public function deleteUser($id)
	{
		$user = User::find($id);
        $loginUser =  JWTAuth::user();
		if (!$user) {
			return response()->json(['message' => 'user not found'], 404);
		}

        if ($loginUser->id == $user->id) {
            return response()->json(['message' => 'You can not delete yourself', 'status' => 403]);
        }

        if ($user->profile) {
            $image = $user->profile;
            $baseUrl = url('storage/') . '/';
            $replaceFile = str_replace($baseUrl, '', $image);
            Storage::disk('public')->delete($replaceFile);
        }

		$user->delete();
		return response()->json(['message' => 'user deleted successfully', 'status' => 200]);
	}
}
