<?php
// check capabilities
$imagecreate = "imagecreatetruecolor";
$imagecolor = "imagecolorexact";
if (!function_exists($imagecreate)) {
    $imagecreate = "imagecreate";
	$imagecolor = "imagecolorallocate";
}
$imagemime = "image/png";
$imageout = "imagepng";
if (!function_exists($imageout)) {
	$imagemime = "image/gif";
	$imageout = "imagegif";
}
if (!function_exists($imageout)) {
	$imagemime = "image/jpeg";
	$imageout = "imagejpeg";
}
// get size and params
$owner_uid = FALSE; $table_id = FALSE;
$number = 1;
if (isset($_GET['uid'])) $owner_uid = $_GET['uid'];
if (isset($_GET['tid'])) $table_id = $_GET['tid'];
if (isset($_GET['n'])) $number = $_GET['n'];
$gwidth = 0; $gheight = 0;
if (isset($_GET['w'])) $gwidth = $_GET['w'];
if (isset($_GET['h'])) $gheight = $_GET['h'];
$start_date = FALSE;
if (isset($_GET['ytd'])&&$_GET['ytd']=='1') 
	$start_date = mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y")-1);
else if (isset($_GET['lm'])&&$_GET['lm']=='1')
	$start_date = mktime(date("H"),date("i"),date("s"),date("m")-1,date("d"),date("Y"));
$weekly = FALSE;
if (isset($_GET['wkly'])&&$_GET['wkly']=='1') $weekly = TRUE;
// error function
function error_msg($str1,$str2) {
	global $imagecreate, $imagecolor;
	$img = $imagecreate(350,40);
	$bgcol = $imagecolor($img,0,0,0);
	$fgcol = $imagecolor($img,255,255,255);
	imagefill($img,1,1,$bgcol);
	imagestring($img,3,4,2,$str1,$fgcol);
	imagestring($img,2,4,12,$str2,$fgcol);
	header("Content-Type: image/gif");
	imagegif($img);
	exit();
}
// load wordpress config
if (!file_exists( dirname(__FILE__) . '/../../../../wp-config.php' )) {
	error_msg("Graph Plugin is not properly installed.","wp-config.php not found. Check installation path.");
}
require_once ( dirname(__FILE__) . '/../../../../wp-config.php' );
// dirty hack to get wp 2.1 compatibility :)
$table_prefix = $wpdb->prefix;
// get options
$options = get_option('pjm_graph_options');
// set default options as necessary
if (!is_array($options[$number]))
	$options[$number] = Array('title' => '', 'text' => '', 'width' => 160, 'height' => 120,
		'bg_col' => 'FFFFFF', 'fg_col' => '000000', 'line_col' => '0000FF',
		'bg_line_col' => 'CCCCFF', 'trend_line_col' => '88FF88', 'target_line_col' => 'FF0000',
		'date_fmt' => 'y/m/d', 'show_text' => TRUE, 'show_title' => TRUE, 'show_trend' => FALSE,
		'show_target' => FALSE, 'show_hl_graph' => TRUE, 'user_id' => 1, 'table_id' => 1 );
if ($owner_uid===FALSE)
	$owner_uid = $options[$number]['user_id'];
if ($table_id===FALSE)
	$table_id = $options[$number]['table_id'];
// get date format
$datefmt = $options[$number]['date_fmt'];
// get some options
$show_trend = $options[$number]['show_trend'];
if (isset($_GET['t'])&&$_GET['t']=='1') $show_trend = TRUE;
$show_target = $options[$number]['show_target'];
$show_highlow = $options[$number]['show_hl_graph'];
// helper functions
function getColor($hex,$image) {
	global $imagecolor;
	$r = hexdec(substr($hex,0,2));
	$g = hexdec(substr($hex,2,2));
	$b = hexdec(substr($hex,4,2));
	$c = $imagecolor($image,$r,$g,$b);
	return $c;
}
function parseConf() {
	global $gwidth, $gheight, $imagecreate, $imagecolor, $options, $number;
	$width = $options[$number]['width'];
	$height = $options[$number]['height']; 
	if ($gwidth!=0) $width = $gwidth;
	if ($gheight!=0) $height = $gheight;
	$image = $imagecreate($width,$height);
	$background = getColor($options[$number]['bg_col'],$image); 
	$foreground = getColor($options[$number]['fg_col'],$image); 
	$linecol = getColor($options[$number]['line_col'],$image); 
	$bglinecol = getColor($options[$number]['bg_line_col'],$image);
	$trendcol = getColor($options[$number]['trend_line_col'],$image);
	$targetcol = getColor($options[$number]['target_line_col'],$image);
	$target = $options[$number]['target'];
	return array($image,$width,$height,$background,$foreground,$linecol,$bglinecol,$trendcol,$targetcol,$target);
}
function parseData() {
	global $wpdb, $table_prefix, $start_date, $owner_uid, $table_id;
	$sql = "SELECT MAX(stamp) AS highdate, MIN(stamp) AS lowdate, "
	     . "MAX(value) AS highvalue, MIN(value) AS lowvalue "
	     . "FROM ".$table_prefix."simple_graph WHERE user_id=$owner_uid AND table_id=$table_id";
	if ($start_date !== FALSE)
		$sql .= " WHERE stamp > $start_date";
	if ( $valueset = $wpdb->get_results($sql) ) {
		foreach ($valueset as $values) {
			$highvalue = $values->highvalue;
			$lowvalue = $values->lowvalue;
			$highdate = $values->highdate;
			$lowdate = $values->lowdate;
			return array($highvalue,$lowvalue,$highdate,$lowdate);		
		}
	}	
}

