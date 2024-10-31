<?php 
/*
Plugin Name: ProgressFly
Plugin URI: http://www.deborahmcdonnell.com/
Description: A plugin which stores work-in-progress data in the database, and displays a graphical progress-meter 
Version: 0.62
Author: damselfly
Author URI: http://www.deborahmcdonnell.com/
*/

// ProgressFly Options
$progressfly_version = "0.62";

// Install or upgrade the program
if(function_exists('register_activation_hook')) {
	register_activation_hook(__FILE__, 'progressfly_install');
} else {
	add_action('activate_progressfly.php', 'progressfly_install');
}

function progressfly_install () 
{
    global $wpdb, $progressfly_version;
    
    $table_name = $wpdb->prefix . "progressfly";
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
    {  
        // Create a brand new install
        $sql = "CREATE TABLE " . $table_name . " (
        wipid INT(11) NOT NULL AUTO_INCREMENT,
        title TEXT NOT NULL,
        titlelink VARCHAR(255) NOT NULL,
        target VARCHAR(11) NOT NULL,
        progress VARCHAR(11) NOT NULL,
        units VARCHAR(255) NOT NULL,
        pfcategory VARCHAR(255) NOT NULL,
        h3style VARCHAR(255) NOT NULL,
        meterwidth INT(5) NOT NULL,
        meterheight INT(5) NOT NULL,
        intmargin INT(5) NOT NULL,
        bgcolor VARCHAR(6) NOT NULL,
        targstyle VARCHAR(255) NOT NULL,
        pcolor VARCHAR(6) NOT NULL,
        progstyle VARCHAR(255) NOT NULL,
        bordercolor VARCHAR(6) NOT NULL,
        borderwidth INT(5) NOT NULL,
        borderstyle VARCHAR(6) NOT NULL,
        textstyle VARCHAR(255) NOT NULL,
        intextstyle VARCHAR(255) NOT NULL,
        complete ENUM('yes','no') NOT NULL,
        visible ENUM('yes','no') NOT NULL,
        PRIMARY KEY  (wipid)
        );"; 
        
        require_once(ABSPATH . 'wp-admin/upgrade.php');
        dbDelta($sql);
        
        update_option("progressfly_version", $progressfly_version);
        
        $progressfly_defaults = array ('bgcolor' => 'FFFFFF',
        'pcolor' => '000000',
        'bordercolor' => '000000',
        'borderwidth' => '1',
        'borderstyle' => 'solid',
        'meterheight' => '15',
        'meterwidth' => '100',
        'intmargin' => '1',
        'precision' => '0',
        'targstyle' => 'margin: 0; padding: 0;',
        'progstyle' => 'padding:0;',
        'textstyle' => 'margin: 5px 0px 15px 0px; padding: 0; color: #333333; font-size: 0.9em;',
        'intextstyle' => 'font-weight: bold; color: #333333; font-size: 0.85em; float: left; padding-left: 45%; line-height: 1em;',
        'h3style' => 'margin: 15px 0px 5px 0px; padding: 0; border: none; font-size: 1em;'
        );
        
        update_option("progressfly_defaults", $progressfly_defaults);
        
    }
    
    // Upgrade from a previous version
    $installed_version = get_option("progressfly_version");
    $checkdefaults = get_option("progressfly_defaults");
    $addprecision = $checkdefaults['precision'];
    
    if( $installed_version != $progressfly_version ) 
    {
    
        $sql = "CREATE TABLE " . $table_name . " (
        wipid INT(11) NOT NULL AUTO_INCREMENT,
        title TEXT NOT NULL,
        titlelink VARCHAR(255) NOT NULL,
        target VARCHAR(11) NOT NULL,
        progress VARCHAR(11) NOT NULL,
        units VARCHAR(255) NOT NULL,
        pfcategory VARCHAR(255) NOT NULL,
        h3style VARCHAR(255) NOT NULL,
        meterwidth INT(5) NOT NULL,
        meterheight INT(5) NOT NULL,
        intmargin INT(5) NOT NULL,
        bgcolor VARCHAR(6) NOT NULL,
        targstyle VARCHAR(255) NOT NULL,
        pcolor VARCHAR(6) NOT NULL,
        progstyle VARCHAR(255) NOT NULL,
        bordercolor VARCHAR(6) NOT NULL,
        borderwidth INT(5) NOT NULL,
        borderstyle VARCHAR(6) NOT NULL,
        textstyle VARCHAR(255) NOT NULL,
        intextstyle VARCHAR(255) NOT NULL,
        complete ENUM('yes','no') NOT NULL,
        visible ENUM('yes','no') NOT NULL,
        PRIMARY KEY  (wipid)
        );"; 
        
        require_once(ABSPATH . 'wp-admin/upgrade.php');
        dbDelta($sql);
        
        update_option( "progressfly_version", $progressfly_version ); 
    }
    if ( empty($checkdefaults) ) 
    {
        $progressfly_defaults = array ('bgcolor' => 'FFFFFF',
        'pcolor' => '000000',
        'bordercolor' => '000000',
        'borderwidth' => '1',
        'borderstyle' => 'solid',
        'meterheight' => '15',
        'meterwidth' => '100',
        'intmargin' => '1',
        'precision' => '0',
        'targstyle' => 'margin: 0; padding: 0;',
        'progstyle' => 'padding:0;',
        'textstyle' => 'margin: 5px 0px 15px 0px; padding: 0; color: #333333; font-size: 0.9em;',
        'intextstyle' => 'font-weight: bold; color: #333333; font-size: 0.85em; float: left; padding-left: 45%; line-height: 1em;',
        'h3style' => 'margin: 15px 0px 5px 0px; padding: 0; border: none; font-size: 1em;'
        );
        update_option("progressfly_defaults", $progressfly_defaults);
    }
    if ( !empty($checkdefaults) && empty($addprecision) ) 
    {
        $checkdefaults['precision'] = 0;
				update_option("progressfly_defaults", $checkdefaults);
		}
		
}

// Add the management pages to the administration panel; sink function for 'admin_menu' hook
function progressfly_admin_menu()
{
	// Add the Management submenu
	add_management_page('ProgressFly', 'Progress Meters', 8, basename(__FILE__), 'progressfly_manage');
	
	//Add the Options submenu
	add_options_page('ProgressFly', 'Progress Meters', 8, basename(__FILE__), 'progressfly_options');
}

