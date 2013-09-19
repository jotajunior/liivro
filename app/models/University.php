<?php

class University extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->hasMany("id", "User", "university");
	}
	
	public function findByAcronym($acronym)
	{
		return self::findOne(array(
							"conditions" => "acronym = ?1",
							"bind" => array(1 => $acronym)
							));
	}
	
	private function getEmailHost($email)
	{
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);

		if (!$email) {
			throw new \InvalidArgumentException("Invalid e-mail");
		}

		preg_match_all("/\@(.*)/", $email, $host);
		return $host[1][0];
	}

	private function isSupported($host)
	{
		$options = $explode(".", $host);
	}

	public function validateEmail($email)
	{
		$host = $this->getEmailHost($email);
			
	}
}