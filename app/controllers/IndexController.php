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
									echo 2;
		if (!$auth) {
			$facebook_login_url = $this->users->getFacebookLoginUrl();
			
			echo $this->view->render('landing/index.php', array(
													   "facebook_login_url" => $facebook_login_url
													  )
								);
							echo 1;

		} else {
			echo "Ola, ".$this->session->get('name');
						echo "<a href='/liivro/index/logout'>logout</a>";
						
		}
	}
	
	public function logoutAction()
	{
		$this->users->logout();
		$this->response->redirect("index");
	}
}