// Handles the ProgressFly management page
function progressfly_manage()
{
	global $wpdb;

	$updateaction = !empty($_REQUEST['updateaction']) ? $_REQUEST['updateaction'] : '';
	$wipid = !empty($_REQUEST['wipid']) ? $_REQUEST['wipid'] : '';
	
	if (isset($_REQUEST['action']) ):
		if ($_REQUEST['action'] == 'delete_project') 
		{
			$wipid = intval($_GET['wipid']);
			if (empty($wipid))
			{
				?><div class="error"><p><strong>Failure:</strong> No Project-ID given. I guess I deleted nothing successfully.</p></div><?php
			}
			else
			{
				$wpdb->query("DELETE FROM " . $wpdb->prefix . "progressfly WHERE wipid = '" . $wipid . "'");
				$sql = "SELECT wipid FROM " . $wpdb->prefix . "progressfly WHERE wipid = '" . $wipid . "'";
				$check = $wpdb->get_results($sql);
				if ( empty($check) || empty($check[0]->wipid) )
				{
					?><div class="updated"><p>Project <?php echo $wipid; ?> deleted successfully.</p></div><?php
				}
				else
				{
					?><div class="error"><p><strong>Failure:</strong> Ninjas proved my kung-fu to be too weak to delete that entry.</p></div><?php
				}
			}
		} // end delete_project block
	endif;
	
	if ( $updateaction == 'update_project' )
	{
		$title = !empty($_REQUEST['wip_title']) ? $_REQUEST['wip_title'] : '';
		$titlelink = !empty($_REQUEST['wip_titlelink']) ? $_REQUEST['wip_titlelink'] : '';
		$target = !empty($_REQUEST['wip_target']) ? $_REQUEST['wip_target'] : '';
		$progress = !empty($_REQUEST['wip_progress']) ? $_REQUEST['wip_progress'] : '';
		$units = !empty($_REQUEST['wip_units']) ? $_REQUEST['wip_units'] : '';
		$pfcategory = !empty($_REQUEST['wip_category']) ? $_REQUEST['wip_category'] : '';
		$h3style = !empty($_REQUEST['wip_h3style']) ? $_REQUEST['wip_h3style'] : '';
		$meterwidth = !empty($_REQUEST['wip_meterwidth']) ? $_REQUEST['wip_meterwidth'] : '';
		$meterheight = !empty($_REQUEST['wip_meterheight']) ? $_REQUEST['wip_meterheight'] : '';
		$intmargin = !empty($_REQUEST['wip_intmargin']) ? $_REQUEST['wip_intmargin'] : '';
		$bgcolor = !empty($_REQUEST['wip_bgcolor']) ? $_REQUEST['wip_bgcolor'] : '';
		$targstyle = !empty($_REQUEST['wip_targstyle']) ? $_REQUEST['wip_targstyle'] : '';
		$pcolor = !empty($_REQUEST['wip_pcolor']) ? $_REQUEST['wip_pcolor'] : '';
		$progstyle = !empty($_REQUEST['wip_progstyle']) ? $_REQUEST['wip_progstyle'] : '';
		$bordercolor = !empty($_REQUEST['wip_bordercolor']) ? $_REQUEST['wip_bordercolor'] : '';
		$borderwidth = !empty($_REQUEST['wip_borderwidth']) ? $_REQUEST['wip_borderwidth'] : '';
		$borderstyle = !empty($_REQUEST['wip_borderstyle']) ? $_REQUEST['wip_borderstyle'] : '';
		$textstyle = !empty($_REQUEST['wip_textstyle']) ? $_REQUEST['wip_textstyle'] : '';
		$intextstyle = !empty($_REQUEST['wip_intextstyle']) ? $_REQUEST['wip_intextstyle'] : '';
		$complete =!empty($_REQUEST['wip_complete']) ? $_REQUEST['wip_complete'] : '';
		$visible = !empty($_REQUEST['wip_visible']) ? $_REQUEST['wip_visible'] : '';
		
		if ( empty($wipid) )
		{
			?><div class="error"><p><strong>Failure:</strong> No project-id given. Can't save nothing. Giving up...</p></div><?php
		}
		else
		{
			$sql = "UPDATE " . $wpdb->prefix . "progressfly SET title = '" . $title . "', titlelink = '" . $titlelink . "', target = '" . $target . "', progress = '" . $progress . "', units = '" . $units . "', pfcategory = '" . $pfcategory . "', h3style = '" . $h3style . "', meterwidth = '" . $meterwidth . "', meterheight = '". $meterheight . "', intmargin = '" . $intmargin . "', bgcolor = '" . $bgcolor . "', targstyle = '" . $targstyle . "', pcolor = '" . $pcolor . "', progstyle = '" . $progstyle . "', bordercolor = '" . $bordercolor . "', borderwidth = '" . $borderwidth . "', borderstyle = '". $borderstyle . "', textstyle = '" . $textstyle . "', intextstyle = '" . $intextstyle . "', complete = '" . $complete . "', visible = '" . $visible . "' WHERE wipid = '" . $wipid . "'";
			$wpdb->get_results($sql);
			$sql = "SELECT wipid FROM " . $wpdb->prefix . "progressfly WHERE title = '" . $title . "' and target = '" . $target . "' and progress = '" . $progress . "' and units = '" . $units . "' and complete = '" . $complete . "' and visible = '" . $visible . "' LIMIT 1";
			$check = $wpdb->get_results($sql);
			if ( empty($check) || empty($check[0]->wipid) )
			{
				?><div class="error"><p><strong>Failure:</strong> The Evil Monkey Overlord wouldn't let me update your entry. Try again?</p></div><?php
			}
			else
			{
				?><div class="updated"><p>Project <?php echo $wipid; ?> updated successfully.</p></div><?php
			}
		}
	} // end update_project block
	elseif ( $updateaction == 'add_project' )
	{
		$title = !empty($_REQUEST['wip_title']) ? $_REQUEST['wip_title'] : '';
		$titlelink = !empty($_REQUEST['wip_titlelink']) ? $_REQUEST['wip_titlelink'] : '';
		$target = !empty($_REQUEST['wip_target']) ? $_REQUEST['wip_target'] : '';
		$progress = !empty($_REQUEST['wip_progress']) ? $_REQUEST['wip_progress'] : '';
		$units = !empty($_REQUEST['wip_units']) ? $_REQUEST['wip_units'] : '';
		$pfcategory = !empty($_REQUEST['wip_category']) ? $_REQUEST['wip_category'] : '';
		$h3style = !empty($_REQUEST['wip_h3style']) ? $_REQUEST['wip_h3style'] : '';
		$meterwidth = !empty($_REQUEST['wip_meterwidth']) ? $_REQUEST['wip_meterwidth'] : '';
		$meterheight = !empty($_REQUEST['wip_meterheight']) ? $_REQUEST['wip_meterheight'] : '';
		$intmargin = !empty($_REQUEST['wip_intmargin']) ? $_REQUEST['wip_intmargin'] : '';
		$bgcolor = !empty($_REQUEST['wip_bgcolor']) ? $_REQUEST['wip_bgcolor'] : '';
		$targstyle = !empty($_REQUEST['wip_targstyle']) ? $_REQUEST['wip_targstyle'] : '';
		$pcolor = !empty($_REQUEST['wip_pcolor']) ? $_REQUEST['wip_pcolor'] : '';
		$progstyle = !empty($_REQUEST['wip_progstyle']) ? $_REQUEST['wip_progstyle'] : '';
		$bordercolor = !empty($_REQUEST['wip_bordercolor']) ? $_REQUEST['wip_bordercolor'] : '';
		$borderwidth = !empty($_REQUEST['wip_borderwidth']) ? $_REQUEST['wip_borderwidth'] : '';
		$borderstyle = !empty($_REQUEST['wip_borderstyle']) ? $_REQUEST['wip_borderstyle'] : '';
		$textstyle = !empty($_REQUEST['wip_textstyle']) ? $_REQUEST['wip_textstyle'] : '';
		$intextstyle = !empty($_REQUEST['wip_intextstyle']) ? $_REQUEST['wip_intextstyle'] : '';
		$complete =!empty($_REQUEST['wip_complete']) ? $_REQUEST['wip_complete'] : '';
		$visible = !empty($_REQUEST['wip_visible']) ? $_REQUEST['wip_visible'] : '';
		
		$sql = "INSERT INTO " . $wpdb->prefix . "progressfly SET title = '" . $title . "', titlelink = '" . $titlelink . "', target = '" . $target . "', progress = '" . $progress . "', units = '" . $units . "', pfcategory = '" . $pfcategory . "', h3style = '" . $h3style . "', meterwidth = '" . $meterwidth . "', meterheight = '". $meterheight . "', intmargin = '" . $intmargin . "', bgcolor = '" . $bgcolor . "', targstyle = '" . $targstyle . "', pcolor = '" . $pcolor . "', progstyle = '" . $progstyle . "', bordercolor = '" . $bordercolor . "', borderwidth = '" . $borderwidth . "', borderstyle = '" . $borderstyle . "', textstyle = '" . $textstyle . "', intextstyle = '" . $intextstyle . "', complete = '" . $complete . "', visible = '" . $visible . "'";
		$wpdb->get_results($sql);
		$sql = "SELECT wipid FROM " . $wpdb->prefix . "progressfly WHERE title = '" . $title . "' and target = '" . $target . "' and progress = '" . $progress . "' and units = '" . $units . "' and complete = '" . $complete . "' and visible = '" . $visible . "'";
		$check = $wpdb->get_results($sql);
		if ( empty($check) || empty($check[0]->wipid) )
		{
			?><div class="error"><p><strong>Failure:</strong> Holy crap you destroyed the internet! That, or something else went wrong when I tried to insert the entry. Try again? </p></div><?php
		}
		else
		{
			?><div class="updated"><p>Slow down &#8212; you'll give yourself a hernia! By which I mean to say, project <?php echo $check[0]->wipid;?> added successfully.</p></div><?php
		}
	} // end add_project block
	?>

	<div class=wrap>
	<?php
	if ( $_REQUEST['action'] == 'edit_project' )
	{
		?>
		<h2><?php _e('Edit Project'); ?></h2>
		<?php
		if ( empty($wipid) )
		{
			echo "<div class=\"error\"><p>I didn't get an entry identifier from the query string. Giving up...</p></div>";
		}
		else
		{
			progressfly_editform('update_project', $wipid);
		}	
	}
	else
	{
		?>

		<a name="addprogress"></a>
		<h2><?php _e('Add Project'); ?></h2>
		<div class="wrap" style="font-weight: bold; background-color: #FFFFCC; border: 1px solid #2580B2;"">
			<a href="#projectslist">View/Edit Projects List</a>  |  <a href="options-general.php?page=progressfly.php">ProgressFly Default Options</a>
		</div>
		<?php progressfly_editform(); ?>
	
		<div style="padding: 10px; clear: both;"></div>
		<a name="projectslist"></a>
		<h2><?php _e('Manage Projects'); ?></h2>
		<div class="wrap" style="font-weight: bold; background-color: #FFFFCC; border: 1px solid #2580B2;"">
			<a href="#addprogress">Add New Progress Meter</a>
		</div>
		<?php
			progressfly_displaylist();

	}
	?>
	</div><?php
}



