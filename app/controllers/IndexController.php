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
			$this->view->setVar("facebook_login_url", $this->users->getFacebookLoginUrl());
		} else {
			$userInformation = $this->users->extractUserInformation();
			$this->registerLoginSessions($userInformation);
			$this->response->redirect('listing/index');
			$this->view->disable();
		}
	}

	public function homeAction()
	{
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

	public function logoutAction()
	{
		$this->session->destroy();
		$this->response->redirect();
		$this->view->disable();
	}
}