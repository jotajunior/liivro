<?php

class UniversityController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->universities = new Universities();
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
	}

	public function registerAction()
	{
	}

	public function verifyAction($hash, $email, $user_id)
	{
		$valid = $this->universities->checkVerificationHash($hash, $email, $user_id);

		if ($valid) {
			$university_id = $this->universities->checkIfIsSupported($email);
			$users = new Users();
			$users->id = (int) $this->session->get("id");
			$users->university_email = urldecode($email);
			$users->chooseUniversity($university_id);
			$this->universities->deleteVerificationHash($user_id);
			$this->session->set("status", Users::ACTIVE);
			$this->view->setVar("success", $valid);
		} else {
			$this->view->setVar("success", $valid);
		}
	}

	public function doRegisterAction()
	{
		$filter = new \Phalcon\Filter();
		$email = $filter->sanitize($_POST["user_email"], "email");
		$user_id = (int) $this->session->get('id');

		$this->universities->sendVerificationEmail($email, $user_id);
	}
}