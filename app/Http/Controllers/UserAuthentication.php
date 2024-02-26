<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserAuthentication extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
     
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // User Signup
        $name = $request->name;
        $username = $request->username;
        $password = $request->password;

        // Validate user data
        $rules = [
            'name' => 'required|string|max:255|min:5',
            'username' => 'required|email|unique:users|max:255|min:15',
            'password' => 'required|string|min:10',
        ];

        // Run the validation
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(), 'message' => "Error in user data", 'success' => false
            ], 422); // Return 422 Unprocessable Entity status code for validation errors
        }

        $insertValue = [
            "name" => $name,
            "username" => $username,
            "password" => bcrypt($password),
        ];
        $success = DB::table('users')->insert($insertValue);

        if ($success) {

            // $message = "Congratulations! Your account has been created successfully."; // Your message content
            $message = ""; // Your message content

            // Mail::raw($message, function ($mail) use ($username) {
            //     $mail->to($username)->subject('Test Email');
            // });
            return response()->json([
                'message' => 'Congratulations! Your account has been created successfully.' . $message, 'user' => $username
            ], 201);
        } else {
            return response()->json([
                'message' => 'Failed to create account. Please try again later.',
                'success' => false
            ], 500);
        }
        return response()->json(['message' => 'Internal server error'], 500);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        // User Signin
        $username = $request->username;
        $password = $request->password;

        // Validate user data
        $rules = [
            'username' => 'required|email|max:255|min:15',
            'password' => 'required|string|min:10',
        ];

        // Run the validation
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(), 'message' => "Error in user data", 'success' => false
            ], 422); // Return 422 Unprocessable Entity status code for validation errors
        }

        // fetch user detail from database
        $userData = DB::table('users')->where('username', $username)->first();

        // return $userData;
        if (!empty($userData)) {
            // Access the first element of the array (assuming there's only one user in the response)

            // Extract values from the user data
            $userid = $userData->userid;
            $name = $userData->name;
            $passwordDB = $userData->password;

            if (Hash::check($password, $passwordDB)) {
                return response()->json(['message' => "User found", 'name' => $name, 'success' => true, 'username' => $username], 200);
            } else {
                return response()->json(['error' => "Wrong Password", 'success' => false], 200);
            }
            return response()->json(['message' => 'Internal server error'], 500);
        } else {
            return response()->json([
                'error' => "User not found",
                'success' => false
            ], 404);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        //  User forget
        $username = $request->username;

        // Validate user data
        $rules = [
            'username' => 'required|email|max:255|min:15',

        ];

        // Run the validation
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(), 'message' => "Error in user data", 'success' => false
            ], 422); // Return 422 Unprocessable Entity status code for validation errors
        }

        // fetch user detail from database
        $userData = DB::table('users')->where('username', $username)->first();

        // return $userData;
        if (!empty($userData)) {
            // Extract values from the user data
            $name = $userData->name;
            $otp = rand(100000, 1000000);

            // delete previous data
            $deleteQuery = DB::table('password_reset_tokens')->where('username', $username)->delete();

            $success = DB::table('password_reset_tokens')->insert(
                [
                    'username' => $username,
                    'token' => $otp,
                    'used' => false
                ]
            );

            // Mail send
            $message = "Hi " . $name . "

You've requested to reset your password. Your otp is " . $otp . "
            
If you didn't make this request, just ignore this message.";

            if ((Mail::raw($message, function ($mail) use ($username) {
                $mail->to($username)->subject('Password Reset Request');
            })) && $success) {
                return response()->json(['message' => "We have sent a mail please check your otp there.", 'success' => true, 'token' => $otp, 'username' => $username], 200);
            }
            return response()->json(['message' => "Internal error", 'success' => false], 400);
        } else {
            return response()->json(['error' => "User not found", 'success' => false], 200);
        }
        return response()->json(['message' => 'Internal server error'], 500);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // reset password
        $username = $request->username;
        $token = $request->token;
        $password = $request->password;
        $password_confirmation = $request->cpassword;

        // Validate user data
        $rules = [
            'username' => 'required|email|max:255|min:15',
            'token' => 'required|max:6|min:4',
            'password' => 'required|string|max:255|min:10|confirmed',
        ];

        // Run the validation
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(), 'message' => "Error in user data", 'success' => false
            ], 422); // Return 422 Unprocessable Entity status code for validation errors
        }

        // fetch user detail from database
        $userData = DB::table('password_reset_tokens')->where('username', $username)->first();

        if (!empty($userData)) {
            // Extract values from the user data
            $tokenDB = $userData->token;
            $userStatus = $userData->used;

            // token used status
            if ($userStatus)
                return response()->json(['error' => "Please resend the forget request because you have used the password", 'success' => false], 200);


            if ($token == $tokenDB) {
                // update data
                if (DB::table('users')->where('username', $username)->update([
                    'password' => $password
                ])) {
                    return response()->json(['message' => "Password changed", 'success' => true], 200);
                }
                return response()->json(['message' => "Internal error", 'success' => false], 400);
            } else {
                return response()->json(['error' => "Wrong otp", 'success' => false], 200);
            }
        } else {
            return response()->json(['error' => "Cancel the process", 'success' => false], 200);
        }
        return response()->json(['message' => 'Internal server error'], 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