// Displays the list of projects
function progressfly_displaylist() 
{
	global $wpdb;
	
	$projects = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "progressfly ORDER BY Complete DESC, wipid DESC");
	
	if ( !empty($projects) )
	{
		?>
		<table width="100%" cellpadding="3" cellspacing="3">
			<tr>
				<th scope="col"><?php _e('ID') ?></th>
				<th scope="col"><?php _e('Title') ?></th>
				<th scope="col"><?php _e('Link') ?></th>
				<th scope="col"><?php _e('Target') ?></th>
				<th scope="col"><?php _e('Progress') ?></th>
				<th scope="col"><?php _e('Units') ?></th>
				<th scope="col"><?php _e('Complete') ?></th>
				<th scope="col"><?php _e('Visible') ?></th>
				<th scope="col"><?php _e('Preview') ?></th>
				<th scope="col"><?php _e('Category') ?></th>
				<th scope="col"><?php _e('Edit') ?></th>
				<th scope="col"><?php _e('Delete') ?></th>
			</tr>
		<?php
		$class = '';
		foreach ( $projects as $project )
		{
			$class = ($class == 'alternate') ? '' : 'alternate';
			?>
			<tr class="<?php echo $class; ?>">
				<th scope="row"><?php echo $project->wipid; ?></th>
				<td><?php echo $project->title ?></td>
				<td><?php echo $project->titlelink!='' ? 'Y' : 'N'; ?></td>
				<td><?php echo $project->target; ?></td>
				<td><?php echo $project->progress; ?></td>
				<td><?php echo $project->units; ?></td>
				<td><?php echo $project->complete=='yes' ? 'Y' : 'N'; ?></td>
				<td><?php echo $project->visible=='yes' ? 'Y' : 'N'; ?></td>
				<td><?php pf_specific($project->wipid, "preview") ?></td>
				<td><?php echo $project->pfcategory; ?></td>
				<td><a href="tools.php?page=<?php echo basename(__FILE__)?>&amp;action=edit_project&amp;wipid=<?php echo $project->wipid;?>" class="edit"><?php echo __('Edit'); ?></a></td>
				<td><a href="tools.php?page=<?php echo basename(__FILE__)?>&amp;action=delete_project&amp;wipid=<?php echo $project->wipid;?>" class="delete" onclick="return confirm('Are you sure you want to delete this entry?')"><?php echo __('Delete'); ?></a></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}
	else
	{
		?>
		<p><?php _e("You haven't entered any projects yet.") ?></p>
		<?php	
	}
}

