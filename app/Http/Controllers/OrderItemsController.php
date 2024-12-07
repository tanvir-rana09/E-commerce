<?php

namespace App\Http\Controllers;

use App\Models\OrderItems;
use Illuminate\Http\Request;

class OrderItemsController extends Controller
{
    public function OrderItems(Request $request, $id)
    {

        $orders = OrderItems::where('order_id', $id)->with('product')->get();

        // Check if the order exists
        if (!$orders) {
            return response()->json([
                "status" => "failed",
                "message" => "No order found"
            ], 404);
        }

        // Return response with order and associated items
        return response()->json([
            "status" => 200,
            "data" => $orders
        ], 200);
    }
}
