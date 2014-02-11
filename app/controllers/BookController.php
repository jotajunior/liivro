<?php

class BookController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
	}

	public function createAction()
	{
	}

	public function doCreateAction()
	{
		$books = new Books();
		$books->title = $this->request->getPost("title", "string");
		$books->price = $this->request->getPost("price", "float");
		$books->category = (int) $this->request->getPost("category", "int");
		$books->user_id = (int) $this->session->get("id");
		$books->university = (int) $this->session->get("university");

		if ($this->request->hasFiles() == true) {
			$uploadSecurity = new UploadSecurity();

			$pictures_per_book = 0;

			foreach ($this->request->getUploadedFiles() as $file) {
				if ($pictures_per_book >= $this->config->book->max_pictures_per_book) {
					break;
				}

				if (!$uploadSecurity->checkImage($file->getTempName(), $file->getType())) {
					continue;
				}

			 	$unique_file_name = $books->generatePictureName($books->title, $books->university, $books->user_id);
			 	$pic_name = $this->config->book->picture_listing_dir . $unique_file_name;

                if ($file->moveTo($pic_name)) {
                	if (!$books->resizePicture($pic_name)) {
                		unlink($pic_name);
                		continue;	
                	}

					$books->picture = $books->uploadPictureToS3($pic_name, $unique_file_name);
					$pictures_per_book++;
					unlink($pic_name);
                }
            }
        }

        $success = $books->save();
	}
}