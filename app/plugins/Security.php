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
	}

	private function getRole()
	{
		$status = $this->session->get('status');
        if (!$status) {
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
				default:
					$role = 'visitor';
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
				"index" => array("index", "logout", "home", "auth")
				);

			$trial = array(
				"index" => array("index", "logout", "home", "auth")
				);

			$invalid = array(
				"index" => array("index", "logout", "home", "auth")
				);

			$visitor = array(
				"index" => array("index", "auth")
				);

			$acl['active'] = $active;
			$acl['trial'] = $trial;
			$acl['invalid'] = $invalid;
			$acl['visitor'] = $visitor;

			$this->persistent->aclArray = $acl;
		//}

		return $this->persistent->aclArray;
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

	}

}