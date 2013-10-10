<?php
use Phalcon\Events\Event,
	Phalcon\Mvc\User\Plugin,
	Phalcon\Mvc\Dispatcher,
	Phalcon\Acl;

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
            $role = 'Visitor';
        } else {
			switch ($status) {
				case 0:
					$role = 'Active';
					break;
				case 1:
					$role = 'In_trial';
					break;
				case 2:
					$role = 'Invalid';
					break;
				default:
					$role = 'Visitor';
					break;
			}
        }
		return $role;
	}
	/**
	 * This action is executed before execute any action in the application
	 */
	public function beforeDispatch(Event $event, Dispatcher $dispatcher)
	{
		$role = $this->getRole();
		$controller = $dispatcher->getControllerName();
		$action = $dispatcher->getActionName();
		echo $controller, " ", $action;

		$this->acl = $this->getAcl();

		$allowed = $this->acl->isAllowed($role, $controller, $action);
		if ($allowed != Acl::ALLOW) {
			$this->flash->error("You don't have access to this module");
			$dispatcher->forward(
				array(
					'controller' => 'index',
					'action' => 'index'
				)
			);
			return false;
		}

	}

}