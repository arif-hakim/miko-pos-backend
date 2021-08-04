<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Products;
use App\Lib\Response;

class MakeOrderController extends Controller
{
    public function getUnit(Request $request){
        return Response::success('', $request->authenticatedUnit);
    }
}