// Displays the add/edit form
function progressfly_editform($mode='add_project', $wipid=false)
{
	global $wpdb;
	$data = false;
	$current_defaults = get_option('progressfly_defaults');
	
	if ( $wipid !== false )
	{
		// this next line makes me about 200 times cooler than you.
		if ( intval($wipid) != $wipid )
		{
			echo "<div class=\"error\"><p>Bad Monkey! No banana!</p></div>";
			return;
		}
		else
		{
			$data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "progressfly WHERE wipid = '" . $wipid . "' LIMIT 1");
			if ( empty($data) )
			{
				echo "<div class=\"error\"><p>I couldn't find a project linked up with that identifier. Giving up...</p></div>";
				return;
			}
			$data = $data[0];
		}	
	}
	
	?>
	<form name="projectform" id="projectform" class="wrap" method="post" action="tools.php?page=<?php echo basename(__FILE__); ?>">
		<input type="hidden" name="updateaction" value="<?php echo $mode?>">
		<input type="hidden" name="wipid" value="<?php echo $wipid?>">
	
		<div id="item_manager">
			<div style="float: left; width: 98%; clear: both;" class="top">

				<div style="float: right; width: 150px; border: 1px solid #ccc; padding: 10px;" class="top">

				<fieldset class="small"><legend><?php _e('Complete?'); ?></legend>
					<input type="radio" name="wip_complete" class="input" value="yes" <?php if ( !empty($data) && $data->complete=='yes' ) echo "checked" ?>/> Yes <input type="radio" name="wip_complete" class="input" value="no" <?php if ( empty($data) || $data->complete=='no' ) echo "checked" ?>/> No
				</fieldset>
					<br />

				<fieldset class="small"><legend><?php _e('Visible?'); ?></legend>
					<input type="radio" name="wip_visible" class="input" value="yes" <?php if ( empty($data) || $data->visible=='yes' ) echo "checked" ?>/> Yes <input type="radio" name="wip_visible" class="input" value="no" <?php if ( !empty($data) && $data->visible=='no' ) echo "checked" ?>/> No
				</fieldset>
				<br />

				<input type="submit" name="save" class="button bold" value="Save &raquo;" />
				
				</div><!-- close the right float -->

				<!-- List URL -->
				
				<div style="padding: 10px;">
				<?php _e('<h4>Meta</h4>'); ?>
				<fieldset class="small"><legend><?php _e('Title'); ?></legend>
					<input type="text" name="wip_title" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->title); ?>" />
				</fieldset>
				
				<fieldset class="small"><legend><?php _e('Link'); ?></legend>
					<input type="text" name="wip_titlelink" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->titlelink); ?>" />
				</fieldset>
				
				<fieldset class="small"><legend><?php _e('Progress / Target (Units)'); ?></legend><input type="text" name="wip_progress" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->progress); ?>" /><?php _e(' / '); ?><input type="text" name="wip_target" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->target); ?>" /><?php _e(' ( '); ?><input type="text" name="wip_units" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->units); ?>" /><?php _e(' ) '); ?>
				</fieldset>

				<fieldset class="small"><legend><?php _e('Category'); ?></legend>
					<input type="text" name="wip_category" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->pfcategory); ?>" />
				</fieldset>

				</div>
				
				<div style="padding: 10px;">
				<table width="100%" border=0 cellspacing=3 cellpadding=3>
					<tr valign="top">
						<td colspan=2><?php _e('<h4>The Colors:</h4>'); ?></td>
						<td width="10%">&nbsp;</td>
						<td colspan=2><?php _e('<h4>The Numbers:</h4>'); ?></td>
					</tr>
          <tr valign="top">
	          <td align="left" width="15%"><?php _e(' Background '); ?></td>
						<td align="left" width="15%">#<input type="text" name="wip_bgcolor" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->bgcolor); } else { echo $current_defaults['bgcolor']; }  ?>" /></td>
						<td width="10%">&nbsp;</td>
	          <td align="left" width="15%"><?php _e(' Border Style '); ?></td>
						<td align="left" width="15%"><input type="text" name="wip_borderstyle" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->borderstyle); } else { echo $current_defaults['borderstyle']; }  ?>" /></td>
          </tr>
          <tr valign="top">
	          <td align="left" width="15%"><?php _e(' Progress '); ?></td>
						<td align="left" width="15%">#<input type="text" name="wip_pcolor" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->pcolor); } else { echo $current_defaults['pcolor']; }  ?>" /></td>
						<td width="10%">&nbsp;</td>
  					<td align="left" width="15%"><?php _e(' Border Width '); ?></td>
  					<td align="left" width="15%"><input type="text" name="wip_borderwidth" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->borderwidth); } else { echo $current_defaults['borderwidth']; }  ?>" /><?php _e(' px'); ?></td>
          </tr>
          <tr valign="top">
	          <td align="left" width="15%"><?php _e(' Border '); ?></td>
						<td align="left" width="15%">#<input type="text" name="wip_bordercolor" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->bordercolor); } else { echo $current_defaults['bordercolor']; }  ?>" /></td>
						<td width="10%">&nbsp;</td>
  					<td align="left" width="15%"><?php _e(' Meter Width '); ?></td>
  					<td align="left" width="15%"><input type="text" name="wip_meterwidth" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->meterwidth); } else { echo $current_defaults['meterwidth']; }  ?>" /><?php _e(' px'); ?></td>
          </tr>
					<tr valign="top">
						<td colspan=3>&nbsp;</td>
  					<td align="left" width="15%"><?php _e(' Meter Height '); ?></td>
  					<td align="left" width="15%"><input type="text" name="wip_meterheight" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->meterheight); } else { echo $current_defaults['meterheight']; }  ?>" /><?php _e(' px'); ?></td>
					</tr>
					<tr valign="top">
						<td colspan=3>&nbsp;</td>
  					<td align="left" width="15%"><?php _e(' Internal Margin '); ?></td>
  					<td align="left" width="15%"><input type="text" name="wip_intmargin" class="input" size=6 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->intmargin); } else { echo $current_defaults['intmargin']; }  ?>" /><?php _e(' px'); ?></td>
					</tr>
        </table>
				
				</div>
				
				<div style="padding: 10px;">
				<?php _e('<h4>The Freestyle CSS:</h4>'); ?>
				
				<table width="100%" border=0 cellspacing=3 cellpadding=3>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Target / Background'); ?></td>
						<td align="left"><input type="text" name="wip_targstyle" class="input" size=45 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->targstyle); } else { echo $current_defaults['targstyle']; }  ?>" /></td>
						<td align="left" width="30%"><?php _e('Note: from v0.50, height and width should be specified in the Meter Height and Meter Width variables in <em>The Numbers</em>, above. Including them here may break the display of your meters.)'); ?></td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Progress Bar'); ?></td>
						<td align="left"><input type="text" name="wip_progstyle" class="input" size=45 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->progstyle); } else { echo $current_defaults['progstyle']; }  ?>" /></td>
						<td align="left" width="30%"><?php _e('Note: from v0.50, do not specify height or margin here. The height is now automatically calculated, and the margin should be specified in the Internal Margin variable in <em>The Numbers</em>, above.'); ?></td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Title Text: '); ?></td>
						<td align="left"><input type="text" name="wip_h3style" class="input" size=45 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->h3style); } else { echo $current_defaults['h3style']; }  ?>" /></td>
						<td align="left" width="30%">&nbsp;</td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Progress Text: '); ?></td>
						<td align="left"><input type="text" name="wip_textstyle" class="input" size=45 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->textstyle); } else { echo $current_defaults['textstyle']; }  ?>" /></td>
						<td align="left" width="30%">&nbsp;</td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Internal Progress Text: '); ?></td>
						<td align="left"><input type="text" name="wip_intextstyle" class="input" size=45 value="<?php if ( !empty($data) ) { echo htmlspecialchars($data->intextstyle); } else { echo $current_defaults['intextstyle']; }  ?>" /></td>
						<td align="left" width="30%"><?php _e('To control precisely where your % figure appears inside the progress meter, include one of the following expressions: <br />- "float: right;" &#8212; to right-align the text<br />- "float: left;" &#8212; to left-align the text<br />- "float: left; padding-left: 45%;" &#8212; to (approximately) centre the text. Play with the padding percentage until it looks right to you. I find 45% gives me good results in Firefox, and 55% gives good results in IE.'); ?></td>
					</tr>
				</table>

				</div>
				
			</div>
			<div style="clear:both; height:1px;">&nbsp;</div>
		</div>
	</form>
	<?php
}


