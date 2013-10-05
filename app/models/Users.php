<?php

class Users extends \Phalcon\Mvc\Model
{
	private static $facebook = NULL;
	private $id;
	private $uid = NULL;
	private $name;
	private $email = NULL;
	private $last_updated = NULL;
	private $status = NULL;
	private $university = NULL;

	public function initialize()
	{
		$this->hasMany("id", "Books", "user_id");
		$this->belongsTo("university", "Universities", "id");
		$this->skipAttributesOnCreate(array('created_at'));
        $this->skipAttributesOnUpdate(array('last_updated'));

        $this->config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');
        $this->db = \Phalcon\DI\FactoryDefault::getDefault()->get('db');
        $this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
	}

	private function registerLoginSessions()
	{
		if ($this->uid == NULL) return false;

		if (!$this->isAuthenticated()) {
			$this->session->set('uid', $this->uid);
			$this->session->set('id', $this->id);
			$this->session->set('university', $this->university);
			$this->session->set('email', $this->email);
			$this->session->set('name', $this->name);
		}
		
		return true;
	}

	private function getFacebookParams()
	{
		return array("appId"  => $this->config->facebook->id,
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
			if ($lastUpdated == NULL) return false;

			$now = new DateTime('now');
			$now = $now->format('Y-m-d H:i:s');
			$then = new DateTime($lastUpdated);

			$interval = $now->diff($then)->format("%d");

			if ($interval < $this->config->facebook->daysToUpdate) return true;
		}
		
		return false;
	}
	
	public function refreshInformation()
	{
		$user = $this->getByUid();

		if (!$user) {
			return $this->save();
		}
		
		return true;
	}
	
	public function getFacebookLoginUrl()
	{
		$facebook = $this->getFacebookInstance();
		$params = array(
  					'scope' => 'email',
				    'redirect_uri' => $this->config->application->baseUri
					);
		return $facebook->getLoginUrl($params);
	}
	
	public function facebookAuth()
	{
		$facebook = $this->getFacebookInstance();
		
		if ($this->isAuthenticatedOnFacebook()) {

			$this->getUserFacebookInformation();
			$this->registerLoginSessions();

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
				array($university_id, 0),
				"uid = ".$this->uid
				);
	}
	
	public function isAuthenticated()
	{
		return $this->session->has('uid');
	}
	
	public function logout()
	{
		$this->session->destroy();
		return true;
	}
}