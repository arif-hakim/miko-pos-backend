<?php

namespace App\Http\Controllers;

use App\Models\Unit as Model;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Lib\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Crypt;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $company_id = $request->authenticatedUser->company_id;
        $branch_id = $request->branch_id;
        
        $units = Model::whereHas('branch.company', function($query) use ($company_id) {
            $query->where('company_id', $company_id);
        })->with('branch.company');
        if ($branch_id) $units = $units->whereBranchId($branch_id);       
        $units = $units->get();

        return Response::success('', $units);
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
    public function update(Request $request, $id)
    {
        $validation = \Validator::make($request->all(), [
            'name' => 'required',
        ]);
        
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        $isAlreadyExists = Model::where([
            'branch_id' => $request->branch_id,
            'name' => $request->name
        ])->first();

        if($isAlreadyExists) return Response::error('Unit name already exists!');
        
        $unit = Model::find($id);
        if(!$unit) return Response::error('Data not found!');
        
        $unit->update($request->all());
        
        return Response::success('Unit has been successfully updated!', $unit);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $unit = Model::find($id);
        if (!$unit) return Response::error('Data not found!');
        $unit->delete();
        return Response::success('Unit has been successfully deleted!');
    }

    public function generateQRCode($id){
        $unit = Model::find($id);
        $extension = 'png';
        $filename = $unit->id . '_' . strtotime(\Carbon\Carbon::now()) . '.' . $extension;
        $dir = '/qrcodes';

        // Create QR Codes directory if it doesn't exists ..
        if (!\Storage::disk('public')->exists($dir)) {
            $result = \Storage::disk('public')->makeDirectory('qrcodes');
            if (!$result) return Response::error('QR codes directory doesn\'t exists');
        }

        // Generating QR Codes ..
        $secret = config('app.jwt_secret');
        $code = JWT::encode($unit->id, $secret);
        $content = config('app.order_page_url') . "?id=$code";
        $qrcode = QrCode::size(300)->format($extension)->generate($content, public_path("storage/$dir/$filename"));
        // dd($qrcode);
        
        if ($unit->qrcode) \Storage::disk('public')->delete("$dir/$unit->qrcode");

        // Saving ..
        $unit->qrcode = $filename;
        $unit->qrcode_content = $content;
        $unit->save();
        return Response::success('QR Code succesfully generated!', $unit);
    }

    public function download($id) {
        $data = Model::find($id);
        $isExists = \Storage::disk('public')->exists('/qrcodes/' . $data->qrcode);
        if (!$isExists) return Response::error('QR code doesn\'t exists!');
        return response()->download(storage_path('app/public/qrcodes/' . $data->qrcode), $data->name . '_qrcode.png');
    }

    public function updateTax(Request $request, $id) {
        $data = Model::find($id);
        if (!$data) return Response::error('Data not found!');
        $data->tax = $request->tax;
        $data->save();
        return Response::success('Tax has been successfully updated!');
    }
}
