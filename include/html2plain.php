<?php

function html2plain($html, $wraplength = 75, $compact = false)
{
	return Friendica\Content\Text\HTML::toPlaintext($html, $wraplength, $compact);
}