// Handles the ProgressFly options page
function progressfly_options()
{
	global $wpdb;
	
	if ($_REQUEST['action'] == "save_options" ) { 
		
		$newdefaults['bgcolor'] = !empty($_REQUEST['pfdef_bgcolor']) ? $_REQUEST['pfdef_bgcolor'] : 'FFFFFF';
		$newdefaults['pcolor'] = !empty($_REQUEST['pfdef_pcolor']) ? $_REQUEST['pfdef_pcolor'] : '000000';
		$newdefaults['bordercolor'] = !empty($_REQUEST['pfdef_bordercolor']) ? $_REQUEST['pfdef_bordercolor'] : '000000';
		$newdefaults['borderwidth'] = !empty($_REQUEST['pfdef_borderwidth']) ? $_REQUEST['pfdef_borderwidth'] : '1';
		$newdefaults['borderstyle'] = !empty($_REQUEST['pfdef_borderstyle']) ? $_REQUEST['pfdef_borderstyle'] : 'solid';
		$newdefaults['meterheight'] = !empty($_REQUEST['pfdef_meterheight']) ? $_REQUEST['pfdef_meterheight'] : '15';
		$newdefaults['meterwidth'] = !empty($_REQUEST['pfdef_meterwidth']) ? $_REQUEST['pfdef_meterwidth'] : '100';
		$newdefaults['intmargin'] = !empty($_REQUEST['pfdef_intmargin']) ? $_REQUEST['pfdef_intmargin'] : '1';
		$newdefaults['precision'] = !empty($_REQUEST['pfdef_precision']) ? $_REQUEST['pfdef_precision'] : '0';
		$newdefaults['targstyle'] = !empty($_REQUEST['pfdef_targstyle']) ? $_REQUEST['pfdef_targstyle'] : 'margin: 0 padding: 0;';
		$newdefaults['progstyle'] = !empty($_REQUEST['pfdef_progstyle']) ? $_REQUEST['pfdef_progstyle'] : 'padding: 0';
		$newdefaults['textstyle'] = !empty($_REQUEST['pfdef_textstyle']) ? $_REQUEST['pfdef_textstyle'] : 'margin: 5px 0px 15px 0px; padding: 0; color: #333333; font-size: 0.9em;';
		$newdefaults['intextstyle'] = !empty($_REQUEST['pfdef_intextstyle']) ? $_REQUEST['pfdef_intextstyle'] : 'font-weight: bold; color: #333333; font-size: 0.85em; float: left; padding-left: 45%; line-height: 1em;';
		$newdefaults['h3style'] = !empty($_REQUEST['pfdef_h3style']) ? $_REQUEST['pfdef_h3style'] : 'margin: 15px 0px 5px 0px; padding: 0; border: none; font-size: 1em;';
		
		update_option('progressfly_defaults', $newdefaults);

		?>

		<div class="updated"><p><strong>Options saved.</strong></p></div>

 	<?php } 

	$current_defaults = get_option('progressfly_defaults');
	
	?>
	
	<div class=wrap>

  	<h2><?php _e('ProgressFly Default Options'); ?></h2>
  	<div class="wrap" style="font-weight: bold; background-color: #FFFFCC; border: 1px solid #2580B2;">
  		<a href="tools.php?page=progressfly.php">Manage ProgressFly Meters</a>
  	</div>
		
		<form method="post" action="options-general.php?page=<?php echo basename(__FILE__); ?>">
			<input type="hidden" name="action" value="save_options" />
			
			<div id="item_manager">
				<div style="float: left; width: 98%; clear: both;" class="top">
				
				<div style="padding: 10px;">
				<table width="100%" border=0 cellspacing=3 cellpadding=3>
				<tr valign="top">
					<td colspan=2><?php _e('<h4>The Colors:</h4>'); ?></td>
					<td width="10%"></td>
					<td colspan=2><?php _e('<h4>The Numbers:</h4>'); ?></td>
				</tr>
          <tr valign="top">
	          <td align="left" width="15%"><?php _e(' Background '); ?></td>
						<td align="left" width="15%">#<input type="text" name="pfdef_bgcolor" class="input" size=6 value="<?php echo $current_defaults['bgcolor']; ?>" /></td>
						<td width="10%">&nbsp;</td>
	          <td align="left" width="15%"><?php _e(' Border Style '); ?></td>
						<td align="left" width="15%"><input type="text" name="pfdef_borderstyle" class="input" size=6 value="<?php echo $current_defaults['borderstyle']; ?>" /></td>
          </tr>
          <tr valign="top">
	          <td align="left" width="15%"><?php _e(' Progress '); ?></td>
						<td align="left" width="15%">#<input type="text" name="pfdef_pcolor" class="input" size=6 value="<?php echo $current_defaults['pcolor'];  ?>" /></td>
						<td width="10%">&nbsp;</td>
  					<td align="left" width="15%"><?php _e(' Border Width '); ?></td>
  					<td align="left" width="15%"><input type="text" name="pfdef_borderwidth" class="input" size=6 value="<?php echo $current_defaults['borderwidth']; ?>" /><?php _e(' px'); ?></td>
          </tr>
          <tr valign="top">
	          <td align="left" width="15%"><?php _e(' Border '); ?></td>
						<td align="left" width="15%">#<input type="text" name="pfdef_bordercolor" class="input" size=6 value="<?php echo $current_defaults['bordercolor']; ?>" /></td>
						<td width="10%">&nbsp;</td>
  					<td align="left" width="15%"><?php _e(' Meter Width '); ?></td>
  					<td align="left" width="15%"><input type="text" name="pfdef_meterwidth" class="input" size=6 value="<?php echo $current_defaults['meterwidth']; ?>" /><?php _e(' px'); ?></td>
          </tr>
					<tr valign="top">
					<td colspan=3></td>
  					<td align="left" width="15%"><?php _e(' Meter Height '); ?></td>
  					<td align="left" width="15%"><input type="text" name="pfdef_meterheight" class="input" size=6 value="<?php echo $current_defaults['meterheight']; ?>" /><?php _e(' px'); ?></td>
					</tr>
					<tr valign="top">
					<td colspan=3></td>
  					<td align="left" width="15%"><?php _e(' Internal Margin '); ?></td>
  					<td align="left" width="15%"><input type="text" name="pfdef_intmargin" class="input" size=6 value="<?php echo $current_defaults['intmargin']; ?>" /><?php _e(' px'); ?></td>
					</tr>
					<tr valign="top">
					<td colspan=3></td>
  					<td align="left" width="15%"><?php _e(' Precision '); ?></td>
  					<td align="left" width="15%"><input type="text" name="pfdef_precision" class="input" size=1 value="<?php echo $current_defaults['precision']; ?>" /><?php _e(' numbers after the decimal point'); ?></td>
					</tr>
        </table>
				
				</div>
				
				<div style="padding: 10px;">
				<?php _e('<h4>The Freestyle CSS:</h4>'); ?>
				
				<table width="100%" border=0 cellspacing=3 cellpadding=3>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Target / Background'); ?></td>
						<td align="left"><input type="text" name="pfdef_targstyle" class="input" size=45 value="<?php echo $current_defaults['targstyle']; ?>" /></td>
						<td><?php _e('Note: from v0.50, height and width should be specified in the Meter Height and Meter Width variables in <em>The Numbers</em>, above. Including them here may break the display of your meters.)'); ?></td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Progress Bar'); ?></td>
						<td align="left"><input type="text" name="pfdef_progstyle" class="input" size=45 value="<?php echo $current_defaults['progstyle']; ?>" /></td>
						<td><?php _e('Note: from v0.50, do not specify height or margin here. The height is now automatically calculated, and the margin should be specified in the Internal Margin variable in <em>The Numbers</em>, above.'); ?></td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Title Text: '); ?></td>
						<td align="left"><input type="text" name="pfdef_h3style" class="input" size=45 value="<?php echo $current_defaults['h3style']; ?>" /></td>
						<td></td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Progress Text: '); ?></td>
						<td align="left"><input type="text" name="pfdef_textstyle" class="input" size=45 value="<?php echo $current_defaults['textstyle']; ?>" /></td>
						<td></td>
					</tr>
					<tr valign="top">
						<td align="left" width="20%"><?php _e('Internal Progress Text: '); ?></td>
						<td align="left"><input type="text" name="pfdef_intextstyle" class="input" size=45 value="<?php echo $current_defaults['intextstyle']; ?>" /></td>
						<td><?php _e('To control precisely where your % figure appears inside the progress meter, include one of the following expressions: <br />- "float: right;" &#8212; to right-align the text<br />- "float: left;" &#8212; to left-align the text<br />- "float: left; padding-left: 45%;" &#8212; to (approximately) centre the text. Play with the padding percentage until it looks right to you. I find 45% gives me good results in Firefox, and 55% gives good results in IE.'); ?></td>
					</tr>
				</table>

				</div>

				<input type="submit" name="save" class="button bold" value="Update Options &raquo;" />
				
			</div>
			<div style="clear:both; height:1px;">&nbsp;</div>
		</div>
			
		</form>
		
		<div style="padding: 10px; clear: both;"></div>
		<a name="defaults"></a>
		<h2><?php _e('Default CSS'); ?></h2>
		<p>Tinkering with the CSS broken something? Below are the default values used, in case you need to resort to a 'clean' install:</p>
		<ul>
		<li>Background Color: FFFFFF</li>
		<li>Progress Color: 000000</li>
		<li>Border Color: 000000</li>
		<li>Meter Width: 100</li>
		<li>Meter Height: 15</li>
		<li>Border Style: solid<br /><a href="http://www.w3schools.com/css/pr_border-style.asp">Allowable Values</a>: solid, double, groove, inset, outset, ridge, dashed, dotted</li>
		<li>Border Width: 1</li>
		<li>Internal Margin: 1</li>
		<li>Title Text: margin: 15px 0px 5px 0px; padding: 0; border: none; font-size: 1em;</li>
		<li>Progress Text: margin: 5px 0px 15px 0px; padding: 0; color: #333333; font-size: 0.9em;</li>
		<li>Internal Text: font-weight: bold; color: #333333; font-size: 0.85em; float: left; padding-left: 45%; line-height: 1em;</li>
		</ul>
		
		<h3>Don't Want a Border?</h3>
		<p>Simply change the Border Width variable to 0, instead of the default 1. You can do this per-project in the Management submenu, or change the global default in the Options submenu</p>
		
		<h3>Don't Want a Gap?</h3>
		<p>If you don't want a gap between the outer edge of the progress meter and the edge of the progress bar, simply change the Internal Margin variable to 0. You can do this per-project in the Management submenu, or change the global default in the Options submenu</p>
		
		<h3>More CSS?</h3>
		<p>The ProgressFly meters are controlled entirely through CSS, which means how they display is entirely up to you. If you want to know more about how to code in CSS, visit <a href="http://www.w3schools.com/">W3Schools</a> for their tutorials and standards.

	
	</div>
	
	<?php
}


