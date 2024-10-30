<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    function addProduct(Request $request)
    {

        try {
            $validated = $request->validate([
                "name" => "required|min:3",
                "price" => "decimal:1|required",
                "quantity" => "numeric|required",
                "category_id" => "numeric",
                "subcategory_id" => "numeric",
                'images' => 'array',
                'images.*' => 'image',
                'banner' => 'image',
                'short_desc' => 'min:3',
                'long_desc' => 'min:3',
            ]);

            if (!empty($validated["category_id"])) {
                $validated['category_id'] = $validated["category_id"];
            }
            if (!empty($validated["subcategory_id"])) {
                $validated['subcategory_id'] = $validated["subcategory_id"];
            }

            $requestImages = $request->file("images");
            $requestBanner = $request->file("banner");
            $imagePath = uploadFile($requestImages, "products");
            $bannerPath = uploadFile($requestBanner, "products");
            $validated['images'] = json_encode($imagePath);
            $validated['banner'] = $bannerPath;

            $product = Product::create($validated);

            return response()->json([
                "status" => "success",
                "data" => $product
            ], 201);
        } catch (QueryException $e) {
            if ($e->getCode() === "23000") {
                return response()->json([
                    "status" => "failed",
                    "message" => "your provided category not available in our database!",
                ], 423);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
    function getAllProducts(Request $request)
    {

        $query = $request->query();
        $products = Product::with(["category", "subcategory"]);

        if (!empty($query['name'])) {
            $products->where("name", "like", "%" . $query['name'] . "%");
        }

        $products = $products->get();

        if ($products->isEmpty()) {
            return response()->json([
                "status" => "failed",
                "message" => "No products found"
            ]);
        }

        return response()->json([
            "status" => "success",
            "data" => $products
        ], 200);
    }
    function updateProduct($id, Request $request)
    {
        try {

            $product = Product::find($id);
            if (!$product) {
                # code...
                return response()->json(["status" => "failed", "message" => "product not found with this id $id"], 404);
            }

            $validated = $request->validate([
                "name" => "sometimes|min:3",
                "price" => "sometimes|numeric|between:0,999999.99",
                "quantity" => "sometimes|numeric",
                "category_id" => "sometimes|numeric",
                "subcategory_id" => "sometimes|numeric",
                'images' => 'sometimes|array',
                'images.*' => 'image',
                'banner' => 'image|sometimes',
                'short_desc' => 'min:3|sometimes',
                'long_desc' => 'min:3|sometimes',
            ]);

            if ($request->hasFile("images")) {
                if ($product->images) {
                    $oldImages = $product->images;
                    Storage::disk("public")->delete($oldImages);
                }
                $imagePaths = uploadFile($request->file("images"), "products");
                $validated['images'] = json_encode($imagePaths);
            }
            if ($request->hasFile("banner")) {
                if ($product->banner) {
                    $oldImages = $product->banner;
                    Storage::disk("public")->delete($oldImages);
                }
                $imagePaths = uploadFile($request->file("banner"), "products");
                $validated['banner'] = $imagePaths;
            }

            $product->update($validated);
            return response()->json([
                "status" => "success",
                "message" => "Product updated successfully!",
                "product" => $product
            ]);
        } catch (QueryException $error) {
            if ($error->getCode() === "23000") {
                return response()->json([
                    "status" => "MySql Error",
                    "message" => "the parent category not avaiable"
                ]);
            }

            return response()->json([
                "status" => "failed",
                "message" => "something went wrong"
            ]);
        }
    }
    function deleteProduct($id)
    {

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                "status" => "error",
                "message" => "Product not found"
            ], 404);
        }

        if ($product->images) {
            $oldImages = $product->images;
            Storage::disk("public")->delete($oldImages);
        }

        $product->delete();

        return response()->json([
            "status" => "success",
            "message" => "Product deleted successfully"
        ], 200);
    }
}
