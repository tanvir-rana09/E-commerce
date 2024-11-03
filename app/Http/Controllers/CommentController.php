<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Add a new comment
    public function addComment(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'desc' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5', // Ensure rating is between 1 and 5
        ]);

        $comment = Comment::create($validatedData);

        return response()->json([
            'message' => 'Comment created successfully!',
            'data' => $comment
        ], 201);
    }

    // Get all comments with optional filtering
    public function getAllComments(Request $request)
    {
        $query = $request->query();
        $commentsQuery = Comment::with(["user"=>function($query){
            return $query->select("id","name","email");
        }]);

        if (!empty($query['product_id'])) {
            $commentsQuery->where('product_id', $query['product_id']);
        }

        $comments = $commentsQuery->get();

        if ($comments->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No comments found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $comments
        ], 200);
    }

    // Update a specific comment by ID
    public function updateComment(Request $request, $id)
    {
        $validatedData = $request->validate([
            'desc' => 'sometimes|string',
            'rating' => 'nullable|integer|min:1|max:5', // Ensure rating is between 1 and 5
        ]);

        $comment = Comment::findOrFail($id);
        $comment->update($validatedData);

        return response()->json([
            'message' => 'Comment updated successfully!',
            'data' => $comment
        ]);
    }

    // Delete a specific comment by ID
    public function deleteComment($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully!'
        ]);
    }
}
