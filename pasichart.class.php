<?php
class PasiChart {
	
	// private declarations
	
	var 	$_name,				// string
		$_width, $_height,		// int
		$_in_bgcolor, $_in_fgcolor,	// GraphColor
		$_out_bgcolor, $_out_fgcolor,	// GraphColor
		$_series_cumulative, 		// boolean
		$_view_legend,			// boolean or string
		$_view_points;			// boolean, view graph points
	
	var	$_x_axis_format,		// string, X-axis string format (printf)
		$_y_axis_format,		// string, Y-axis	- "" -
		$_x_axis_is_date,		// boolean, true if X-axis contains dates
		$_y_axis_is_date,		// boolean, true if Y-axis contains dates
		$_x_axis_high,			// int, highest value of X-axis
		$_y_axis_high,			// int
		$_x_axis_low,			// int
		$_y_axis_low,			// int
		$_x_axis_value_length,		// int
		$_y_axis_value_length,		// int
		$_x_axis_name,			// string
		$_y_axis_name;			// string
	
	var	$_em_width = 5,			// character width, font 1
		$_em_height = 7,		// character height, font 1
		$_em_f3_width = 7,		// character width, font 3
		$_em_f3_height = 10,		// character height, font 3
		$_legend_xpos,			// legend top-left corner x-pos
		$_legend_ypos,			// legend top-left corner y-pos
		$_legend_width,			// legend box width
		$_legend_height,		// legend box height
		$_graph_xpos,			// graph top-left corner x-pos
		$_graph_ypos,			// graph top-left corner y-pos
		$_graph_width,			// graph box width
		$_graph_height;			// graph box height
		
	var 	$_dataseries = Array(),		// multi-dim array of multiple dataseries
		$_x_series,			// X-axis series
		$_y_series;			// Y-axis series
	
	var	$_colors;
	
	function _init() {
		$this->_colors = Array( new GraphColor("#FF0000"),	// some default colors
			  new GraphColor("#00FF00"),
			  new GraphColor("#0000FF"),
			  new GraphColor("#FFFF00"),
			  new GraphColor("#FF00FF"),
			  new GraphColor("#00FFFF") );
	}
	// public declarations
	
	function PasiChart($name = "", $width = 240, $height = 160, 
	$cumulative = FALSE, $legend = TRUE, $view_points = TRUE, $in_bgcolor = "#FFFFFF", 
	$out_bgcolor = "#AAAAFF", $in_fgcolor = "#CCCCFF", $out_fgcolor = "#000000") {
		$this->_init();
		$this->_name 		= $name;
		$this->_width		= $width;
		$this->_height		= $height;
		$this->_in_bgcolor	= new GraphColor($in_bgcolor);
		$this->_out_bgcolor	= new GraphColor($out_bgcolor);
		$this->_in_fgcolor	= new GraphColor($in_fgcolor);
		$this->_out_fgcolor	= new GraphColor($out_fgcolor);
		$this->_series_cumulative = $cumulative;
		if ( !is_bool( $legend ) )
			$this->_view_legend = strtolower($legend);
		else
			$this->_view_legend	= $legend;
		$this->_view_points	= $view_points;
	}
	
	function addDataSeries($name, $series, $type = "line", $color = FALSE) {
		if ($color == FALSE) {
			$color = $this->_colors[count($this->_dataseries)%count($this->_colors)];
		}
		$type = strtolower($type);
		if ($type!="line"&&$type!="bar"&&$type!="pie")
			$type = "line";
		$this->_dataseries[count($this->_dataseries)] =
			Array($name, $type, $color, $series);
	}
	
