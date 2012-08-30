<?php

/*
 * Name: Softy
 * Version: Version 0.1
 * Author: Alex <alex@friendica.pixelbits.de>
 * Maintainer: Alex alex@friendica.pixelbits.de>
 * Description: Based on "Test Bubble", optimized for iPad.
 * Screenshot: <a href="screenshot.png">Screenshot</a>
 */

$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {

$('html').click(function() { $("#nav-notifications-menu" ).hide(); });
});
</script>
EOT;
