<?php
if(!function_exists('deletenode')) {
	function deletenode(&$doc, $node)
	{
		$xpath = new DomXPath($doc);
		$list = $xpath->query("//".$node);
		foreach ($list as $child)
			$child->parentNode->removeChild($child);
	}
}

function parseurl_getsiteinfo($url) {
	$siteinfo = array();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_USERAGENT,'Opera/9.64(Windows NT 5.1; U; de) Presto/2.1.1');

	$header = curl_exec($ch);
	curl_close($ch);

	if (preg_match('/charset=(.*?)\n/', $header, $matches))
		$charset = trim(array_pop($matches));
	else
		$charset = "utf-8";

	$pos = strpos($header, "\r\n\r\n");

	if ($pos)
		$body = trim(substr($header, $pos));
	else
		$body = $header;

	$body = mb_convert_encoding($body, "UTF-8", $charset);
	$body = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");

	$doc = new DOMDocument();
	@$doc->loadHTML($body);

	deletenode($doc, 'style');
	deletenode($doc, 'script');
	deletenode($doc, 'option');
	deletenode($doc, 'h1');
	deletenode($doc, 'h2');
	deletenode($doc, 'h3');
	deletenode($doc, 'h4');
	deletenode($doc, 'h5');
	deletenode($doc, 'h6');
	deletenode($doc, 'ol');
	deletenode($doc, 'ul');

	$xpath = new DomXPath($doc);

	$list = $xpath->query("head/title");
	foreach ($list as $node)
		$siteinfo["title"] =  html_entity_decode($node->nodeValue, ENT_QUOTES, "UTF-8");

	$list = $xpath->query("head/meta[@name]");
	foreach ($list as $node) {
		$attr = array();
		if ($node->attributes->length)
                        foreach ($node->attributes as $attribute)
                                $attr[$attribute->name] = $attribute->value;

		$attr["content"] = html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8");

		switch (strtolower($attr["name"])) {
			case "fulltitle":
				$siteinfo["title"] = $attr["content"];
				break;
			case "description":
				$siteinfo["text"] = $attr["content"];
				break;
			case "dc.title":
				$siteinfo["title"] = $attr["content"];
				break;
			case "dc.description":
				$siteinfo["text"] = $attr["content"];
				break;
		}
	}

	$list = $xpath->query("head/meta[@property]");
	foreach ($list as $node) {
		$attr = array();
		if ($node->attributes->length)
                        foreach ($node->attributes as $attribute)
                                $attr[$attribute->name] = $attribute->value;

		$attr["content"] = html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8");

		switch (strtolower($attr["property"])) {
			case "og:image":
				$siteinfo["image"] = $attr["content"];
				break;
			case "og:title":
				$siteinfo["title"] = $attr["content"];
				break;
			case "og:description":
				$siteinfo["text"] = $attr["content"];
				break;
		}
	}

	if ($siteinfo["image"] == "") {
                require_once('include/Photo.php');
                $list = $xpath->query("//img[@src]");
                foreach ($list as $node) {
                        $attr = array();
                        if ($node->attributes->length)
                                foreach ($node->attributes as $attribute)
                                        $attr[$attribute->name] = $attribute->value;

                        // guess mimetype from headers or filename
                        $type = guess_image_type($attr["src"],true);

                        $i = fetch_url($attr["src"]);
                        $ph = new Photo($i, $type);

                        if(($ph->getWidth() > 200) and ($ph->getHeight() > 200))
                                $siteinfo["image"] = $attr["src"];
                }
        }

	if ($siteinfo["text"] == "") {
		$text = "";

		$list = $xpath->query("//div[@class='article']");
		foreach ($list as $node)
			$text .= " ".trim($node->nodeValue);

		if ($text == "") {
			$list = $xpath->query("//div[@class='content']");
			foreach ($list as $node)
				$text .= " ".trim($node->nodeValue);
		}

		// If none text was found then take the paragraph content
		if ($text == "") {
			$list = $xpath->query("//p");
			foreach ($list as $node)
				$text .= " ".trim($node->nodeValue);
		}

		if ($text != "") {
			$text = trim(str_replace(array("\n", "\r"), array(" ", " "), $text));

			while (strpos($text, "  "))
				$text = trim(str_replace("  ", " ", $text));

			$siteinfo["text"] = html_entity_decode(substr($text,0,350), ENT_QUOTES, "UTF-8").'...';
		}
	}

	return($siteinfo);
}

