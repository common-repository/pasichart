<?php
/*
Plugin Name: PasiChart
Plugin URI: http://www.pasi.fi/slightly-advanced-graphing-with-pasichart/
Description: 
Author: Pasi Matilainen
Version: 0.1
Author URI: http://www.pasi.fi/
*/ 

// add filter
add_filter('the_content', 'pchart_filter');

// def filter
function pchart_filter($content = '') {
	while ( strpos(strtolower($content), '[[pasichart') !== FALSE ) {
		$pasichart = substr( $content, strpos(strtolower($content), '[[pasichart') );
		$open = 0; $breakpoint = 0;
		for ($i = 0; $i < strlen($pasichart); $i++) {
			if ( '[' == $pasichart[$i] ) $open++;
			if ( ']' == $pasichart[$i] ) $open--;
			if ( $open == 0 && $i > 2 ) { $breakpoint = $i + 1; break; }
		}
		$pasichart = substr($pasichart, 0, $breakpoint);
		$img_tag = pchart_parsetags($pasichart);
		$content = str_replace($pasichart, $img_tag, $content);
	}
	return $content;
}

function pchart_get_siteurl() {
	$siteurl = get_option('siteurl');
	if ("/"==substr($siteurl,strlen($siteurl)-1)) 
	$siteurl = substr($siteurl,0,strlen($siteurl)-1);	
	return $siteurl;
}

function pchart_parse_blog_params($type) {
	$parts = explode(" ",$type);
	$timetype = 0;
	$days = 0;
	if (count($parts)>1)
		$timetype = strtolower(trim($parts[1]));
	if (count($parts)>2)
		$days = trim($parts[2]);
	switch ($timetype) {
	case 'd':
	case 'day':
	case 'daily': $timetype = 0; break;
	case 'w':
	case 'week':
	case 'weekly': $timetype = 1; break;
	case 'm':
	case 'month':
	case 'monthly': $timetype = 2; break;
	case 'y':
	case 'year':
	case 'yearly': $timetype = 3; break;
	default: $timetype = 0; break;
	}
	return array ($timetype, $days);
}

function pchart_parsetags($tags) {
	$siteurl = pchart_get_siteurl();
	$tags = substr($tags, 1); // strip initial bracket
	$caption = FALSE; $type = FALSE; $width = FALSE; $height = FALSE;
	$series = Array(); $sc = 0;
	while (pchart_has_tags($tags)) {
		$tag = pchart_next_tag($tags);
		switch (pchart_tag_name($tag)) {
		case 'pasichart': $type = pchart_tag_value($tag); break;
		case 'caption': $caption = pchart_tag_value($tag); break;
		case 'size': list ($width, $height) = explode(",",pchart_tag_value($tag)); break;
		case 'series': $series[$sc++] = pchart_parse_series($tag); break;
		}
	}
	$img_tag = "<img src=\"$siteurl/wp-content/plugins/pasichart/doChart.php?";
	// url parameters
	if ($caption !== FALSE)		$img_tag .= "c=$caption&amp;";
	if ($width !== FALSE)		$img_tag .= "w=$width&amp;";
	if ($height !== FALSE)		$img_tag .= "h=$height&amp;";
	if ( $type === FALSE || strtolower( $type ) == 'normal' ) {
		for ($i = 0; $i < count($series); $i++)
			$img_tag .= "s[$i]=".urlencode(serialize($series[$i]))."&amp;";
	} else if ( strpos( strtolower( $type ), 'blog' ) !== FALSE ) {
		if ($width === FALSE) $width = 0;
		if ($height === FALSE) $height = 0;
		if ($caption === FALSE) $caption = "";
		list ($timetype, $days) = pchart_parse_blog_params($type);
		return pasichart_insert_blog_stats(0,$timetype,$days,$width,$height,$caption,FALSE);
	}
	$img_tag .= "\""; // end src
	// img parameters
	if ($caption !== FALSE)		$img_tag .= "alt=\"$caption\"";
	if ($width !== FALSE)		$img_tag .= "width=\"$width\"";
	if ($height !== FALSE)		$img_tag .= "height=\"$height\"";
	$img_tag .= " />"; // end tag
	return $img_tag;
}

function pchart_has_tags($tags) {
	if (strpos($tags,'[')!==FALSE) return TRUE;
	return FALSE;
}

function pchart_next_tag(&$tags) {
	$tag = substr($tags, strpos($tags,'['));
	$tag = substr($tag, 0, strpos($tag,']') + 1);
	$tags = str_replace($tag, '', $tags);
	return $tag;
}

function pchart_tag_name($tag) {
	$tag = trim(substr($tag,1)); // strip initial bracket and remove leading whitespace
	$x = strpos($tag,' ');
	if ($x===FALSE) return FALSE;
	$tagname = substr($tag,0,$x);
	return strtolower(trim($tagname));
}

function pchart_tag_value($tag) {
	$tag = trim(substr($tag,1)); // strip initial bracket and remove leading whitespace
	$x = strpos($tag, ' '); // hit first space
	if ($x === FALSE) return FALSE;
	$tagvalue = substr($tag, $x + 1);
	$tagvalue = trim(substr($tagvalue,0,strlen($tagvalue)-1)); // strip ending bracket and whitespace
	return $tagvalue;
}

function pchart_parse_series($tag) {
	$tagvalue = pchart_tag_value($tag);
	$parts = explode(' ', $tagvalue);
	$values = $parts[count($parts)-1];
	unset($parts[count($parts)-1]);
	$caption = implode(' ', $parts);
	return Array($caption,explode(',',$values));
}

// API functions

function pasichart_insert_blog_stats( $data = 0, $type = 0, $days = 0, $width = 0, $height = 0, $caption = "", $echo_tag = TRUE ) {
	$posts = TRUE; $comments = TRUE;
	switch ($data) {
		case 0: $posts = TRUE; $comments = TRUE; break;
		case 1: $posts = TRUE; $comments = FALSE; break;
		case 2: $posts = FALSE; $comments = TRUE; break;
	}
	if ($type < 0 || $type > 3) $type = 0;
	if ($width <= 0) $width = FALSE;
	if ($height <= 0) $height = FALSE;
	if ($days < 0) $days = 0;
	if ($caption == null || $caption == "") $caption = FALSE;
	$siteurl = pchart_get_siteurl();
	$img_tag = "<img src=\"$siteurl/wp-content/plugins/pasichart/doChart.php?";
	// url parameters
	if ($caption !== FALSE)		$img_tag .= "c=$caption&amp;";
	if ($width !== FALSE)		$img_tag .= "w=$width&amp;";
	if ($height !== FALSE)		$img_tag .= "h=$height&amp;";
	$img_tag .= "t=b&amp;";
	if ($posts) $img_tag .= "p=1&amp;";
	if ($comments) $img_tag .= "r=1&amp;";
	$img_tag .= "d=$days&amp;";
	$img_tag .= "bt=$type&amp;";
	$img_tag .= "\""; // end src
	// img parameters
	if ($caption !== FALSE)		$img_tag .= "alt=\"$caption\"";
	if ($width !== FALSE)		$img_tag .= "width=\"$width\"";
	if ($height !== FALSE)		$img_tag .= "height=\"$height\"";
	$img_tag .= " />"; // end tag
	if ($echo_tag)
		echo $img_tag;
	else
		return $img_tag;
	return TRUE;
}

?>
