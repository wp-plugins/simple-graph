<?php
/*
Plugin Name: Simple Graph
Plugin URI: http://www.pasi.fi/simple-graph-wordpress-plugin/
Description: Administrator modules for simple graph tool. Requires Wordpress 2.0 or newer, and GD graphics library.
Author: Pasi Matilainen
Version: 0.9.7
Author URI: http://www.pasi.fi/
*/ 

function widget_pjm_graph_init() {
	if (!function_exists('register_sidebar_widget'))
		return;
	function widget_pjm_graph_widget($args) {
		global $wpdb;
		$title = get_option('pjm_graph_title');
		if ($title=="") $title = null;
		extract($args);
		?>
			<?php echo $before_widget; ?>
				<?php if ($title!=null) {
					echo $before_title
					. $title
					. $after_title; } ?>
					<?php pjm_graph(); ?>
			<?php echo $after_widget; ?>
		<?php
	}
	register_sidebar_widget(array('Simple Graph','widgets'),'widget_pjm_graph_widget');
	
	function widget_pjm_graph_control() {
		$widget_title = get_option('pjm_graph_title');
		if ($_POST['pjm_graph_submit']) {
			$widget_title = strip_tags(stripslashes($_POST['pjm_graph_title']));
			update_option('pjm_graph_title',$widget_title);
		}
		echo '<p style="text-align:right;"><label for="pjm_graph_title">' . __('Title:') .' <input style="width:200px;" id="pjm_graph_title" name="pjm_graph_title" type="text" value="' . $widget_title . '" /></label></p>';
		echo '<input type="hidden" id="pjm_graph_submit" name="pjm_graph_submit" value="1" />';
	}
	register_widget_control(array('Simple Graph','widgets'),'widget_pjm_graph_control',300,100);
}
add_action('plugins_loaded','widget_pjm_graph_init');

function pjm_graph($x=0,$y=0,$trend=FALSE,$target=FALSE,$ytd=FALSE,$lm=FALSE,$wkly=FALSE) {
$width = get_option('pjm_graph_width');
$height = get_option('pjm_graph_height');
if ($x!=0) $width = $x;
if ($y!=0) $height = $y;
$siteurl = get_option('siteurl');
if ("/"==substr($siteurl,strlen($siteurl)-1)) 
	$siteurl = substr($siteurl,0,strlen($siteurl)-1);
?>
<img src="<?php echo $siteurl; ?>/wp-content/plugins/pjm_graph/grapher/graph.php?<?php if ($trend) echo "t=1&amp;"; ?><?php if ($ytd) echo "ytd=1&amp;"; if ($lm) echo "lm=1&amp;"; if ($wkly) echo "wkly=1&amp;"; if ($target) echo "l=1&amp;"; ?><?php if ($x!=0) { ?>w=<?php echo $width;?>&amp;h=<?php echo $height; } ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" alt="Graph by www.pasi.fi/simple-graph-wordpress-plugin/" style="border:0;" />
<?php }

$table_prefix = $wpdb->prefix;

function pjm_graph_install() {
	global $table_prefix, $wpdb, $user_level;
	if ($user_level!=10) return;
	$table_name = $table_prefix . 'pjm_graph';
	if ( $wpdb->get_var("show tables like '$table_name'") != $table_name ) {
		$sql = "CREATE TABLE $table_name (
			id int PRIMARY KEY AUTO_INCREMENT,
			stamp int NOT NULL,
			value double NOT NULL)";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
		
		update_option('pjm_graph_width','160');
		update_option('pjm_graph_height','100');
		update_option('pjm_graph_bgcolor','FFFFFF');
		update_option('pjm_graph_fgcolor','000000');
		update_option('pjm_graph_linecolor','0000FF');
		update_option('pjm_graph_bglinecolor','A0A0FF');
	}
}

register_activation_hook(__FILE__,'pjm_graph_install');

