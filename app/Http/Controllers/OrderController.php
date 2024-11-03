<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
	protected $orderService;
	function __construct(OrderService $orderService)
	{
		$this->orderService = $orderService;
	}

	function createOrder(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|exists:users,id',
				'products' => 'required|array|min:1',
				'products.*.product_id' => 'required|exists:products,id',
				'products.*.quantity' => 'required|integer|min:1',
				'products.*.price' => 'required|integer|min:0',
				'total_items' => 'required|integer|min:1', 
				'subtotal' => 'integer|min:0',
				'total_price' => 'integer|min:0',
				'discount_amount' => 'integer|min:0',
				'shipping' => 'integer|min:0',
				'shipping_address' => 'required|array',
				'shipping_address.address' => 'required|string|max:255',
				'shipping_address.city' => 'required|string|max:100',
				'shipping_address.division' => 'required|string|max:100',
				'shipping_address.postal_code' => 'required|string|max:10',
				'payment_method' => 'required|string|in:bkash,rocket,cash_on_delivery',
				'payment_number' => 'string|max:20',
				'trx_id' => 'string|max:50',
				'payment_status' => 'required|string|in:pending,successful,failed',
				'coupon_code' => 'nullable|string|max:20',
				'delivery_status' => 'required|string|in:pending,confirmed,delivered,canceled',
				'order_notes' => 'nullable|string|max:500',
			]);

			if ($validator->fails()) {
				return response()->json(['errors' => $validator->errors()], 422);
			}

			$orderData = $this->orderService->prepareOrder($request);

			DB::beginTransaction();

			$order = Order::create($orderData);

			foreach ($request->products as $product) {
				$productModel = Product::findOrFail($product['product_id']);
				$productModel->decrement('stock', $product['quantity']);
			}

			DB::commit();

			return response()->json(["status" => "success", "data" => $order], 201);
		} catch (\Exception $e) {
			// Rollback the transaction if something goes wrong
			DB::rollBack();

			// Return error response
			return response()->json(['error' => 'Failed to create order', 'message' => $e->getMessage()], 500);
		}
	}


	public function userOrders($id = null)
	{
		$user = Auth::user();
		if ($id) {
			$order = Order::where('user_id', $user->id)->where('id', $id)->first();

			if (!$order) {
				return response()->json(['message' => 'Order not found'], 404);
			}

			return response()->json(['order' => $order]);
		}

		$orders = Order::where('user_id', $user->id)->get();

		return response()->json(['orders' => $orders]);
	}


	public function cancelOrder($id)
	{

		$order = Order::find($id);

		if (!$order) {
			return response()->json(['message' => 'Order not found'], 404);
		}

		if ($order->delivery_status === 'delivered') {
			return response()->json(['message' => 'Delivered orders cannot be canceled'], 400);
		}

		if ($order->user_id !== auth()->id()) {
			return response()->json(['message' => 'Unauthorized'], 403);
		}


		$order->delivery_status = 'canceled';
		$order->save();

		return response()->json(['message' => 'Order canceled successfully']);
	}


	// admin function -------------------------->

	function Allorders(Request $request)
	{
		$order = Order::query();

		if ($request->has('payment_status')) {
			$order->where('payment_status', $request->payment_status);
		}
		if ($request->has('delivery_status')) {
			$order->where('delivery_status', $request->delivery_status);
		}
		if ($request->has('user_id')) {
			$order->where('user_id', $request->user_id);
		}

		$order = $order->orderBy(['created_at','desc'])->get();
		return response()->json(["status" => 'success', "data" => $order], 200);
	}

	public function adminShow($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

	public function adminUpdate(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validatedData = $request->validate([
            'payment_status' => 'in:pending,successful,failed',
            '    ' => 'in:pending,confirmed,delivered,canceled',
            'order_notes' => 'nullable|string|max:500',
        ]);

        $order->update($validatedData);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }


    public function adminDestroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
