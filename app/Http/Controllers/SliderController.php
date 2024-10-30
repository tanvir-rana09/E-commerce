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
            // 'files.*' => 'required_if:page,home|image|mimes:jpg,jpeg,png,webp|max:2048',
            // 'files' => 'required_unless:page,home|mimes:mp4,avi,mov|max:10240'
        ]);
 
        if ($request->page === 'home' && $request->hasFile('files')) {
            $validatedData['files'] = uploadFile($request->file('files'), 'slider/image');
        } else {
            foreach ($request->file('files') as $file) {
                # code...
                // return $file;
                $file = $request->file('files')[0];
                $validatedData['files'] = $file->store('videos', 'public');
            }
        }

        $slider = Slider::create([
            'page' => $validatedData['page'],
            'files' => json_encode($validatedData['files']),
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
        $validatedData = $request->validate([
            'page' => 'sometimes|string|max:255',
            'files.*' => 'required_if:page,home|image|mimes:jpg,jpeg,png,webp|max:2048',
            'file' => 'required_unless:page,home|mimes:mp4,avi,mov|max:10240'
        ]);

        $slider = Slider::findOrFail($id);
        $filePaths = json_decode($slider->files, true) ?? [];

        // Delete old files if new files are provided
        if ($request->page === 'home' && $request->hasFile('files')) {
            foreach ($filePaths as $filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $filePaths = [];
            foreach ($request->file('files') as $file) {
                $filePaths[] = uploadFile($file, 'slider/home');
            }
        } elseif ($request->hasFile('file')) {
            if (!empty($filePaths)) {
                Storage::disk('public')->delete($filePaths[0]); // Delete the single video file
            }
            $filePaths = [uploadFile($request->file('file'), 'slider/others')];
        }

        $slider->update([
            'page' => $validatedData['page'] ?? $slider->page,
            'files' => json_encode($filePaths),
        ]);

        return response()->json([
            'message' => 'Slider updated successfully!',
            'data' => $slider
        ]);
    }

    // Delete a specific slider by ID and all associated files
    public function deleteSlider($id)
    {
        $slider = Slider::findOrFail($id);
        $filePaths = json_decode($slider->files, true) ?? [];

        foreach ($filePaths as $filePath) {
            Storage::disk('public')->delete($filePath);
        }

        $slider->delete();

        return response()->json([
            'message' => 'Slider deleted successfully!'
        ]);
    }
}
