<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function App\Helpers\uploadFile;

class SectionController extends Controller
{
    /**
     * Add a new section.
     */
    public function addSection(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:sections,name',
            'file' => 'required|image',
            'description' => 'required|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable',
        ]);

        $validatedData['file'] = uploadFile($request->file('file'), 'sections');
        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $validatedData['type'] = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $validatedData['type'] = 'video';
        } else {
            return response()->json([
                'message' => 'Invalid file type. Only images and videos are allowed.',
                'status' => 422,
            ], 422);
        }

        $section = Section::create($validatedData);

        return response()->json([
            'message' => 'Section created successfully!',
            'data' => $section,
            'status' => 201,
        ], 201);
    }

    public function getAllSections(Request $request)
    {
        $query = $request->query();
        $sectionsQuery = Section::query();

        // Apply filtering
        if (!empty($query['name'])) {
            $sectionsQuery->where('name', 'like', "%{$query['name']}%");
        }
        if (!empty($query['id'])) {
            $sectionsQuery->where('id', $query['id']);
        }

        // Pagination parameters
        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['per_page'] ?? 10);

        $paginatedSections = $sectionsQuery->paginate($perPage, ['*'], 'page', $page);

        if ($paginatedSections->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No sections found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'total' => $paginatedSections->total(),
            'current_page' => $paginatedSections->currentPage(),
            'per_page' => $paginatedSections->perPage(),
            'total_pages' => $paginatedSections->lastPage(),
            'data' => $paginatedSections->items(),
        ], 200);
    }

    /**
     * Update a specific section by ID.
     */
    public function updateSection(Request $request, $id)
    {
        $section = Section::findOrFail($id);

        $validatedData = $request->validate([
            'name' => "sometimes|string",
            'file' => 'sometimes|file',
            'description' => 'sometimes|string',
            'button_text' => 'sometimes|string|max:255',
            'button_link' => 'sometimes',
            'status' => 'sometimes|boolean',
        ]);
        if ($request->hasFile('file')) {
            if ($section->file) {
                $image = $section->file;
                $baseUrl = url('storage/') . '/';
                $replaceFile = str_replace($baseUrl, '', $image);
                Storage::disk('public')->delete($replaceFile);
            }
            $validatedData['file'] = uploadFile($request->file('file'), 'sections');
        }

        $section->update($validatedData);

        return response()->json([
            'message' => 'Section updated successfully!',
            'data' => $section,
            'status' => 200,
        ], 200);
    }

    /**
     * Delete a specific section by ID.
     */
    public function deleteSection($id)
    {
        $section = Section::findOrFail($id);

        if (!empty($section->file)) {
            $image = $section->file;
            $baseUrl = url('storage/') . '/';
            $replaceFile = str_replace($baseUrl, '', $image);
            Storage::disk('public')->delete($replaceFile);
        }

        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully!',
            'status' => 200,
        ], 200);
    }
}
