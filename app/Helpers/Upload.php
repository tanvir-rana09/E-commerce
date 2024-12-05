<?php

namespace App\Helpers;


if (!function_exists('uploadFile')) {
    function uploadFile($images, $folder = "taskImages")
    {
        $isMultiple = is_array($images);
        $imagesPath = [];
        $imagesArray = $isMultiple ? $images : [$images];

        foreach ($imagesArray as $image) {
            if (is_file($image)) {
                $imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs($folder, $imageName, 'public');
                $imagesPath[] = $path;
            } else {
                // Add only if it's a URL or existing path
                $imagesPath[] = $image;
            }
        }

        return $isMultiple ? $imagesPath : $imagesPath[0];
    }
}
