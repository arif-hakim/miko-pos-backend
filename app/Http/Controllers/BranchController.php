<?php

namespace App\Http\Controllers;

use App\Models\Branch as Model;
use App\Models\User;
use Illuminate\Http\Request;
use App\Lib\Response;
use Firebase\JWT\JWT;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id = $request->authenticatedUser->company_id;
        $data = Model::whereCompanyId($id)->with(['company', 'units'])->get();
        return Response::success('', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $isAlreadyExists = Model::where([
            'company_id' => $request->authenticatedUser->company_id,
            'name' => $request->name
        ])->first();
        if($isAlreadyExists) return Response::error('Branch name already exists!');

        $request['company_id'] = $request->authenticatedUser->company_id;
        $branch = Model::create($request->all());
        
        return Response::success('New branch has been successfully created!', $branch);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $branch = Model::whereId($id)->with(['company', 'units'])->first();
        if(!$branch) return Response::error('Data not found!');
        return Response::success('', $branch);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function edit(Branch $branch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validation = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $isAlreadyExists = Model::where([
            'company_id' => $request->authenticatedUser->company_id,
            'name' => $request->name,
        ])->where('id', '!=', $request->id)->first();
        
        if($isAlreadyExists) return Response::error('Branch name already exists!');

        $branch = Model::find($id);
        if(!$branch) return Response::error('Data not found!');
        $branch->update($request->all());
        
        return Response::success('Branch has been successfully updated!', $branch);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $branch = Model::find($id);
        if (!$branch) return Response::error('Branch not found!');
        $branch->delete();
        return Response::success('Branch has been successfully deleted!');
    }

    public function employeeBranch(Request $request) {
        try {
            $token = $request->employee_token;
            $secret = config('app.jwt_secret');
            $decoded = JWT::decode($token, $secret, array('HS256'));
            
            $user = User::whereEmail($decoded->email)->with('company.branches')->first();
            if(!$user) return Response::unauthorized('Unauthorized.', 'User doesn\'t exists.');
            
            $branches = Model::where('company_id', $user->company_id)->with('units')->get();
            return Response::success('', $branches);
        } catch (\Exception $e){
            return Response::unauthorized('Unauthorized.', $e->getMessage());
        }
    }
}
