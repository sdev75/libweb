<?php

class DbPagination{

	public $pages = array();
	public $max_items;
	public $max_pages;
	public $items_per_page;

	public $total_items = 0;
	public $total_pages = 1;
	public $page = 1;
	public $items_count = 0;
	public $page_prev = false;
	public $page_next = false;
	public $page_first = false;
	public $page_last = false;

	function __construct($items_per_page=false,$current_page=false){
		if($items_per_page){
			$this->setItemsPerPage($items_per_page);
		}
		if($current_page){
			$this->setCurrentPage($current_page);
		}
	}

	public function setTotalItems($value){
		$this->total_items = abs($value);
		$this->total_pages = $this->getTotalPages();
	}

	public function setItemsCount($value){
		$this->items_count = $value;
	}

	public function setMaxItems($value){
		$this->max_items = abs($value);
	}

	public function setMaxPages($value){
		$this->max_pages = abs($value);
	}

	public function setItemsPerPage($value){
		$this->items_per_page = $value;
	}

	public function setCurrentPage($page){
		$page = abs($page);
		if($page < 1) {
			$page = 1;
		}
		if($this->max_pages){
			if($this->max_pages < $page){
				$page = $this->max_pages;
			}
		}
		$this->page = $page;
	}

	public function getTotalItems(){
		return $this->total_items;
	}

	public function getTotalPages(){
		if(isset($this->max_pages)) {
			if($this->total_items <= $this->max_pages * $this->items_per_page) {
				$res = round(ceil($this->total_items / $this->items_per_page));
			} else {
				$res = round(ceil($this->max_pages*$this->items_per_page / $this->items_per_page));
			}
		} else {
			$res = round(ceil($this->total_items / $this->items_per_page));
		}

		if($res <= 0) {
			$res = 1;
		}

		return $res;
	}

	public function getPagesArray(){

		$adjacents = 1;
		$lastpage = $this->total_pages;

		$lpm1 = $lastpage - 1; //last page minus 1

		$res = array();

		if($lastpage > 1) {
			//pages
			if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
			{
				for ($counter = 1; $counter <= $lastpage; $counter++)
				{
					$res[] = $counter;
				}
			}
			elseif($lastpage >= 7 + ($adjacents * 2))	//enough pages to hide some
			{
				//close to beginning; only hide later pages
				if($this->page < 1 + ($adjacents * 3))
				{
					for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
					{
						$res[] = $counter;
					}
					$res[] = '...';
					$res[] = $lpm1;
					$res[] = $lastpage;
				}
				//in middle; hide some front and some back
				elseif($lastpage - ($adjacents * 2) > $this->page && $this->page > ($adjacents * 2))
				{
					$res[] = 1;
					$res[] = 2;
					$res[] = '...';
					for ($counter = $this->page - $adjacents; $counter <= $this->page + $adjacents; $counter++)
					{
						$res[] = $counter;
					}

					$res[] = '...';
					$res[] = $lpm1;
					$res[] = $lastpage;
				}
				//close to end; only hide early pages
				else
				{
					$res[] = 1;
					$res[] = 2;
					$res[] = '...';
					for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++)
					{
						$res[] = $counter;
					}
				}
			}
		}

		return $res;
	}

}