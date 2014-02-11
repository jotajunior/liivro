<?php

class Universities extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
        $this->config = \Phalcon\DI\FactoryDefault::getDefault()->getShared('config');
        $this->db = \Phalcon\DI\FactoryDefault::getDefault()->get('db');
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

		if ($number_of_dots === 2) {
			return $exploded[1] . "." . $exploded[2]; 
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

	public function checkIfIsSupported($email)
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

	private function saveVerificationHash($user_id, $hash, $email)
	{
		settype($user_id, "integer");

		return $this->db->insert(
						 "verification_hashes",
						 array($email, $hash, $user_id),
						 array("email", "hash", "user_id")
				);
	}
	
	public function deleteVerificationHash($user_id)
	{
		settype($user_id, 'integer');
		return $this->db->delete("verification_hashes", "user_id = " . $user_id);
	}

	public function checkVerificationHash($hash, $email, $user_id)
	{
		settype($user_id, "integer");

		$email = urldecode($email);

		$sql = "SELECT * FROM verification_hashes WHERE hash = :hash AND email = :email AND user_id = :user_id";
		$bindParams = array("hash" => $hash, "email" => $email, "user_id" => $user_id);
		
		$result = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParams);
		
		if ($result) {
			return true;
		}

		return false;
	}

	private function generateVerificationUrl($user_id, $hash, $email)
	{
		settype($user_id, "integer");

		$url = $this->url->get("university/verify");
		$url .= "/" . $hash . "/" . urlencode($email) . "/" . $user_id;

		return $url;
	}

	private function generateVerificationHash($email, $user_id)
	{
		settype($user_id, 'integer');

		$salt = "lKisYuR5o09dm@wt%ahyO9s8&s1*9()s_=";
		$salt .= $email;
		$salt .= uniqid('', true);
		$salt2 = "koo9a8jJdhnMMajdnbY7*7a%%42$#afG";
		$salt2 .= $user_id * 97.32;

		return sha1($salt2 . md5($salt));
	}

	private function sendEmail($hash, $email, $user_id)
	{
		$sendgrid = new SendGrid($this->config->sendgrid->username, $this->config->sendgrid->password);
		$mail = new SendGrid\Mail();
		
		$verification_url = $this->generateVerificationUrl($user_id, $hash, $email);
		
		$mail->addTo($email)
			 ->setFrom($this->config->sendgrid->noreply)
			 ->setSubject("Verify your Liivro account")
			 ->setHtml($verification_url);

		if (!$sendgrid->web->send($mail)) {
			$this->deleteVerificationHash($user_id);
			throw new \Exception("We failed sending your email. Please, try again.");
		}
	}

	private function checkIfIsAlreadyActivated($id)
	{
		settype($id, 'int');
		$sql = "SELECT status FROM users WHERE id = :id";
		$bindParams = array("id" => $id);
		
		$result = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bindParams);
		
		if ($result == array())
			throw new \Exception("Invalid user id for verification e-mail.");
		else if ($result['status'] == 0)
			throw new \Exception("Your account has already been activated.");
	}

	public function sendVerificationEmail($email, $id)
	{
		$this->checkIfIsSupported($email);
		$this->checkIfIsAlreadyActivated($id);
		$hash = $this->generateVerificationHash($email, $id);
		$this->deleteVerificationHash($id);
		$this->saveVerificationHash($id, $hash, $email);
		$this->sendEmail($hash, $email, $id);
	}
}