	function generateXYseries() {
		
		// find high and low values in both dimensions, also max len of series' names
		$series_name_length = 0;
		foreach ($this->_dataseries as $dataseries) {
			if ( strlen( $dataseries[0] ) > $series_name_length )
				$series_name_length = strlen( $dataseries[0] );
			if (is_array($dataseries[3])) { // contains series, this may be redundant...
				foreach ($dataseries[3] as $x_value => $y_value) {
					if ( $this->_x_axis_high === null || $x_value > $this->_x_axis_high ) 
						$this->_x_axis_high = $x_value;
					if ( $this->_x_axis_low  === null || $x_value < $this->_x_axis_low  ) 
						$this->_x_axis_low  = $x_value;
					if ( $this->_y_axis_high === null || $y_value > $this->_y_axis_high )
						$this->_y_axis_high = $y_value;
					if ( $this->_y_axis_low  === null || $y_value < $this->_y_axis_low  )
						$this->_y_axis_low  = $y_value;
				}
			}
		}
		
		// ugly hack
		if ($this->_y_axis_low == 1) $this->_y_axis_low = 0;
		
		// find lengths of high values
		// first, x-axis
		if ($this->_x_axis_is_date) {	// if x-axis is a date axis
			$this->_x_axis_value_length = 
				strlen( date( $this->_x_axis_format, $this->_x_axis_high ) );
		} else {			// if x-axis is ordinary value
			$this->_x_axis_value_length =
				strlen( sprintf( $this->_x_axis_format, $this->_x_axis_high ) );
		}
		// second, y-axis
		if ($this->_y_axis_is_date) {	// if y-axis is a date axis
			$this->_y_axis_value_length = 
				strlen( date( $this->_y_axis_format, $this->_y_axis_high ) );
		} else {			// if y-axis is ordinary value
			$this->_y_axis_value_length =
				strlen( sprintf( $this->_y_axis_format, $this->_y_axis_high ) );
		}
		
		// init x- and y-series, if not given
		if ($this->_x_series==null) {
			if ($this->_x_axis_is_date) {
				$xpoints = $this->_x_axis_high - $this->_x_axis_low;
				$xpoints = $xpoints / 60 / 60 / 24; // make seconds into days
				$temp_series = array();
				for ($i = 0; $i <= $xpoints; $i++)
					$temp_series[$i] = $this->_x_axis_low + ( $i * 60 * 60 * 24 );
				$this->_x_series = $temp_series;
			} else {
				$this->_x_series = array( $this->_x_axis_low,
					1.0 * $this->_x_axis_low + ( ( $this->_x_axis_high - $this->_x_axis_low ) / 2.0 ),
					$this->_x_axis_high );
			}
		}
		if ($this->_y_series==null) {
			if ($this->_y_axis_is_date) {
				$ypoints = $this->_y_axis_high - $this->_y_axis_low;
				$ypoints = $xpoints / 60 / 60 / 24; // make seconds into days
				$temp_series = array();
				for ($i = 0; $i <= $ypoints; $i++)
					$temp_series[$i] = $this->_y_axis_low + ( $i * 60 * 60 * 24 );
				$this->_y_series = $temp_series;
			} else {
				$this->_y_series = array( $this->_y_axis_low,
					$this->_y_axis_low + ( $this->_y_axis_high - $this->_y_axis_low ) / 2.0,
					$this->_y_axis_high );
			}
		}
		
		
		// find values for legend borders, if necessary
		if ($this->_view_legend) {
			$this->_legend_width  = $this->_em_width * $series_name_length + 12;
			$this->_legend_xpos   = $this->_width - $this->_legend_width - 2;
			$this->_legend_height = ($this->_em_height + 2) * count($this->_dataseries) + 2;
			$this->_legend_ypos   = ( $this->_height - $this->_legend_height ) / 2;
		} else {
			$this->_legend_width  = 0;
			$this->_legend_xpos   = 0;
			$this->_legend_height = 0;
			$this->_legend_ypos   = 0;
		}
		
		$top_margin = 0;
		if ($this->_name!=null&&$this->_name!="")
			$top_margin = $this->_em_f3_height + 8;
		$bottom_margin = 0;
		if ( strlen( $this->_x_axis_name ) > 0 )
			$bottom_margin = $this->_em_f3_height + 6;
		$left_margin = 0;
		if ( strlen( $this->_y_axis_name ) > 0 )
			$left_margin = $this->_em_f3_height + 6;
		// find values for graph borders
		$this->_graph_xpos   = $this->_y_axis_value_length * $this->_em_width + 1 + $left_margin;
		$this->_graph_ypos   = 1 + $top_margin;
		$this->_graph_width  = $this->_width - $this->_legend_width - 6 - $this->_graph_xpos;
		$this->_graph_height = $this->_height - $this->_em_height - 2 - 
			$this->_graph_ypos - $bottom_margin;
		
		// finished
	}
	
	function initXAxis($name = "", $value_format = "%d", $is_date = FALSE) {
		$this->_x_axis_name    = $name;
		$this->_x_axis_format  = $value_format;
		$this->_x_axis_is_date = $is_date;
	}
	
