<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

use function App\Helpers\uploadFile;
use function PHPUnit\Framework\isEmpty;

class SiteSettingController extends Controller
{
    public function updateSettings(Request $request)
    {
        $validationRules = [
            'site_name' => 'nullable|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'contact_email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:15',
            'contact_address' => 'nullable|string|max:255',
            'footer_title' => 'nullable|string|max:255',
            'footer_description' => 'nullable|string',
            'footer_copywritetext' => 'nullable|string|max:255',
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'instagram' => 'nullable|url',
            'whatsapp' => 'nullable|url',
            'linkedin' => 'nullable|url',
        ];

        // Validate the incoming request data
        $validatedData = $request->validate($validationRules);

        $uploadedFilePath = null;



        if ($request->hasFile('site_logo')) {
            $currentLogo = SiteSetting::where('key', 'site_logo')->value('value');
            if ($currentLogo) {
                $baseUrl = url('storage/') . '/';
                $fileToDelete = str_replace($baseUrl, '', $currentLogo);
                Storage::disk('public')->delete($fileToDelete);
            }
            $uploadedFilePath = uploadFile($request->file('site_logo'), 'settings');
            $validatedData['site_logo'] = $uploadedFilePath;
        }
        if ($request->hasFile('favicon')) {
            $currentLogo = SiteSetting::where('key', 'favicon')->value('value');
            if ($currentLogo) {
                $baseUrl = url('storage/') . '/';
                $fileToDelete = str_replace($baseUrl, '', $currentLogo);
                Storage::disk('public')->delete($fileToDelete);
            }
            $uploadedFilePath = uploadFile($request->file('favicon'), 'settings');
            $validatedData['favicon'] = $uploadedFilePath;
        }

        DB::beginTransaction();

        try {
            // Prepare data for upsert
            $data = collect($validatedData)->map(function ($value, $key) {
                return [
                    'key' => $key,
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            })->values()->toArray();

            // Perform batch upsert
            DB::table('site_settings')->upsert($data, ['key'], ['value', 'updated_at']);

            DB::commit();

            // Cache the updated settings
            Cache::put('site_settings', DB::table('site_settings')->pluck('value', 'key')->toArray(), 3600);

            return response()->json(['message' => 'Site settings updated successfully.','status' => 200]);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Delete the newly uploaded logo file in case of an error
            if ($uploadedFilePath) {
                Storage::disk('public')->delete($uploadedFilePath);
            }

            return response()->json(['error' => 'Failed to update site settings.', 'data' => $e], 500);
        }
    }


    public function getAllSettings()
    {
        try {
            $settings = SiteSetting::pluck('value', 'key')->map(function ($value) {
                    $decoded = json_decode($value, true);
                    return $decoded !== null ? $decoded : $value;
                });
            if ($settings['site_logo']) {
                $settings['site_logo'] = url('storage/' .  $settings['site_logo']);
            }
            if (isset($settings['favicon']) && !empty($settings['favicon'])) {
                $settings['favicon'] = url('storage/' . $settings['favicon']);
            }
            return response()->json(['data' => $settings, 'status' => 200], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch site settings.', 'data' => $e], 500);
        }
    }
}
