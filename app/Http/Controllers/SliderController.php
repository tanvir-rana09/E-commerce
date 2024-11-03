<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    // Add a new slider with file(s) upload based on the page type
    public function addSlider(Request $request)
    {

        $validatedData = $request->validate([
            'page' => 'required|string|max:255',
            'files' => 'array',
        ]);
        
        $files = $request->file('files', []);
        $uploadedFiles = [];
        
        foreach ($files as $file) {
            $folder = str_starts_with($file->getMimeType(), "image/") ? 'slider/image' : 'slider/video';
            $uploadedFiles[] = uploadFile($file, $folder);
        }
        
        $slider = Slider::create([
            'page' => $validatedData['page'],
            'files' => json_encode($uploadedFiles),
        ]);
        
        return response()->json([
            'message' => 'Slider created successfully!',
            'data' => $slider
        ], 201);
    }

    // Get all sliders with optional page filtering
    public function getAllSliders(Request $request)
    {
        $query = $request->query();
        $slidersQuery = Slider::query();

        if (!empty($query['page'])) {
            $slidersQuery->where('page', $query['page']);
        }

        $sliders = $slidersQuery->get();

        if ($sliders->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No sliders found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $sliders
        ], 200);
    }

    // Update a specific slider by ID with new file(s) based on the page type
    public function updateSlider(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $validatedData = $request->validate([
            'page' => 'required|string|max:255',
            'files' => 'array',
        ]);
    
        $files = $request->file('files', []);

        if ($request->hasFile('files') && $slider->files) {
            Storage::disk("public")->delete($slider->files);
        }

        $uploadedFiles = $slider->files ?? []; // Keep existing files if no new uploads
        foreach ($files as $file) {
            $folder = str_starts_with($file->getMimeType(), "image/") ? 'slider/image' : 'slider/video';
            $uploadedFiles[] = uploadFile($file, $folder);
        }
    
        $slider->update([
            'page' => $validatedData['page'],
            'files' => json_encode($uploadedFiles),
        ]);
    
        return response()->json([
            'message' => 'Slider updated successfully!',
            'data' => $slider
        ], 200);
    }

    // Delete a specific slider by ID and all associated files
    public function deleteSlider($id)
    {
        $slider = Slider::findOrFail($id);

        if(!empty($slider->files)) {
            Storage::disk('public')->delete($slider->files);
        }

        $slider->delete();

        return response()->json([
            'message' => 'Slider deleted successfully!'
        ]);
    }
}
