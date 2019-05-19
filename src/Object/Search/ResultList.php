<?php

namespace Friendica\Object\Search;

use Friendica\Model\Search;

/**
 * A list of search results with details
 *
 * @see Search for details
 */
class ResultList
{
	/**
	 * Page of the result list
	 * @var int
	 */
	private $page;
	/**
	 * Total count of results
	 * @var int
	 */
	private $total;
	/**
	 * items per page
	 * @var int
	 */
	private $itemsPage;
	/**
	 * Array of results
	 * @var Result[]
	 */
	private $results;

	/**
	 * @return int
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @return int
	 */
	public function getTotal()
	{
		return $this->total;
	}

	/**
	 * @return int
	 */
	public function getItemsPage()
	{
		return $this->itemsPage;
	}

	/**
	 * @return Result[]
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * @param int      $page
	 * @param int      $total
	 * @param int      $itemsPage
	 * @param Result[] $results
	 */
	public function __construct($page = 0, $total = 0, $itemsPage = 0, array $results = [])
	{
		$this->page      = $page;
		$this->total     = $total;
		$this->itemsPage = $itemsPage;

		$this->results = $results;
	}

	/**
	 * Adds a result to the result list
	 *
	 * @param Result $result
	 */
	public function addResult(Result $result)
	{
		$this->results[] = $result;
	}
}
