<?php
if (file_exists("$THEMEPATH/style.css")){
    echo file_get_contents("$THEMEPATH/style.css");
}
$s_colorset = get_config('duepuntozero','colorset');
$uid = local_user();
$colorset = get_pconfig( $uid, 'duepuntozero', 'colorset');
if (!x($colorset)) 
    $colorset = $s_colorset;

?>
