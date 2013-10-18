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
			$facebook_login_url = $this->users->getFacebookLoginUrl();
			echo $this->view->render('landing/index', array(
													   "facebook_login_url" => $facebook_login_url
													  )
								);
		} else {
			echo "Ola, ".$this->session->get('name');
						echo "<a href='/liivro/index/logout'>logout</a>";
						
		}
	}
	
	public function testSessionAction()
	{
		$this->session->set("whatever", 2);
		var_dump($this->session->get("whatever"));
	}
	public function logoutAction()
	{
		$this->users->logout();
		$this->response->redirect("index");
	}
}