<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SectionController extends Controller
{
    public function addSection(Request $request)
    {

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'desc' => 'required|string',
            'image' => 'required|image',
        ]);

        $image = $request->file('image');
        $validatedData['image'] = uploadFile($image, 'section');


        $section = Section::create($validatedData);

        return response()->json([
            'message' => 'Section created successfully!',
            'data' => $section
        ], 201);
    }

    // Get all sections with optional title filtering
    public function getAllSections(Request $request)
    {
        $query = $request->query();
        $sectionsQuery = Section::query();

        if (!empty($query['title'])) {
            $sectionsQuery->where('title', $query['title']);
        }

        $sections = $sectionsQuery->get();

        if ($sections->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No sections found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $sections
        ], 200);
    }

    // Update a specific section by ID with new file(s) based on the title type
    public function updateSection(Request $request, $id)
    {
        $section = Section::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'sometimes|string|max:255',
            'desc' => 'sometimes|string',
            'image' => 'sometimes|image',
        ]);

        $image = $request->file('image');

        if ($request->hasFile('image') && $section->image) {
            Storage::disk("public")->delete($section->image);
            $validatedData['image'] = uploadFile($image, "section");
        }


        $section->update($validatedData);

        return response()->json([
            'message' => 'Section updated successfully!',
            'data' => $section
        ], 200);
    }

    // Delete a specific section by ID and all associated image
    public function deleteSection($id)
    {
        $section = Section::findOrFail($id);

        if (!empty($section->image)) {
            Storage::disk('public')->delete($section->image);
        }

        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully!'
        ]);
    }
}
