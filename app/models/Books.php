<?php

class Books extends \Phalcon\Mvc\Model
{
	private $title = NULL;
	private $price = NULL;
	private $picture = NULL;
	private $university = NULL;
	private $user_id = NULL;
	private $category = NULL;
	
	private $page = 1;
	private $per_page = 30;

	public function initialize()
	{
		$this->belongsTo("user_id", "Users", "id");
	}
	
	public function __set($var, $val)
	{
		switch($var) {
			case "university":
			case "user_id":
			case "category":
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
				throw new \Exception("You cannot create new attributes on Books.");
				break;
		}
	}
	
	private function uploadPicture($tmp_name)
	{
		//upload fucking shit to S3
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
			throw new \Exception("There is not enought information for pagination.");
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
		if ($this->category == NULL) {
			return $this->fetchListingByUniversity();
		} else {
			return $this->fetchListingByUniversityAndCategory();
		}	
	}
}