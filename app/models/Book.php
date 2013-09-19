<?php

class Book extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->belongsTo("user_id", "User", "id");
	}
}