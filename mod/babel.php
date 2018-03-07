<?php
/**
 * @file mod/babel.php
 */

use Friendica\Content\Text;
use Friendica\Core\L10n;

function visible_lf($s)
{
	return str_replace("\n", '<br />', $s);
}

function babel_content()
{
	$o = '<h1>Babel Diagnostic</h1>';

	$o .= '<form action="babel" method="post">';
	$o .= L10n::t("Source \x28bbcode\x29 text:") . EOL;
	$o .= '<textarea name="text" cols="80" rows="10">' . htmlspecialchars($_REQUEST['text']) . '</textarea>' . EOL;
	$o .= '<input type="submit" name="submit" value="Submit" /></form>';

	$o .= '<br /><br />';

	$o .= '<form action="babel" method="post">';
	$o .= L10n::t("Source \x28Diaspora\x29 text to convert to BBcode:") . EOL;
	$o .= '<textarea name="d2bbtext" cols="80" rows="10">' . htmlspecialchars($_REQUEST['d2bbtext']) . '</textarea>' . EOL;
	$o .= '<input type="submit" name="submit" value="Submit" /></form>';

	$o .= '<br /><br />';

	if (x($_REQUEST, 'text')) {
		$text = trim($_REQUEST['text']);
		$o .= '<h2>' . L10n::t('Source input: ') . '</h2>' . EOL . EOL;
		$o .= visible_lf($text) . EOL . EOL;

		$html = Text\BBCode::convert($text);
		$o .= '<h2>' . L10n::t("BBCode::convert \x28raw HTML\x28: ") . '</h2>' . EOL . EOL;
		$o .= htmlspecialchars($html) . EOL . EOL;

		$o .= '<h2>' . L10n::t('BBCode::convert: ') . '</h2>' . EOL . EOL;
		$o .= $html . EOL . EOL;

		$bbcode = Text\HTML::toBBCode($html);
		$o .= '<h2>' . L10n::t('BBCode::convert => HTML::toBBCode: ') . '</h2>' . EOL . EOL;
		$o .= visible_lf($bbcode) . EOL . EOL;

		$diaspora = Text\BBCode::toMarkdown($text);
		$o .= '<h2>' . L10n::t('BBCode::toMarkdown: ') . '</h2>' . EOL . EOL;
		$o .= visible_lf($diaspora) . EOL . EOL;

		$html = Text\Markdown::convert($diaspora);
		$o .= '<h2>' . L10n::t('BBCode::toMarkdown =>  Markdown::convert: ') . '</h2>' . EOL . EOL;
		$o .= $html . EOL . EOL;

		$bbcode = Text\Markdown::toBBCode($diaspora);
		$o .= '<h2>' . L10n::t('BBCode::toMarkdown => Markdown::toBBCode: ') . '</h2>' . EOL . EOL;
		$o .= visible_lf($bbcode) . EOL . EOL;

		$bbcode = Text\HTML::toBBCode($html);
		$o .= '<h2>' . L10n::t('BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode: ') . '</h2>' . EOL . EOL;
		$o .= visible_lf($bbcode) . EOL . EOL;
	}

	if (x($_REQUEST, 'd2bbtext')) {
		$d2bbtext = trim($_REQUEST['d2bbtext']);
		$o .= '<h2>' . L10n::t("Source input \x28Diaspora format\x29: ") . '</h2>' . EOL . EOL;
		$o .= '<pre>' . $d2bbtext . '</pre>' . EOL . EOL;

		$bb = Text\Markdown::toBBCode($d2bbtext);
		$o .= '<h2>' . L10n::t('Markdown::toBBCode: ') . '</h2>' . EOL . EOL;
		$o .= '<pre>' . $bb . '</pre>' . EOL . EOL;
	}

	return $o;
}
