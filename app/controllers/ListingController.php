<?php

class ListingController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
//		$this->users = new Users();
	}

	public function indexAction()
	{
		$books = new Books();
	}
}