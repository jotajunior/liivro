<?php

class Books extends \Phalcon\Mvc\Model
{
	public $id;
	public $title = NULL;
	public $price = NULL;
	public $picture = NULL;
	public $university = NULL;
	public $user_id = NULL;
	public $category = NULL;
	public $last_updated = NULL;
	public $created_at = NULL;
	
	private $page = 1;
	private $per_page = 30;
	
	public function __set($var, $val)
	{
		switch ($var) {
			case "university":
			case "user_id":
			case "category":
			case "id":
				$this->$var = (int) $val;
				break;
			case "price":
				$this->$var = (float) $val;
				break;
			case "title":
				$this->$var = $val;
				break;
			case "picture":
				$pic_name = $this->uploadPicture($val);
				$this->$var = $pic_name;
				break;
			default:
				throw new \Exception("$var **** You cannot create new attributes on Books.");
				break;
		}
	}

	public function beforeSave()
	{
		$now = new DateTime("now");
		$now = $now->format("Y-m-d H:i:s");

		$this->created_at = $now;
		$this->last_updated = $now;
	}

	public function university($university)
	{
		settype($university, 'int');
		
		$this->university = $university;
		return $this;
	}
	
	public function perPage($perPage)
	{
		settype($perPage, 'int');
		$this->per_page = $perPage;
		
		return $this;
	}
	
	public function page($page)
	{
		settype($page, 'int');

		$this->page = $page;
		return $this;
	}

	public function category($category)
	{
		settype($category, 'int');
		
		$this->category = $category;
		return $this;
	}

	private function getSkip()
	{
		if ($this->page == NULL || $this->university == NULL || $this->per_page == NULL) {
			throw new \Exception("There is not enough information for pagination.");
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
		
		$sql = "SELECT * FROM books WHERE university :university AND category = :category";
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
}