function pjm_managePanel() {
	if (function_exists('add_management_page')) {
		add_management_page('Simple Graph','Simple Graph',5,basename(__FILE__),'pjm_show_manage_panel');
	}
}

add_action('admin_menu','pjm_managePanel');

function pjm_show_manage_panel() {
global $wpdb, $table_prefix, $user_level;
if ($user_level<5) return;
if (isset($_GET['pjm_graph_delete'])) { ?>
<div class="updated"><p><strong><?php _e('Data deleted.'); 
?></strong></p></div><?php
$item_id = $_GET['pjm_graph_delete'];
$sql = "DELETE FROM ".$table_prefix."pjm_graph WHERE id=".$item_id;
$wpdb->query($sql);
}
if (isset($_POST['pjm_graph_value'])) { ?>
<div class="updated"><p><strong><?php _e('Data added.');
?></strong></p></div><?php
// insert data here
$date = strtotime($_POST['pjm_graph_year']."-".$_POST['pjm_graph_month']."-".$_POST['pjm_graph_day']);
$value = $_POST['pjm_graph_value'];
$sql = "INSERT INTO ".$table_prefix."pjm_graph (stamp, value) values ($date,$value)";
$wpdb->query($sql);
} else if (isset($_POST['batch_insert'])) { ?>
<div class="updated"><p><strong><?php _e('Batch insert results'); ?></strong></p>
<p><?php
$lines = explode("\n",$_POST['batch_insert']);
$accepted = 0; $rejected = 0;
foreach ($lines as $line) {
	$reject = FALSE;
	$line = trim($line);
	$parts = explode(",",$line);
	if (count($parts)==2) {
		$dateparts = explode("-",$parts[0]);
		if (count($dateparts)==3) {
			$date = mktime(0,0,0,$dateparts[1],$dateparts[2],$dateparts[0]);
			$value = mysql_real_escape_string($parts[1]);
			$sql = "INSERT INTO ".$table_prefix."pjm_graph (stamp, value) values ($date,$value)";
			$wpdb->query($sql);
		} else $reject = TRUE;
	} else 
		$reject = TRUE;
	if ($reject) {
		print("<b>Rejected:</b> $line<br />");
		$rejected++;
	} else {
		$accepted++;
	}
}
print("Accepted <b>$accepted</b> data points and rejected <b>$rejected</b> data points.");
?></p>
</div><?php
}
?>
<div class="wrap">
<form method="post">
<h2><?php _e('Simple Graph Data'); ?></h2>
<form method="post">
<fieldset class="options">
<legend><?php _e('Insert new data point'); ?></legend>
<table class="editform optiontable">
<tr>
<th scope="row"><?php _e('Date'); ?>:</th>
<td>Year: <select name="pjm_graph_year"><?php
$year = date("Y")-2;
for ($y = $year; $y<$year+5; $y++) { ?>
<option value="<?php echo $y; ?>"<?php if ($y==($year+2)) echo " selected=\"selected\"";?>><?php echo $y; ?></option>
<?php } ?></select>
Month: <select name="pjm_graph_month"><?php
for ($m = 1; $m<=12; $m++) { ?>
<option value="<?php printf("%02d",$m); ?>"<?php if ($m==date("m")) echo 
" selected=\"selected\""; ?>><?php printf("%02d",$m); ?></option>
<?php } ?></select>
Day: <select name="pjm_graph_day"><?php
for ($m = 1; $m<=31; $m++) { ?>
<option value="<?php printf("%02d",$m); ?>"<?php if ($m==date("d")) echo 
" selected=\"selected\""; ?>><?php printf("%02d",$m); ?></option>
<?php } ?></select>
</td></tr>
<tr>
<th scope="row"><?php _e('Value'); ?>:</th>
<td><input type="text" name="pjm_graph_value" /></td>
</tr>
</table>
</fieldset>
<p class="submit">
<input type="submit" name="graph_insert" value="<?php _e('Insert data'); ?> &raquo;" />
</p>
</form>

<form method="post">
<fieldset class="options">
<legend><?php _e('Batch insert'); ?></legend>
<p>Use this to insert multiple values at once. Please enter one 
date-value pair per line. Dates must be in YYYY-MM-DD format, 
followed by a comma, and a decimal value. (For example, 
<code>"2006-09-19,95.5"</code>.)
Lines that can't be parsed will be rejected, while other lines are
inserted into database without further validation. <b>Therefore, this 
is for advanced users only!</b></p>
<table class="editform optiontable">
<tr>
<th scope="row">Insert dates &amp; values:</th>
<td><textarea name="batch_insert" rows="10" cols="40"></textarea></td>
</tr>
</table>
</fieldset>
<p class="submit">
<input type="submit" name="graph_insert" value="<?php _e('Insert data'); ?> &raquo;" />
</p>
</form>

</div>
<div class="wrap">
<h2><?php _e('Data points'); ?></h2>
<table id="the-list-x" width="50%" cellpadding="3" cellspacing="3">
<tr><th>ID</th><th><?php _e('Date'); ?></th><th><?php _e('Value'); ?></th></tr>
<?php
$offset = 0; $row_count = 50;
if (isset($_REQUEST['offset']))
	$offset = mysql_real_escape_string($_REQUEST['offset']);
if (isset($_REQUEST['rows']))
	$row_count = mysql_real_escape_string($_REQUEST['rows']);
$sql = "SELECT * FROM ".$table_prefix."pjm_graph ORDER BY id DESC LIMIT $offset,$row_count";
if ( $valueset = $wpdb->get_results($sql) ) {
	$rows = count($valueset);
	print("<caption>");
	if ($offset>0) {
		$new_off = $offset - $row_count;
		if ($new_off < 0) $new_off = 0;
		print(" <a href=\"edit.php?page=pjm_graph.php&offset=$new_off&amp;rows=50\">&laquo; Show previous 50</a> ");
	}
	print("Data points $offset - ".($offset+$rows));
	if ($rows>=$row_count)
		print(" <a href=\"edit.php?page=pjm_graph.php&offset=".($offset+$rows)."&rows=50\">Show next 50 &raquo;</a> ");
	print("</caption>");
	foreach ($valueset as $values) { 
		$class = ('alternate' == $class) ? '' : 'alternate'; ?>
<tr id="post-"<?php echo $values->id; ?>" class="<?php echo $class; ?>">
<td><?php echo $values->id; ?></td>
<td><?php echo date("Y-m-d",$values->stamp); ?></td>
<td><?php echo $values->value; ?></td>
<td><a href="edit.php?page=pjm_graph.php&amp;pjm_graph_delete=<?php echo $values->id; ?>"><?php _e('Delete'); ?></a></td>
</tr>	
	<?php }
}
?>
</table>
</div>
<?php
}

