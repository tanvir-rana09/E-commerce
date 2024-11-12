<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use function App\Helpers\uploadFile;

class CategoryController extends Controller
{
    function addCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                "name" => "string|required",
                "parent_id" => "numeric"
            ]);
    
            $catgory = Category::create($validated);
            if (!empty($validated["parent_id"])) {
                $catgory->parent_id = $validated["parent_id"];
                $catgory->save();
            }
            return response()->json($catgory);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The specified parent_id does not exist in the database.'
                ], 422);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.',
                'error'=>$e
            ], 500);
        }
    }

    function getAllCategories(Request $request)
    {
        $query = $request->query();

        $categories = Category::with("subcategory")
            ->whereNull("parent_id");

        if (!empty($query['name'])) {
            $categories->where("name", "like", "%" . $query['name'] . "%");
        }
        if (!empty($query['parent_id'])) {
            $categories->find($query['parent_id']);
        }

        $categories = $categories->get();

        if ($categories->isEmpty()) {
            return response()->json([
                "status" => "failed",
                "message" => "No categories found"
            ], 404);
        }

        return response()->json([
            "status" => "success",
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
                "image" => "sometimes|image",
                "parent_id" => "sometimes|numeric"
            ]);

            if ($request->hasFile("image")) {
                if ($category->image && file_exists(public_path("storage/" . $category->image))) {
                    unlink(public_path("storage/" . $category->image));
                }
                // return dd($category->image && file_exists(public_path("storage/catgory/" . $category->image)));
                $path = uploadFile($request->file("image"), "category");
                $validated["image"] = $path;
            }

            $category->update($validated);
            return response()->json([
                "status" => "success",
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
                "message" => "category not found"
            ], 404);
        }

        if ($category->image && file_exists(public_path("storage/" . $category->image))) {
            unlink(public_path("storage/" . $category->image));
        }

        $category->delete();

        return response()->json([
            "status" => "success",
            "message" => "category deleted successfully"
        ], 200);
    }
}
