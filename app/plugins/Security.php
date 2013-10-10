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
		$this->session = \Phalcon\DI\FactoryDefault::getDefault()->getShared('session');
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

	public function getAcl()
	{
		if (!isset($this->persistent->acl)) {

			$acl = new Phalcon\Acl\Adapter\Memory();

			$acl->setDefaultAction(Phalcon\Acl::DENY);

			//Register roles
			$roles = array(
				'Active' => new Phalcon\Acl\Role('Active'),
				'In_trial' => new Phalcon\Acl\Role('In_trial'),
				'Invalid' => new Phalcon\Acl\Role('Invalid'),
				'Visitor' => new Phalcon\Acl\Role('Visitor')
			);
			foreach ($roles as $role) {
				$acl->addRole($role);
			}

			//Private area resources
			$privateResources = array(
				'index' => array('index', 'testSession'),
				'listing' => array('index'),
				'books' => array('show', 'create')
			);
			foreach ($privateResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			//Public area resources
			$publicResources = array(
				'index' => array('index', 'testSession'),
			);
			foreach ($publicResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			$protectedResources = array(
					'index' => array('index'),
					'listing' => array('index')
				);
			foreach ($protectedResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}
			//Grant access to public areas to both users and guests
			foreach ($roles as $role) {
				foreach ($publicResources as $resource => $actions) {
					$acl->allow($role->getName(), $resource, '*');
				}
			}

			//Grant acess to private area to role Users
			foreach ($privateResources as $resource => $actions) {
				foreach ($actions as $action){
					$acl->allow('Active', $resource, $action);
				}
			}

			//The acl is stored in session, APC would be useful here too
			$this->persistent->acl = $acl;
		}

		return $this->persistent->acl;
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

		$allowed = $acl->isAllowed($role, $controller, $action);
		echo $role, " ", $controller, " ", $action;
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