// execute
list($image,$width,$height,$bgcol,$fgcol,$linecol,$bglinecol,$trendcol,$targetcol,$target) = parseConf();
// background color
imagefill($image,1,1,$bgcol);
// parse data
list($highvalue,$lowvalue,$highdate,$lowdate) = parseData();
//print("$highvalue $lowvalue $highdate $lowdate"); flush();
$graphhigh = $highvalue * 1.03;
$graphlow  = $lowvalue  * 0.97;
$midvalue   = ($graphhigh+$graphlow)/2.0;
$leftmargin = strlen(" ".(int)$graphhigh)*5;
if ($leftmargin < 20) $leftmargin = 20;
$rightmargin = 2;
$margins = $leftmargin + $rightmargin;
$graphwidth = $width - $margins;
$days = (int)(($highdate - $lowdate)/60/60/24);
if ($weekly)
	$days = $days / 7;
$daygap = 1; $gappixels = 1;
if (($graphwidth)/$days>3) {
	$daygap = 1;
	$gappixels = (($graphwidth)/$days);
} else if (($graphwidth)/$days*7>3) {
	$daygap = 7;
	$gappixels = (($graphwidth)/$days*7);
} else if (($graphwidth)/$days*30>3) {
	$daygap = 30;
	$gappixels = (($graphwidth)/$days*30);
} else if (($graphwidth)/$days*100>3) {
	$daygap = 100;
	$gappixels = (($graphwidth-22)/$days*100);
} else if (($graphwidth)/$days*365>3) {
	$daygap = 365;
	$gappixels = (($graphwidth)/$days*365);
} else {
	$daygap = 1000;
	$gappixels = (($graphwidth)/$days*1000);
}
// graph layout
imagestring($image,1,2,1,sprintf("% 3d",$graphhigh),$fgcol);
imagestring($image,1,2,2+(($height-2-7)/2)-4,sprintf("% 3d",$midvalue),$fgcol);
imagestring($image,1,2,$height-2-6-7,sprintf("% 3d",$graphlow),$fgcol);
imageline($image,$leftmargin-1,2,$width-$rightmargin,2,$bglinecol);
imageline($image,$leftmargin-1,2,$leftmargin,2,$fgcol);
imageline($image,$leftmargin-1,2+(($height-2-7)/2),$width-$rightmargin,2+(($height-2-7)/2),$bglinecol);
imageline($image,$leftmargin-1,2+(($height-2-7)/2),$leftmargin+1,2+(($height-2-7)/2),$fgcol);
imageline($image,$leftmargin-1,$height-2-7,$width-$rightmargin,$height-2-7,$fgcol);
imageline($image,$leftmargin-2,2,$leftmargin-2,$height-2-7,$fgcol);
$x = $leftmargin + $gappixels; $date = $lowdate; $lastdate = 0;
while ($x<$width-2) {
	imageline($image,$x,$height-3-7,$x,$height-4-7,$fgcol);
	$div = 1;
	if ($weekly) $div = 7;
	$date += $daygap*24*60*60 * $div;
	$datelen = strlen(date($datefmt,$date)) * 5 + 4;
	if ($lastdate<$x-$datelen) {
		$tx = $x-20;
		if ($tx>$width-42) $tx = $width-41;
		if ($lastdate<$tx-($datelen/2)) {
			//imagestring($image,1,$tx,$height-7,sprintf("%02d.%02d.%02d",date("d",$date),date("m",$date),date("y",$date)),$fgcol);
			imagestring($image,1,$tx,$height-7,date($datefmt,$date),$fgcol);
			imageline($image,$x,$height-12,$x,2,$bglinecol);
			$lastdate = $x;
		}
	}
	$x += $gappixels; 
} 
// plot data 
$lowpoint = $height-2-7;
$highpoint = 2; $lastdate = $lowdate; $prevdate = $lowdate;
$lastx = FALSE; $lasty = FALSE; $last_trend_y = FALSE;
$onepoint = ($lowpoint-$highpoint)/($graphhigh-$graphlow);
$oneday = ($width-$margins)/$days; $trendpoint = FALSE;
$lowpoint_shown = FALSE; $highpoint_shown = FALSE;
$date_clause = "";
if ($start_date!==FALSE)
	$date_clause = " WHERE stamp > $start_date ";
