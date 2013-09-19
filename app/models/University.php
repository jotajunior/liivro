<?php

class University extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->hasMany("id", "User", "university");
	}
}