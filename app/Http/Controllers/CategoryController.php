<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use function App\Helpers\uploadFile;

class CategoryController extends Controller
{
    function addCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                "name" => "string|required|unique:categories,name",
                "status" => "string|nullable",
                "parent_id" => "exists:categories,id|numeric",
                'file' => 'required|image',
            ]);
            $validated['file'] = uploadFile($request->file('file'), 'category');

            $catgory = Category::create([
                'name' => $validated['name'],
                'file' => $validated['file'],
                'status' => $validated['status'],
            ]);
            if (!empty($validated["parent_id"])) {
                $catgory->parent_id = $validated["parent_id"];
                $catgory->save();
            }
            return response()->json([
                'status' => 200,
                'message' => 'category added',
                'data' => $catgory
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The specified parent_id does not exist in the database.'
                ], 422);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.',
                'error' => $e
            ], 500);
        }
    }

    function getAllCategories(Request $request)
    {
        $query = $request->query();

        $categories = Category::with("subcategory")
            ->whereNull("parent_id")->orderBy("created_at", "desc");

        if (!empty($query['name'])) {
            $categories->where("name", "like", "%" . $query['name'] . "%");
        }
        if (!empty($query['status'])) {
            $categories->where("status", $query['status']);
        }
        if (!empty($query['parent_id'])) {
            $categories->find($query['parent_id']);
        }

        $page = $query['page'] ?? 1;
        $perPage = $query['per_page'] ?? 10;


        // Calculate the offset and apply pagination
        $offset = ($page - 1) * $perPage;
        $count = $categories->count();
        $categories = $categories->offset($offset)->limit($perPage)->get();


        if ($categories->isEmpty()) {
            return response()->json([
                "status" => "failed",
                "message" => "No categories found"
            ], 404);
        }

        return response()->json([
            "status" => 200,
            "total" => $count,
            "current_page" => $page,
            "per_page" => $perPage,
            "total_pages" => ceil($count / $perPage),
            "data" => $categories
        ], 200);
    }
    function updateCategory($id, Request $request)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                # code...
                return response()->json([
                    "status" => "error",
                    "message" => "category not found"
                ]);
            }

            $validated = $request->validate([
                "name" => "sometimes|string",
                "file" => "sometimes|image",
                "status" => "sometimes|numeric",
                "parent_id" => "sometimes|numeric"
            ]);

            if ($request->hasFile('file')) {
                if ($category->file) {
                    $image = $category->file;
                    $baseUrl = url('storage/') . '/';
                    $replaceFile = str_replace($baseUrl, '', $image);
                    Storage::disk('public')->delete($replaceFile);
                }
                $validatedData['file'] = uploadFile($request->file('file'), 'category');
            }

            $category->update($validated);

            return response()->json([
                "status" => 200,
                "message" => "category updated successfully",
                "data" => $category
            ], 200);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The specified parent_id does not exist in the database.'
                ], 422);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    function deleteCategory($id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            "status" => "error",
            "message" => "Category not found"
        ], 404);
    }

    // Delete the category's file if it exists
    if (!empty($category->file)) {
        $this->deleteFile($category->file);
    }

    // Get all subcategories of this category
    $subcategories = $category->subcategory;

    if (!empty($subcategories)) {
        foreach ($subcategories as $subcategory) {
            // Delete subcategory files
            if (!empty($subcategory->file)) {
                $this->deleteFile($subcategory->file);
            }
            // Optionally delete sub-subcategories recursively if needed
            $this->deleteSubcategoriesRecursively($subcategory);
        }
    }

    // Delete the category
    $category->delete();

    return response()->json([
        "status" => "success",
        "message" => "Category and its subcategories deleted successfully"
    ], 200);
}

    public function getCategoryIdsAndNames(Request $request)
    {

        $status = $request->query('status');
        $parentId = $request->query('parent_id');
        $parents = $request->query('parents', false);
        $childs = $request->query('childs', false);
        $categories = Category::select('id', 'name', 'created_at', 'file', 'slug', 'parent_id')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($parentId, function ($query) use ($parentId) {
                $query->where('parent_id', $parentId);
            })
            ->when($parents, function ($query) {
                $query->whereNull('parent_id');
            })
            ->when($childs, function ($query) {
                $query->whereNotNull('parent_id');
            })
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => $categories,
            'status' => 200
        ]);
    }

    private function deleteFile($filePath)
{
    $baseUrl = url('storage/') . '/';
    $replaceFile = str_replace($baseUrl, '', $filePath);
    Storage::disk('public')->delete($replaceFile);
}

/**
 * Recursively delete subcategories and their files.
 */
private function deleteSubcategoriesRecursively($category)
{
    foreach ($category->subcategory as $subcategory) {
        if (!empty($subcategory->file)) {
            $this->deleteFile($subcategory->file);
        }
        // Recursive call for nested subcategories
        $this->deleteSubcategoriesRecursively($subcategory);
        $subcategory->delete();
    }
}
}
