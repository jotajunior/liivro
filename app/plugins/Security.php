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
	const ACTIVE = 'active';
	const TRIAL = 'trial';
	const INVALID = 'invalid';
	const VISITOR = 'visitor';

	public function __construct($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
	}

	private function getRole()
	{
		$status = $this->session->get('status');

        if ($status === false || $status === NULL) {
            $role = self::VISITOR;
        } else {
			switch ($status) {
				case 0:
					$role = self::ACTIVE;
					break;
				case 1:
					$role = self::TRIAL;
					break;
				case 2:
					$role = self::INVALID;
					break;
				case 3:
					$role = self::VISITOR;
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
				"book" => array("create", "doCreate", "delete", "show", "search", "edit", "doEdit"),
				"listing" => array("index"),
				"user" => array("books")
				);

			$trial = array(
				"index" => array("index", "logout", "home", "auth"),
				"university" => array("register", "doRegister", "verify"),
				"book" => array("show", "search"),
				"listing" => array("index")
				);

			$invalid = array(
				"index" => array("index", "logout", "home", "auth"),
				"university" => array("register", "doRegister", "verify")
				);

			$visitor = array(
				"index" => array("index", "auth")
				);

			$acl[self::ACTIVE] = $active;
			$acl[self::TRIAL] = $trial;
			$acl[self::INVALID] = $invalid;
			$acl[self::VISITOR] = $visitor;

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

		if (!$allowed) {
			$response = new \Phalcon\Http\Response();
			$response->redirect();
			$response->send();

			return false;
		}
		return true;
	}

}