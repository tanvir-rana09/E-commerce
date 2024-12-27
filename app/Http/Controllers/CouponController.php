<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function createCoupon(Request $request)
    {
        $this->validateCoupon($request);

        $coupon = Coupon::create($request->only('code', 'discount', 'expires_at', 'usage_limit'));

        return response()->json([
            'message' => 'Coupon created successfully',
            'coupon' => $coupon,
            'status' => 201
        ], 201);
    }

   
    public function getCoupons(Request $request)
    {
        $search = $request->query('search');

        
        $couponQuery = Coupon::query()
           ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%$search%");});
            })
            ->orderBy('created_at', 'desc');

       
        $perPage = $request->query('per_page', 10);
        $coupons = $couponQuery->paginate($perPage);

        if ($coupons->isEmpty()) {
            return response()->json([
                "status" => "success",
                "message" => "No coupons found",
                "data" => [],
            ], 200);
        }

        return response()->json([
            "status" => "success",
            "total" => $coupons->total(),
            "current_page" => $coupons->currentPage(),
            "per_page" => $coupons->perPage(),
            "total_pages" => $coupons->lastPage(),
            "data" => $coupons->items(),
        ], 200);
    }

    
    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);

        $coupon = Coupon::validCoupon($validated['code']);

        // Check if coupon has usage limit and has been used up
        if ($coupon->usage_limit > 0 && $coupon->times_used >= $coupon->usage_limit) {
            return response()->json([
                'message' => 'Coupon usage limit reached.',
                'status' => 400
            ], 200);
        }

        // Apply the discount
        $coupon->increment('times_used'); // Increase usage count
        $coupon->save();

        return response()->json([
            'message' => 'Coupon applied successfully',
            'discount_percentage' => $coupon,
            'status' => 200
        ]);
    }


    public function deleteCoupon($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully'
        ]);
    }

   
    private function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'discount' => 'required|numeric|min:0|max:100',
            'expires_at' => 'required|date|after:now',
            'usage_limit' => 'nullable|integer|min:1',
        ]);
    }
}
