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

	private function addResource($resources)
	{
		foreach ($resources as $resource => $actions) {
			$this->acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
		}
	}
	
	private function grantAccess($name, $resources)
	{
		foreach ($resources as $resource => $actions) {
			foreach ($actions as $action) {
				$this->acl->allow($name, $resource, $action);
			}
		}
	}

	private function registerActiveAreaResources()
	{
		$activeResources = array(
				'listing' => array('index', 'create'),
			);
		$this->addResource($activeResources);
		return $activeResources;
	}
	
	private function registerTrialAreaResources()
	{
		$trialResources = array(
				'listing' => array('index')
			);
		$this->addResource($trialResources);
		return $trialResources;
	}
	
	private function registerPublicAreaResources()
	{
		$activeResources = array(
				'index' => array('index', 'testSession'),
			);
		$this->addResource($activeResources);
		return $activeResources;
	}
	
	private function grantPublicResource($roles, $resources)
	{
		foreach ($roles as $role) {
			foreach ($resources as $resource => $actions) {
				$acl->allow($role->getName(), $resource, '*');
			}
		}
	}
	
	private function registerRoles()
	{
		$roles = array(
				'active' => new Phalcon\Acl\Role('Active'),
				'in_trial' => new Phalcon\Acl\Role('In_trial'),
				'invalid' => new Phalcon\Acl\Role('Invalid'),
				'visitor' => new Phalcon\Acl\Role('Visitor')
			);

		foreach ($roles as $role) {
			$this->acl->addRole($role);
		}
		return $roles;
	}
	
	public function getAcl()
	{
		if (!isset($this->persistent->acl)) {

			$this->acl = new Phalcon\Acl\Adapter\Memory();

			$this->acl->setDefaultAction(Phalcon\Acl::DENY);

			$roles = $this->registerRoles();
	
			$activeResources = $this->registerActiveAreaResources();
			$this->grantAccess('Active', $activeResources);

			$trialResources = $this->registerTrialAreaResources();
			$this->grantAccess('In_trial', $trialResources);			
			
			
			$publicResources = $this->registerPublicAreaResources();
			$this->grantPublicResource($roles, $publicResources);

			$this->persistent->acl = $this->acl;
		}

		return $this->persistent->acl;
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