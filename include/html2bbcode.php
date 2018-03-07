<?php

function html2bbcode($message, $basepath = '')
{
	return Friendica\Content\Text\HTML::toBBCode($message, $basepath);
}