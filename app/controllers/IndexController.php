<?php

class IndexController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$u = new University();
		var_dump($u->getEmailHost("jotavsdsdrj@dcc.gmail.com"));
	}
}