<?php

class colors {

/* Convert hexdec color string to rgb(a) string */
 
	function hex2rgba($color, $opacity = false) {

		$default = 'rgb(0,0,0)';

		//Return default if no color provided
		if(empty($color))
		  return $default; 

		//Sanitize $color if "#" is provided 
		if ($color[0] == '#' ) {
			$color = substr( $color, 1 );
		}

		//Check if color has 6 or 3 characters and get values
		if (strlen($color) == 6) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default;
		}

		//Convert hexadec to rgb
		$rgb =  array_map('hexdec', $hex);

		//Check if opacity is set(rgba or rgb)
		if($opacity){
			if(abs($opacity) > 1)
				$opacity = 1.0;
			$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
		} else {
			$output = 'rgb('.implode(",",$rgb).')';
		}

		//Return rgb(a) color string
		return $output;
	}

	function hex2rgb( $colour ) {
		if ( $colour[0] == '#' ) {
			$colour = substr( $colour, 1 );
		}
		if ( strlen( $colour ) == 6 ) {
			list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
		} elseif ( strlen( $colour ) == 3 ) {
			list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
		} else {
			return false;
		}
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return array( 'red' => $r, 'green' => $g, 'blue' => $b );
	}


	function rgbToHsl( $r, $g, $b ) {
		$oldR = $r;
		$oldG = $g;
		$oldB = $b;
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$h;
		$s;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;

		if( $d == 0 ){
			$h = $s = 0; // achromatic
		} else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );
			switch( $max ){
				case $r:
					$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 ); 
					if ($b > $g) {
						$h += 360;
					}
					break;
				case $g: 
					$h = 60 * ( ( $b - $r ) / $d + 2 ); 
					break;
				case $b: 
					$h = 60 * ( ( $r - $g ) / $d + 4 ); 
					break;
			}
		}

		return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
	}
	function hslToRgb( $h, $s, $l ){
		$r = ""; 
		$g = ""; 
		$b = "";

		$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
		$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
		$m = $l - ( $c / 2 );
		if ( $h < 60 ) {
			$r = $c;
			$g = $x;
			$b = 0;
		} else if ( $h < 120 ) {
			$r = $x;
			$g = $c;
			$b = 0;
		} else if ( $h < 180 ) {
			$r = 0;
			$g = $c;
			$b = $x;
		} else if ( $h < 240 ) {
			$r = 0;
			$g = $x;
			$b = $c;
		} else if ( $h < 300 ) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}

		$r = ( $r + $m ) * 255;
		$g = ( $g + $m ) * 255;
		$b = ( $b + $m  ) * 255;

		return array( floor( $r ), floor( $g ), floor( $b ) );
	}

	/*
	 * Som more example code - this needs to be deletet if we don't need it in
	 * the future
	 */

	  function HTMLToRGB($htmlCode)
	  {
	    if($htmlCode[0] == '#')
	      $htmlCode = substr($htmlCode, 1);

	    if (strlen($htmlCode) == 3)
	    {
	      $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
	    }

	    $r = hexdec($htmlCode[0] . $htmlCode[1]);
	    $g = hexdec($htmlCode[2] . $htmlCode[3]);
	    $b = hexdec($htmlCode[4] . $htmlCode[5]);

	    return $b + ($g << 0x8) + ($r << 0x10);
	  }

	  function RGBToHTML($RGB)
	  {
	    $r = 0xFF & ($RGB >> 0x10);
	    $g = 0xFF & ($RGB >> 0x8);
	    $b = 0xFF & $RGB;

	    $r = dechex($r);
	    $g = dechex($g);
	    $b = dechex($b);

	    return "#" . str_pad($r, 2, "0", STR_PAD_LEFT) . str_pad($g, 2, "0", STR_PAD_LEFT) . str_pad($b, 2, "0", STR_PAD_LEFT);
	  }

	  function ChangeLuminosity($RGB, $LuminosityPercent)
	  {
	    $HSL = RGBToHSL($RGB);
	    $NewHSL = (int)(((float)$LuminosityPercent / 100) * 255) + (0xFFFF00 & $HSL);
	    return HSLToRGB($NewHSL);
	  }

	  function RGBToHSL($RGB)
	  {
	    $r = 0xFF & ($RGB >> 0x10);
	    $g = 0xFF & ($RGB >> 0x8);
	    $b = 0xFF & $RGB;

	    $r = ((float)$r) / 255.0;
	    $g = ((float)$g) / 255.0;
	    $b = ((float)$b) / 255.0;

	    $maxC = max($r, $g, $b);
	    $minC = min($r, $g, $b);

	    $l = ($maxC + $minC) / 2.0;

	    if($maxC == $minC)
	    {
	      $s = 0;
	      $h = 0;
	    }
	    else
	    {
	      if($l < .5)
	      {
		$s = ($maxC - $minC) / ($maxC + $minC);
	      }
	      else
	      {
		$s = ($maxC - $minC) / (2.0 - $maxC - $minC);
	      }
	      if($r == $maxC)
		$h = ($g - $b) / ($maxC - $minC);
	      if($g == $maxC)
		$h = 2.0 + ($b - $r) / ($maxC - $minC);
	      if($b == $maxC)
		$h = 4.0 + ($r - $g) / ($maxC - $minC);

	      $h = $h / 6.0; 
	    }

	    $h = (int)round(255.0 * $h);
	    $s = (int)round(255.0 * $s);
	    $l = (int)round(255.0 * $l);

	    $HSL = $l + ($s << 0x8) + ($h << 0x10);
	    return $HSL;
	  }

	  function HSLToRGB($HSL)
	  {
	    $h = 0xFF & ($HSL >> 0x10);
	    $s = 0xFF & ($HSL >> 0x8);
	    $l = 0xFF & $HSL;

	    $h = ((float)$h) / 255.0;
	    $s = ((float)$s) / 255.0;
	    $l = ((float)$l) / 255.0;

	    if($s == 0)
	    {
	      $r = $l;
	      $g = $l;
	      $b = $l;
	    }
	    else
	    {
	      if($l < .5)
	      {
		$t2 = $l * (1.0 + $s);
	      }
	      else
	      {
		$t2 = ($l + $s) - ($l * $s);
	      }
	      $t1 = 2.0 * $l - $t2;

	      $rt3 = $h + 1.0/3.0;
	      $gt3 = $h;
	      $bt3 = $h - 1.0/3.0;

	      if($rt3 < 0) $rt3 += 1.0;
	      if($rt3 > 1) $rt3 -= 1.0;
	      if($gt3 < 0) $gt3 += 1.0;
	      if($gt3 > 1) $gt3 -= 1.0;
	      if($bt3 < 0) $bt3 += 1.0;
	      if($bt3 > 1) $bt3 -= 1.0;

	      if(6.0 * $rt3 < 1) $r = $t1 + ($t2 - $t1) * 6.0 * $rt3;
	      elseif(2.0 * $rt3 < 1) $r = $t2;
	      elseif(3.0 * $rt3 < 2) $r = $t1 + ($t2 - $t1) * ((2.0/3.0) - $rt3) * 6.0;
	      else $r = $t1;

	      if(6.0 * $gt3 < 1) $g = $t1 + ($t2 - $t1) * 6.0 * $gt3;
	      elseif(2.0 * $gt3 < 1) $g = $t2;
	      elseif(3.0 * $gt3 < 2) $g = $t1 + ($t2 - $t1) * ((2.0/3.0) - $gt3) * 6.0;
	      else $g = $t1;

	      if(6.0 * $bt3 < 1) $b = $t1 + ($t2 - $t1) * 6.0 * $bt3;
	      elseif(2.0 * $bt3 < 1) $b = $t2;
	      elseif(3.0 * $bt3 < 2) $b = $t1 + ($t2 - $t1) * ((2.0/3.0) - $bt3) * 6.0;
	      else $b = $t1;
	    }

	    $r = (int)round(255.0 * $r);
	    $g = (int)round(255.0 * $g);
	    $b = (int)round(255.0 * $b);

	    $RGB = $b + ($g << 0x8) + ($r << 0x10);
	    return $RGB;
	  }
}