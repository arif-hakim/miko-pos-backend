<?php

namespace App\Http\Controllers;

use App\Models\Branch as Model;
use Illuminate\Http\Request;
use App\Lib\Response;

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
        
        return Response::success('Your first branch has been successfully created!', $branch);
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
    public function update(Request $request, Branch $branch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function destroy(Branch $branch)
    {
        //
    }
}