function pjm_settingsPanel() {
//	if (function_exists('add_options_page')) {
//		add_options_page('Simple Graph','Simple Graph',10,basename(__FILE__),'pjm_show_settings_panel');
//	}
	add_submenu_page('plugins.php','Simple Graph Configuration','Simple Graph Configuration',10,basename(__FILE__),'pjm_show_settings_panel');
}

add_action('admin_menu','pjm_settingsPanel');

function pjm_show_settings_panel() {
if (isset($_POST['graph_update'])) { ?>
<div class="updated"><p><strong><?php _e('Settings updated.'); 
?></strong></p></div><?php
update_option('pjm_graph_title',$_POST['graph_title']);
update_option('pjm_graph_width',$_POST['graph_width']);
update_option('pjm_graph_height',$_POST['graph_height']);
update_option('pjm_graph_bgcolor',$_POST['graph_bgcol']);
update_option('pjm_graph_fgcolor',$_POST['graph_fgcol']);
update_option('pjm_graph_linecolor',$_POST['graph_linecol']);
update_option('pjm_graph_bglinecolor',$_POST['graph_bglinecol']);
update_option('pjm_graph_trendcolor',$_POST['graph_trendcol']);
update_option('pjm_graph_datefmt',$_POST['graph_datefmt']);
}
?>
<div class="wrap">
<form method="post">
<h2><?php _e('Simple Graph Options'); ?></h2>
<p><b>Compatibility check:</b><br />
PHP Version: <?php echo phpversion(); ?><br />
GD Loaded: <?php echo extension_loaded('gd') ? "Yes" : "No"; ?><br />
GD Version: <?php echo phpversion('gd') ? phpversion('gd') : "N/A"; ?><br />
Image format: <?php echo function_exists('imagecreatetruecolor') ? "True color" : "Palette"; ?>
<?php print(" ");
if (function_exists('imagepng')) { echo "PNG"; } 
else if (function_exists('imagegif')) { echo "GIF"; } 
else if (function_exists('imagejpeg')) { echo "JPG"; } else { echo "N/A"; } ?>
</p>
<fieldset class="options">
<legend><?php _e('Graphical layout'); ?></legend>
<table class="editform optiontable">
<tr>
<th scope="row"><?php _e('Widget title'); ?>:</th>
<td><input name="graph_title" type="text" id="graph_title" class="code" 
value="<?php echo get_option('pjm_graph_title'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Graph width'); ?>:</th>
<td><input name="graph_width" type="text" id="graph_width" class="code" 
value="<?php echo get_option('pjm_graph_width'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Graph Height'); ?>:</th>
<td><input name="graph_height" type="text" id="graph_height" class="code" 
value="<?php echo get_option('pjm_graph_height'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Background color'); ?>:</th>
<td><input name="graph_bgcol" type="text" id="graph_bgcol" class="code" 
value="<?php echo get_option('pjm_graph_bgcolor'); ?>" size="20" 
/></td></tr>
<th scope="row"><?php _e('Foreground color'); ?>:</th>
<td><input name="graph_fgcol" type="text" id="graph_fgcol" class="code" 
value="<?php echo get_option('pjm_graph_fgcolor'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Graph line color'); ?>:</th>
<td><input name="graph_linecol" type="text" id="graph_linecol" class="code" 
value="<?php echo get_option('pjm_graph_linecolor'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Background line color'); ?>:</th>
<td><input name="graph_bglinecol" type="text" id="graph_bglinecol" class="code" 
value="<?php echo get_option('pjm_graph_bglinecolor'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Trend line color'); ?>:</th>
<td><input name="graph_trendcol" type="text" id="graph_trendcol" class="code" 
value="<?php echo get_option('pjm_graph_trendcolor') ? get_option('pjm_graph_trendcolor') : get_option('pjm_graph_bglinecolor'); ?>" size="20" 
/></td></tr>
<tr>
<th scope="row"><?php _e('Date format'); ?>:</th>
<td><select name="graph_datefmt" id="graph_datefmt">
<?php
$defaultfmt = "y/m/d";
if (get_option('pjm_graph_datefmt'))
	$defaultfmt = get_option('pjm_graph_datefmt');
$formats = Array( "d.m.y", "d.m.Y", "y/m/d", "Y/m/d", "y-m-d", "Y-m-d", "d/M/y", "d/M/Y", "D/m/y", "D/M/y" );
foreach ($formats as $fmt) {
	$sel = "";
	if ($fmt==$defaultfmt) $sel = " selected=\"selected\"";
	print("<option value=\"$fmt\"$sel>".date($fmt)."</option>");
}
?>
</select></td></tr>
</table>
</fieldset>
<p class="submit">
<input type="submit" name="graph_update" value="<?php _e('Update options'); ?> &raquo;" />
</p>
</form>
</div>
<?php
}
?>
