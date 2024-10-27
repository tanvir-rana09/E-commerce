<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('uploadFile')) {
	function uploadFile($images, $folder = "taskImages")
	{
		$isMultiple = is_array($images);
		$imagesPath = [];
		$imagesArray = $isMultiple ? $images : [$images];

		foreach ($imagesArray as $image) {
			$imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
			$path = $image->storeAs("products", $imageName, 'public');
			$imagesPath[] = str_replace("products/", "", $path);
		}

		return $isMultiple ? $imagesPath : $imagesPath[0];
	}
}
