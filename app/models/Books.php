<?php

class Books extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->belongsTo("user_id", "Users", "id");
	}
}