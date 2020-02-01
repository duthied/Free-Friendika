<?php

namespace Friendica\Util\EMailer;

use Exception;
use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Builder for system-wide emails without any dependency to concrete entities (like items, activities, ..)
 */
class SystemMailBuilder extends MailBuilder
{
	/** @var string */
	protected $subject;
	/** @var string */
	protected $preamble;
	/** @var string */
	protected $body;

	/** @var string */
	protected $siteAdmin;

	public function __construct(App $a, L10n $l10n, BaseURL $baseUrl, IConfig $config)
	{
		parent::__construct($l10n, $baseUrl, $config);

		$siteName = $this->config->get('config', 'sitename');

		if ($this->config->get('config', 'admin_name')) {
			$this->siteAdmin = $l10n->t('%1$s, %2$s Administrator', $this->config->get('config', 'admin_name'), $siteName);
		} else {
			$this->siteAdmin = $l10n->t('%s Administrator', $siteName);
		}

		$this->senderAddress = $a->getSenderEmailAddress();
	}

	/**
	 * Adds a message
	 *
	 * @param string      $subject  The subject of the email
	 * @param string      $preamble The preamble of the email
	 * @param string|null $body     The body of the email (if not set, the preamble will get used as body)
	 *
	 * @return static
	 */
	public function withMessage(string $subject, string $preamble, string $body = null)
	{
		if (!isset($body)) {
			$body = $preamble;
		}

		$this->subject  = $subject;
		$this->preamble = $preamble;
		$this->body     = $body;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getSubject()
	{
		return $this->subject;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	protected function getHtmlMessage()
	{
		$htmlVersion = BBCode::convert($this->body);

		// load the template for private message notifications
		$tpl = Renderer::getMarkupTemplate('email/system/html.tpl');
		return Renderer::replaceMacros($tpl, [
			'$preamble'    => str_replace("\n", "<br>\n", $this->preamble),
			'$thanks'      => $this->l10n->t('thanks'),
			'$site_admin'  => $this->siteAdmin,
			'$htmlversion' => $htmlVersion,
		]);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	protected function getPlaintextMessage()
	{
		$textVersion = BBCode::toPlaintext($this->body);

		// load the template for private message notifications
		$tpl = Renderer::getMarkupTemplate('email/system/text.tpl');
		return Renderer::replaceMacros($tpl, [
			'$preamble'    => $this->preamble,
			'$thanks'      => $this->l10n->t('thanks'),
			'$site_admin'  => $this->siteAdmin,
			'$textversion' => $textVersion,
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function build(bool $raw = false)
	{
		// for system emails, always use the sitename/site address as the sender
		$this->withSender($this->config->get('config', 'sitename'), $this->senderAddress);

		return parent::build($raw);
	}
}
