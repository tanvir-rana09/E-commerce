<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                "discount" => "sometimes|numeric",
                "stock" => "numeric|required",
                "category_id" => "required|numeric",
                "subcategory_id" => "numeric",
                'images' => 'array',
                'size' => 'array',
                'images.*' => 'image',
                'banner' => 'required|image',
                'short_desc' => 'required|min:3',
                'long_desc' => 'min:3',
                'gender' => 'required|min:3',
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

            // Check if the images file is present and upload it
            if ($requestImages) {
                $this->imagePath = uploadFile($requestImages, "products");
                $validated['images'] = json_encode($this->imagePath); // Store image path as a JSON encoded string
            }

            // Check if the banner file is present and upload it
            if ($requestBanner) {
                $this->bannerPath = uploadFile($requestBanner, "products");
                $validated['banner'] = $this->bannerPath; // Store banner path
            }
            $validated['sku'] =strtoupper(Str::random(8));;
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
        if (!empty($query['id'])) {
            $products->where("id", $query['id']);
        }

        // Filter by subcategory
        if (!empty($query['subcategory_id'])) {
            $products->where("subcategory_id", $query['subcategory_id']);
        }
        if (isset($query['status'])) {
            $products->where("status", $query['status']);
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
                    $products->orderBy('created_at', 'asc');
                    break;
                case 'rating':
                    $products->orderBy('rating', 'desc'); // Sort by rating first
                    break;
            }
        } else {
            $products->orderBy('created_at', 'desc');
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



        $page = $query['page'] ?? 1;
        $perPage = $query['per_page'] ?? 10;


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
                return response()->json(["status" => "failed", "message" => "product not found with this id $id"], 404);
            }

            $validated = $request->validate([
                "name" => "sometimes|min:3",
                "price" => "sometimes|numeric|between:0,999999.99",
                "discount" => "sometimes|numeric|between:0,99",
                "stock" => "sometimes|numeric",
                "category_id" => "sometimes|numeric",
                "subcategory_id" => "sometimes|numeric",
                'images' => 'sometimes|array',
                'size' => 'sometimes|array',
                'banner' => 'image|sometimes',
                'short_desc' => 'min:3|sometimes',
                'long_desc' => 'min:3|sometimes',
                'gender' => 'min:3|sometimes',
                'status' => 'numeric|sometimes',
            ]);

            if ($request->has('images')) {
                $newImages = [];

                if ($request->input("images")) {
                    $baseUrl = url('storage/') . '/';

                    $newImagesWithUrl = $request->input("images");
                    $newImages = array_map(function ($image) use ($baseUrl) {
                        return str_replace($baseUrl, '', $image);
                    }, $newImagesWithUrl ?? []);

                    $oldImages = array_values(array_diff($product->images ?? [], $newImages));
                    $uniqueOldImages = array_map(function ($image) use ($baseUrl) {
                        return str_replace($baseUrl, '', $image);
                    }, $oldImages ?? []);
                    Storage::disk('public')->delete($uniqueOldImages);
                }

                if ($request->file("images")) {
                    $uploadedImages = uploadFile($request->file("images"), "products");
                    $newImages = array_merge($newImages, $uploadedImages);
                }

                $validated['images'] = json_encode($newImages);
            }


            // Process banner if provided
            if ($request->hasFile("banner")) {
                // Delete old banner if present
                if ($product->banner) {
                    Storage::disk("public")->delete($product->banner);
                }
                $bannerPath = uploadFile($request->file("banner"), "products");
                $validated['banner'] = $bannerPath;
            }

            $product->update($validated);
            return response()->json([
                "status" => 200,
                "message" => "Product updated successfully!",
                "product" => $product
            ]);
        } catch (QueryException $error) {
            if ($error->getCode() === "23000") {
                return response()->json([
                    "status" => "MySql Error",
                    "message" => "the parent category not available"
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
                "status" => 404,
                "message" => "Product not found"
            ], 200);
        }

        if ($product->images) {
            $oldImages = $product->images;
            Storage::disk("public")->delete($oldImages);
        }

        $product->delete();

        return response()->json([
            "status" => 200,
            "message" => "Product deleted successfully"
        ], 200);
    }
}
