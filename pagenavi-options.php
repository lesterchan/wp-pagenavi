<?php

### Form Processing
// Update Options
if ( !empty($_POST['Submit']) ) {
	$pagenavi_options = array();
	$pagenavi_options['pages_text'] = stripslashes(@$_POST['pagenavi_pages_text']);
	$pagenavi_options['current_text'] = stripslashes(@$_POST['pagenavi_current_text']);
	$pagenavi_options['page_text'] = stripslashes(@$_POST['pagenavi_page_text']);
	$pagenavi_options['first_text'] = stripslashes(@$_POST['pagenavi_first_text']);
	$pagenavi_options['last_text'] = stripslashes(@$_POST['pagenavi_last_text']);
	$pagenavi_options['next_text'] = stripslashes(@$_POST['pagenavi_next_text']);
	$pagenavi_options['prev_text'] = stripslashes(@$_POST['pagenavi_prev_text']);
	$pagenavi_options['dotright_text'] = stripslashes(@$_POST['pagenavi_dotright_text']);
	$pagenavi_options['dotleft_text'] = stripslashes(@$_POST['pagenavi_dotleft_text']);
	$pagenavi_options['style'] = intval(@$_POST['pagenavi_style']);
	$pagenavi_options['num_pages'] = intval(@$_POST['pagenavi_num_pages']);
	$pagenavi_options['always_show'] = (bool) @$_POST['pagenavi_always_show'];
	$pagenavi_options['num_larger_page_numbers'] = intval(@$_POST['pagenavi_num_larger_page_numbers']);
	$pagenavi_options['larger_page_numbers_multiple'] = intval(@$_POST['pagenavi_larger_page_numbers_multiple']);
	$pagenavi_options['use_pagenavi_css'] = (bool) @$_POST['use_pagenavi_css'];

	if ( update_option('pagenavi_options', $pagenavi_options) )
		echo '<div class="updated fade"><p>' . __('Settings updated.') . '</p></div>';
	else
		echo '<div class="error"><p>' . __('Settings not updated.') . '</p></div>';
}

// Main Page
$pagenavi_options = get_option('pagenavi_options');
?>
<form method="post" action="">
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Page Navigation Options', 'wp-pagenavi'); ?></h2>
	<h3><?php _e('Page Navigation Text', 'wp-pagenavi'); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Number Of Pages', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_pages_text" value="<?php echo htmlspecialchars($pagenavi_options['pages_text']); ?>" size="50" /><br />
				%CURRENT_PAGE% - <?php _e('The current page number.', 'wp-pagenavi'); ?><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-pagenavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Current Page', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_current_text" value="<?php echo htmlspecialchars($pagenavi_options['current_text']); ?>" size="30" /><br />
				%PAGE_NUMBER% - <?php _e('The page number.', 'wp-pagenavi'); ?><br />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Page', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_page_text" value="<?php echo htmlspecialchars($pagenavi_options['page_text']); ?>" size="30" /><br />
				%PAGE_NUMBER% - <?php _e('The page number.', 'wp-pagenavi'); ?><br />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For First Page', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_first_text" value="<?php echo htmlspecialchars($pagenavi_options['first_text']); ?>" size="30" /><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-pagenavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Last Page', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_last_text" value="<?php echo htmlspecialchars($pagenavi_options['last_text']); ?>" size="30" /><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', 'wp-pagenavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Next Page', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_next_text" value="<?php echo htmlspecialchars($pagenavi_options['next_text']); ?>" size="30" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Previous Page', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_prev_text" value="<?php echo htmlspecialchars($pagenavi_options['prev_text']); ?>" size="30" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Next ...', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_dotright_text" value="<?php echo htmlspecialchars($pagenavi_options['dotright_text']); ?>" size="30" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Previous ...', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_dotleft_text" value="<?php echo htmlspecialchars($pagenavi_options['dotleft_text']); ?>" size="30" />
			</td>
		</tr>
	</table>
	<h3><?php _e('Page Navigation Options', 'wp-pagenavi'); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Use pagenavi.css?', 'wp-pagenavi'); ?></th>
			<td>
				<input type="checkbox" name="use_pagenavi_css" value="1" <?php checked($pagenavi_options['use_pagenavi_css']); ?>>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Page Navigation Style', 'wp-pagenavi'); ?></th>
			<td>
				<select name="pagenavi_style" size="1">
					<option value="1"<?php selected('1', $pagenavi_options['style']); ?>><?php _e('Normal', 'wp-pagenavi'); ?></option>
					<option value="2"<?php selected('2', $pagenavi_options['style']); ?>><?php _e('Drop Down List', 'wp-pagenavi'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Number Of Pages To Show?', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_num_pages" value="<?php echo htmlspecialchars($pagenavi_options['num_pages']); ?>" size="4" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Always Show Page Navigation?', 'wp-pagenavi'); ?></th>
			<td>
				<input type="checkbox" name="pagenavi_always_show" value="1" <?php checked($pagenavi_options['always_show']); ?>>
				<?php _e("Show navigation even if there's only one page", 'wp-pagenavi'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Number Of Larger Page Numbers To Show?', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_num_larger_page_numbers" value="<?php echo htmlspecialchars($pagenavi_options['num_larger_page_numbers']); ?>" size="4" />
				<br />
				<?php _e('Larger page numbers are in additional to the default page numbers. It is useful for authors who is paginating through many posts.', 'wp-pagenavi'); ?>	
				<br />
				<?php _e('For example, WP-PageNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50', 'wp-pagenavi'); ?>	
				<br />
				<?php _e('Enter 0 to disable.', 'wp-pagenavi'); ?>	
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Show  Larger Page Numbers In Multiples Of:', 'wp-pagenavi'); ?></th>
			<td>
				<input type="text" name="pagenavi_larger_page_numbers_multiple" value="<?php echo htmlspecialchars($pagenavi_options['larger_page_numbers_multiple']); ?>" size="4" />
				<br />
				<?php _e('If mutiple is in 5, it will show: 5, 10, 15, 20, 25', 'wp-pagenavi'); ?>	
				<br />				
				<?php _e('If mutiple is in 10, it will show: 10, 20, 30, 40, 50', 'wp-pagenavi'); ?>	
			</td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" name="Submit" class="button" value="<?php _e('Save Changes', 'wp-pagenavi'); ?>" />
	</p>
</div>
</form>