// ProgressFly functions

// Calculate percent completed
function pf_percent($progress,$target,$complete,$precision)
{
		if ($target > 0) {
			$fraction = $progress/$target;
			$completed = 100 * $fraction;
			$percentcomplete = number_format($completed, $precision);
			if ($complete=='yes') $percentcomplete = 100;
		} else {
			$percentcomplete = 0;
		}
	return $percentcomplete;
}

// Prints specific ProgressMeter (with progress text displayed under the meter)
function pf_printmeter_css($wipdata, $precision)
{
	$heightfactor = 2 * $wipdata->intmargin;
	$progheight = $wipdata->meterheight - $heightfactor;
	$percent = pf_percent($wipdata->progress, $wipdata->target, $wipdata->complete, $precision);
	if ($percent>100) { $barwidth = 100; } else { $barwidth = $percent; }
	?>
	<?php if (!empty($wipdata->titlelink) ) { _e('<a href="'); echo($wipdata->titlelink); _e('">'); } ?>
	<h3 style="<?php echo $wipdata->h3style; ?>"><?php echo ($wipdata->title); ?></h3>
	<?php if (!empty($wipdata->titlelink) ) { _e('</a>'); } ?>
	<div style="border: <?php echo $wipdata->borderwidth; ?>px <?php echo $wipdata->borderstyle; ?> #<?php echo ($wipdata->bordercolor); ?>; background-color: #<?php echo ($wipdata->bgcolor); ?>; height: <?php echo $wipdata->meterheight; ?>px; width: <?php echo $wipdata->meterwidth; ?>px; <?php echo $wipdata->targstyle; ?> display: table;">
		<div style="width: <?php echo $barwidth; ?>%; background-color: #<?php echo ($wipdata->pcolor); ?>; height: <?php echo $progheight; ?>px; margin: <?php echo $wipdata->intmargin; ?>px; <?php echo $wipdata->progstyle; ?>"></div>
	</div>
	<p style="<?php echo $wipdata->textstyle; ?>"><?php echo($wipdata->units); ?>: <?php echo number_format($wipdata->progress, $precision); ?> / <?php echo number_format($wipdata->target, $precision); ?> (<?php echo $percent; ?>%)</p>
	<?php
}

