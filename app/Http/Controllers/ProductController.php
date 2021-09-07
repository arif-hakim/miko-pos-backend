<?php

namespace App\Http\Controllers;

use App\Models\Product as Model;
use App\Models\ProductStockHistory;
use App\Models\Recipe;
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
        $data = Model::whereUnitId($request->unit_id)->with(['category', 'recipe.raw_material']);
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
            'picture' => 'required|file|max:2048|mimes:jpg,jpeg,png'
        ]);
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        try {
            \DB::beginTransaction();
            
            if ($request->hasFile('picture') && $request->picture->isValid()) {
                $dir = 'products/';
                $filename = $request->unit_id . '_' . $request->name . '_' . strtotime(\Carbon\Carbon::now());
                $input['picture'] = $this->uploadImage($request->picture, $dir, $filename);
            }
    
            $data = Model::create($input);
            
            // Recipes ..
            $recipes = json_decode($request->recipe);
            Recipe::where('unit_id', $data->unit_id)->where('product_id', $data->id)->delete();
            foreach($recipes as $item) {
                $recipe = new Recipe();
                $recipe->product_id = $data->id;
                $recipe->raw_material_id = $item->raw_material_id;
                $recipe->amount = $item->amount;
                $recipe->unit_id = $data->unit_id;
                $recipe->save();
            }
            \DB::commit();
            return Response::success('Product has been successfully created!', $data);
        } catch (\Exception $e) {
            \DB::rollback();
            return Response::error('A problem encountered while creating new product!', $data);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $data = Model::whereId($id)->with(['category', 'recipe.raw_material'])->first();
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
        try {
            \DB::beginTransaction();
            $input = $request->all();
            if ($request->hasFile('picture') && $request->picture->isValid()) {
                $dir = 'products/';
                $filename = $request->unit_id . '_' . $request->name . '_' . strtotime(\Carbon\Carbon::now());
                
                if ($data->picture) \Storage::disk('public')->delete($dir . $data->picture);
                
                $input['picture'] = $this->uploadImage($request->picture, $dir, $filename);
            } else $input = $request->except(['picture']);

            // Recipes ..
            $recipes = json_decode($request->recipe);
            Recipe::where('unit_id', $data->unit_id)->where('product_id', $data->id)->delete();
            foreach($recipes as $item) {
                $recipe = new Recipe();
                $recipe->product_id = $data->id;
                $recipe->raw_material_id = $item->raw_material_id;
                $recipe->amount = $item->amount;
                $recipe->unit_id = $data->unit_id;
                $recipe->save();
            }
            //  Updating ..
            $data->update($input);
            \DB::commit();
            return Response::success('Product has been successfully updated!', $data);
        } catch(\Exception $e) {
            \DB::rollback();
            return Response::error('A problem encountered while updating product!', ['err' => $e->getMessage() ]);
        } 
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
            
            $history->from = $data->stock;
            $history->changes = $request->changes;
            $history->to = $data->stock + $request->changes;
            
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
        $data = ProductStockHistory::whereProductId($id)->orderBy('created_at', 'desc')->get();
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
            // $result = \Image::make($file)->resize(350, null, function($constraint){ 
            //     $constraint->aspectRatio(); 
            // })->crop(350, 350)->fill('#fff')->encode($encode)->save($destination);
            $result = \Image::canvas(510, 350, '#fff')->insert($destination, 'center')->save($destination);

            return $filename;
        }
        return null;
    }
}