	function initYAxis($name = "", $value_format = "%d", $is_date = FALSE) {
		$this->_y_axis_name    = $name;
		$this->_y_axis_format  = $value_format;
		$this->_y_axis_is_date = $is_date;
	}
	
	function _remakeColor($image, $color) {
		return imagecolorexact( $image, $color->red, $color->green, $color->blue );
	}
	
	function initGraphics(&$image) {
		
		// create image
		$image = imagecreatetruecolor($this->_width,$this->_height);
		
		// remake colors
		$this->_in_bgcolor  = $this->_remakeColor($image, $this->_in_bgcolor );
		$this->_in_fgcolor  = $this->_remakeColor($image, $this->_in_fgcolor );
		$this->_out_bgcolor = $this->_remakeColor($image, $this->_out_bgcolor);
		$this->_out_fgcolor = $this->_remakeColor($image, $this->_out_fgcolor);
		
		// also series colors
		for ( $i = 0; $i < count($this->_dataseries); $i++ ) {
			$dataseries = $this->_dataseries[$i];
			$dataseries[2] = $this->_remakeColor($image, $dataseries[2]);
			$this->_dataseries[$i] = $dataseries;
		}
		
		// background
		imagefill($image,0,0,$this->_out_bgcolor);
		
		// caption
		imagestring($image, 3,
			( $this->_width - strlen( $this->_name ) * $this->_em_f3_width ) / 2,
			0,
			$this->_name,
			$this->_out_fgcolor );
		// x-axis caption
		if ( strlen( $this->_x_axis_name ) > 0 )
		imagestring($image, 3,
			$this->_graph_xpos + ( $this->_graph_width - 
				strlen( $this->_x_axis_name ) * $this->_em_f3_width ) / 2,
			$this->_graph_ypos + $this->_graph_height + $this->_em_height + 4,
			$this->_x_axis_name,
			$this->_out_fgcolor );
		// y-axis caption
		if ( strlen( $this->_y_axis_name ) > 0 )
		imagestringup($image, 3,
			0,
			($this->_graph_ypos + $this->_graph_height ) - 
				( $this->_graph_height - 
				strlen( $this->_y_axis_name ) * $this->_em_f3_width ) / 2,
			$this->_y_axis_name,
			$this->_out_fgcolor );

	}
	
	function drawGraphBox(&$image) {
		if ($image==null)
			$this->initGraphics($image);
		// box background and frame
		imagefilledrectangle( $image, $this->_graph_xpos, $this->_graph_ypos,
			$this->_graph_xpos + $this->_graph_width,
			$this->_graph_ypos + $this->_graph_height,
			$this->_in_bgcolor );
		imageline( $image, $this->_graph_xpos, 
				$this->_graph_ypos,
				$this->_graph_xpos,
				$this->_graph_ypos + $this->_graph_height,
				$this->_out_fgcolor );
		imageline( $image, $this->_graph_xpos, 
				$this->_graph_ypos + $this->_graph_height,
				$this->_graph_xpos + $this->_graph_width,
				$this->_graph_ypos + $this->_graph_height,
				$this->_out_fgcolor );
		if ( is_array( $this->_x_series ) ) {
			$points = count($this->_x_series) - 1;
			$pointgap = 1.0 * $this->_graph_width / $points;
			$x = 0; $last_capt = FALSE; $n = 0;
			while ( $x <= $this->_graph_width ) {
				imageline( $image,
					$this->_graph_xpos + $x,
					$this->_graph_ypos + $this->_graph_height - 1,
					$this->_graph_xpos + $x,
					$this->_graph_ypos + $this->_graph_height - 2,
					$this->_out_fgcolor );
				$string = "";
				if ( $this->_x_axis_is_date ) {
					$string = date( $this->_x_axis_format, $this->_x_series[$n] );
				} else {
					$string = sprintf( $this->_x_axis_format, $this->_x_series[$n] );
				}
				$capt_width = ( strlen( $string ) ) * $this->_em_width;
				if ( $last_capt === FALSE || $x > $last_capt + $capt_width ) {
					imageline( $image,
						$this->_graph_xpos + $x,
						$this->_graph_ypos + $this->_graph_height - 3,
						$this->_graph_xpos + $x,
						$this->_graph_ypos,
						$this->_in_fgcolor );
					imagestring( $image, 1,
						$this->_graph_xpos + $x - floor ( $capt_width / 2.0 ),
						$this->_graph_ypos + $this->_graph_height + 1,
						$string,
						$this->_out_fgcolor );
					$last_capt = $x;
				}
				$x += $pointgap;
				$n++;
			}
		}
		if (is_array($this->_y_series)) {
			$points = count($this->_y_series) - 1;
			$pointgap = 1.0 * $this->_graph_height / $points;
			if ( $pointgap < 3 )
				$pointgap = 3;
			$y = 0; $last_capt = -$this->_graph_ypos; 
			$n = 0;
			while ( $y <= $this->_graph_height ) {
				imageline( $image,
					$this->_graph_xpos,
					$this->_graph_ypos + $this->_graph_height - $y,
					$this->_graph_xpos + 2,
					$this->_graph_ypos + $this->_graph_height - $y,
					$this->_out_fgcolor );
				$string = "";
				if ( $this->_y_axis_is_date ) {
					$string = date( $this->_y_axis_format, $this->_y_series[$n] );
				} else {
					$string = sprintf( $this->_y_axis_format, $this->_y_series[$n] );
				}
				$capt_height = $this->_em_height + 1;
				$capt_width  = strlen( $string ) * $this->_em_width;
				if ( $y > $last_capt + $capt_height + 2 ) {
					if ( $y > 0 ) imageline( $image,
						$this->_graph_xpos + 3,
						$this->_graph_ypos + $this->_graph_height - $y,
						$this->_graph_xpos + $this->_graph_width,
						$this->_graph_ypos + $this->_graph_height - $y,
						$this->_in_fgcolor );
					imagestring( $image, 1,
						$this->_graph_xpos - $capt_width - 1,
						$this->_graph_ypos + $this->_graph_height - $y - ( $capt_height / 2.0 ),
						$string,
						$this->_out_fgcolor );
					$last_capt = $y;
				}
				$y += $pointgap;
				$n++;
			}
		}
	}
	
