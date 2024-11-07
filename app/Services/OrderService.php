<?php
namespace App\Services;
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
			'delivery_status' => $request->delivery_status,
			'order_notes' => $request->order_notes,
		];
	}

	private function calculateSubtotal($products)
	{
		return collect($products)->sum(fn($p) => $p['quantity'] * $p['price']);
	}
	private function calculateDiscount($subtotal, $coupon)
	{
		return 0;
	}
	private function calculateShipping($shipping_address)
	{
		if ($shipping_address['division'] === 'dhaka') {
			# code...
			return 50;
		} else return 100;
	}
}
