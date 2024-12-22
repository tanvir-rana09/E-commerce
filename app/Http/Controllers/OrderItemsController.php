<?php

namespace App\Http\Controllers;

use App\Models\OrderItems;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderItemsController extends Controller
{
    public function OrderItems(Request $request, $id)
    {
        $orders = OrderItems::where('order_id', $id)
            ->with(['product' => function ($query) {
                return $query->select(['id', 'banner', 'name']);
            }])->get();

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

    public function cancelOrderItem(Request $request, $id)
    {
        $orderItem = OrderItems::find($id);

        if (!$orderItem) {
            return response()->json([
                "status" => "failed",
                "message" => "Order item not found"
            ], 404);
        }


        $order = $orderItem->with('order')->first();

        $user = JWTAuth::user();
        if ($order->user_id != JWTAuth::user()->id && $user->role != 'admin') {
            return response()->json([
                "status" => "failed",
                "message" => "Unauthorized"
            ], 403);
        }


        if ($orderItem->status == 'delivered') {
            return response()->json([
                "status" => "failed",
                "message" => "Delivered items cannot be canceled"
            ], 400);
        }

        $orderItem->status = 'canceled';
        $orderItem->save();

        return response()->json([
            "status" => 200,
            "message" => "Order item canceled successfully",
            "orderItem" => $orderItem
        ]);
    }
}
