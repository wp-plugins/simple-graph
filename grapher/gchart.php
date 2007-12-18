<?php
$weekly = TRUE;
$owner_uid = $options[$number]['user_id'];
$table_id = $options[$number]['table_id'];
// get date format
$datefmt = $options[$number]['date_fmt'];
// get some options
$show_trend = $options[$number]['show_trend'];
$show_target = $options[$number]['show_target'];
$show_highlow = $options[$number]['show_hl_graph'];
// helper functions
function getColor($hex,$image) {
	global $imagecolor;
	$r = hexdec(substr($hex,0,2));
	$g = hexdec(substr($hex,2,2));
	$b = hexdec(substr($hex,4,2));
	//$c = $imagecolor($image,$r,$g,$b);
	return $hex;//$c;
}
function parseConf($gwidth, $gheight, $imagecreate, $imagecolor, $options, $number) {
	$width = $options[$number]['width'];
	$height = $options[$number]['height']; 
	if ($gwidth!=0) $width = $gwidth;
	if ($gheight!=0) $height = $gheight;
	$image = 0;
	//$image = $imagecreate($width,$height);
	$background = getColor($options[$number]['bg_col'],$image); 
	$foreground = getColor($options[$number]['fg_col'],$image); 
	$linecol = getColor($options[$number]['line_col'],$image); 
	$bglinecol = getColor($options[$number]['bg_line_col'],$image);
	$trendcol = getColor($options[$number]['trend_line_col'],$image);
	$targetcol = getColor($options[$number]['target_line_col'],$image);
	$target = $options[$number]['target'];
	return array($image,$width,$height,$background,$foreground,$linecol,$bglinecol,$trendcol,$targetcol,$target);
}
function parseData($start_date, $owner_uid, $table_id, $options, $number) {
	global $wpdb;

	$sql = "SELECT MAX(stamp) AS highdate, MIN(stamp) AS lowdate, "
	     . "MAX(value) AS highvalue, MIN(value) AS lowvalue "
	     . "FROM ".$wpdb->prefix."simple_graph WHERE user_id=$owner_uid AND table_id=$table_id";
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
$start_date = false; //mktime(date("H"),date("i"),date("s"),date("m")-3,date("d"),date("Y"));
// execute
list($image,$width,$height,$bgcol,$fgcol,$linecol,$bglinecol,$trendcol,$targetcol,$target) = 
  parseConf($gwidth, $gheight, $imagecreate, $imagecolor, $options, $number);
// background color
//imagefill($image,1,1,$bgcol);
// parse data
list($highvalue,$lowvalue,$highdate,$lowdate) = 
  parseData($start_date, $owner_uid, $table_id, $options, $number);
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
$verticalaxislabels = sprintf("1:|%.1f|%.1f|%.1f",$lowvalue,$midvalue,$highvalue);
$x = $leftmargin + $gappixels; $date = $lowdate; $lastdate = 0;
$horizontalaxislabels = "0:|";
while ($x<$width-2) {
	$div = 1;
	if ($weekly) $div = 7;
	$date += $daygap*24*60*60 * $div;
	$datelen = strlen(date($datefmt,$date)) * 5 + 4;
	if ($lastdate<$x-$datelen) {
		$tx = $x-20;
		if ($tx>$width-42) $tx = $width-41;
		if ($lastdate<$tx-($datelen/2)) {
			$horizontalaxislabels .= date($datefmt,$date) . "|";
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
global $wpdb;
if ($start_date!==FALSE)
	$date_clause = " WHERE stamp > $start_date ";
$sql = "SELECT * FROM ".$wpdb->prefix."simple_graph $date_clause WHERE user_id=$owner_uid AND table_id=$table_id ORDER BY stamp ASC";
$trenddata = "|";
$data = $datatype;
if ( $datalines = $wpdb->get_results($sql) ) {
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
		$trenddata .= sprintf("%.1f,",abs(($trendpoint-$lowvalue)/($highvalue-$lowvalue)*100.0));
		$last_trend_y = $trend_y;
	}
	}
	if ($lastx!==FALSE) {
		$data .= sprintf("%.1f,",abs(($todays_point-$lowvalue)/($highvalue-$lowvalue)*100.0));
	}
	$lastx = $x;
	$lasty = $y;
	$tx = $x - 12.5;
	if ($tx>($width-2-25)) $tx = $width - 2 - 25;
	if ($tx<22) $tx = 22;
	$prevdate = $date;
	}
    }
}
$horizontalaxislabels = sprintf("0:|%s|%s|",date($datefmt,$lowdate),date($datefmt,$highdate));
$data = substr($data,0,strlen($data)-1);
$trenddata = substr($trenddata,0,strlen($trenddata)-1);
if ($options[$number]['show_trend'])
  $data .= $trenddata;
$colors = "$linecol,$trendcol";
?>