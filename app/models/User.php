<?php

class User extends \Phalcon\Mvc\Model
{
	private static $facebook = NULL;
	private $id;
	private $uid = NULL;
	private $name;
	private $email = NULL;
	private $last_updated = NULL;
	private $status = NULL;

	public function initialize()
	{
		$this->hasMany("id", "Book", "user_id");
		$this->belongsTo("university", "University", "id");
	}

	private function getFacebookParams()
	{
		return array( "appId"  => $this->config->facebook->id,
			     	  "secret" => $this->config->facebook->secret,
					  "cookie" => false
					);
	}

	private function getFacebookInstance()
	{
		$params = $this->getFacebookParams();
		
		if (self::$facebook == NULL) {
			self::$facebook = new Facebook($params);
		}
		
		return self::$facebook;
	}

	private function isAuthenticatedOnFacebook()
	{
		$facebook = $this->getFacebookInstance();
		$id = $facebook->getUser();
		
		if ($id == 0) {
			return false;
		} else {
			$this->uid = $id;
			return true;
		}
	}
	
	private function getUserFacebookInformation()
	{
		$facebook = $this->getFacebookInstance();
		$result = $facebook->api('/me', 'GET');

		$this->name = $result['name'];
		$this->email = $result['email'];
	}
	
	private function facebookInformationIsRefreshed()
	{
		$user = self::findOne(array(
    						"conditions" => "uid = ?1",
						    "bind" => array(1 => $this->uid)
						  ));
		
		if ($user) {
			$lastUpdated = $user['last_updated'];
			if ($lastUpdated == NULL) return false;

			$now = new DateTime('now');
			$now = $now->format('Y-m-d H:i:s');
			$then = new DateTime($lastUpdated);

			$interval = $now->diff($then)->format("%d");

			if ($interval <= $this->config->facebook->daysToUpdate) return true;
		}
		
		return false;
	}
	
	public function facebookAuth()
	{
		$facebook = $this->getFacebookInstance();
		
		if ($this->isAuthenticatedOnFacebook()) {
			$this->getUserFacebookInformation();
			
			if (!$this->facebookInformationIsRefreshed()) {
				return $this->save();
			}
		}

		return false;
	}
	
	public function chooseUniversity($university_id)
	{
		settype($university_id, 'int');
		
		$this->status = 0;
		$this->save();
	}
}