<?php

class Universities extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->hasMany("id", "Users", "university");

        $this->config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');
        $this->db = \Phalcon\DI\FactoryDefault::getDefault()->get('db');
        $this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
        $this->url = \Phalcon\DI\FactoryDefault::getDefault()->getShared('url');
	}
	
	public function findByAcronym($acronym)
	{
		return self::findOne(array(
							"conditions" => "acronym = ?1",
							"bind" => array(1 => $acronym)
							));
	}
	
	private function removeEmailPrepend($host)
	{
		$exploded = explode(".", $host);
		$number_of_dots = count($exploded) - 1;

		if ($number_of_dots == 2) {
			return $exploded[1].'.'.$exploded[2]; 
		}
		
		return $host;
	}

	private function getEmailHost($email)
	{
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);

		if (!$email) {
			throw new \InvalidArgumentException("Invalid email.");
		}

		preg_match_all("/\@(.*)/", $email, $host);
		$host = $this->removeEmailPrepend($host[1][0]);

		return $host;
	}

	private function getListOfSupportedEmailHosts()
	{
		return array(1 => array("ufmg.br")
					);
	}

	private function checkIfIsSupported($email)
	{
		$host = $this->getEmailHost($email);
		$supported = $this->getListOfSupportedEmailHosts();
		
		foreach ($supported as $university_id => $list_of_hosts) {
			if (in_array($host, $list_of_hosts)) {
				return $university_id;
			}
		}
		
		throw new \InvalidArgumentException("Your university is not currently supported.");
	}

	private function saveVerificationHash($hash, $email)
	{
		return $this->db->insert(
						 "verification_hashes",
						 array("email", "hash"),
						 array($email, $hash)
				);
	}
	
	private function deleteVerificationHash($hash, $email)
	{
		return $this->db->execute("DELETE FROM verification_hashes WHERE email = ? AND hash = ?", array(1 => $email, 2 => $hash));
	}

	private function checkVerificationHash($hash, $email)
	{
		$hash = urldecode($hash);
		$email = urldecode($email);
		
		$sql = "SELECT * FROM verification_hashes WHERE hash = :hash AND email = :email ORDER BY id DESC LIMIT 1";
		$bindParams = array("hash" => $hash, "email" => $email);
		
		$result = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParams);
		
		if ($result) return true;
		return false;
	}

	private function generateVerificationUrl($hash, $email)
	{
		$url = $this->url->get("user/verify");
		$url .= "/".urlencode($hash)."/".urlencode($email);

		return $url;
	}

	private function generateVerificationHash($email)
	{
		$salt = "lKisYuR5o09dm@wt%ahyO9s8&s1*9()s_=";
		$salt .= $email;
		$salt .= uniqid('', true);
		
		return password_hash($salt, PASSWORD_DEFAULT);
	}

	private function sendEmail($hash, $email)
	{
		$sendgrid = new SendGrid($this->config->sendgrid->username, $this->config->sendgrid->password);
		$mail = new SendGrid\Mail();
		
		$verification_url = $this->generateVerificationUrl($hash, $email);
		
		$mail->addTo($email)
			 ->setFrom($this->config->sendgrid->noreply)
			 ->setSubject("Verify your Liivro account")
			 ->setHtml($verification_url);

		if (!$sendgrid->web->send($mail)) {
			$this->deleteVerificationHash($hash, $email);
			throw new \Exception("We failed sending your email. Please, try again.");
		}
	}

	private function checkIfIsAlreadyActivated($id)
	{
		settype($uid, 'int');
		$sql = "SELECT status FROM users WHERE id = :id";
		$bindParams = array("id" => $uid);
		
		$result = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParams);
		
		if ($result == array())
			throw new \Exception("Invalid user id for verification e-mail.");
		else if ($result[0]['status'] == 0)
			throw new \Exception("Your account has already been activated.");
	}

	public function sendVerificationEmail($email, $id)
	{
		$this->checkIfIsSupported($email);
		$this->checkIfIsAlreadyActivated($id);
		
		$hash = $this->generateVerificationHash($email);
		$this->saveVerificationHash($hash, $email);
		$this->sendEmail($hash, $email);
	}
}