<?php namespace Illuminate\Pagination;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Presenter;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;

class Paginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, Jsonable, PaginatorContract {

	use PaginatorTrait;

	/**
	 * Determine if there are more items in the data source.
	 *
	 * @return bool
	 */
	protected $hasMore;

	/**
	 * Create a new paginator instance.
	 *
	 * @param  mixed  $items
	 * @param  int  $currentPage
	 * @param  int  $perPage
	 * @param  array  $options (path, query, fragment, pageName)
	 * @return void
	 */
	public function __construct($items, $currentPage, $perPage, array $options = array())
	{
		foreach ($options as $key => $value)
		{
			$this->{$key} = $value;
		}

		$this->items = $items instanceof Collection ? $items : Collection::make($items);

		$this->perPage = $perPage;
		$this->currentPage = $this->setCurrentPage($currentPage);
		$this->path = $this->path != '/' ? rtrim($this->path, '/').'/' : $this->path;

		$this->checkForMorePages();
	}

	/**
	 * Check for more pages. The last item will be sliced off.
	 *
	 * @return void
	 */
	protected function checkForMorePages()
	{
		$this->hasMore = count($this->items) > ($this->perPage);

		$this->items = $this->items->slice(0, $this->perPage);
	}

	/**
	 * Get the current page for the request.
	 *
	 * @param  int  $lastPage
	 * @return int
	 */
	protected function setCurrentPage($currentPage)
	{
		return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
	}

	/**
	 * Get the URL for the next page.
	 *
	 * @return string
	 */
	public function nextPageUrl()
	{
		if ($this->hasMore)
		{
			return $this->url($this->currentPage() + 1);
		}
	}

	/**
	 * Determine if there are more items in the data source.
	 *
	 * @return bool
	 */
	public function hasMorePages()
	{
		return $this->hasMore;
	}

	/**
	 * Render the paginator using the given presenter.
	 *
	 * @param  \Illuminate\Contracts\Pagination\Presenter  $presenter
	 * @return string
	 */
	public function render(Presenter $presenter = null)
	{
		$presenter = $presenter ?: new SimpleBootstrapThreePresenter($this);

		return $presenter->render();
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'per_page' => $this->perPage(), 'current_page' => $this->currentPage(),
			'next_page_url' => $this->nextPageUrl(), 'prev_page_url' => $this->previousPageUrl(),
			'from' => $this->firstItem(), 'to' => $this->lastItem(), 'data' => $this->items->toArray(),
		);
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

}
