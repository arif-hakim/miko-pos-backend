<?php

namespace App\Http\Controllers;

use App\Models\Conversion as Model;
use Illuminate\Http\Request;
use App\Lib\Response;
use Illuminate\Validation\Rule;

class ConversionController extends Controller
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
                Rule::unique('conversions')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'start_unit_measurement' => 'required',
            'operator' => 'required',
            'amount' => 'required',
            'end_unit_measurement' => 'required',
            'unit_id' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $data = Model::create($request->all());
        return Response::success('New conversion has been successfully created!', $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Model::whereId($id)->first();
        if(!$data) return Response::error('Data not found!');
        return Response::success('', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function edit(Conversion $conversion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        
        $unit_id = $data->unit_id;
        $validation = \Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('conversions')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', '!=', $unit_id);
                })
            ],
            'start_unit_measurement' => 'required',
            'operator' => 'required',
            'amount' => 'required',
            'end_unit_measurement' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $data->update($request->all());
        return Response::success('Conversion has been successfully updated!', $data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Conversion  $conversion
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        $data->delete();
        return Response::success('Conversion has been successfully deleted!');
    }
}
