<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    function addCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                "name" => "string|required",
                "image" => "image",
                "parent_id" => "numeric"
            ]);
            $requestPath = $request->file("image");
            $path = uploadFile($requestPath, "category");

            $catgory = Category::create([
                "name" => $validated["name"],
                "image" => $path,
            ]);
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
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    function getAllCategories()
    {
        $categories = Category::with("subcategory")->whereNull("parent_id")->get();
        return response()->json($categories);
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
                # code...
                $path = uploadFile($request->file("image"), "catgory");
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

        $category->delete();

        return response()->json([
            "status" => "success",
            "message" => "category deleted successfully"
        ], 200);
    }
}
