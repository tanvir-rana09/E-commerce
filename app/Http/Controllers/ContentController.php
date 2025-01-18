<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function upsertContent(Request $request, $type)
    {
        // Validate input
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        // Upsert content
        $content = Content::updateOrCreate(
            ['type' => $type],
            ['content' => $validated['content']]
        );

        return response()->json([
            'message' => ucfirst($type) . ' updated successfully.',
            'data' => $content,
            'status' => 200,
        ], 200);
    }

    public function getContent($type)
    {
        // Find content by type
        $content = Content::where('type', $type)->first();

        // Handle not found case
        if (!$content) {
            return response()->json([
                'message' => ucfirst($type) . ' not found.',
                'status'=>404
            ], 200);
        }

        return response()->json([
            'data' => $content,
            'status' => 200,
        ], 200);
    }
}
