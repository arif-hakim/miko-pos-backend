<?php

namespace App\Http\Controllers;

use App\Models\Unit as Model;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Lib\Response;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id = $request->authenticatedUser->company_id;
        $company = Company::whereId($id)->with(['units'])->first();
        return Response::success('', $company->units);
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
            'branch_id' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $isAlreadyExists = Model::where([
            'branch_id' => $request->branch_id,
            'name' => $request->name
        ])->first();
        if($isAlreadyExists) return Response::error('Unit name already exists!');

        $unit = Model::create($request->all());
        
        return Response::success('Unit ' . $request->name . ' has been successfully created!', $unit);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $unit = Model::whereId($id)->with('branch.company')->first();
        if(!$unit) return Response::error('Data not found!');
        return Response::success('', $unit);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function edit(Unit $unit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Unit $unit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function destroy(Unit $unit)
    {
        //
    }
}
