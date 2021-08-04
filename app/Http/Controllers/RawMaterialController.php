<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial as Model;
use App\Models\RawMaterialStockHistory;
use Illuminate\Http\Request;
use App\Lib\Response;
use Illuminate\Validation\Rule;

class RawMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Model::whereUnitId($request->unit_id)->with('raw_material_category');
        if ($request->search) $data = $data->where('name', 'like', "%$request->search%");
        $data = $data->get();
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
        $input = $request->all();
        $validation = \Validator::make($input, [
            'name' => [
                'required',
                Rule::unique('raw_materials')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'code' => [
                'required',
                Rule::unique('raw_materials')->where(function ($query) use($unit_id) {
                    return $query->where('unit_id', $unit_id);
                })
            ],
            'unit_id' => 'required',
            'raw_material_category_id' => 'required',
        ]);
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);
        
        if ($request->hasFile('picture') && $request->picture->isValid()) {
            $dir = 'raw_materials/';
            $filename = $request->unit_id . '_' . $request->name . '_' . strtotime(\Carbon\Carbon::now());
            $input['picture'] = $this->uploadImage($request->picture, $dir, $filename);
        }

        $data = Model::create($input);
        return Response::success('Raw material has been successfully created!', $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RawMaterial  $rawMaterial
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
     * @param  \App\Models\RawMaterial  $rawMaterial
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RawMaterial  $rawMaterial
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
                Rule::unique('raw_materials')->where(function ($query) use($unit_id, $id) {
                    return $query->where('unit_id', $unit_id)->where('id', '!=' , $id);
                })
            ],
            'code' => [
                'required',
                Rule::unique('raw_materials')->where(function ($query) use($unit_id, $id) {
                    return $query->where('unit_id', $unit_id)->where('id', '!=' , $id);
                })
            ],
            'raw_material_category_id' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);
        
        $input = $request->all();
        if ($request->hasFile('picture') && $request->picture->isValid()) {
            $dir = 'raw_materials/';
            $filename = $request->unit_id . '_' . $request->name . '_' . strtotime(\Carbon\Carbon::now());
            
            if ($data->picture) \Storage::disk('public')->delete($dir . $data->picture);
            $input['picture'] = $this->uploadImage($request->picture, $dir, $filename);
        } else $input = $request->except(['picture']);
        //  Updating ..
        $data->update($input);
        return Response::success('Raw material has been successfully updated!', $data);
    }

    public function updateStock (Request $request, $id) {
        try {
            \DB::beginTransaction();
            $data = Model::find($id);
            if (!$data) return Response::error('Data not found!');
            if ($request->changes == 0) return Response::error('Stock changes cannot be zero.');
            
            $history = new RawMaterialStockHistory();
            $history->raw_material_id = $id;
            
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
            return Response::error('Something went wrong!' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RawMaterial  $rawMaterial
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        $data->delete();
        return Response::success('Data has been successfully deleted!');
    }

    public function getStockHistory($id)
    {
        $data = RawMaterialStockHistory::whereRawMaterialId($id)->get();
        return Response::success('', $data);
    }

    private function uploadImage($file, $dir, $name){
        if (!\Storage::disk('public')->exists($dir)) \Storage::disk('public')->makeDirectory($dir);
        
        if($file->isValid()){
            $encode = 'png';
            $filename = $name . ".$encode";
            $destination = "storage/$dir/$filename";

            $src = \Image::make($file)->resize(510, null, function($constraint){ 
                        $constraint->aspectRatio(); 
                    })->encode($encode)->save($destination);
            $result = \Image::canvas(510, 350, '#fff')->insert($destination, 'center')->save($destination);

            return $filename;
        }
        return null;
    }
}