	function _drawPoint( &$image, $x, $y, $color, $n ) {
		if (!$this->_view_points)
			return;
		switch ( $n % 4 ) {
			case 0:
				imagefilledrectangle( $image,
					$x - 3, $y - 3,
					$x + 3, $y + 3,
					$color );
				break;
			case 1:
				imagefilledpolygon( $image,
					array( 	$x, $y - 4,
						$x - 4, $y,
						$x, $y + 4,
						$x + 4, $y ),
					4, $color );
				break;
			case 2:
				imagefilledpolygon( $image,
					array( 	$x, $y - 4,
						$x - 4, $y + 3,
						$x + 4, $y + 3 ),
					3, $color );
				break;
			case 3:
				imagefilledellipse( $image,
					$x, $y,
					7, 7,
					$color );
				break;
		}
	}
	
	function drawLegend( &$image ) {
		if ( !$this->_view_legend )
			return;
		// draw legend box
		imagefilledrectangle( $image, $this->_legend_xpos,
			$this->_legend_ypos,
			$this->_legend_xpos + $this->_legend_width,
			$this->_legend_ypos + $this->_legend_height,
			$this->_in_bgcolor );
		imagerectangle( $image, $this->_legend_xpos,
			$this->_legend_ypos,
			$this->_legend_xpos + $this->_legend_width,
			$this->_legend_ypos + $this->_legend_height,
			$this->_out_fgcolor );
		// draw legends
		$n = 0;
		foreach ($this->_dataseries as $dataseries) {
/*			imagefilledrectangle( $image,
				$this->_legend_xpos + 2,
				$this->_legend_ypos + 2 + $n * ($this->_em_height+2),
				$this->_legend_xpos + 8,
				$this->_legend_ypos + 8 + $n * ($this->_em_height+2),
				$dataseries[2] );
			imagerectangle( $image,
				$this->_legend_xpos + 2,
				$this->_legend_ypos + 2 + $n * ($this->_em_height+2),
				$this->_legend_xpos + 8,
				$this->_legend_ypos + 8 + $n * ($this->_em_height+2),
				$this->_out_fgcolor ); */
			$this->_drawPoint( $image, 
				$this->_legend_xpos + 5,
				$this->_legend_ypos + 5 + $n * ($this->_em_height+2),
				$dataseries[2],
				$n );
			imagestring( $image,
				1,
				$this->_legend_xpos + 10,
				$this->_legend_ypos + 2 + $n * ($this->_em_height+2),
				$dataseries[0],
				$this->_out_fgcolor );
			$n++;
		}
	}
	
