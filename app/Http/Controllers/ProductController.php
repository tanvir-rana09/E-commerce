<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function App\Helpers\uploadFile;
use function PHPUnit\Framework\isEmpty;

class ProductController extends Controller
{
    public $imagePath;
    public $bannerPath;
    function addProduct(Request $request)
    {

        try {
            $validated = $request->validate([
                "name" => "required|min:3|unique:products,name",
                "price" => "numeric|required",
                "discount" => "sometimes|numeric|min:1|max:99",
                "stock" => "numeric|required|min:1",
                "category_id" => "required|numeric|exists:categories,id",
                "subcategory_id" => "numeric|exists:categories,id",
                'images' => 'array|max:5',
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
            $validated['sku'] = strtoupper(Str::random(8));


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
        if (!empty($query['slug'])) {
            $products->where("slug", $query['slug']);
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
                case 'oldest':
                    $products->orderBy('created_at', 'asc');
                    break;
                case 'newest':
                    $products->orderBy('created_at', 'desc');
                    break;
                case 'sells':
                    $products->orderBy('sells', 'desc');
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
            $baseUrl = url('storage/') . '/';

            $validated = $request->validate([
                "name" => "sometimes|min:3",
                "price" => "sometimes|numeric|between:0,999999.99",
                "discount" => "sometimes|numeric|between:0,99",
                "stock" => "sometimes|numeric",
                "category_id" => "sometimes|numeric",
                "subcategory_id" => "sometimes|numeric",
                'images' => 'sometimes|array|max:5',
                'size' => 'sometimes|array',
                'banner' => 'image|sometimes',
                'short_desc' => 'min:3|sometimes',
                'long_desc' => 'min:3|sometimes',
                'gender' => 'min:3|sometimes',
                'status' => 'numeric|sometimes',
            ]);


            if ($request->hasFile('images')) {
                $newImages = [];

                if ($request->input("images")) {
                    $newImagesWithUrl = $request->input("images");

                    $newImages = array_map(function ($image) use ($baseUrl) {
                        return str_replace($baseUrl, '', $image);
                    }, $newImagesWithUrl ?? []);

                    $modelImages = $product->images;

                    $uniqueOldImages = array_map(function ($image) use ($baseUrl) {
                        return str_replace($baseUrl, '', $image);
                    }, $modelImages ?? []);

                    $oldImages = array_values(array_diff($uniqueOldImages ?? [], $newImages));
                    Storage::disk('public')->delete($oldImages);
                }

                if ($request->file("images")) {
                    $uploadedImages = uploadFile($request->file("images"), "products");
                    $newImages = array_merge($newImages, $uploadedImages);
                }

                $validated['images'] = json_encode($newImages);
            } elseif (isEmpty($request->input('images')) && !$request->hasFile('images')) {
                $modelImages = $product->images;
                $uniqueOldImages = array_map(function ($image) use ($baseUrl) {
                    return str_replace($baseUrl, '', $image);
                }, $modelImages ?? []);
                Storage::disk('public')->delete($uniqueOldImages);
                $validated['images'] = json_encode([]);
            }


            // Process banner if provided
            if ($request->hasFile("banner")) {
                if ($product->banner) {
                    $image = $product->banner;
                    $oldImage = str_replace($baseUrl, '', $image);
                    Storage::disk("public")->delete($oldImage);
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

    public function getProductIdsAndNames(Request $request)
    {
        $status = $request->query('status');
        $type = $request->query('gender');
        $category = $request->query('category_id');
        $orderByCreatedAt = $request->query('order_by_created_at', false);
        $orderBySells = $request->query('order_by_sells', false);
        $limit = $request->query('limit');

        $products = Product::select('id', 'name', 'banner', 'discount', 'images', 'price', 'slug','category_id')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($orderByCreatedAt, function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->when($orderBySells, function ($query) {
                $query->orderBy('sells', 'desc');
            })
            ->when($category, function ($query) use ($category) {
                $query->where('category_id', $category);
            })
            ->orderBy('name', 'asc')
            ->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })
            ->get();

        return response()->json([
            'data' => $products,
            'status' => 200
        ]);
    }

    public function getAllProductIdsAndNames(Request $request)
    {
        $query = $request->query();
        $products = Product::query();


        if (!empty($query['status'])) {
            $products->where('status',  $query['status']);
        }
        if (!empty($query['name'])) {
            $products->where('name', 'like', '%' . $query['name'] . '%');
        }
        if (!empty($query['category_slug'])) {
            $products->where('name', 'like', '%' . $query['name'] . '%');
        }

        // **2. Filter by Category**
        if (!empty($query['category_id']) || !empty($query['category_slug'])) {
            if (!empty($query['category_slug']) && empty($query['category_id'])) {
                $category = Category::where('slug', $query['category_slug'])->first();
                if ($category) {
                    $query['category_id'] = $category->id;
                }
            }

            if (!empty($query['category_id'])) {
                $products->where('category_id', $query['category_id']);
            }
        }


        // **3. Filter by Subcategories (Multiple)**
        if (!empty($query['subcategory_ids'])) {
            $subcategoryIds = explode(',', $query['subcategory_ids']); // Comma-separated list
            $products->whereIn('subcategory_id', $subcategoryIds);
        }

        // **4. Filter by Price Range**
        if (!empty($query['min_price']) && !empty($query['max_price'])) {
            $products->whereBetween('price', [$query['min_price'], $query['max_price']]);
        }


        if (!empty($query['sort_by'])) {
            switch ($query['sort_by']) {
                case 'price_asc':
                    $products->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $products->orderBy('price', 'desc');
                    break;
                case 'oldest':
                    $products->orderBy('created_at', 'desc');
                    break;
                case 'newest':
                    $products->orderBy('created_at', 'asc');
                    break;
                case 'sells':
                    $products->orderBy('sells', 'desc');
                    break;
                case 'a_to_z':
                    $products->orderBy('name', 'asc');
                    break;
                case 'z_to_a':
                    $products->orderBy('name', 'desc');
                    break;
            }
        } else {
            $products->orderBy('created_at', 'desc');
        }


        if (!empty($query['product_type'])) {
            $products->where('gender', $query['product_type']);
        }

        // **10. Filter by In Stock**
        if (!empty($query['in_stock']) && $query['in_stock'] == 'true') {
            $products->where('stock', '>', 0);
        }

        // **11. Pagination**
        $perPage = $query['per_page'] ?? 10; // Default to 10 items per page
        $page = $query['page'] ?? 1;         // Default to page 1
        $paginatedProducts = $products->select('id', 'name', 'banner', 'discount', 'images', 'price', 'slug','category_id')->paginate($perPage, ['*'], 'page', $page);

        // **12. Return Response**
        return response()->json([
            "status" => "success",
            "total" => $paginatedProducts->total(),
            "current_page" => $paginatedProducts->currentPage(),
            "per_page" => $paginatedProducts->perPage(),
            "total_pages" => $paginatedProducts->lastPage(),
            "data" => $paginatedProducts->items()
        ], 200);
    }
}
