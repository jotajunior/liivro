<?php

class Books extends \Phalcon\Mvc\Model
{
	public $id = NULL;
	public $title = NULL;
	public $price = NULL;
	public $picture = NULL;
	public $university = NULL;
	public $user_id = NULL;
	public $category = NULL;
	public $last_updated = NULL;
	public $created_at = NULL;
	public $isbn = NULL;
	public $author = NULL;
	
	public $page = 1;
	public $per_page = 30;
	
	public function initialize()
	{
		$this->db = \Phalcon\DI\FactoryDefault::getDefault()->get('db');
	}

	public function beforeSave()
	{
		$now = new DateTime("now");
		$now = $now->format("Y-m-d H:i:s");

		$this->created_at = $now;
		$this->last_updated = $now;
	}

	private function getSkip()
	{
		if ($this->page === NULL || $this->per_page === NULL) {
			throw new \Exception("There is not enough information for pagination.");
		}
		
		if ($this->page == 0) {
			return 0;
		}

		return ($this->page - 1)*$this->per_page;
	}
	
	private function fetchListingByUniversity()
	{
		$skip = (int) $this->getSkip();
		
		$sql = "SELECT * FROM books WHERE university = :university";
		$sql .= " ORDER BY id DESC LIMIT ".$skip.", ".$this->per_page;
		
		$bindParam = array("university" => $this->university);
		
		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}
	
	private function fetchListingByUniversityAndCategory()
	{
		$skip = (int) $this->getSkip();
		
		$sql = "SELECT * FROM books WHERE university = :university AND category = :category";
		$sql .= " ORDER BY id DESC LIMIT ".$skip.", ".$this->per_page;
		
		$bindParam = array("university" => $this->university, "category" => $this->category);
		
		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}

	public function fetchUniversityListing()
	{	
		if ($this->category === NULL) {
			return $this->fetchListingByUniversity();
		} else {
			return $this->fetchListingByUniversityAndCategory();
		}	
	}

	public function generatePictureName($title, $university, $user_id)
	{
		$name = md5(uniqid("", true) . $title);
		$name .= $university;
		$name = $user_id . "_" . sha1($name . $user_id) . ".jpg";

		return $name;
	}

	public function uploadPictureToS3($pic_name, $unique_file_name)
	{
		$config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');

		$client = Aws\S3\S3Client::factory(array(
    				'key'    => $config->s3->key,
   			 		'secret' => $config->s3->secret
				));

		$result = $client->putObject(array(
    				'Bucket'     => $config->s3->books_bucket,
    				'Key'        => $unique_file_name,
    				'SourceFile' => $pic_name,
    				'ACL'        => 'public-read'
				));

		if (isset($result['ObjectURL'])) {
			return $result['ObjectURL'];
		} else {
			return NULL;
		}
	}

	public function resizePicture($pic_name)
	{
		$config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');

		list($width, $height) = getimagesize($pic_name);

		$ratio = min($config->book->picture_listing_width/$width, $config->book->picture_listing_height/$width);

		if ($ratio >= 1) {
			return true;
		}

		$new_width = $width * $ratio;
		$new_height = $height * $ratio;

		$img = new \Imagick($pic_name);
		$img->thumbnailImage($new_width, $new_height, true);
		$done = $img->writeImage($pic_name);
		$img->destroy();

		return $done;
	}

	public function getTotalCount()
	{
		if ($this->university == NULL) {
			return false;
		}

		$sql = "SELECT COUNT(id) AS counter FROM books";
		$result = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC);
		return $result['counter'];
	}

	public function getCategories()
	{
		$sql = "SELECT * FROM categories";
		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);
	}

	public function verifyOwnershipById($id)
	{
		if ($id === NULL || $this->user_id === NULL) {
			throw new \Exception("There is no enough information to verify the ownership of this book.");
		}

		$sql = "SELECT user_id FROM books WHERE id = :id";
		$bindParam = array("id" => $id);

		$result = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);

		if ($result == array() || $result == FALSE) {
			return false;
		}

		return $result['user_id'] == $this->user_id;
	}

	// to prevent phalcon overhead on Books::find()
	public function getById($id)
	{
		settype($id, "int");

		if ($id == 0) {
			return false;
		}

		$sql = "SELECT * FROM books WHERE id = :id";
		$bindParam = array("id" => $id);

		return $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}

	public function textSearch($term)
	{
		$sql = "SELECT * FROM books WHERE title LIKE :term";
		$bindParam = array("term" => "%" . $term . "%");

		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}

	public function getByIsbn($isbn)
	{
		preg_replace("/[^0-9]/", "", $isbn);
		$sql = "SELECT * FROM books WHERE isbn = :isbn";
		$bindParam = array("isbn" => $isbn);

		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}

	public function getMostSoldBooksByIsbn($isbn, $limit)
	{
		settype($limit, "integer");

		$sql = "SELECT books.isbn, COUNT(books.id) AS counter FROM transactions INNER JOIN books ON transactions.book = books.id
		WHERE books.isbn = :isbn GROUP BY transactions.isbn ORDER BY counter DESC LIMIT $limit";

		$bindParam = array("isbn" => $isbn);

		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}
}