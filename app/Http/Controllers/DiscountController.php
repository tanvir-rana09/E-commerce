<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
   
    public function setDiscount(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:single,category,global',
            'product_id' => 'nullable|exists:products,id|unique:discounts,product_id',
            'category_id' => 'nullable|exists:categories,id|unique:discounts,category_id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);

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
        $search = $request->query('search');
        $discounts = Discount::when($search, fn($q) => $q->where('type', 'like', "%{$search}%"))->get();

        return response()->json($discounts);
    }
}
