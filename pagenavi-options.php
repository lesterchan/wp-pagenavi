<?php

class PageNavi_Options_Page extends scbAdminPage {

	function setup() {
		$this->textdomain = 'wp-pagenavi';

		$this->args = array(
			'page_title' => __('Page Navigation Options', $this->textdomain),
			'menu_title' => __('PageNavi', $this->textdomain),
		);
	}

	function form_handler() {
		if ( empty($_POST['Submit']) )
			return;

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

		$this->options->update($pagenavi_options);

		$this->admin_msg(__('Settings updated.', $this->textdomain));
	}

	function page_content() {
		$pagenavi_options = $this->options->get();
?>
<form method="post" action="">
	<h3><?php _e('Page Navigation Text', $this->textdomain); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Number Of Pages', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_pages_text" value="<?php echo esc_html($pagenavi_options['pages_text']); ?>" size="50" /><br />
				%CURRENT_PAGE% - <?php _e('The current page number.', $this->textdomain); ?><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', $this->textdomain); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Current Page', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_current_text" value="<?php echo esc_html($pagenavi_options['current_text']); ?>" class="regular-text" /><br />
				%PAGE_NUMBER% - <?php _e('The page number.', $this->textdomain); ?><br />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Page', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_page_text" value="<?php echo esc_html($pagenavi_options['page_text']); ?>" class="regular-text" /><br />
				%PAGE_NUMBER% - <?php _e('The page number.', $this->textdomain); ?><br />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For First Page', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_first_text" value="<?php echo esc_html($pagenavi_options['first_text']); ?>" class="regular-text" /><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', $this->textdomain); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Last Page', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_last_text" value="<?php echo esc_html($pagenavi_options['last_text']); ?>" class="regular-text" /><br />
				%TOTAL_PAGES% - <?php _e('The total number of pages.', $this->textdomain); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Next Page', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_next_text" value="<?php echo esc_html($pagenavi_options['next_text']); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Previous Page', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_prev_text" value="<?php echo esc_html($pagenavi_options['prev_text']); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Next ...', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_dotright_text" value="<?php echo esc_html($pagenavi_options['dotright_text']); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Text For Previous ...', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_dotleft_text" value="<?php echo esc_html($pagenavi_options['dotleft_text']); ?>" class="regular-text" />
			</td>
		</tr>
	</table>

	<h3><?php _e('Page Navigation Options', $this->textdomain); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><?php _e('Use pagenavi.css', $this->textdomain); ?></th>
			<td>
				<input type="checkbox" name="use_pagenavi_css" value="1" <?php checked($pagenavi_options['use_pagenavi_css']); ?>>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Page Navigation Style', $this->textdomain); ?></th>
			<td>
				<select name="pagenavi_style" size="1">
					<option value="1"<?php selected('1', $pagenavi_options['style']); ?>><?php _e('Normal', $this->textdomain); ?></option>
					<option value="2"<?php selected('2', $pagenavi_options['style']); ?>><?php _e('Drop Down List', $this->textdomain); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Number Of Pages To Show', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_num_pages" value="<?php echo esc_html($pagenavi_options['num_pages']); ?>" class="small-text" />
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Always Show Page Navigation', $this->textdomain); ?></th>
			<td>
				<input type="checkbox" name="pagenavi_always_show" value="1" <?php checked($pagenavi_options['always_show']); ?>>
				<?php _e("Show navigation even if there's only one page", $this->textdomain); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Number Of Larger Page Numbers To Show', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_num_larger_page_numbers" value="<?php echo esc_html($pagenavi_options['num_larger_page_numbers']); ?>" class="small-text" />
				<br />
				<?php _e('Larger page numbers are in additional to the default page numbers. It is useful for authors who is paginating through many posts.', $this->textdomain); ?>	
				<br />
				<?php _e('For example, WP-PageNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50', $this->textdomain); ?>	
				<br />
				<?php _e('Enter 0 to disable.', $this->textdomain); ?>	
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e('Show  Larger Page Numbers In Multiples Of:', $this->textdomain); ?></th>
			<td>
				<input type="text" name="pagenavi_larger_page_numbers_multiple" value="<?php echo esc_html($pagenavi_options['larger_page_numbers_multiple']); ?>" class="small-text" />
				<br />
				<?php _e('If mutiple is in 5, it will show: 5, 10, 15, 20, 25', $this->textdomain); ?>	
				<br />				
				<?php _e('If mutiple is in 10, it will show: 10, 20, 30, 40, 50', $this->textdomain); ?>	
			</td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" name="Submit" class="button" value="<?php _e('Save Changes', $this->textdomain); ?>" />
	</p>
</form>
<?php
	}
}

