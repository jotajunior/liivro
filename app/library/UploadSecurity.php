<?php

class UploadSecurity
{
	private static $imageMimetypes = NULL;
	private static $imageExtensions = NULL;

	public function __construct()
	{
		$this->config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');
	}

	private static function getAcceptableImageMimeTypes()
	{
		if (self::$imageMimetypes === NULL) {
			self::$imageMimetypes = array("image/jpeg",
									 "image/pjpeg",
									 "image/png",
									 "image/bmp",
									 "image/x-windows-bmp"
				);
		}

		return self::$imageMimetypes;
	}

	private static function getAcceptableImageExtensions()
	{
		if (self::$imageExtensions === NULL) {
			self::$imageExtensions = array("jpg", "jpeg", "png", "bmp");
		}

		return self::$imageExtensions;
	}

	private function checkHeuristicallyImageMimeType($path)
	{
		$path = realpath($path);
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$fileContents = file_get_contents($path);
   		$mimeType = $finfo->buffer($fileContents);
   		
   		return in_array($mimeType, self::getAcceptableImageMimeTypes());
	}

	private function checkImageExtension($path)
	{
		$path = realpath($path);
		$extension = explode(".", $path);

		if (!is_array($extension)) {
			return false;
		}

		return in_array(end($extension), self::getAcceptableImageExtensions());
	}

	private function checkImageSize($path)
	{
		return filesize(realpath($path)) <= $this->config->upload->picture_max_size;
	}

	private function checkImageHeaderMimeType($mimeType)
	{
		return in_array($mimeType, self::getAcceptableImageMimeTypes());
	}

	public function checkImage($path, $mimetype)
	{
		// level 1 checking (cheapest ones); just move level 2 if passed here
		if (!$this->checkImageHeaderMimeType($mimetype)) {
			return false;
		}

		// level 2 checking; not so cheap.
		if (!$this->checkImageSize($path)) {
			return false;
		}

		// level 3 (and last) checking
		return $this->checkHeuristicallyImageMimeType($path);
	}
}