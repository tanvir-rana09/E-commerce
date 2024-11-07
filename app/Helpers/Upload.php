<?php
namespace App\Helpers;


if (!function_exists('uploadFile')) {
	function uploadFile($images, $folder = "taskImages")
	{
		$isMultiple = is_array($images);
		$imagesPath = [];
		$imagesArray = $isMultiple ? $images : [$images];

		foreach ($imagesArray as $image) {
			$imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
			$path = $image->storeAs($folder, $imageName, 'public');
			$imagesPath[] = $path;
		}

		return $isMultiple ? $imagesPath : $imagesPath[0];
	}
}
