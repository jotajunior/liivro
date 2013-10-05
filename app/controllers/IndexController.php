<?php

class IndexController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->users = new Users();
	}

	public function indexAction()
	{
		$auth = $this->users->facebookAuth();
		
		if (!$auth) {
			echo "<a href='".$this->users->getFacebookLoginUrl()."'>Login</a>|||";

		} else {
			echo "Ola, ".$this->session->get('name');
		}
					echo "<a href='/liivro/index/logout'>logout</a>";
	}
	
	public function logoutAction()
	{
		$this->users->logout();
		$this->response->redirect("index");
	}
}