	function drawGraphs( &$image ) {
		if ( $this->_dataseries == null || count( $this->_dataseries ) == 0 )
			return FALSE;
		// initial parsing
		$bars = 0; $lines = 0; $pies = 0;
		foreach ( $this->_dataseries as $dataseries ) {
			$type = strtolower($dataseries[1]);
			switch ( $type ) {
				case 'bar': $bars++; break;
				case 'line': $lines++; break;
				case 'pie': $pies++; break;
			}
		}
		if ( count( $this->_dataseries ) != 1 ) {
			$lines += $pies;
		} else if ( 'pie' == $this->_dataseries[0][1] ) { // do the pie thing
			// THIS IS A PIE
			return TRUE;
		}
		$n = 0;
		foreach ( $this->_dataseries as $dataseries ) {
			$type = $dataseries[1];
			$color = $dataseries[2];
			$series = $dataseries[3];
			$datalow = $this->_y_axis_low;
			$datahigh = $this->_y_axis_high;
			$pointlow = $this->_x_axis_low;
			$pointhigh = $this->_x_axis_high;
			$datagap = 1.0 * $this->_graph_height / ( $datahigh - $datalow );
			$pointgap = 1.0 * $this->_graph_width / ( $pointhigh - $pointlow );
			$lastxpos = false;
			$lastypos = false;
			if ($bars>0)
				$barwidth = 1.0 * ( $this->_graph_width / count($series) ) / $bars;
			else
				$barwidth = 0;
			if ($barwidth < 1) $barwidth = 1;
			foreach ( $series as $point => $data ) {
				if ( $point >= $pointlow && $point <= $pointhigh ) {
					if ( $data > $datahigh ) $data = $datahigh;
					if ( $data < $datalow  ) $data = $datalow;
					$xpos = ( $point - $pointlow ) * $pointgap;
					//$xpos = $x * $pointgap;
					//$y = ( $datahigh - $datalow ) / $data;
					//$ypos = $y * $datagap;
					$ypos = ( $data - $datalow ) * $datagap;
					if ("line"==$type) {
						if ( $lastxpos !== FALSE ) {
							imageline( $image,
								$this->_graph_xpos + $lastxpos,
								$this->_graph_ypos + $this->_graph_height - $lastypos,
								$this->_graph_xpos + $xpos,
								$this->_graph_ypos + $this->_graph_height - $ypos,
								$color );
						}
						//imagesetpixel(
						$this->_drawPoint(
							$image, 
							$this->_graph_xpos + $xpos,
							$this->_graph_ypos + $this->_graph_height - $ypos,
							$color, $n );
					} else if ("bar"==$type) {
						imagefilledrectangle( $image,
							$this->_graph_xpos + $xpos + $n * $barwidth,
							$this->_graph_ypos + $this->_graph_height - $ypos,
							$this->_graph_xpos + $xpos + ( 1 + $n ) * $barwidth,
							$this->_graph_ypos + $this->_graph_height - 1,
							$color );
					}
					$lastxpos = $xpos;
					$lastypos = $ypos;
				}
			}
			$n++;
		}
	}
	
}

class GraphColor {
	
	var $red, $green, $blue;
	
	function GraphColor($color, $green = FALSE, $blue = FALSE) {
		if ($green!==FALSE&&$blue!==FALSE) { // parameters are RGB
			$this->red 	= $color;
			$this->green 	= $green;
			$this->blue 	= $blue;
		} else if ( "#" == substr($color,0,1) ) { // $color is html color
			$color = str_pad($color,7,"0",STR_PAD_RIGHT); // fill short strings
			$this->red 	= hexdec(substr($color,1,2));
			$this->green 	= hexdec(substr($color,3,2));
			$this->blue 	= hexdec(substr($color,5,2));
		} else if ( is_numeric($color) ) { // $color is *probably* 24bit color integer
			$this->red 	= (($color >> 16) & 0xFF);
			$this->green 	= (($color >> 8) & 0xFF);
			$this->blue 	= (($color) & 0xFF);
		} else { // can't figure out $color, so make it black :)
			$this->red	= 0;
			$this->green	= 0;
			$this->blue	= 0;
		}
	}
	
}

?>
