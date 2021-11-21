<?php

namespace Friendica\Module;

use Friendica\Capabilities\ICanReadAndWriteToResponds;
use Friendica\Capabilities\IRespondToRequests;
use Friendica\Network\HTTPException\InternalServerErrorException;

class Response implements ICanReadAndWriteToResponds
{
	/**
	 * @var string[][]
	 */
	protected $headers = [];
	/**
	 * @var string
	 */
	protected $content = '';
	/**
	 * @var string
	 */
	protected $type = IRespondToRequests::TYPE_CONTENT;

	/**
	 * {@inheritDoc}
	 */
	public function addHeader(string $key, string $value)
	{
		$this->headers[$key][] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addContent(string $content)
	{
		$this->content .= $content;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setType(string $type)
	{
		if (!in_array($type, IRespondToRequests::ALLOWED_TYPES)) {
			throw new InternalServerErrorException('wrong type');
		}

		$this->type = $type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTyp(): string
	{
		return $this->type;
	}
}
