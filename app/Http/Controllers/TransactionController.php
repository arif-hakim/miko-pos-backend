<?php

namespace App\Http\Controllers;

use App\Models\Transaction as Model;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductStockHistory;
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

        $data = Model::whereUnitId($request->unit_id)
                ->with(['transaction_details', 'employee', 'employee_unit.branch']);

        if ($request->start_date && $request->end_date) {
            $from = date('Y-m-d 00:00:00', strtotime($request->start_date));
            $to = date('Y-m-d 23:59:59', strtotime($request->end_date));
            $data = $data->whereBetween('created_at', [$from, $to]);
        }
        
        $data = $data->orderBy('created_at', 'desc')->get();
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
        $unit = Unit::find($unit_id);
        if (!$unit) return Response::error('Unit not found!');
        $validation = \Validator::make($request->all(), [
            'items.*' => 'required',
            'payment_status' => 'required',
            'unit_id' => 'required',
        ]);
        if ($validation->fails()) return Response::error('Please fullfil the form properly', ['validation' => $validation->errors()]);
        try {
            DB::beginTransaction();

            // Using timestamp as transaction code
            $uniqueCode = strtotime(Carbon::now());
            $dataTransaction = [
                'code' => 'TR' . $uniqueCode,
                'unit_id' => $request->unit_id,
                'payment_status' => $request->payment_status,
                'description' => $request->description,
                'tax' => $unit->tax,
            ];

            if ($request->customer_name) $dataTransaction['customer_name'] = $request->customer_name;
            if ($request->table_number) $dataTransaction['table_number'] = $request->table_number;

            if ($request->employee_id) $dataTransaction['employee_id'] = $request->employee_id;
            if ($request->employee_unit_id) $dataTransaction['employee_unit_id'] = $request->employee_unit_id;

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

                $productStockHistory = new ProductStockHistory();
                $productStockHistory->product_id = $item['product_id'];
                $productStockHistory->from = $product->stock;
                $productStockHistory['changes'] = -1 * $item['quantity'];
                $productStockHistory->to = $product->stock - $item['quantity'];
                $productStockHistory->description = "#$transaction->code";
                if ($transaction->employee_id) {
                    $productStockHistory->source = 'unit';
                    $productStockHistory->source_id = $transaction->employee_unit_id;
                }
                $productStockHistory->save();
                $product->stock -= $item['quantity'];
                $product->save();

                $product->useRawMaterial($transaction->code, $transaction->employee_unit_id);
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
        $data = Model::whereId($id)->with(['transaction_details', 'employee', 'employee_unit.branch'])->first();
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
            $data->officer_name = $request->authenticatedUser->firstname . ' ' . $request->authenticatedUser->lastname;
            if ($request->payment_status == 'Canceled') {
                $cancel = $data->restoreAllProductsStock();
                if (!$cancel) return Response::error('A problem encountered while canceling order.');
            }
            if ($request->payment_status == 'Paid' && $request->paid){
                $data->paid = $request->paid;
            }
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
        return Response::success('', $data->stock_histories);
    }

    public function salesReport(Request $request)
    {
        $unit = Unit::whereId($request->unit_id)->with('branch.company')->first();
        $from = date('Y-m-d H:i:s', strtotime($request->from . ' 00:00'));
        $to = date('Y-m-d H:i:s', strtotime($request->to . ' 23:59'));
        $transactions = Model::with(['employee', 'employee_unit'])->whereUnitId($unit->id)->whereBetween('created_at', [$from, $to])->get();
        $revenue = 0;
        $profit = 0;
        $costs = 0;
        $total_tax = 0;

        foreach($transactions as $x) {
            if ($x->payment_status == 'Paid') {
                $revenue += $x->transaction_value;
                $profit += $x->profit;
                $costs += $x->transaction_base_price;
                $total_tax += $x->transaction_value * $x->tax / 100;
            }
        }
            
        $payload = [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'transactions' => $transactions,
            'revenue' => $revenue,
            'costs' => $costs,
            'profit' => $profit,
            'total_tax' => $total_tax,
            'unit' => $unit,
        ];

        // return view('pdf.sales', $payload);

        $pdf = \PDF::loadView('pdf.sales', $payload);
        $pdf->setOption('no-stop-slow-scripts', true);
        return $pdf->download('Sales Report.pdf');

        return Response::success('', $payload);
    }
}
