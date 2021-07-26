<?php

namespace App\Http\Controllers;

use App\Models\Transaction as Model;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Lib\Response;
use Illuminate\Validation\Rule;
use DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = Model::whereUnitId($request->unit_id)->with('transaction_details')->get();
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
            'items.*' => 'required',
            'payment_status' => 'required',
            'unit_id' => 'required',
            'customer_name' => 'required',
        ]);
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);
        try {
            DB::beginTransaction();

            // Using timestamp as transaction code
            $uniqueCode = strtotime(Carbon::now());
            $dataTransaction = [
                'code' => 'TR' . $uniqueCode,
                'unit_id' => $request->unit_id,
                'customer_name' => $request->customer_name,
                'payment_status' => $request->payment_status,
                'description' => $request->description,
            ];

            $transaction = Model::create($dataTransaction);

            $errorMessages = [];
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) return Response::error('Oops, there is a product that is not available!', $item);

                if ($product->stock == 0) $errorMessages[$product->code] = ["Oops, $product->name is sold out!"];
                else if ($product->stock - $item['quantity'] < 0) $errorMessages[$product->code] = ["Oops, $product->name only $product->stock $product->unit_measurement left!"];
                
                $transactionDetail = new TransactionDetail();
                $transactionDetail->product_id = $product->id;
                $transactionDetail->transaction_id = $transaction->id;
                $transactionDetail->quantity = $item['quantity'];
                $transactionDetail->profit = ($product->selling_price * $item['quantity']) - ($product->base_price * $item['quantity']);
                $transactionDetail->price = $product->selling_price;
                $transactionDetail->total_base_price = $product->base_price * $item['quantity'];
                $transactionDetail->total_price = $product->selling_price * $item['quantity'];
                $transactionDetail->note = $item['note'] ?? '';
                $transactionDetail->items = [
                    'code' => $product->code,
                    'name' => $product->name,
                    'description' => $product->description,
                    'unit_measurement' => $product->unit_measurement,
                ];
                
                $transactionDetail->save();
                $product->stock -= $item['quantity'];
                $product->save();
            }

            if (count($errorMessages) > 0) {
                \DB::rollback();
                return Response::error(null, ['validation' => $errorMessages]);
            }
            DB::commit();
            return Response::success('Order created!', $transaction);
        } catch (\Exception $e) {
            DB::rollback();
            return Response::error('A problem encountered while creating the order!', $e->getTrace());
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
        $data = Model::whereId($id)->with('transaction_details')->first();
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
        // $data = Model::find($id);
        // if (!$data) return Response::error('Data not found!');
        
        // $unit_id = $data->unit_id;
        // $validation = \Validator::make($request->all(), [
        //     'name' => [
        //         'required',
        //         Rule::unique('products')->where(function ($query) use($unit_id, $id) {
        //             return $query->where('unit_id', $unit_id)->where('id', '!=' , $id);
        //         })
        //     ],
        //     'code' => [
        //         'required',
        //         Rule::unique('products')->where(function ($query) use($unit_id, $id) {
        //             return $query->where('unit_id', $unit_id)->where('id', '!=' , $id);
        //         })
        //     ],
        //     'base_price' => 'required',
        //     'selling_price' => 'required',
        //     'category_id' => 'required',
        // ]);
        
        // if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);

        // //  Updating ..
        // $data->update($request->all());
        // return Response::success('Product has been successfully updated!', $data);
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
        if ($data->payment_status === 'Unpaid') return Response::error('Transaction is still running!');
        $data->delete();
        return Response::success('Transaction has been successfully deleted!');
    }

    public function updateStatus(Request $request, $id){
        try {
            \DB::beginTransaction();
            $data = Model::find($id);
            if (!$data) return Response::error('Data not found!');
            if ($data->payment_status == 'Canceled') return Response::error('Transaction was already canceled!');
            if ($data->payment_status == 'Paid') return Response::error('Transaction was already paid!');

            $data->payment_status = $request->payment_status;
            if ($request->payment_status == 'Canceled') $data->restoreAllProductsStock();
            $data->save();
            \DB::commit();
            return Response::success('Status has been successfully updated!', $data);
        } catch (\Exception $e) {
            \DB::rollback();
            return Response::error('Something went wrong!');
        }
    }

    public function updateTransactionStatus(Request $request) {
        try {
            $data = Model::whereId($id)->first();
            if (!$data) return Response::error('Data not found!');
            $data->payment_status = $request->payment_status;
            if ($request->payment_status == 'Canceled') $data->restoreAllProductsStock();
            $data->save();

            return Response::success('Transaction status updated!');
        } catch (\Exception $e) {
            return Response::error('A problem encountered while updating transaction status!', $e->getMessage());
        }
    }
    
    public function getStockHistory($id)
    {
        $data = Model::whereId($id)->with('stockHistories')->first();
        return Response::success('', $data);
    }
}
