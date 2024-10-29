<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    // Add a new blog with image upload
    public function addBlog(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'desc' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Image validation
        ]);

        // Save the image
        if ($request->hasFile('image')) {
            $validatedData['image'] = uploadFile($request->file("image"),"blog");
        }

        $blog = Blog::create($validatedData);

        return response()->json([
            'message' => 'Blog created successfully!',
            'data' => $blog
        ], 201);
    }

    public function getAllCategories(Request $request){
        $query = $request->query();
        $blogsQuery = Blog::query();

        if (!empty($query['title'])) {
            $blogsQuery->where('title', 'like', '%' . $query['title'] . '%');
        }
    
        $blogs = $blogsQuery->get();
    
        if ($blogs->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No blogs found'
            ]);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $blogs
        ], 200);
    }

    public function updateBlog(Request $request, $id)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes|string|max:255',
            'desc' => 'sometimes|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Image validation
        ]);

        $blog = Blog::findOrFail($id);

        // If a new image is uploaded, delete the old one and upload the new one
        if ($request->hasFile('image')) {
            if ($blog->image) {
                Storage::disk('public')->delete($blog->image);
            }
            $validatedData['image'] = uploadFile($request->file("image"),"blog");
        }

        $blog->update($validatedData);

        return response()->json([
            'message' => 'Blog updated successfully!',
            'data' => $blog
        ]);
    }

    // Delete a specific blog by ID along with its image
    public function deleteBlog($id)
    {
        $blog = Blog::findOrFail($id);

        // Delete the image file if it exists
        if ($blog->image) {
            Storage::disk('public')->delete($blog->image);
        }

        $blog->delete();

        return response()->json([
            'message' => 'Blog deleted successfully!'
        ]);
    }
}