$sql = "SELECT * FROM ".$table_prefix."simple_graph $date_clause WHERE user_id=$owner_uid AND table_id=$table_id ORDER BY stamp ASC";
if ( $datalines = $wpdb->get_results($sql) ) {
    if (count($datalines)<2)
	error_msg("Not enough data to generate graph.","At least two data points must be inserted.");
    $totalpoints = 0.0; $pointcount = 0.0;
    foreach ($datalines as $dataline) {
	$date = $dataline->stamp;
	$dayssincelow = ($date-$lastdate)/60/60/24;
	$dayssinceprev = ($date-$prevdate)/60/60/24;
	$continue_point = true;
	$todays_point = $dataline->value;
	$div = 1;
	if ($weekly) {
		$div = 7;
		$totalpoints += $dataline->value;
		$pointcount++;
		if ($dayssinceprev<7) {
			$continue_point = false;
		} else {
			if ($pointcount==0) $pointcount = 1;
			$todays_point = 1.0 * $totalpoints / $pointcount;
			$totalpoints = 0;
			$pointcount = 0;
		}
	}
	if ($continue_point) {
	$x = $leftmargin + $dayssincelow / $div * $oneday;
	$y = $highpoint + ($graphhigh-$todays_point) * $onepoint;
	if ($show_trend) {
	if ($trendpoint === FALSE) {
		$trendpoint = $todays_point;
		$last_trend_y = $y;
	} else {
		$old_trendpoint = $trendpoint;
		$trendpoint = round( $todays_point - $old_trendpoint ) / 10 + $old_trendpoint;
		$trend_y = $highpoint + ($graphhigh - $trendpoint) * $onepoint;
		imageline($image,$lastx,$last_trend_y,$x,$trend_y,$trendcol);
		imageline($image,$x,$trend_y+1, $x, $trend_y - 1, $trendcol);
		/*imageline($image,$x-1,$trend_y-1,$x+1,$trend_y+1,$bglinecol);
		imageline($image,$x+1,$trend_y-1,$x-1,$trend_y+1,$bglinecol);
		imageline($image,$x-2,$trend_y  ,$x+2,$trend_y  ,$bglinecol);
		imageline($image,$x  ,$trend_y-2,$x  ,$trend_y+2,$bglinecol);*/
		$last_trend_y = $trend_y;
	}
	}
	if ($lastx!==FALSE) {
		imageline($image,$lastx,$lasty,$x,$y,$linecol);
	}
	$lastx = $x;
	$lasty = $y;
	imageline($image,$x-1,$y-1,$x+1,$y+1,$linecol);
	imageline($image,$x+1,$y-1,$x-1,$y+1,$linecol);
	imageline($image,$x-2,$y  ,$x+2,$y  ,$fgcol  );
	imageline($image,$x  ,$y-2,$x  ,$y+2,$fgcol  );
	$tx = $x - 12.5;
	if ($tx>($width-2-25)) $tx = $width - 2 - 25;
	if ($tx<22) $tx = 22;
	if ($show_highlow&&!$lowvalue_shown&&$todays_point==$lowvalue) {
		imagestring($image,1,$tx,$y+3,sprintf("% 5.1f",$lowvalue),$fgcol);
		$lowvalue_shown = TRUE;
	}
	if ($show_highlow&&!$highvalue_shown&&$todays_point==$highvalue) {
		imagestring($image,1,$tx,$y-10,sprintf("% 5.1f",$highvalue),$fgcol);
		$highvalue_shown = TRUE;
	}
	$prevdate = $date;
	}
    }
}

header("Content-Type: ".$imagemime);
$imageout($image);
?>
