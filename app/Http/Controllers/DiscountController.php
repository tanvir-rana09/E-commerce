<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiscountController extends Controller
{

    public function setDiscount(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:single,category,global',
            'product_id' => 'nullable|exists:products,id|unique:discounts,product_id',
            'category_id' => 'nullable|exists:categories,id|unique:discounts,category_id',
            'discount_percentage' => 'required|numeric|min:1|max:99',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $validated['start_date'] = Carbon::parse($validated['start_date'])->startOfDay()->format('Y-m-d H:i:s');
        $validated['end_date'] = Carbon::parse($validated['end_date'])->endOfDay()->format('Y-m-d H:i:s');
        $discount = Discount::create($validated);

        return response()->json(['message' => 'Discount created successfully.', 'discount' => $discount]);
    }


    public function removeDiscount($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json(['message' => 'Discount removed successfully.']);
    }

    public function getDiscounts(Request $request)
    {
        $type = $request->query('type');
        $status = $request->query('status');
        $currentDateTime = Carbon::now()->startOfMinute();
        $currentDateTime->setTimezone('UTC');
        $couponQuery = Discount::query()
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($status, function ($query) use ($status, $currentDateTime) {
                if ($status === 'expired') {
                    $query->where('end_date', '<', $currentDateTime);
                } elseif ($status === 'ongoing') {
                    $query->where('start_date', '<=', $currentDateTime)
                        ->where('end_date', '>=', $currentDateTime);
                } elseif ($status === 'not-started') {
                    $query->where('start_date', '>', $currentDateTime);
                }
            })
            ->with([
                'category' => function ($query) {
                    return $query->select(['id', 'name', 'created_at', 'file']);
                },
                'product' => function ($query) {
                    return $query->select(['id', 'name', 'banner']);
                }
            ])->orderBy('created_at', 'desc');


        $perPage = $request->query('per_page', 10);
        $discounts = $couponQuery->paginate($perPage);

        if ($discounts->isEmpty()) {
            return response()->json([
                "status" => "success",
                "message" => "No discounts found",
                "data" => [],
            ], 200);
        }

        return response()->json([
            "status" => "success",
            "total" => $discounts->total(),
            "current_page" => $discounts->currentPage(),
            "per_page" => $discounts->perPage(),
            "total_pages" => $discounts->lastPage(),
            "data" => $discounts->items(),
        ], 200);
    }
}
