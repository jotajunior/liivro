<?php

class Universities extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->hasMany("id", "Users", "university");
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
		return array(1 => array("ufmg.br", "dcc.ufmg.br", "mat.ufmg.br", "fis.ufmg.br")
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

	private function saveVerificationHash($hash, $email) {
		$is_saved = false; // select hash where email == $email from verification_hashes
		
		if ($is_saved) return false;
		
		// save verification hash to db
	}
	
	private function deleteVerificationHash($hash, $email) {
		
	}

	private function checkVerificationHash($hash, $email) {
		$hash = urldecode($hash);
		$email = urldecode($email);
	}

	private function generateVerificationUrl($hash, $email) {
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

	public function sendVerificationEmail($email)
	{
		$this->checkIfIsSupported($email);
		
		$hash = $this->generateVerificationHash($email);
		$this->saveVerificationHash($hash, $email);
		$this->sendEmail($hash, $email);
	}
}