<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use function App\Helpers\uploadFile;

class ProductController extends Controller
{
    public $imagePath;
    public $bannerPath;
    function addProduct(Request $request)
    {

        try {
            $validated = $request->validate([
                "name" => "required|min:3",
                "price" => "numeric|required",
                "stock" => "numeric|required",
                "category_id" => "required|numeric",
                "subcategory_id" => "numeric",
                'images' => 'array',
                'images.*' => 'image',
                'banner' => 'required|image',
                'short_desc' => 'required|min:3',
                'long_desc' => 'min:3',
                'item_type' => 'required|min:3',
                'status' => 'numeric',
            ]);

            if (!empty($validated["category_id"])) {
                $validated['category_id'] = $validated["category_id"];
            }
            if (!empty($validated["subcategory_id"])) {
                $validated['subcategory_id'] = $validated["subcategory_id"];
            }

            $requestImages = $request->file("images");
            $requestBanner = $request->file("banner");
            $this->imagePath = uploadFile($requestImages, "products");
            $this->bannerPath = uploadFile($requestBanner, "products");
            $validated['images'] = json_encode($this->imagePath);
            $validated['banner'] = $this->bannerPath;
            $product = Product::create($validated);

            return response()->json([
                "status-type" => "success",
                "data" => $product,
                "status" => 200,
            ], 201);
        } catch (QueryException $e) {

            if ($this->bannerPath || $this->imagePath) {
                $oldImages = $this->imagePath;
                $oldBanner = $this->bannerPath;
                Storage::disk("public")->delete($oldImages);
                Storage::disk("public")->delete($oldBanner);
            }

            if ($e->getCode() === "23000") {
                return response()->json([
                    "status" => "failed",
                    "message" => "your provided category not available in our database!",
                ], 423);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.',
                'data' => $e
            ], 500);
        }
    }




    function getAllProducts(Request $request)
    {
        $query = $request->query();
        $products = Product::with(["category", "subcategory"]);

        // Filter by product name
        if (!empty($query['name'])) {
            $products->where("name", "like", "%" . $query['name'] . "%");
        }

        // Filter by category
        if (!empty($query['category_id'])) {
            $products->where("category_id", $query['category_id']);
        }

        // Filter by subcategory
        if (!empty($query['subcategory_id'])) {
            $products->where("subcategory_id", $query['subcategory_id']);
        }

        // Filter by price range
        if (!empty($query['min_price']) && !empty($query['max_price'])) {
            $products->whereBetween('price', [$query['min_price'], $query['max_price']]);
        }

        // Sorting
        if (!empty($query['sort_by'])) {
            switch ($query['sort_by']) {
                case 'price_asc':
                    $products->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $products->orderBy('price', 'desc');
                    break;
                case 'latest':
                    $products->orderBy('created_at', 'desc');
                    break;
                case 'rating':
                    $products->orderBy('rating', 'desc'); // Sort by rating first
                    break;
            }
        }

        // Check for additional sorting criteria if both price and rating are provided
        if (!empty($query['sort_by']) && isset($query['sort_secondary'])) {
            switch ($query['sort_secondary']) {
                case 'price_asc':
                    $products->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $products->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $products->orderBy('rating', 'desc');
                    break;
            }
        }



        $page = $query['page'];
        $perPage = $query['per_page'];

        // Calculate the offset and apply pagination
        $offset = ($page - 1) * $perPage;
        $count = $products->count();
        $products = $products->offset($offset)->limit($perPage)->get();

        if ($products->isEmpty()) {
            return response()->json([
                "status" => "failed",
                "message" => "No products found"
            ]);
        }

        // Return paginated response
        return response()->json([
            "status" => "success",
            "total" => $count,
            "current_page" => $page,
            "per_page" => $perPage,
            "total_pages" => ceil($count / $perPage),
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
                "stock" => "sometimes|numeric",
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
