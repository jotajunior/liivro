<?php

class IndexController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->users = new Users();
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
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
			$userInformation = $this->users->extractUserInformation();
			$this->registerLoginSessions($userInformation);
			echo "Ola, ".$this->session->get('name');
						echo "<a href='/liivro/index/logout'>logout</a>";
						
		}
	}

	private function registerLoginSessions($userInformation)
	{
		if ($userInformation == false) {
			return false;
		}
		
		$this->session->set('uid', $userInformation['uid']);
		$this->session->set('id', $userInformation['id']);
		$this->session->set('university', $userInformation['university']);
		$this->session->set('general_email', $userInformation['general_email']);
		$this->session->set('university_email', $userInformation['university_email']);
		$this->session->set('name', $userInformation['name']);
		$this->session->set('status', $userInformation['status']);
		$this->session->set('created_at', $userInformation['created_at']);
		
		return true;
	}

	public function testSessionAction()
	{
		$this->session->set("whatever", 2);
		var_dump($this->session->get("whatever"));
	}
	public function logoutAction()
	{
		$this->session->destroy();
		$this->response->redirect("index");
	}
}