// Prints specific ProgressMeter (with progress % displayed inside the meter, and no progress text below)
function pf_printmeter_cssbare($wipdata, $precision)
{
	$heightfactor = 2 * $wipdata->intmargin;
	$progheight = $wipdata->meterheight - $heightfactor;
	$topmargin = -$progheight;
        $percent = pf_percent($wipdata->progress, $wipdata->target, $wipdata->complete, $precision);
        if ($percent>100) { $barwidth = 100; } else { $barwidth = $percent; }
        ?>
            <h3 style="<?php echo $wipdata->h3style; ?>">
								<?php if (!empty($wipdata->titlelink) ) { _e('<a href="'); echo($wipdata->titlelink); _e('">'); } ?> <?php echo ($wipdata->title); ?>        
								<?php if (!empty($wipdata->titlelink) ) { _e('</a>'); } ?>
						</h3>
        <div style="border: <?php echo $wipdata->borderwidth; ?>px <?php echo $wipdata->borderstyle; ?> #<?php echo ($wipdata->bordercolor); ?>; background-color: #<?php echo ($wipdata->bgcolor); ?>; height: <?php echo $wipdata->meterheight; ?>px; width: <?php echo $wipdata->meterwidth; ?>px; <?php echo $wipdata->targstyle; ?> display: table;">
                <div style="width: <?php echo $barwidth; ?>%; background-color: #<?php echo ($wipdata->pcolor); ?>; height: <?php echo $progheight; ?>px; margin: <?php echo $wipdata->intmargin; ?>px; <?php echo $wipdata->progstyle; ?>"></div>
								<span style="margin-top: <?php echo $topmargin; ?>px; <?php echo $wipdata->intextstyle; ?>"><?php echo $percent; ?>%</span>
        </div>
        <?php
}

// Prints specific ProgressMeter for preview purposes: no accompanying text (This is the function used on the Management Page)
function pf_printmeter_preview($wipdata, $precision)
{
	$heightfactor = 2 * $wipdata->intmargin;
	$progheight = $wipdata->meterheight - $heightfactor;
        $percent = pf_percent($wipdata->progress, $wipdata->target, $wipdata->complete, $precision);
        if ($percent>100) { $barwidth = 100; } else { $barwidth = $percent; }
        ?>
        <div style="border: <?php echo $wipdata->borderwidth; ?>px <?php echo $wipdata->borderstyle; ?> #<?php echo ($wipdata->bordercolor); ?>; background-color: #<?php echo ($wipdata->bgcolor); ?>; height: <?php echo $wipdata->meterheight; ?>px; width: <?php echo $wipdata->meterwidth; ?>px; <?php echo $wipdata->targstyle; ?> display: table;">
                <div style="width: <?php echo $barwidth; ?>%; background-color: #<?php echo ($wipdata->pcolor); ?>; height: <?php echo $progheight; ?>px; margin: <?php echo $wipdata->intmargin; ?>px; <?php echo $wipdata->progstyle; ?>"></div>
        </div>
        <?php
}


function pf_specific($wipid, $metertype="css", $globalprec="yes", $precision=0) //prints out the specified meter
{
	global $wpdb, $table_prefix;
	$table_name = $table_prefix . "progressfly";
	if ( $globalprec == "yes" ) {
		$defaults = get_option("progressfly_defaults");
		$precision = $defaults['precision'];
	}
		$sql = "select * from " . $table_name . " where wipid='{$wipid}'";
		
		$results = $wpdb->get_results($sql);
		
		if ( !empty($results) ) {
			if ($metertype == "css") { pf_printmeter_css($results[0],$precision); }
			elseif ($metertype == "cssbare") { pf_printmeter_cssbare($results[0],$precision); }
			elseif ($metertype == "preview") { pf_printmeter_preview($results[0],$precision); }
		}
}

/* Code contributed to by Christy Hall */
function pf_printprojects($limit=-1, $metertype="css", $complete="no", $pfcategory="all", $orderby="wipid", $orderdxn="DESC", $visible="yes", $globalprec="yes", $precision=0)
{
		global $wpdb;
		$table_name = $wpdb->prefix . "progressfly";
	if ( $globalprec == "yes" ) {
		$defaults = get_option("progressfly_defaults");
		$precision = $defaults['precision'];
	}

      if ($pfcategory == "all") {
        if ($visible == "all") {
          if ($complete == "all") {
              $sql = "select * from " . $table_name . " ORDER BY " . $orderby . " " . $orderdxn;
          } else {
              $sql = "select * from " . $table_name . " where complete='" . $complete . "' ORDER BY " . $orderby . " " . $orderdxn;
          }
        } else {
          if ($complete == "all") {
              $sql = "select * from " . $table_name . " WHERE visible='" . $visible . "' ORDER BY " . $orderby . " " . $orderdxn;
          } else {
              $sql = "select * from " . $table_name . " where (complete='" . $complete . "' AND visible='" . $visible . "') ORDER BY " . $orderby . " " . $orderdxn;
          }
        }
      } else {
        if ($visible == "all") {
          if ($complete == "all") {
              $sql = "select * from " . $table_name . " where pfcategory='" . $pfcategory . "' ORDER BY " . $orderby . " " . $orderdxn;
          } else {
              $sql = "select * from " . $table_name . " where (complete='" . $complete . "' AND pfcategory='" . $pfcategory . "') ORDER BY " . $orderby . " " . $orderdxn;
          }
        } else {
          if ($complete == "all") {
              $sql = "select * from " . $table_name . " where (visible='" . $visible . "' AND pfcategory='" . $pfcategory . "') ORDER BY " . $orderby . " " . $orderdxn;
          } else {
              $sql = "select * from " . $table_name . " where (complete='" . $complete . "' AND visible='" . $visible . "' AND pfcategory='" . $pfcategory . "') ORDER BY " . $orderby . " " . $orderdxn;
          }
        }
      }
      
		if ($limit != -1) $sql .= " LIMIT " . $limit;
		
		$results = $wpdb->get_results($sql);
		
		if (!empty($results) )
		{
			foreach ($results as $row)
			{
				if ($metertype == "css") { pf_printmeter_css($row, $precision); }
				elseif ($metertype == "cssbare") { pf_printmeter_cssbare($row, $precision); }
				elseif ($metertype == "preview") { pf_printmeter_preview($row, $precision); }
			}
		} else { _e('Resultset empty!'); } 
}

