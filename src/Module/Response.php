<?php

namespace Friendica\Module;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Capabilities\IRespondToRequests;
use Friendica\Network\HTTPException\InternalServerErrorException;

class Response implements ICanCreateResponses
{
	/**
	 * @var string[]
	 */
	protected $headers = [];
	/**
	 * @var string
	 */
	protected $content = '';
	/**
	 * @var string
	 */
	protected $type = IRespondToRequests::TYPE_HTML;

	/**
	 * {@inheritDoc}
	 */
	public function setHeader(?string $header = null, ?string $key = null): void
	{
		if (!isset($header) && !empty($key)) {
			unset($this->headers[$key]);
		}

		if (isset($header)) {
			if (empty($key)) {
				$this->headers[] = $header;
			} else {
				$this->headers[$key] = $header;
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function addContent($content): void
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
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setType(string $type, ?string $content_type = null): void
	{
		if (!in_array($type, IRespondToRequests::ALLOWED_TYPES)) {
			throw new InternalServerErrorException('wrong type');
		}

		switch ($type) {
			case static::TYPE_JSON:
				$content_type = $content_type ?? 'application/json';
				break;
			case static::TYPE_XML:
				$content_type = $content_type ?? 'text/xml';
				break;
		}


		$this->setHeader($content_type, 'Content-type');

		$this->type = $type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string
	{
		return $this->type;
	}
}
