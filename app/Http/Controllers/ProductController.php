<?php

namespace App\Http\Controllers;

use App\Models\Product as Model;
use App\Models\ProductStockHistory;
use Illuminate\Http\Request;
use App\Lib\Response;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = Model::whereUnitId($request->unit_id)->with('category')->get();
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
                Rule::unique('products')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'code' => [
                'required',
                Rule::unique('products')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'base_price' => 'required',
            'selling_price' => 'required',
            'unit_id' => 'required',
            'category_id' => 'required',
        ]);
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $data = Model::create($request->all());
        return Response::success('Product has been successfully created!', $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $data = Model::whereId($id)->first();
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
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        
        $unit_id = $data->unit_id;
        $validation = \Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('products')->where(function ($query) use($unit_id, $id) {
                    return $query->where('unit_id', $unit_id)->where('id', '!=' , $id);
                })
            ],
            'code' => [
                'required',
                Rule::unique('products')->where(function ($query) use($unit_id, $id) {
                    return $query->where('unit_id', $unit_id)->where('id', '!=' , $id);
                })
            ],
            'base_price' => 'required',
            'selling_price' => 'required',
            'category_id' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        //  Updating ..
        $data->update($request->all());
        return Response::success('Product has been successfully updated!', $data);
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
        return Response::success('Product has been successfully deleted!');
    }

    public function updateStock(Request $request, $id){
        try {
            \DB::beginTransaction();
            $data = Model::find($id);
            if (!$data) return Response::error('Data not found!');
            if ($request->changes == 0) return Response::error('Stock changes cannot be zero.');
            
            $history = new ProductStockHistory();
            $history->product_id = $id;
            
            $isMinus = $request->changes < 0;
            $willBeMinus = ($data->stock + $request->changes) < 0;
            if ($isMinus && $willBeMinus) return Response::error('Update failed! Stock will be minus.', ['result' => [$data->stock, $request->changes, $data->stock - $request->changes]]);
            $history->changes = $request->changes;
            
            if ($request->description) $history->description = $request->description;
            if ($request->source) $history->source = $request->source;
            if ($request->source_id) $history->source_id = $request->source_id;
            
            $data->stock = $data->stock + $request->changes;
            $data->save();
            
            $history->save();
            \DB::commit();
            return Response::success('Stock has been successfully updated!');
        } catch (\Exception $e) {
            \DB::rollback();
            return Response::error('Something went wrong!');
        }
    }
    
    public function getStockHistory($id)
    {
        $data = Model::whereId($id)->with('stockHistories')->first();
        return Response::success('', $data);
    }
}
