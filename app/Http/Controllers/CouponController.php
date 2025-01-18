<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function createCoupon(Request $request)
    {
        $this->validateCoupon($request);
        $data = $request->only('code', 'discount', 'expires_at', 'usage_limit');
        $data['expires_at'] = Carbon::parse($data['expires_at'])->endOfDay()->format('Y-m-d H:i:s');
        $coupon = Coupon::create($data);

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
                    $subQuery->where('code', 'LIKE', "%$search%");
                });
            })->orderBy('created_at', 'desc');


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

        $coupon = Coupon::where('code', $validated['code'])->first();

        if ($coupon->usage_limit > 0 && $coupon->times_used >= $coupon->usage_limit) {
            return response()->json([
                'message' => 'Coupon usage limit reached.',
                'status' => 400
            ], 200);
        }
        $currentDate = now();
        $expireDate = Carbon::parse($coupon->expires_at); 
    
        // Check if the coupon has expired
        if ($currentDate->greaterThanOrEqualTo($expireDate)) {
            return response()->json([
                'message' => 'Coupon Expired',
                'status' => 400
            ], 200);
        }

        return response()->json([
            'message' => 'Coupon applied successfully',
            'discount' => $coupon,
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
            'discount' => 'required|numeric|min:1|max:99',
            'expires_at' => 'required|date',
            'usage_limit' => 'required|integer|min:1',
        ]);
    }
}
