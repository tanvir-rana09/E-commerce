<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

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
				'size' => 'sometimes|string',
				'products.*.product_id' => 'required|exists:products,id',
				'products.*.quantity' => 'required|integer|min:1',
				'products.*.price' => 'required|integer|min:0',
				'total_items' => 'required|integer|min:1',
				'subtotal' => 'integer|min:0',
				'total_price' => 'integer|min:0',
				'discount_amount' => 'integer|min:0',
				'shipping_cost' => 'integer|min:0',
				'shipping_address' => 'required|array',
				'shipping_address.address' => 'required|string|max:255',
				'shipping_address.name' => 'required|string|max:255',
				'shipping_address.email' => 'required|email|max:255',
				'shipping_address.phone' => 'required|string|max:255',
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

			DB::beginTransaction();

			$orderData = $this->orderService->prepareOrder($request);
			$order = Order::create($orderData);

			foreach ($order->products as $product) {
				$productModel = Product::find($product['product_id']);

				// Check if enough stock is available
				if ($productModel->stock < $product['quantity']) {
					DB::rollBack();
					return response()->json([
						'error' => 'Failed to create order',
						'message' => "Product {$productModel->name} is out of stock"
					], 404);
				}

				// Create order item and update stock
				OrderItems::create([
					'order_id' => $order->id,
					'product_id' => $product['product_id'],
					'quantity' => $product['quantity'],
					'price' => $productModel['price'],
					'total_price' => $productModel['price'] * $product['quantity'],
				]);
				$productModel->decrement('stock', $product['quantity']);
			}

			DB::commit();

			return response()->json(["status" => "success", "data" => $order], 201);
		} catch (\Exception $e) {
			DB::rollBack();

			if ($e->getCode() == '22003') {
				return response()->json(['error' => 'Failed to create order', 'message' => 'Product out of stock'], 404);
			}
			return response()->json(['error' => 'Failed to create order', 'message' => $e->getMessage()], 500);
		}
	}

	public function orderedItems($id = null)
	{
		try {
			if ($id) {
				$order = orderItems::with(['product', 'order'])->where('id', $id)->first();
				if (!$order) {
					return response()->json(['message' => 'Ordered items not found'], 404);
				}
				return response()->json(['orders' => $order]);
			}
			$orders = orderItems::with(['product', 'order'])->get();
			return response()->json(['orders' => $orders]);
		} catch (\Exception $e) {
			return $e;
		}
	}

	public function userOrders($id = null)
	{
		try {
			$user = Auth::user();
			if ($id) {
				$order = Order::with(['orderItems' => function ($query) {
					$query->select(['id', 'order_id', 'product_id', 'price', 'quantity'])->with('product');
				}])->where('user_id', $user->id)->where('id', $id)->first();

				if (!$order) {
					return response()->json(['message' => 'Order not found '], 404);
				}

				return response()->json(['order' => $order]);
			}

			$orders = Order::with(['orderItems' => function ($query) {
				$query->with('product');
			}])->where('user_id', $user->id)->get();

			return response()->json(['orders' => $orders]);
		} catch (\Exception $e) {
			return $e;
		}
	}


	public function cancelOrder($id)
	{
		$order = Order::find($id);

		if (!$order) {
			return response()->json(['message' => 'Order not found'], 404);
		}

		// Prevent cancellation if the order is delivered
		if ($order->delivery_status === 'delivered') {
			return response()->json(['message' => 'Delivered orders cannot be canceled'], 400);
		}

		// Check if the authenticated user owns the order
		if ($order->user_id != JWTAuth::id()) {
			return response()->json(['message' => 'Unauthorized'], 403);
		}

		// Update the delivery_status and status
		$order->delivery_status = 'canceled';
		$order->status = 'canceled'; // Update overall status to reflect cancellation
		$order->save();

		return response()->json([
			'message' => 'Order canceled successfully',
			'order' => $order, // Return the updated order details
		], 200);
	}


	function Allorders(Request $request)
	{
		$query = $request->query();
		$orders = Order::query();
		// return $order;
		if (!empty($query['payment_status'])) {
			$orders->where('payment_status', $query['payment_status']);
		}
		if (!empty($query['delivery_status'])) {
			$orders->where('delivery_status', $query['delivery_status']);
		}
		if (!empty($query['start_date']) && !empty($query['end_date'])) {
			$orders->whereBetween('created_at', [$query['start_date'], $query['end_date']]);
		}
		if (!empty($query['user_id'])) {
			$orders->where('user_id', $query['user_id']);
		}


		$page = $query['page'] ?? 1;
		$perPage = $query['per_page'] ?? 10;
		$offset = ($page - 1) * $perPage;
		$count = $orders->count();
		$orders = $orders->offset($offset)->limit($perPage)->orderBy('created_at', 'desc')->get();

		if ($orders->isEmpty()) {
			return response()->json([
				"status" => "failed",
				"message" => "No order found"
			]);
		}

		// Return paginated response
		return response()->json([
			"status" => 200,
			"total" => $count,
			"current_page" => $page,
			"per_page" => $perPage,
			"total_pages" => ceil($count / $perPage),
			"data" => $orders
		], 200);
	}

	public function adminSingleOrder($id)
	{
		$order = Order::find($id);

		if (!$order) {
			return response()->json(['message' => 'Order not found'], 404);
		}

		return response()->json($order);
	}


	public function adminOrderUpdate(Request $request, $id)
	{
		$order = Order::find($id);

		if (!$order) {
			return response()->json(['message' => 'Order not found'], 404);
		}

		$validatedData = $request->validate([
			'payment_status' => 'sometimes|in:pending,successful,failed',
			'delivery_status' => 'sometimes|in:pending,confirmed,delivered,canceled',
		]);

		// Update the payment_status and delivery_status
		$order->update($validatedData);

		// Determine the overall status based on payment_status and delivery_status
		if ($order->payment_status === 'failed') {
			$order->status = 'payment_failed';
		} elseif ($order->payment_status === 'successful' && $order->delivery_status === 'delivered') {
			$order->status = 'completed';
			foreach ($order->products as $product) {
				$orderItem = OrderItems::find($product['product_id']);
				$orderItem->status = 'delivered';
				if ($orderItem->status == 'delivered') {
					$product = Product::find($orderItem['product_id']);
					$product->increment('sells', $product['quantity']);
				}
			}
		}elseif ($order->payment_status === 'pending' && $order->delivery_status === 'delivered') {
			$order->status = 'awaiting_payment';
			foreach ($order->products as $product) {
				$orderItem = OrderItems::find($product['product_id']);
				$orderItem->status = 'delivered';
				if ($orderItem->status == 'delivered') {
					$product = Product::find($orderItem['product_id']);
					$product->increment('sells', $product['quantity']);
				}
			}
		} elseif ($order->delivery_status === 'canceled') {
			$order->status = 'canceled';
			foreach ($order->products as $product) {
				$orderItem = OrderItems::find($product['product_id']);
				$orderItem->status = 'canceled';
			}
		} elseif ($order->payment_status === 'successful' && $order->delivery_status === 'pending') {
			$order->status = 'awaiting_delivery';
		} elseif ($order->payment_status === 'pending' && $order->delivery_status === 'pending') {
			$order->status = 'pending';
		} else {
			$order->status = 'processing';
		}

		$order->save();

		return response()->json([
			'message' => 'Order updated successfully',
			'order' => $order,
			'status' => 200
		]);
	}


	public function adminDestroy($id)
	{
		$order = Order::find($id);

		if (!$order) {
			return response()->json(['message' => 'Order not found'], 404);
		}

		array_map(function ($item) {
			$product = Product::find($item['product_id']);
			$product->decrement('sells', $item['quantity']);
		}, $order->products);

		$order->delete();

		return response()->json(['message' => 'Order deleted successfully', 'status' => 200]);
	}
}
