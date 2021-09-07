<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response;
use Validator;
use DB;
use App\Models\User as Model;
use Firebase\JWT\JWT;

class UserController extends Controller
{
    public function index(Request $request) {
        return Model::whereCompanyId($request->authenticatedUser->company_id)->get();
    }

    public function store(Request $request) {
        $validation = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|max:15',
            'address' => 'required',
            'phone' => 'required',
            'role' => 'required',
            'access_rights' => 'required'
        ]);

        if ($validation->fails()) return Response::error('Please fill the form properly', ['validation' => $validation->errors()], 'REG-VALIDATION');

        $isAlreadyExists = Model::whereEmail($request->email)->first();
        if($isAlreadyExists) return Response::error('Email already exists!'); 

        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $user = Model::create($data);

        return Response::success('User have been succesfully created!', $user);
    }

    public function update(Request $request, $id){
        $validation = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email',
            'address' => 'required',
            'phone' => 'required',
            'role' => 'required',
            'access_rights' => 'required'
        ]);

        if ($validation->fails()) return Response::error('Please fill the form properly', ['validation' => $validation->errors()], 'REG-VALIDATION');

        $user = Model::find($id);
        if (!$user) return Response::error('Data not found');

        $isAlreadyExists = Model::whereEmail($request->email)->where('id', '<>', $id)->first();
        if($isAlreadyExists) return Response::error('Email already exists!'); 

        $updating = $user->update($request->all());

        return Response::success('User have been succesfully updated!', $user);
    }

    public function destroy($id) {
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        $data->delete();
        return Response::success('User has been successfully deleted!');
    }

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

        $isAlreadyExists = Model::whereEmail($request->email)->first();
        if($isAlreadyExists) return Response::error('Email already exists!'); 

        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $user = Model::create($data);

        // Create token
        $secret = config('app.jwt_secret');
        $token = JWT::encode($user, $secret);

        return Response::success('You have been succesfully registered!', [
            'user' => $user,
            'token' => $token, 
        ]);
    }

    public function login (Request $request) {
        // $tahun = Carbon::now()->format('Y');
        // $nomor = DB::table('penerimaan')->where('tahun_penerimaan', $tahun)->max('nomor_penerimaan');

        // if ($nomor) $nomor = str_pad($nomor + 1, 3) // kalo di tahun X ada hasil fungsi MAX dari nomor penerimaan, lanjutin nomornya
        // else $nomor = str_pad(1, 3) // kalo gaada, mulai dari 1 lagi 

        $validation = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
        if ($validation->fails()) return Response::error('Please fill your username and password', ['validation' => $validation->errors()], 'AUTH-VALIDATION');
        
        // Check user email
        $user = Model::whereEmail($request->email)->first();
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
