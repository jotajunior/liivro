<?php

class UserController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
	}

	public function booksAction()
	{
		$user_id = (int) $this->session->get('id');

		$users = new Users();
		$users->id = $user_id;
		$books = $users->getBooksById();

		$this->view->setVar("books", $books);
	}
}