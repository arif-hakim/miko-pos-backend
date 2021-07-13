<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response;
use Validator;
use DB;
use App\Models\User;
use Firebase\JWT\JWT;

class UserController extends Controller
{
    public function register (Request $request) {
        $validation = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|max:15',
            'address' => 'required',
            'phone' => 'required',
        ]);

        if ($validation->fails()) return Response::error('Please fill the form properly', ['validation' => $validation->errors()], 'REG-VALIDATION');

        $isAlreadyExists = User::whereEmail($request->email)->first();
        if($isAlreadyExists) return Response::error('Email already exists!'); 

        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $user = User::create($data);

        // Create token
        $secret = config('app.jwt_secret');
        $token = JWT::encode($user, $secret);

        return Response::success('You have been succesfully registered!', [
            'user' => $user,
            'token' => $token, 
        ]);
    }

    public function login (Request $request) {
        $validation = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
        if ($validation->fails()) return Response::error('Please fill your username and password', ['validation' => $validation->errors()], 'AUTH-VALIDATION');
        
        // Check user email
        $user = User::whereEmail($request->email)->first();
        if (!$user) return Response::error('Email invalid!');
        
        // Check user password
        $isPasswordMatch = \Hash::check($request->password, $user->password);
        if(!$isPasswordMatch) return Response::error('Password invalid!'); 
        
        // Create token
        $secret = config('app.jwt_secret');
        $token = JWT::encode($user, $secret);

        return Response::success('Login success!', [ 
            'user' => $user, 
            'token' => $token 
        ]);
    }

    public function loginByToken (Request $request) {
        // Doing the logic in AccessToken middleware 
        return Response::success('Login success!', [ 
            'user' => $request->authenticatedUser, 
            'token' => $request->authenticatedToken 
        ]);
    }
}
