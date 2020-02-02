<?php

namespace Friendica\Util\EMailer;

use Exception;
use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Builder for notification emails (notification, source, links, ...)
 */
class NotifyMailBuilder extends SystemMailBuilder
{
	/** @var bool */
	private $contentAllowed = true;
	/** @var string */
	private $title = '';
	/** @var array Details to print a photo:
	 * - image
	 * - link
	 * - name
	 */
	private $photo = [
		'image' => null,
		'link'  => null,
		'name'  => null,
	];
	/** @var array HTML/Plain version of the Site Link:
	 * - html
	 * - text
	 */
	private $siteLink = [
		'html' => '',
		'text' => '',
	];
	/** @var string The item link */
	private $itemLink = '';

	public function __construct(L10n $l10n, BaseURL $baseUrl, IConfig $config, string $siteEmailAddress, string $siteName)
	{
		parent::__construct($l10n, $baseUrl, $config, $siteEmailAddress, $siteName);

		// check whether sending post content in email notifications is allowed
		$this->contentAllowed = $this->config->get('system', 'enotify_no_content');
	}

	/**
	 * Adds a notification (in fact a more detailed message)
	 *
	 * @param string      $subject
	 * @param string      $preamble
	 * @param string      $title
	 * @param string|null $body
	 *
	 * @return static
	 */
	public function withNotification(string $subject, string $preamble, string $title, string $body = null)
	{
		$this->title = stripslashes($title);

		return $this->withMessage($subject, $preamble, $body);
	}

	/**
	 * Adds a photo of the source of the notify
	 *
	 * @param string $image The image link to the photo
	 * @param string $link  The link to the source
	 * @param string $name  The name of the source
	 *
	 * @return static
	 */
	public function withPhoto(string $image, string $link, string $name)
	{
		$this->photo = [
			'image' => $image ?? '',
			'link'  => $link ?? '',
			'name'  => $name ?? '',
		];

		return $this;
	}

	/**
	 * Adds a sitelink to the notification
	 *
	 * @param string $text The text version of the site link
	 * @param string $html The html version of the site link
	 *
	 * @return static
	 */
	public function withSiteLink(string $text, string $html = '')
	{
		$this->siteLink = [
			'text' => $text,
			'html' => $html,
		];

		return $this;
	}

	/**
	 * Adds a link to the item of the notification
	 *
	 * @param string $link The text version of the item link
	 *
	 * @return static
	 */
	public function withItemLink(string $link)
	{
		$this->itemLink = $link;

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
		$tpl = Renderer::getMarkupTemplate('email/notify/html.tpl');
		return Renderer::replaceMacros($tpl, [
			'$preamble'        => str_replace("\n", "<br>\n", $this->preamble),
			'$source_name'     => $this->photo['name'],
			'$source_link'     => $this->photo['link'],
			'$source_photo'    => $this->photo['image'],
			'$hsitelink'       => $this->siteLink['html'],
			'$hitemlink'       => sprintf('<a href="%s">%s</a>', $this->itemLink, $this->itemLink),
			'$thanks'          => $this->l10n->t('thanks'),
			'$site_admin'      => $this->siteAdmin,
			'$title'           => $this->title,
			'$htmlversion'     => $htmlVersion,
			'$content_allowed' => $this->contentAllowed,
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
		$tpl = Renderer::getMarkupTemplate('email/notify/text.tpl');
		return Renderer::replaceMacros($tpl, [
			'$preamble'        => $this->preamble,
			'$tsitelink'       => $this->siteLink['text'],
			'$titemlink'       => $this->itemLink,
			'$thanks'          => $this->l10n->t('thanks'),
			'$site_admin'      => $this->siteAdmin,
			'$title'           => $this->title,
			'$textversion'     => $textVersion,
			'$content_allowed' => $this->contentAllowed,
		]);
	}
}
