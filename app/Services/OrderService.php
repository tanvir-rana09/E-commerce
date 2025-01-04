<?php

namespace App\Services;

use App\Models\Coupon;
use Carbon\Carbon;

class OrderService
{
	public function prepareOrder($request)
	{
		$subtotal = $this->calculateSubtotal($request->products);
		$discount = $this->calculateDiscount($subtotal, $request->coupon_code);
		$shipping = $this->calculateShipping($request->shipping_address);

		return [
			'user_id' => $request->user_id,
			'products' => json_encode($request->products),
			'subtotal' => $subtotal,
			'discount_amount' => $discount,
			'shipping_cost' => $shipping,
			'total_price' => $subtotal - $discount + $shipping,
			'total_items' => collect($request->products)->sum('quantity'),
			'shipping_address' => json_encode($request->shipping_address),
			'payment_method' => $request->payment_method,
			'payment_number' => $request->payment_number,
			'trx_id' => $request->trx_id,
			'payment_status' => $request->payment_status,
			'coupon_code' => $request->coupon_code,
			'order_notes' => $request->order_notes,
		];
	}

	private function calculateSubtotal($products)
	{
		$productIds = collect($products)->pluck('product_id');
		$dbProducts = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

		// Calculate subtotal
		$subtotal = 0;
		foreach ($products as $product) {
			$price = $dbProducts[$product['product_id']]->discount_price ?? 0;
			$subtotal += $price * $product['quantity'];
		}

		return $subtotal;
		return collect($products)->sum(fn($p) => $p['quantity'] * $p['price']);
	}


	private function calculateDiscount($subtotal, $coupon)
	{
		if (!$coupon) {
			return 0;
		}

		$couponDetails = Coupon::where('code', $coupon)->first();

		if ($couponDetails) {
			$currentDate = now();
			$expireDate = Carbon::parse($couponDetails->expires_at);

			if ($currentDate->greaterThanOrEqualTo($expireDate)) {
				return 0;
			}

			return ($couponDetails->discount / 100) * $subtotal;
		}

		return 0;
	}


	private function calculateShipping($shipping_address)
	{
		if (str_contains(strtolower($shipping_address['division']), 'dhaka')) {
			# code...
			return 50;
		} else return 100;
	}
}
