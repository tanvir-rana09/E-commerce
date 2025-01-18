<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function App\Helpers\uploadFile;

class SectionController extends Controller
{

    public function addSection(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'title' => 'nullable|string|unique:sections,title',
            'file' => 'required|image',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string',
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
        $sectionsQuery = Section::query()->orderBy("created_at", "desc");
    
        // Apply filtering
        if (!empty($query['title'])) {
            $sectionsQuery->where('title', 'like', "%{$query['title']}%");
        }
        if (!empty($query['name'])) {
            $sectionsQuery->where('name', $query['name']);
        }
        if (!empty($query['id'])) {
            $sectionsQuery->where('id', $query['id']);
        }
    
        // Check for pagination parameters
        $page = $query['page'] ?? null;
        $perPage = $query['per_page'] ?? null;
    
        if ($page && $perPage) {
            // Apply pagination
            $paginatedSections = $sectionsQuery->paginate((int) $perPage, ['*'], 'page', (int) $page);
    
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
        } else {
            // Return all data without pagination
            $sections = $sectionsQuery->get();
    
            if ($sections->isEmpty()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No sections found',
                ], 404);
            }
    
            return response()->json([
                'status' => 200,
                'total' => $sections->count(),
                'data' => $sections,
            ], 200);
        }
    }
    

    public function updateSection(Request $request, $id)
    {
        $section = Section::find($id);

        $validatedData = $request->validate([
            'name' => "sometimes|string",
            'title' => "sometimes|string|unique:sections,title",
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
        $section->save();

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
