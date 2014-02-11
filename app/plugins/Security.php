<?php

use Phalcon\Events\Event,
	Phalcon\Mvc\User\Plugin,
	Phalcon\Mvc\Dispatcher;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin
{

	public function __construct($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
	}

	private function getRole()
	{
		$status = $this->session->get('status');

        if ($status === false || $status === NULL) {
            $role = 'visitor';
        } else {
			switch ($status) {
				case 0:
					$role = 'active';
					break;
				case 1:
					$role = 'trial';
					break;
				case 2:
					$role = 'invalid';
					break;
				case 3:
					$role = 'visitor';
					break;
				default:
					throw new \Exception("Invalid value for status code.");
					break;
			}
        }
		return $role;
	}

	private function isAllowed($role, $controller, $action, $acl)
	{
		if (!isset($acl[$role][$controller])) {
			return false;
		}

		return in_array($action, $acl[$role][$controller]);
	}

	public function getAcl()
	{
		//if (!isset($this->persistent->aclArray)) {
			$acl = array();

			$active = array(
				"index" => array("index", "logout", "home", "auth"),
				"university" => array("register", "doRegister", "verify"),
				"book" => array("create", "doCreate")
				);

			$trial = array(
				"index" => array("index", "logout", "home", "auth"),
				"university" => array("register", "doRegister", "verify"),
				);

			$invalid = array(
				"index" => array("index", "logout", "home", "auth"),
				"university" => array("register", "doRegister", "verify"),
				);

			$visitor = array(
				"index" => array("index", "auth")
				);

			$acl['active'] = $active;
			$acl['trial'] = $trial;
			$acl['invalid'] = $invalid;
			$acl['visitor'] = $visitor;

		//	$this->persistent->aclArray = $acl;
		//}

		return $acl; //$this->persistent->aclArray;
	}

	/**
	 * This action is executed before execute any action in the application
	 */
	public function beforeDispatch(Event $event, Dispatcher $dispatcher)
	{
		$role = $this->getRole();
		$controller = $dispatcher->getControllerName();
		$action = $dispatcher->getActionName();

		$acl = $this->getAcl();

		$allowed = $this->isAllowed($role, $controller, $action, $acl);
	//	echo $role, $controller, $action; die();
		if (!$allowed) {
			$response = new \Phalcon\Http\Response();
			$response->redirect();
			$response->send();

			return false;
		}
		return true;
	}

}