function pf_embedstring($wipdata, $progress, $target, $display, $precision)
{
	$heightfactor = 2 * $wipdata->intmargin;
	$progheight = $wipdata->meterheight - $heightfactor;
	$topmargin = -$progheight; 
	$percent = pf_percent($progress, $target, 'no', $precision);
	if ($percent>100) { $barwidth = 100; } else { $barwidth = $percent; }
	if (!empty($wipdata->titlelink)) { $titlelinked = '<a href="' . $wipdata->titlelink . '">'; }
	$titlelinked .= $wipdata->title;
	if (!empty($wipdata->titlelink))  $titlelinked .= '</a>';
	
	if ( $display == 1) {

  	$metercode = '<h3 style="' . $wipdata->h3style . '">' . $titlelinked . '</h3><div style="display: table; border: ' . $wipdata->borderwidth . 'px ' . $wipdata->borderstyle . ' #' . $wipdata->bordercolor . '; background-color: #' . $wipdata->bgcolor . '; height: ' . $wipdata->meterheight . 'px; width: ' . $wipdata->meterwidth . 'px; ' . $wipdata->targstyle . '"><div style="width: ' . $barwidth . '%; background-color: #' . $wipdata->pcolor . '; height: ' . $progheight . 'px; margin: ' . $wipdata->intmargin . 'px; ' . $wipdata->progstyle . '"></div></div><p style="' . $wipdata->textstyle . '">' . $wipdata->units . ': ' . number_format($progress, $precision) . ' / ' . number_format($target, $precision) . ' (' . $percent . '%)';

	} elseif ($display == 2 ) {

  	$metercode = '<h3 style="' . $wipdata->h3style . '">' . $titlelinked . '</h3><div style="display: table; border: ' . $wipdata->borderwidth . 'px ' . $wipdata->borderstyle . ' #' . $wipdata->bordercolor . '; background-color: #' . $wipdata->bgcolor . '; height: ' . $wipdata->meterheight . 'px; width: ' . $wipdata->meterwidth . 'px; ' . $wipdata->targstyle . '"><div style="width: ' . $barwidth . '%; background-color: #' . $wipdata->pcolor . '; height: ' . $progheight . 'px; margin: ' . $wipdata->intmargin . 'px; ' . $wipdata->progstyle . '"></div><span style="margin-top: ' . $topmargin . 'px; ' . $wipdata->intextstyle . '">' . $percent . '%</span></div>';
	
	} elseif ($display == 3) {

  	$metercode = '<h3 style="' . $wipdata->h3style . '">' . $titlelinked . '</h3><div style="display: table; border: ' . $wipdata->borderwidth . 'px ' . $wipdata->borderstyle . ' #' . $wipdata->bordercolor . '; background-color: #' . $wipdata->bgcolor . '; height: ' . $wipdata->meterheight . 'px; width: ' . $wipdata->meterwidth . 'px; ' . $wipdata->targstyle . '"><div style="width: ' . $barwidth . '%; background-color: #' . $wipdata->pcolor . '; height: ' . $progheight . 'px; margin: ' . $wipdata->intmargin . 'px; ' . $wipdata->progstyle . '"></div></div>';
	
	}

return $metercode;
}

function pf_embedfunction($content)
{
	global $post, $wpdb;
	$table_name = $wpdb->prefix . "progressfly";

//tag taxonomy: [pfmeter id=$id target=$target progress=$progress display=$display precision=$precision] (where $target and $progress are optional; if omitted, will produce dynamic meter)

//step one: do a search (preg_match) for the full tag: 
if (preg_match_all("/\[pfmeter.*\]/i", $content, $matches)) { 
// the forwardslash is a beginning/end delimiter; the i indicates case-sensitive search; i think the backslash "escapes" the square brackets; and I presume the .* means "pfmeter plus anything that comes after it until the square bracket"

$data = $matches[0]; 
//$matches[0] is the stuff that matched the full string -- in this case, the pfmeter tags

//start the loop to go through the matches[0] array and fill the $meter array
	$i = 0;
	foreach ($data as $datum) {
		//for each project meter, capture the id:
		if (preg_match("/\sid=(\S*?)[\]\s]/i", $datum, $intmatches)) {
			// the forwardslash is beginning/end delimiter; the i indicates case-sensivite search; I have no idea what the \s\s does, nor the \S*? (except that should be the id integer)
			$wipid = intval($intmatches[1]);
		}
		
		//for each project meter, capture the target (if specified)
		if (preg_match("/\starget=(\S*?)[\]\s]/i", $datum, $intmatches)) {
			// the forwardslash is beginning/end delimiter; the i indicates case-sensivite search; I have no idea what the \s\s does, nor the \S*? (except that should be the id integer)
			$target = $intmatches[1];
		}

		//for each project meter, capture the progress (if specified)
		if (preg_match("/\sprogress=(\S*?)[\]\s]/i", $datum, $intmatches)) {
			// the forwardslash is beginning/end delimiter; the i indicates case-sensivite search; I have no idea what the \s\s does, nor the \S*? (except that should be the id integer)
			$progress = $intmatches[1];
		}
		
		//for each project meter, capture the display style (1 = css [default], 2 = cssbare, 3 = preview)
		if (preg_match("/\sdisplay=(\S*?)[\]\s]/i", $datum, $intmatches)) {
			// the forwardslash is beginning/end delimiter; the i indicates case-sensivite search; I have no idea what the \s\s does, nor the \S*? (except that should be the id integer)
			$display = $intmatches[1];
		}

		//for each project meter, capture the precision
		if (preg_match("/\sprecision=(\S*?)[\]\s]/i", $datum, $intmatches)) {
			// the forwardslash is beginning/end delimiter; the i indicates case-sensivite search; I have no idea what the \s\s does, nor the \S*? (except that should be the id integer)
			$precision = $intmatches[1];
		}

		// fetch other necessary information from the database
		$sql = "select title, ";
		if ( empty($target) || empty($progress) ) $sql .= "target, progress, ";
		$sql .= "h3style, titlelink, progstyle, borderwidth, borderstyle, intmargin, meterheight, meterwidth, intextstyle, targstyle, textstyle, units, bgcolor, pcolor, bordercolor, complete from " . $table_name . " where wipid='{$wipid}'";
		
		$results = $wpdb->get_results($sql);
		if ( empty($display) ) $display =1; 
		if ( empty($precision) ) 
		{
			$defaults = get_option("progressfly_defaults");
			$precision = $defaults['precision'];
			if ( empty($precision) ) $precision=0;
		}
		if ( empty($target) || empty($progress) ) {
			$meter[$i] = pf_embedstring($results[0], $results[0]->progress, $results[0]->target, $display, $precision);
		} else {
			$meter[$i] = pf_embedstring($results[0], $progress, $target, $display, $precision);
		}
		$i++;
		$target = null;
		$progress = null;
		$display = null;
		$precision = null;
	}
	
	$content = str_replace($data, $meter, $content); // should search $content for all instances of $data and replace with the corresponding instance of $meter
}	
	return $content;
}
 
add_filter('the_content', 'pf_embedfunction'); // this will pass post content through the function that should strip out embedded tag call and replace it with the embedded string

// Insert the progressfly_admin_menu() sink into the plugin hook list for 'admin_menu'
add_action('admin_menu', 'progressfly_admin_menu');
?>