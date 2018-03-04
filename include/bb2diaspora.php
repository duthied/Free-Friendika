<?php

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Markdown;

function diaspora2bb($s) {
	return Markdown::toBBCode($s);
}

function bb2diaspora($Text, $fordiaspora = true) {
	return BBCode::toMarkdown($Text, $fordiaspora);
}
