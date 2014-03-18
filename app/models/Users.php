<?php

class Users extends \Phalcon\Mvc\Model
{
	private $id = NULL;
	private $uid = NULL;
	private $name;
	private $general_email = NULL;
	private $university_email = NULL;
	private $last_updated = NULL;
	private $status = NULL;
	private $university = NULL;
	private $created_at = NULL;
	private $major = NULL;

	const ACTIVE = 0;
	const IN_TRIAL = 1;
	const INVALID = 2;
	const VISITOR = 3;

	public function initialize()
	{
        $this->config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');
        $this->db = \Phalcon\DI\FactoryDefault::getDefault()->get('db');
        $this->url = \Phalcon\DI\FactoryDefault::getDefault()->getShared('url');
        
        $this->facebook = new Facebook(array(
    					 				"appId"  => $this->config->facebook->id,
					 	 				"secret" => $this->config->facebook->secret,
					 	 				"cookie" => false
										));
	}

	public function extractUserInformation()
	{
		$user = $this->getByUid();

		if ($user == array())
			return false;

		$this->checkStatus($user);
		
		$this->id = $user['id'];
		$this->university = $user['university'];
		$this->general_email = $user['general_email'];
		$this->university_email = $user['university_email'];
		$this->name = $user['name'];
		$this->created_at = $user['created_at'];
		$this->major = $user['major'];

		$user['status'] = $this->status;

		return $user;
	}

	private function checkStatus($user = NULL)
	{
		if ($user === NULL) {
			$user = $this->getByUid();
		}

		if (!$user) {
			$this->status = self::IN_TRIAL;
			return true;
		} else {
			$this->status = $user['status'];
		}

		if ($this->status == self::ACTIVE) {
			return true;
		}
		
		$now = new DateTime('now');
		$then = new DateTime($this->created_at);

		$interval = $now->diff($then)->format("%d");

		if ($interval >= $this->config->trial->daysToExpire) {
			$this->status = self::INVALID;
		
			return $this->db->update(
			   			      "users",
					   		  array("status"),
					   		  array(self::INVALID),
					   		  "id = ".$this->id
					);
		} else {
			$this->status = self::IN_TRIAL;
			return true;
		}
	}

	private function isAuthenticatedOnFacebook()
	{
		$id = $this->facebook->getUser();
		
		if ($id == 0) {
			return false;
		} else {
			$this->uid = $id;
			return true;
		}
	}
	
	private function getUserFacebookInformation()
	{
		$result = $this->facebook->api('/me', 'GET');

		$this->name = $result['name'];
		$this->general_email = $result['email'];
	}

	private function getByUid()
	{
		$sql = "SELECT * FROM users WHERE uid = :uid";
		$bindParam = array("uid" => $this->uid);
		
		return $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}
	
	private function facebookInformationIsRefreshed()
	{
		$user = $this->getByUid();
	
		if ($user) {
			$lastUpdated = $user['last_updated'];

			$now = new DateTime('now');
			$then = new DateTime($lastUpdated);

			$interval = $now->diff($then)->format("%d");

			$this->last_updated = $now->format("Y-m-d H:i:s");

			if ($lastUpdated == NULL)
				return false;

			if ($interval < $this->config->facebook->daysToUpdate)
				return true;
		}
		
		return false;
	}
	
	public function refreshInformation()
	{
		$user = $this->getByUid();
		
		if (!$user) {
			$this->getUserFacebookInformation();
			$this->status = self::IN_TRIAL;
			return $this->save();
		} else {
			return $this->db->update(
				"users",
				array("last_updated"),
				array($this->last_updated),
				"uid = " . $this->uid
				);
		}
		
		return true;
	}
	
	public function getFacebookLoginUrl()
	{
		$params = array(
  					'scope' => 'email',
				    'redirect_uri' => $this->config->application->baseUri
					);
		return $this->facebook->getLoginUrl($params);
	}
	
	public function facebookAuth()
	{
		if ($this->isAuthenticatedOnFacebook()) {
			$this->checkStatus();

			if (!$this->facebookInformationIsRefreshed()) {
				return $this->refreshInformation();
			}
			return true;
		}
		return false;
	}
	
	public function chooseUniversity($university_id)
	{
		settype($university_id, 'int');
		
		if (!$university_id) {
			return false;
		}

		return $this->db->update(
				"users",
				array("university", "status", "university_email"),
				array($university_id, self::ACTIVE, $this->university_email),
				"id = " . $this->id
				);
	}

	public function getBooksById()
	{
		settype($this->id, 'int');

		if ($this->id === NULL) {
			throw new \Exception("Undefined user id. Unable to load book list.");
		}

		$sql = "SELECT * FROM books WHERE user_id = :id";
		$bindParam = array("id" => $this->id);

		return $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}

	// prevent phalcon overhead on Users::find()
	public function getById($id)
	{
		settype($id, "int");

		if ($id == 0) {
			return false;
		}

		$sql = "SELECT * FROM users WHERE id = :id";
		$bindParam = array("id" => $id);

		return $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParam);
	}

	public function defineMajor($id, $major)
	{
		settype($major, "int");
		settype($id, "int");

		if ($major == 0) {
			return false;
		}

		return $this->db->update(
				"users",
				array("major"),
				array($major),
				"id = " . $id
				);
	}
}