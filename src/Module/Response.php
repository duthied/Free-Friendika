<?php

namespace Friendica\Module;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Http\Message\ResponseInterface;

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
	protected $type = ICanCreateResponses::TYPE_HTML;

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
		if (!in_array($type, ICanCreateResponses::ALLOWED_TYPES)) {
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

	/**
	 * {@inheritDoc}
	 */
	public function generate(): ResponseInterface
	{
		// Setting the response type as an X-header for direct usage
		$this->headers['X-RESPONSE-TYPE'] = $this->type;

		return new \GuzzleHttp\Psr7\Response(200, $this->headers, $this->content);
	}
}
