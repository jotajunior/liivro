<?php

class ListingController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
	}

	public function indexAction($category = 0, $page = 1)
	{

		$books = new Books();
		$books->university = (int) $this->session->get("university");
		$books->page = (int) $page;

		if ($category != 0) {
			$books->category = $category;
		}
		
		$listing = $books->fetchUniversityListing();
		$total_count = (int) $books->getTotalCount();

		
		$confirmed_user = $this->session->get("status") == Users::ACTIVE;

		$this->view->setVar("category", $category);
		$this->view->setVar("page", $page);
		$this->view->setVar("books", $listing);
		$this->view->setVar("confirmed_user", $confirmed_user);
		$this->view->setVar("total_count", $total_count);
		$this->view->setVar("perPage", $books->per_page);
	}
}