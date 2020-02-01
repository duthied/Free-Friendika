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

	public function __construct(L10n $l10n, BaseURL $baseUrl, IConfig $config, string $siteEmailAddress, string $siteName)
	{
		parent::__construct($l10n, $baseUrl, $config);

		if ($this->config->get('config', 'admin_name')) {
			$this->siteAdmin = $l10n->t('%1$s, %2$s Administrator', $this->config->get('config', 'admin_name'), $siteName);
		} else {
			$this->siteAdmin = $l10n->t('%s Administrator', $siteName);
		}

		// Set the system wide site address/name as sender (default for system mails)
		$this->withSender($siteEmailAddress, $siteName);
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
}
