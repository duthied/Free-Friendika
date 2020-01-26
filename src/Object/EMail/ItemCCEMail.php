<?php

namespace Friendica\Object\EMail;

use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Content\Text\HTML;
use Friendica\Core\L10n;
use Friendica\Model\Item;
use Friendica\Object\Email;

/**
 * Class for creating CC emails based on a received item
 */
class ItemCCEMail extends Email
{
	public function __construct(App $a, L10n $l10n, BaseURL $baseUrl, array $item, string $toEmail, string $authorThumb)
	{
		$disclaimer = '<hr />' . $l10n->t('This message was sent to you by %s, a member of the Friendica social network.', $a->user['username'])
		              . '<br />';
		$disclaimer .= $l10n->t('You may visit them online at %s', $baseUrl . '/profile/' . $a->user['nickname']) . EOL;
		$disclaimer .= $l10n->t('Please contact the sender by replying to this post if you do not wish to receive these messages.') . EOL;
		if (!$item['title'] == '') {
			$subject = Email::encodeHeader($item['title'], 'UTF-8');
		} else {
			$subject = Email::encodeHeader('[Friendica]' . ' ' . $l10n->t('%s posted an update.', $a->user['username']), 'UTF-8');
		}
		$link    = '<a href="' . $baseUrl . '/profile/' . $a->user['nickname'] . '"><img src="' . $authorThumb . '" alt="' . $a->user['username'] . '" /></a><br /><br />';
		$html    = Item::prepareBody($item);
		$message = '<html><body>' . $link . $html . $disclaimer . '</body></html>';;

		parent::__construct($a->user['username'], $a->user['email'], $a->user['email'], $toEmail,
			$subject, $message, HTML::toPlaintext($html . $disclaimer));
	}
}
