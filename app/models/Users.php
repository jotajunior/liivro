<?php

class Users extends \Phalcon\Mvc\Model
{
	private $id;
	private $uid = NULL;
	private $name;
	private $general_email = NULL;
	private $university_email = NULL;
	private $last_updated = NULL;
	private $status = NULL;
	private $university = NULL;
	private $created_at = NULL;

	const ACTIVE = 0;
	const IN_TRIAL = 1;
	const INVALID = 2;
	const VISITOR = 3;

	public function initialize()
	{
		$this->hasMany("id", "Books", "user_id");
		$this->belongsTo("university", "Universities", "id");
		$this->skipAttributesOnCreate(array('created_at'));
        $this->skipAttributesOnUpdate(array('last_updated'));

        $this->config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');
        $this->db = \Phalcon\DI\FactoryDefault::getDefault()->get('db');
        $this->url = \Phalcon\DI\FactoryDefault::getDefault()->getShared('url');
        
        $this->facebook = new Facebook(array(
    					 				"appId"  => $this->config->facebook->id,
					 	 				"secret" => $this->config->facebook->secret,
					 	 				"cookie" => false
										));
	}

	private function extractUserInformation()
	{
		$user = $this->getByUid();

		if ($user == array())
			return false;
		
		$this->id = $user['id'];
		$this->university = $user['university'];
		$this->general_email = $user['general_email'];
		$this->university_email = $user['university_email'];
		$this->name = $user['name'];
		$this->status = $user['status'];
		$this->created_at = $user['created_at'];
		
		return $user;
	}

	private function checkStatus()
	{
		if ($this->status == self::ACTIVE)
			return true;
		
		$now = new DateTime('now');
		$now = $now->format('Y-m-d H:i:s');
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
			$now = $now->format('Y-m-d H:i:s');
			$then = new DateTime($lastUpdated);

			$interval = $now->diff($then)->format("%d");

			$this->last_updated = $now;

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
			$this->status = self::IN_TRIAL;
			return $this->save();
		} else {
			return $this->db->update(
				"users",
				array("last_updated"),
				array($this->last_updated),
				"uid = ".$this->uid
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
			$this->getUserFacebookInformation();
			$this->checkStatus();
			
			if (!$this->facebookInformationIsRefreshed()) {
				return $this->refreshInformation();
			}
		}

		return false;
	}
	
	public function chooseUniversity($university_id)
	{
		settype($university_id, 'int');
		
		if (!$university_id) return false;

		return $this->db->update(
				"users",
				array("university", "status"),
				array($university_id, self::ACTIVE),
				"id = ".$this->id
				);
	}
}