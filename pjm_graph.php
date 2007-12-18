<?php
/*
Plugin Name: Simple Graph
Plugin URI: http://www.pasi.fi/simple-graph-wordpress-plugin/
Description: Administrator modules for simple graph tool. Requires Wordpress 2.0 or newer, and GD graphics library.
Author: Pasi Matilainen
Version: 1.0.5
Author URI: http://www.pasi.fi/
*/ 

define('PJM_GRAPH_PLUGIN_PATH', ABSPATH . '/wp-content/plugins/' .
	dirname(plugin_basename(__FILE__)));

define('PJM_GRAPH_PLUGIN_URL', get_bloginfo('wpurl') . '/wp-content/plugins/'
	. dirname(plugin_basename(__FILE__)));

$simple_graph_version		= "1.0.5";
$simple_graph_db_version	= "1.0";

function widget_pjm_graph_init() {
	if (!function_exists('register_sidebar_widget'))
		return;
	function widget_pjm_graph_widget($args, $number = 1) {
		global $wpdb;
		extract($args);
		$options = get_option('pjm_graph_options');
		if (!is_array($options[$number]))
			$options[$number] = Array('title' => '', 'text' => '', 'width' => 160, 'height' => 120,
				'bg_col' => 'FFFFFF', 'fg_col' => '000000', 'line_col' => '0000FF',
				'bg_line_col' => 'CCCCFF', 'trend_line_col' => '88FF88', 'target_line_col' => 'FF0000',
				'date_fmt' => 'y/m/d', 'show_text' => TRUE, 'show_title' => TRUE, 'show_trend' => FALSE,
				'show_target' => FALSE, 'show_hl_graph' => TRUE, 'user_id' => 1, 'table_id' => 1, 'gchart' => FALSE );
		if (!isset($options[$number]['gchart']))
			$options[$number]['gchart'] = FALSE;
		$title = $options[$number]['title'];
		if ($title=="") 
			$title = null;
		if (!$options[$number]['show_title'])
			$title = null;
		$tags = null;
		if (($options[$number]['show_title']&&strpos($options[$number]['title'],"%")!==FALSE)||
			($options[$number]['show_text'])&&strpos($options[$number]['text'],"%")!==FALSE)
			$tags = pjm_graph_get_tags($options[$number]['user_id'],$options[$number]['table_id']);
		if (is_array($tags)) {
			$tags['target'] = $options[$number]['target'];
			$tags['first_date'] = date($options[$number]['date_fmt'],$tags['first_date']);
			$tags['last_date'] = date($options[$number]['date_fmt'],$tags['last_date']);
		}
		?>
			<?php echo $before_widget; ?>
				<?php if ($title!=null) {
					echo $before_title
					. pjm_graph_tags($title,$tags)
					. $after_title; } ?>
					<p><?php /*pjm_graph($number);*/
					pjm_graph($number,$options[$number]['width'],$options[$number]['height'],$options[$number]['show_trend'],$options[$number]['show_target'],FALSE,FALSE,FALSE,$options[$number]['user_id'],$options[$number]['table_id'],FALSE,$options[$number]['gchart']); ?></p>
					<?php if ($options[$number]['show_text']) echo pjm_graph_tags($options[$number]['text'],$tags); ?>
			<?php echo $after_widget; ?>
		<?php
	}
	//register_sidebar_widget(array('Simple Graph','widgets'),'widget_pjm_graph_widget');

	function pjm_graph_get_tags($uid,$tid) {
		global $wpdb;
		$tags = array();
		$table = $wpdb->prefix . 'simple_graph';
		
		$sql = "SELECT MAX(stamp) AS highdate, MIN(stamp) AS lowdate, "
			 . "MAX(value) AS highvalue, MIN(value) AS lowvalue "
			 . "FROM $table WHERE user_id=$uid AND table_id=$tid;";
		if ( $valueset = $wpdb->get_results($sql) ) {
			foreach ($valueset as $values) {
				$tags['high'] = $values->highvalue;
				$tags['low'] = $values->lowvalue;
				$tags['last_date'] = $values->highdate;
				$tags['first_date'] = $values->lowdate;
			}
		}

		$sql = "SELECT value FROM $table WHERE stamp = {$tags['last_date']};";
		if ( $valueset = $wpdb->get_results($sql) ) {
			$tags['current'] = $valueset[0]->value;
		}

		$sql = "SELECT value FROM $table WHERE stamp = {$tags['first_date']};";
		if ( $valueset = $wpdb->get_results($sql) ) {
			$tags['start'] = $valueset[0]->value;
		}
				
		return $tags;
	}

	function pjm_graph_tags($string,$tag_values) {
		if (!is_array($tag_values))
			return $string;
		$string = str_replace("%CURRENT",$tag_values['current'],$string);
		$string = str_replace("%HIGH",$tag_values['high'],$string);
		$string = str_replace("%LOW",$tag_values['low'],$string);
		$string = str_replace("%START",$tag_values['start'],$string);
		$string = str_replace("%TARGET",$tag_values['target'],$string);
		$string = str_replace("%FIRST_DATE",$tag_values['first_date'],$string);
		$string = str_replace("%LAST_DATE",$tag_values['last_date'],$string);
		return $string;
	}
	
	function format_color($col) {
		$col = strip_tags(stripslashes($col));
		if ($col[0] == '#')
			$col = substr($col,1);
		return $col;
	}
	
	function widget_pjm_graph_control($number) {
		global $wpdb;
		$options = get_option('pjm_graph_options');
		if (!is_array($options[$number]))
			$options[$number] = Array('title' => '', 'text' => '', 'width' => 160, 'height' => 120,
				'bg_col' => 'FFFFFF', 'fg_col' => '000000', 'line_col' => '0000FF',
				'bg_line_col' => 'CCCCFF', 'trend_line_col' => '88FF88', 'target_line_col' => 'FF0000',
				'date_fmt' => 'y/m/d', 'show_text' => TRUE, 'show_title' => TRUE, 'show_trend' => FALSE,
				'show_target' => FALSE, 'show_hl_graph' => TRUE, 'user_id' => 1, 'table_id' => 1, 'gchart' => FALSE );
		if (isset($options[$number]['gchart']))
			$options[$number]['gchart'] = FALSE;
		$newoptions = $options;
		if ($_POST['pjm_graph_submit-'.$number]) {
			$newoptions[$number]['title']	= strip_tags(stripslashes($_POST['pjm_graph_title-'.$number]));
			$newoptions[$number]['text']	= stripslashes($_POST['pjm_graph_text-'.$number]);
			if ( !current_user_can('unfiltered_html') )
				$newoptions[$number]['text'] = stripslashes(wp_filter_post_kses($newoptions[$number]['text']));
			$newoptions[$number]['target']	= strip_tags(stripslashes($_POST['pjm_graph_target-'.$number]));
			$newoptions[$number]['width']	= strip_tags(stripslashes($_POST['pjm_graph_width-'.$number]));
			$newoptions[$number]['height']	= strip_tags(stripslashes($_POST['pjm_graph_height-'.$number]));
			$newoptions[$number]['bg_col']	= format_color($_POST['pjm_graph_bg_col-'.$number]);
			$newoptions[$number]['fg_col']	= format_color($_POST['pjm_graph_fg_col-'.$number]);
			$newoptions[$number]['line_col']	= format_color($_POST['pjm_graph_line_col-'.$number]);
			$newoptions[$number]['bg_line_col']	= format_color($_POST['pjm_graph_bg_line_col-'.$number]);
			$newoptions[$number]['trend_line_col']	= format_color($_POST['pjm_graph_trend_line_col-'.$number]);
			$newoptions[$number]['target_line_col']	= format_color($_POST['pjm_graph_target_line_col-'.$number]);
			$newoptions[$number]['date_fmt']	= stripslashes($_POST['pjm_graph_date_fmt-'.$number]);
			list ($newoptions[$number]['user_id'],$newoptions[$number]['table_id']) = explode(":",$_POST['pjm_graph_user_table_id-'.$number]);
			$newoptions[$number]['show_title']	= isset($_POST['pjm_graph_show_title-'.$number]) ? TRUE : FALSE;
			$newoptions[$number]['show_text']	= isset($_POST['pjm_graph_show_text-'.$number]) ? TRUE : FALSE;
			$newoptions[$number]['show_target']	= isset($_POST['pjm_graph_show_target-'.$number]) ? TRUE : FALSE;
			$newoptions[$number]['show_trend']	= isset($_POST['pjm_graph_show_trend-'.$number]) ? TRUE : FALSE;
			$newoptions[$number]['show_hl_graph'] = isset($_POST['pjm_graph_show_hl_graph-'.$number]) ? TRUE : FALSE;
			$newoptions[$number]['gchart'] = isset($_POST['pjm_graph_use_gchart-'.$number]) ? TRUE : FALSE;
		}
		if ($newoptions != $options) {
			$options = $newoptions;
			update_option('pjm_graph_options',$options);
		}
		$options[$number]['title'] = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
		$options[$number]['text'] = htmlspecialchars($options[$number]['text'], ENT_QUOTES);
		echo '<p style="text-align:right;">';
		echo '<label for="pjm_graph_user_table_id-'.$number.'">' . __('Graph owner and #:') . ' <select id="pjm_graph_user_table_id-'.$number.'" name="pjm_graph_user_table_id-'.$number.'">';
		// get all authors
		$author_sql = "SELECT * FROM {$wpdb->prefix}users;";
		$authors = $wpdb->get_results($author_sql);
		foreach ($authors as $author) {
			// get all tables for this user
			$table_sql = "SELECT DISTINCT(table_id) FROM {$wpdb->prefix}simple_graph WHERE user_id={$author->ID} ORDER BY table_id ASC;";
			$tables = $wpdb->get_results($table_sql);
			foreach ($tables as $table) {
				$sel = '';
				if ( $options[$number]['user_id'] == $author->ID && $options[$number]['table_id'] == $table->table_id )
					$sel = ' selected="selected"';
				echo '<option value="'.$author->ID.':'.$table->table_id.'"'.$sel.'>'.$author->display_name.' / '.$table->table_id.'</option>';
			}
		}
		echo '</select><br />';
		echo 'Tags available for title and text: %CURRENT, %HIGH, %LOW, %START, %TARGET, %FIRST_DATE, %LAST_DATE<br />';
		echo '<label for="pjm_graph_title-'.$number.'">' . __('Title:') .' <input style="width:200px;" id="pjm_graph_title-'.$number.'" name="pjm_graph_title-'.$number.'" type="text" value="' . $options[$number]['title'] . '" /></label><br />';
		echo '<label for="pjm_graph_text-'.$number.'">' . __('Text: ') . (current_user_can('unfiltered_html') ? __('(HTML OK)') : __('(Plain text)')) .' <textarea style="width:220px;height:100px" id="pjm_graph_text-'.$number.'" name="pjm_graph_text-'.$number.'">' . $options[$number]['text'] . '</textarea></label><br />';
		echo '<label for="pjm_graph_show_title-'.$number.'">' . __('Show title:') .' <input type="checkbox" id="pjm_graph_show_title-'.$number.'" name="pjm_graph_show_title-'.$number.'" ' . ($options[$number]['show_title'] ? 'checked="checked"' : '') . ' /></label><br />';
		echo '<label for="pjm_graph_show_text-'.$number.'">' . __('Show text:') .' <input type="checkbox" id="pjm_graph_show_text-'.$number.'" name="pjm_graph_show_text-'.$number.'" ' . ($options[$number]['show_text'] ? 'checked="checked"' : '') . ' /></label><br />';
		echo '<label for="pjm_graph_show_trend-'.$number.'">' . __('Show trend line:') .' <input type="checkbox" id="pjm_graph_show_trend-'.$number.'" name="pjm_graph_show_trend-'.$number.'" ' . ($options[$number]['show_trend'] ? 'checked="checked"' : '') . ' /></label><br />';
		echo '<label for="pjm_graph_show_target-'.$number.'">' . __('Show target line:') .' <input type="checkbox" id="pjm_graph_show_target-'.$number.'" name="pjm_graph_show_target-'.$number.'" ' . ($options[$number]['show_target'] ? 'checked="checked"' : '') . ' /></label><br />';
		echo '<label for="pjm_graph_show_hl_graph-'.$number.'">' . __('Show high/low in graph:') .' <input type="checkbox" id="pjm_graph_show_hl_graph-'.$number.'" name="pjm_graph_show_hl_graph-'.$number.'" ' . ($options[$number]['show_hl_graph'] ? 'checked="checked"' : '') . ' /></label><br />';
		echo '<label for="pjm_graph_use_gchart-'.$number.'">' . __('Use Google Chart API for Rendering:') .' <input type="checkbox" id="pjm_graph_use_gchart-'.$number.'" name="pjm_graph_use_gchart-'.$number.'" ' . ($options[$number]['gchart'] ? 'checked="checked"' : '') . ' /></label><br />';
		echo '<label for="pjm_graph_target-'.$number.'">' . __('Target:') .' <input style="width:80px;" id="pjm_graph_target-'.$number.'" name="pjm_graph_target-'.$number.'" type="text" value="' . $options[$number]['target'] . '" /></label><br />';
		echo '<label for="pjm_graph_width-'.$number.'">' . __('Width:') .' <input style="width:80px;" id="pjm_graph_width-'.$number.'" name="pjm_graph_width-'.$number.'" type="text" value="' . $options[$number]['width'] . '" /></label><br />';
		echo '<label for="pjm_graph_height-'.$number.'">' . __('Height:') .' <input style="width:80px;" id="pjm_graph_height-'.$number.'" name="pjm_graph_height-'.$number.'" type="text" value="' . $options[$number]['height'] . '" /></label><br />';
		echo '<label for="pjm_graph_bg_col-'.$number.'">' . __('Background color:') .' <input style="width:80px;" id="pjm_graph_bg_col-'.$number.'" name="pjm_graph_bg_col-'.$number.'" type="text" value="' . $options[$number]['bg_col'] . '" /></label><br />';
		echo '<label for="pjm_graph_fg_col-'.$number.'">' . __('Foreground color:') .' <input style="width:80px;" id="pjm_graph_fg_col-'.$number.'" name="pjm_graph_fg_col-'.$number.'" type="text" value="' . $options[$number]['fg_col'] . '" /></label><br />';
		echo '<label for="pjm_graph_line_col-'.$number.'">' . __('Line color:') .' <input style="width:80px;" id="pjm_graph_line_col-'.$number.'" name="pjm_graph_line_col-'.$number.'" type="text" value="' . $options[$number]['line_col'] . '" /></label><br />';
		echo '<label for="pjm_graph_bg_line_col-'.$number.'">' . __('Background line color:') .' <input style="width:80px;" id="pjm_graph_bg_line_col-'.$number.'" name="pjm_graph_bg_line_col-'.$number.'" type="text" value="' . $options[$number]['bg_line_col'] . '" /></label><br />';
		echo '<label for="pjm_graph_trend_line_col-'.$number.'">' . __('Trend line color:') .' <input style="width:80px;" id="pjm_graph_trend_line_col-'.$number.'" name="pjm_graph_trend_line_col-'.$number.'" type="text" value="' . $options[$number]['trend_line_col'] . '" /></label><br />';
		echo '<label for="pjm_graph_target_line_col-'.$number.'">' . __('Target line color:') .' <input style="width:80px;" id="pjm_graph_target_line_col-'.$number.'" name="pjm_graph_target_line_col-'.$number.'" type="text" value="' . $options[$number]['target_line_col'] . '" /></label><br />';
		echo '<label for="pjm_graph_date_fmt-'.$number.'">' . __('Date format:') . ' <select name="pjm_graph_date_fmt-'.$number.'" id="pjm_graph_date_fmt-'.$number.'">';
		$defaultfmt = $options[$number]['date_fmt'];
		$formats = Array( "d.m.y", "d.m.Y", "y/m/d", "Y/m/d", "y-m-d", "Y-m-d", "d/M/y", "d/M/Y", "D/m/y", "D/M/y" );
		foreach ($formats as $fmt) {
			$sel = "";
			if ($fmt==$defaultfmt) $sel = " selected=\"selected\"";
			print("<option value=\"$fmt\"$sel>".date($fmt)."</option>");
		}
		echo '</select>';
		echo '</p>';
		echo '<input type="hidden" id="pjm_graph_submit-'.$number.'" name="pjm_graph_submit-'.$number.'" value="1" />';
		?>
<p style="text-align:right;"><b>Compatibility check:</b><br />
PHP Version: <?php echo phpversion(); ?><br />
GD Loaded: <?php echo extension_loaded('gd') ? "Yes" : "No"; ?><br />
GD Version: <?php echo phpversion('gd') ? phpversion('gd') : "N/A"; ?><br />
Image format: <?php echo function_exists('imagecreatetruecolor') ? "True color" : "Palette"; ?>
<?php print(" ");
if (function_exists('imagepng')) { echo "PNG"; } 
else if (function_exists('imagegif')) { echo "GIF"; } 
else if (function_exists('imagejpeg')) { echo "JPG"; } else { echo "N/A"; } ?>
</p>
<?php
	}
	
	function widget_pjm_graph_setup() {
		$options = $newoptions = get_option('pjm_graph_options');
		if ( isset($_POST['pjm-graph-number-submit']) ) {
			$number = (int) $_POST['pjm-graph-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('pjm_graph_options', $options);
			widget_pjm_graph_register($options['number']);
		}
	}
	
	function widget_pjm_graph_page() {
		$options = $newoptions = get_option('pjm_graph_options');
	?>
		<div class="wrap">
			<form method="POST">
				<h2><?php _e('Simple Graph Widgets', 'widgets'); ?></h2>
				<p style="line-height: 30px;"><?php _e('How many Simple Graph widgets would you like?', 'widgets'); ?>
				<select id="pjm-graph-number" name="pjm-graph-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="pjm-graph-number-submit" id="pjm-graph-number-submit" value="<?php _e('Save'); ?>" /></span></p>
			</form>
		</div>
	<?php
	}

	function widget_pjm_graph_register() {
		$options = get_option('pjm_graph_options');
		$number = $options['number'];
		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;
		for ($i = 1; $i <= 9; $i++) {
			$name = array('Simple Graph %s', 'widgets', $i);
			register_sidebar_widget($name, $i <= $number ? 'widget_pjm_graph_widget' : /* unregister */ '', $i);
			register_widget_control($name, $i <= $number ? 'widget_pjm_graph_control' : /* unregister */ '', 400, 620, $i);
		}
		add_action('sidebar_admin_setup', 'widget_pjm_graph_setup');
		add_action('sidebar_admin_page', 'widget_pjm_graph_page');
	}
	
	widget_pjm_graph_register();
	//register_widget_control(array('Simple Graph','widgets'),'widget_pjm_graph_control',400,620);
	
	// add filter
	add_filter('the_content', 'simple_graph_filter');
	
	// def filter
	function simple_graph_filter($content = '') {
		while ( strpos(strtolower($content), '[[simple-graph') !== FALSE ) {
			$simplegraph = substr( $content, strpos(strtolower($content), '[[simple-graph') );
			$breakpoint = strpos( $simplegraph, "]]" ) + 2;
			$simplegraph = substr($simplegraph, 0, $breakpoint);
			$params = explode(" ",$simplegraph);
			$options = array ( 'n' => 1, 'x' => 0, 'y' => 0, 'trend' => false, 'target' => false,
				'ytd' => false, 'lm' => false, 'wkly' => false, 'uid' => false, 'gid' => false );
			foreach ($params as $param) {
				list ($name, $value) = explode("=",$param);
				$options[$name] = $value;
			}
			$img_tag = pjm_graph($options['n'],$options['x'],$options['y'],$options['trend'],$options['target'],
				$options['ytd'],$options['lm'],$options['wkly'],$options['uid'],$options['gid'],true);
			$content = str_replace($simplegraph, $img_tag, $content);
		}
		return $content;
	}

}
add_action('plugins_loaded','widget_pjm_graph_init');

function pjm_graph($number=1,$x=0,$y=0,$trend=FALSE,$target=FALSE,$ytd=FALSE,$lm=FALSE,$wkly=FALSE,$user_id=0,$table_id=0,$only_return_tag=FALSE,$use_gchart=FALSE) {
$options = get_option('pjm_graph_options');
if (!is_array($options[$number]))
	$options[$number] = Array('title' => '', 'text' => '', 'width' => 160, 'height' => 120,
		'bg_col' => 'FFFFFF', 'fg_col' => '000000', 'line_col' => '0000FF',
		'bg_line_col' => 'CCCCFF', 'trend_line_col' => '88FF88', 'target_line_col' => 'FF0000',
		'date_fmt' => 'y/m/d', 'show_text' => TRUE, 'show_title' => TRUE, 'show_trend' => FALSE,
		'show_target' => FALSE, 'show_hl_graph' => TRUE, 'user_id' => 1, 'table_id' => 1, 'gchart' => FALSE );
if (!isset($options[$number]['gchart']))
  $options[$number]['gchart'] = FALSE;
$width = $options[$number]['width'];
$height = $options[$number]['height'];
$uid = $options[$number]['user_id'];
$tid = $options[$number]['table_id'];
$gchart_enabled = $options[$number]['gchart'] & $use_gchart;
if ($x!=0) $width = $x;
if ($y!=0) $height = $y;
if ($user_id!=0) $uid = $user_id;
if ($table_id!=0) $tid = $table_id;
//$siteurl = get_option('siteurl');
//if ("/"==substr($siteurl,strlen($siteurl)-1)) 
//	$siteurl = substr($siteurl,0,strlen($siteurl)-1);
$img_tag = NULL;
if ($gchart_enabled) {
  $datatype = "t:";
  require_once('grapher/gchart.php');
  $img_tag = "<img src=\"http://chart.apis.google.com/chart?chs={$width}x{$height}&amp;chd={$data}&amp;cht=lc&amp;chxt=x,y&amp;chxl={$horizontalaxislabels}{$verticalaxislabels}&amp;";
  $img_tag .= "&amp;chco=$colors\" alt=\"[Graph by www.pasi.fi/simple-graph-wordpress-plugin/]\" />";
} else {
  $img_tag = '<img src="'.PJM_GRAPH_PLUGIN_URL.'/grapher/graph.php?n='.$number.'&amp;uid='.$uid.'&amp;tid='.$tid.'&amp;'; 
    if ($trend) $img_tag .= "t=1&amp;"; 
    if ($ytd) $img_tag .= "ytd=1&amp;"; 
    if ($lm) $img_tag .= "lm=1&amp;"; 
    if ($wkly) $img_tag .= "wkly=1&amp;"; 
    if ($target) $img_tag .= "l=1&amp;";
    if ($x!=0) $img_tag .= "w=$width&amp;h=$height"; 
  $img_tag .= '" width="'.$width.'" height="'.$height.'" alt="Graph by www.pasi.fi/simple-graph-wordpress-plugin/" />';
}
if (!$only_return_tag)
	echo $img_tag;
return $img_tag;
}

function pjm_graph_install() {
	global $wpdb;
	if (!current_user_can('activate_plugins')) return;
	$table_name = $wpdb->prefix . 'simple_graph';

	// if simple_graph table doesn't exist, create it
	if ( $wpdb->get_var("show tables like '$table_name'") != $table_name ) {
		$sql = "CREATE TABLE $table_name (
			id int PRIMARY KEY AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			table_id int NOT NULL,
			stamp int NOT NULL,
			value double NOT NULL)";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);

		// if pjm_graph table exists, i.e. we're upgrading from 0.9.8c or earlier version
		// copy values from that table to the new table, for current user's table one
		$old_table = $wpdb->prefix . 'pjm_graph';
		if ( $wpdb->get_var("show tables like '$old_table'") == $old_table ) {
			global $current_user;
			$user_id = $current_user->data->ID;
			$old_data_sql = "SELECT * FROM $old_table";
			$old_data = $wpdb->get_results($old_data_sql);
			if (!is_empty($old_data)) foreach ($old_data as $old_row) {
				$stamp = $old_row->stamp;
				$value = $old_row->value;
				$insert_sql = "INSERT INTO $table_name (user_id,table_id,stamp,value) values ($user_id,1,$stamp,$value);";
				$wpdb->query($insert_sql);
			}
		}
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
global $wpdb, $current_user;
$table_prefix = $wpdb->prefix;
//if (!current_user_can('edit_pages')) { echo "Insufficient role level. You need to be an Editor."; return; }
if (isset($_GET['pjm_graph_delete'])) { ?>
<div class="updated"><p><strong><?php _e('Data deleted.'); 
?></strong></p></div><?php
$item_id = $_GET['pjm_graph_delete'];
$sql = "DELETE FROM ".$table_prefix."simple_graph WHERE id=".$item_id." AND user_id=".$current_user->data->ID;
$wpdb->query($sql);
}
if (isset($_POST['pjm_graph_value'])) { ?>
<div class="updated"><p><strong><?php _e('Data added.');
?></strong></p></div><?php
// insert data here
$date = strtotime($_POST['pjm_graph_year']."-".$_POST['pjm_graph_month']."-".$_POST['pjm_graph_day']);
$value = $wpdb->escape($_POST['pjm_graph_value']);
$table_id = $wpdb->escape($_POST['pjm_graph_table_id']);
$sql = "INSERT INTO ".$table_prefix."simple_graph (user_id, table_id, stamp, value) values ({$current_user->data->ID},$table_id,$date,$value)";
$wpdb->query($sql);
} else if (isset($_POST['batch_insert'])) { ?>
<div class="updated"><p><strong><?php _e('Batch insert results'); ?></strong></p>
<p><?php
$table_id = $wpdb->escape($_POST['pjm_graph_table_id']);
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
			$sql = "INSERT INTO ".$table_prefix."simple_graph (user_id, table_id, stamp, value) values ({$current_user->data->ID},$table_id,$date,$value)";
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
<th scope="row"><?php _e('Graph#'); ?>:</th>
<td><select name="pjm_graph_table_id"><?php
$tables = $wpdb->get_results("SELECT DISTINCT(table_id) FROM {$wpdb->prefix}simple_graph WHERE user_id={$current_user->data->ID} ORDER BY table_id ASC;");
$high_table = 0;
foreach ($tables as $table) {
	$sel = '';
	if (isset($_POST['pjm_graph_table_id']))
		if ($table->table_id == $_POST['pjm_graph_table_id'])
			$sel = ' selected="selected"';
	echo '<option value="'.$table->table_id.'"'.$sel.'>'.$table->table_id.'</option>';
	if ($table->table_id>$high_table)
		$high_table = $table->table_id;
}
$high_table++;
echo '<option value="'.$high_table.'">'.$high_table.' (Create new)</option>';
?></select>
</td>
</tr>
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
<th scope="row"><?php _e('Graph#'); ?>:</th>
<td><select name="pjm_graph_table_id"><?php
$tables = $wpdb->get_results("SELECT DISTINCT(table_id) FROM {$wpdb->prefix}simple_graph WHERE user_id={$current_user->data->ID} ORDER BY table_id ASC;");
$high_table = 0;
foreach ($tables as $table) {
	$sel = '';
	if (isset($_POST['pjm_graph_table_id']))
		if ($table->table_id == $_POST['pjm_graph_table_id'])
			$sel = ' selected="selected"';
	echo '<option value="'.$table->table_id.'"'.$sel.'>'.$table->table_id.'</option>';
	if ($table->table_id>$high_table)
		$high_table = $table->table_id;
}
$high_table++;
echo '<option value="'.$high_table.'">'.$high_table.' (Create new)</option>';
?></select>
</td>
</tr>
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
<tr><th>Graph#</th><th>ID</th><th><?php _e('Date'); ?></th><th><?php _e('Value'); ?></th></tr>
<?php
$offset = 0; $row_count = 50;
if (isset($_REQUEST['offset']))
	$offset = mysql_real_escape_string($_REQUEST['offset']);
if (isset($_REQUEST['rows']))
	$row_count = mysql_real_escape_string($_REQUEST['rows']);
$sql = "SELECT * FROM ".$table_prefix."simple_graph WHERE user_id={$current_user->data->ID} ORDER BY table_id DESC, id DESC LIMIT $offset,$row_count";
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
<td><?php echo $values->table_id; ?></td>
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

/*function pjm_settingsPanel() {
//	if (function_exists('add_options_page')) {
//		add_options_page('Simple Graph','Simple Graph',10,basename(__FILE__),'pjm_show_settings_panel');
//	}
	add_submenu_page('plugins.php','Simple Graph Configuration','Simple Graph Configuration',10,basename(__FILE__),'pjm_show_settings_panel');
}

add_action('admin_menu','pjm_settingsPanel');*/

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
