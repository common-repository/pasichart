<?php
// load pasichart class
require_once("pasichart.class.php");
// load wordpress config
$path = dirname(__FILE__);
if ( file_exists( $path . '/../../../wp-config.php' ) ) // if plugin is properly installed
	require_once( $path . '/../../../wp-config.php' );
else { // if plugin is installed less properly
	$img = imagecreatetruecolor(300,200);
	imagefill($img,1,1,imagecolorexact($img,0,0,0));
	imagestring($img,2,1,1,'WP-PasiChart not installed properly.',imagecolorexact($img,255,255,255));
	header("Content-Type: image/png");
	imagepng($img);
	exit();
}

// start graphing
$chart_caption = "";
if (isset($_REQUEST['c'])) $chart_caption = $_REQUEST['c'];
$width = 320; $height = 200;
if (isset($_REQUEST['w'])) $width = $_REQUEST['w'];
if (isset($_REQUEST['h'])) $height = $_REQUEST['h'];
$graph = new PasiChart($chart_caption,$width,$height,FALSE,TRUE,TRUE,
"#dddddd","#ffffff","#bbbbbb","#000000");
$image = null;
$series = FALSE;
$type = 'n'; // normal by default
if (isset($_GET['t'])) $type = $_GET['t'];
if ($type=='n') {
	if (isset($_GET['s'])) $series = $_GET['s'];
	if ($series!==FALSE) foreach ($series as $ds) {
		$data = unserialize(stripslashes($ds));
		$graph->addDataSeries($data[0],$data[1]);
	}
	$graph->initXAxis("","%.1f",false);
	$graph->initYAxis("","%.1f",false);
	$graph->generateXYSeries();
	$graph->initGraphics($image);
	$graph->drawGraphBox($image);
	$graph->drawLegend($image);
	$graph->drawGraphs($image);
} else if ($type=='b') {
	$posts = FALSE; $comments = FALSE;
	if (isset($_GET['p'])&&$_GET['p']=='1') $posts = TRUE;
	if (isset($_GET['r'])&&$_GET['r']=='1') $comments = TRUE;
	$days = 0;
	if (isset($_GET['d'])) $days = $_GET['d'];
	$startdate = FALSE;
	if ($days > 0)
		$startdate = mktime(0,0,0,date("m"),date("d")-$days,date("Y"));
	$timetype = 0;
	if (isset($_GET['bt'])) $timetype = $_GET['bt'];
	// begin count
	// count posts
	$postcount = Array();
	if ($posts) {
		$psql =   "SELECT post_date "
			."FROM {$table_prefix}posts "
			."WHERE post_status='publish'";
		if ( $startdate !== FALSE )
			$psql .= " AND post_date >= '".date("Y-m-d",$startdate)." 00:00:00'";
		if ( $results = $wpdb->get_results( $psql ) ) {
			foreach ( $results as $result ) {
				$pdate = strtotime( substr( $result->post_date, 0, 10 ) );
				if (!isset($postcount[$pdate]))
					$postcount[$pdate] = 1;
				else
					$postcount[$pdate]++;
				if ($startdate === FALSE || $pdate < $startdate)
					$startdate = $pdate;
			}
		}
	}
	// count comments
	$commentcount = Array();
	if ($comments) {
		$rsql =   "SELECT comment_date "
			."FROM {$table_prefix}comments "
			."WHERE comment_approved='1'";
		if ( $startdate !== FALSE )
			$rsql .= " AND comment_date >= '".date("Y-m-d",$startdate)." 00:00:00'";
		if ( $results = $wpdb->get_results( $rsql ) ) {
			foreach ( $results as $result ) {
				$rdate = strtotime( substr( $result->comment_date, 0, 10 ) );
				if (!isset($commentcount[$rdate]))
					$commentcount[$rdate] = 1;
				else
					$commentcount[$rdate]++;
				if ($startdate === FALSE || $rdate < $startdate)
					$startdate = $rdate;
			}
		}
	}
	if ($timetype == 0) { // daily
		/*if ($posts)
			$graph->addDataSeries("Posts",$postcount,"bar");
		if ($comments)
			$graph->addDataSeries("Comments",$commentcount,"bar");
		$graph->initXAxis("Dates","y-m-d",true);
		$graph->initYAxis("Amounts","%d",false);
		$graph->generateXYSeries();
		$graph->initGraphics($image);
		$graph->drawGraphBox($image);
		$graph->drawLegend($image);
		$graph->drawGraphs($image);*/
		$posts_in_day = Array();
		$comments_in_day = Array();
		$days = Array();
		$day_one = strtotime(date("Y-m-d 00:00:00",$startdate));
		$day_two = mktime(0,0,0,date("m",$day_one),date("d",$day_one)+1,date("Y",$day_one));
		do {
			$days[count($days)] = $day_one;
			if (!isset($comments_in_day[$day_one]))
				$comments_in_day[$day_one] = 0;
			if (!isset($posts_in_day[$day_one]))
				$posts_in_day[$day_one] = 0;
			foreach ($postcount as $pdate => $count) {
				if ($pdate >= $day_one && $pdate < $day_two) {
					$posts_in_day[$day_one] += $count;
				}
			}
			foreach ($commentcount as $pdate => $count) {
				if ($pdate >= $day_one && $pdate < $day_two) {
					$comments_in_day[$day_one] += $count;
				}
			}
			$day_one = $day_two;
			$day_two = mktime(0,0,0,date("m",$day_one),date("d",$day_one)+1,date("Y",$day_one));
		} while ($day_two<=mktime(0,0,0,date("m"),date("d")+1,date("Y")));
		$days[count($days)] = $day_one;
		$posts_in_day[$day_one] = 0;
		$comments_in_day[$day_one] = 0;
		if ($posts)
			$graph->addDataSeries("Posts",$posts_in_day,"bar");
		if ($comments)
			$graph->addDataSeries("Comments",$comments_in_day,"bar");
		$graph->initXAxis("Days","y/m/d",true);
		$graph->initYAxis("Amounts","%d",false);
		$graph->_x_series = $days;
		$graph->generateXYSeries();
		$graph->initGraphics($image);
		$graph->drawGraphBox($image);
		$graph->drawLegend($image);
		$graph->drawGraphs($image);
	} else if ($timetype == 1) { // weekly
		$posts_in_week = Array();
		$comments_in_week = Array();
		$weeks = Array();
		$week_one = strtotime(date("Y-m-d 00:00:00",$startdate));
		$week_two = mktime(0,0,0,date("m",$week_one),date("d",$week_one)+7,date("Y",$week_one));
		do {
			$weeks[count($weeks)] = $week_one;
			if (!isset($comments_in_week[$week_one]))
				$comments_in_week[$week_one] = 0;
			if (!isset($posts_in_week[$week_one]))
				$posts_in_week[$week_one] = 0;
			foreach ($postcount as $pdate => $count) {
				if ($pdate >= $week_one && $pdate < $week_two) {
					$posts_in_week[$week_one] += $count;
				}
			}
			foreach ($commentcount as $pdate => $count) {
				if ($pdate >= $week_one && $pdate < $week_two) {
					$comments_in_week[$week_one] += $count;
				}
			}
			$week_one = $week_two;
			$week_two = mktime(0,0,0,date("m",$week_one),date("d",$week_one)+7,date("Y",$week_one));
		} while ($week_two<=mktime(0,0,0,date("m"),date("d")+7,date("Y")));
		$weeks[count($weeks)] = $week_one;
		$posts_in_week[$week_one] = 0;
		$comments_in_week[$week_one] = 0;
		if ($posts)
			$graph->addDataSeries("Posts",$posts_in_week,"bar");
		if ($comments)
			$graph->addDataSeries("Comments",$comments_in_week,"bar");
		$graph->initXAxis("Weeks","W/y",true);
		$graph->initYAxis("Amounts","%d",false);
		$graph->_x_series = $weeks;
		$graph->generateXYSeries();
		$graph->initGraphics($image);
		$graph->drawGraphBox($image);
		$graph->drawLegend($image);
		$graph->drawGraphs($image);
	} else if ($timetype == 2) { // monthly
		$posts_in_month = Array();
		$comments_in_month = Array();
		$months = Array();
		$month_one = strtotime(date("Y-m-01 00:00:00",$startdate));
		$month_two = mktime(0,0,0,date("m",$month_one)+1,1,date("Y",$month_one));
		do {
			$months[count($months)] = $month_one;
			if (!isset($comments_in_month[$month_one]))
				$comments_in_month[$month_one] = 0;
			if (!isset($posts_in_month[$month_one]))
				$posts_in_month[$month_one] = 0;
			foreach ($postcount as $pdate => $count) {
				if ($pdate >= $month_one && $pdate < $month_two) {
					$posts_in_month[$month_one] += $count;
				}
			}
			foreach ($commentcount as $pdate => $count) {
				if ($pdate >= $month_one && $pdate < $month_two) {
					$comments_in_month[$month_one] += $count;
				}
			}
			$month_one = $month_two;
			$month_two = mktime(0,0,0,date("m",$month_one)+1,1,date("Y",$month_one));
		} while ($month_two<=mktime(0,0,0,date("m")+1,1,date("Y")));
		$months[count($months)] = $month_one;
		$posts_in_month[$month_one] = 0;
		$comments_in_month[$month_one] = 0;
		if ($posts)
			$graph->addDataSeries("Posts",$posts_in_month,"bar");
		if ($comments)
			$graph->addDataSeries("Comments",$comments_in_month,"bar");
		$graph->initXAxis("Months","M/y",true);
		$graph->initYAxis("Amounts","%d",false);
		$graph->_x_series = $months;
		$graph->generateXYSeries();
		$graph->initGraphics($image);
		$graph->drawGraphBox($image);
		$graph->drawLegend($image);
		$graph->drawGraphs($image);
	} else if ($timetype == 3) { // yearly
		$posts_in_year = Array();
		$comments_in_year = Array();
		$years = Array();
		$year_one = strtotime(date("Y-01-01 00:00:00",$startdate));
		$year_two = mktime(0,0,0,date("m",$year_one),1,date("Y",$year_one)+1);
		do {
			$years[count($years)] = $year_one;
			if (!isset($comments_in_year[$year_one]))
				$comments_in_year[$year_one] = 0;
			if (!isset($posts_in_year[$year_one]))
				$posts_in_year[$year_one] = 0;
			foreach ($postcount as $pdate => $count) {
				if ($pdate >= $year_one && $pdate < $year_two) {
					$posts_in_year[$year_one] += $count;
				}
			}
			foreach ($commentcount as $pdate => $count) {
				if ($pdate >= $year_one && $pdate < $year_two) {
					$comments_in_year[$year_one] += $count;
				}
			}
			$year_one = $year_two;
			$year_two = mktime(0,0,0,date("m",$year_one),1,date("Y",$year_one)+1);
		} while ($year_two<=mktime(0,0,0,date("m"),1,date("Y")+1));
		$years[count($years)] = $year_one;
		$posts_in_year[$year_one] = 0;
		$comments_in_year[$year_one] = 0;
		if ($posts)
			$graph->addDataSeries("Posts",$posts_in_year,"bar");
		if ($comments)
			$graph->addDataSeries("Comments",$comments_in_year,"bar");
		$graph->initXAxis("Years","Y",true);
		$graph->initYAxis("Amounts","%d",false);
		$graph->_x_series = $years;
		$graph->generateXYSeries();
		$graph->initGraphics($image);
		$graph->drawGraphBox($image);
		$graph->drawLegend($image);
		$graph->drawGraphs($image);
	}
/*	header("Content-Type: text/plain");
	print_r($postcount);
	print_r($commentcount);
	exit();*/
}
header("Content-Type: image/png");
imagepng($image);
?>
