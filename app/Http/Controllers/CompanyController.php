<?php

namespace App\Http\Controllers;

use App\Models\Company as Model;
use App\Models\User;
use Illuminate\Http\Request;
use App\Lib\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id = $request->authenticatedUser->company_id;
        $data = Model::whereCompanyId($id)->with(['branches', 'units'])->get();
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
        if ($request->authenticatedUser->company) return Response::error('You already have a company!');
        $validation = \Validator::make($request->all(), [
            'name' => 'required|unique:companies,name',
            'email' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $company = Model::create($request->all());
        
        $owner = $request->authenticatedUser;
        $owner->company_id = $company->id;
        $owner->save();

        return Response::success('Your company has been successfully created!', $company);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $company = Model::whereId($id)->with(['branches', 'units'])->first();
        if(!$company) return Response::error('Data not found!');
        return Response::success('', $company);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }
}