function arr_add_hashes(&$item,$k) {
	$item = '#' . $item;
}

function parse_url_content(&$a) {

	$text = null;
	$str_tags = '';

	$textmode = false;
	if(local_user() && intval(get_pconfig(local_user(),'system','plaintext')))
		$textmode = true;

	if($textmode)
	$br = (($textmode) ? "\n" : '<br /?');

	if(x($_GET,'binurl'))
		$url = trim(hex2bin($_GET['binurl']));
	else
		$url = trim($_GET['url']);

	if($_GET['title'])
		$title = strip_tags(trim($_GET['title']));

	if($_GET['description'])
		$text = strip_tags(trim($_GET['description']));

	if($_GET['tags']) {
		$arr_tags = str_getcsv($_GET['tags']);
		if(count($arr_tags)) {
			array_walk($arr_tags,'arr_add_hashes');
			$str_tags = $br . implode(' ',$arr_tags) . $br;
		}
	}

	logger('parse_url: ' . $url);

	if($textmode)
		$template = $br . '[bookmark=%s]%s[/bookmark]%s' . $br;
	else
		$template = "<br /><a class=\"bookmark\" href=\"%s\" >%s</a>%s<br />";

	$arr = array('url' => $url, 'text' => '');

	call_hooks('parse_link', $arr);

	if(strlen($arr['text'])) {
		echo $arr['text'];
		killme();
	}


	if($url && $title && $text) {

		if($textmode)
			$text = $br . $br . '[quote]' . trim($text) . '[/quote]' . $br;
		else
			$text = '<br /><br /><blockquote>' . trim($text) . '</blockquote><br />';

		$title = str_replace(array("\r","\n"),array('',''),$title);

		$result = sprintf($template,$url,($title) ? $title : $url,$text) . $str_tags;

		logger('parse_url (unparsed): returns: ' . $result);

		echo $result;
		killme();
	}

	$siteinfo = parseurl_getsiteinfo($url);

	if($siteinfo["title"] == "") {
		echo sprintf($template,$url,$url,'') . $str_tags;
		killme();
	} else {
		$image = $siteinfo["image"];
		$text = $siteinfo["text"];
		$title = $siteinfo["title"];
	}

	if ($image != "") {
		$i = fetch_url($image);
		if($i) {
			require_once('include/Photo.php');
			// guess mimetype from headers or filename
			$type = guess_image_type($image,true);

			$ph = new Photo($i, $type);
			if($ph->is_valid()) {
				if($ph->getWidth() > 300 || $ph->getHeight() > 300) {
					$ph->scaleImage(300);
					$new_width = $ph->getWidth();
					$new_height = $ph->getHeight();
					if($textmode)
						$image = $br . $br . '[img=' . $new_width . 'x' . $new_height . ']' . $image . '[/img]';
					else
						$image = '<br /><br /><img height="' . $new_height . '" width="' . $new_width . '" src="' .$image . '" alt="photo" />';
				} else {
					if($textmode)
						$image = $br.$br.'[img]'.$image.'[/img]';
					else
						$image = '<br /><br /><img src="'.$image.'" alt="photo" />';
				}
			}
		}
	}

	if(strlen($text)) {
		if($textmode)
			$text = $br.$br.'[quote]'.trim($text).'[/quote]'.$br ;
		else
			$text = '<br /><br /><blockquote>'.trim($text).'</blockquote><br />';
	}

	if($image) {
		$text = $image.$br.$text;
	}
	$title = str_replace(array("\r","\n"),array('',''),$title);

	$result = sprintf($template,$url,($title) ? $title : $url,$text) . $str_tags;

	logger('parse_url: returns: ' . $result);

	echo $result;
	killme();
}
