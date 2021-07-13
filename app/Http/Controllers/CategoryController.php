<?php

namespace App\Http\Controllers;

use App\Models\Category as Model;
use Illuminate\Http\Request;
use App\Lib\Response;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = Model::whereUnitId($request->unit_id)->get();
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
        $unit_id = $request->unit_id;
        $validation = \Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('categories')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'code' => [
                'required',
                Rule::unique('categories')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'unit_id' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $data = Model::create($request->all());
        return Response::success('New category has been successfully created!', $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $data = Model::whereId($id)->with('products')->first();
        if(!$data) return Response::error('Data not found!');
        return Response::success('', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        $data->delete();
        return Response::success('Category has been successfully deleted!');
    }
}
