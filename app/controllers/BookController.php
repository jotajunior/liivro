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

	public function searchAction()
	{
		$term = $this->request->getPost("term", "string");
		$books = new Books();
		$result = $books->textSearch($term);

		$confirmed_user = $this->session->get("status") == Users::ACTIVE;
		
		$this->view->setVar("confirmed_user", $confirmed_user);
		$this->view->setVar("result", $result);
	}

	public function deleteAction($book_id)
	{
		settype($book_id, 'int');

		$books = new Books();
		$books->user_id = (int) $this->session->get('id');
		$books->id = $book_id;

		if ($books->verifyOwnershipById($book_id) == false) {
			$this->response->redirect('listing/index');
			$this->view->disable();
			return false;
		} else {
			$book = Books::find($book_id);
			$success = $book->delete();

			$this->view->setVar("success", $success);
		}
	}

	public function editAction($book_id)
	{
		settype($book_id, "int");

		$books = new Books();
		$books->user_id = (int) $this->session->get('id');
		$books->id = $book_id;
		$book = $books->getById($book_id);

		if ($books->verifyOwnershipById($book_id) == false || $book == array() || $book == false) {
			$this->response->redirect('listing/index');
			$this->view->disable();
			return false;
		} else {
			$categories = $books->getCategories();
			$this->view->setVar("book", $book);
			$this->view->setVar("categories", $categories);
		}
	}

	public function doEditAction()
	{
		$books = new Books();
		$books->user_id = (int) $this->session->get('id');
		$book_id = (int) $this->request->getPost("id", "int");
		$books->id = $book_id;
		$book = $books->getById($book_id);

		if ($books->verifyOwnershipById($book_id) == false || $book == array() || $book == false) {
			$this->response->redirect('listing/index');
			$this->view->disable();
			return false;
		} else {
			$books->price = $this->request->getPost("price", "float");
			$books->category = $this->request->getPost("category", "int");
			$books->title = $this->request->getPost("title", "string");
			$books->university = (int) $this->session->get("university");
			$books->picture = $book['picture'];

			$success = $books->save();

			$this->view->setVar("book_id", $book_id);
			$this->view->setVar("success", $success);
		}
	}

	public function showAction($book_id = -1)
	{
		if ($book_id == -1) {
			return;
		}

		settype($book_id, "int");
		$books = new Books();
		$book = $books->getById($book_id);
		$this->view->setVar("book", $book);

		if (isset($book['user_id'])) {
			$users = new Users();
			$user = $users->getById($book['user_id']);
			$this->view->setVar("user", $user);
		}
	}

	public function doCreateAction()
	{
		$books = new Books();
		$books->title = $this->request->getPost("title", "string");
		$books->price = $this->request->getPost("price", "float");
		$books->isbn = $this->request->getPost("isbn", "string");
		$books->author = $this->request->getPost("author", "string");
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

        $this->view->setVar("success", $success);
        $this->view->setVar("book_id", $books->id);
	}
}