<?php 

if ( !defined( 'ABSPATH' ) ) exit; 

function bp_show_profile_data_init() { 
	if ( is_admin()  ) { 
		if ( isset( $_GET['page']  ) && !is_super_admin() ) { 
			if ( $_GET['page'] == 'bp-show-profile-data' ) { 
				add_action( 'admin_enqueue_scripts', 'bp_show_profile_data_admin_scripts'  ); 
				add_action( 'admin_enqueue_scripts', 'bp_show_profile_data_admin_styles'  ); 
			} 
		} 
		$bp_profile_data_instance = new BP_Show_Profile_Data_Admin(); 
	} 
	else { 
		if ( bp_is_members_component() || bp_is_group_members()  ) 
			add_action( 'wp_enqueue_scripts', 'bp_show_profile_data_styles'  ); 
	} 
} 
add_action( 'bp_init', 'bp_show_profile_data_init'  ); 

function bp_show_profile_data_admin_scripts() { 
	wp_enqueue_script( 'jquery-ui-sortable'  ); 
	wp_enqueue_script( 'buddyprofiledata-admin-script',  plugins_url( 'admin/js/buddy-profile-admin-scripts.js' , __FILE__  ), array('jquery-ui-sortable'), get_plugin_data(dirname( __FILE__ ).'/loader.php')['Version'] ); 
} 

function bp_show_profile_data_admin_styles() { 
	wp_enqueue_style( 'buddyprofiledata-admin-styles',  plugins_url( 'admin/css/buddy-profile-admin-styles.css' , __FILE__  ), array(), get_plugin_data(dirname( __FILE__ ).'/loader.php')['Version'] ); 
} 

function bp_show_profile_data_styles() { 

	if(file_exists( get_stylesheet_directory() . '/buddy-profile-data.css' )) : 
		wp_enqueue_style( 'buddyprofiledata-styles',  get_stylesheet_directory_uri() . '/buddy-profile-data.css', array()  ); 
	elseif(file_exists( get_stylesheet_directory() . '/buddy-profile-data.css' )) : 
		wp_enqueue_style( 'buddyprofiledata-styles',  get_template_directory() . '/buddy-profile-data.css', array()  ); 
	else: 
		wp_enqueue_style( 'buddyprofiledata-styles',  plugins_url('/css/buddy-profile-data.css', __FILE__)  , array(), get_plugin_data(dirname( __FILE__ ).'/loader.php')['Version'] ); 
	endif; 
} 

class BP_Show_Profile_Data_Admin { 

	private $pages_message = ''; 
	private $fields_message = ''; 
	private $allowed_fields = array(); 
	private $allowed_pages = array('members', 'groups' ); 

	public function __construct() { 
		$this->gather_profile_fields(); 
		$this->pages_update(); 
		$this->fields_update(); 
		add_action('xprofile_field_after_sidebarbox',array($this,'select_profile_field_metabox')); 
		add_action('xprofile_fields_saved_field',array($this,'profile_field_save')); 
	} 

	public function profile_field_save($field) { 

		if(isset($_POST['card-visibility'])) { 

			$profile_fields = get_option( 'pp_profile_data_fields' ); 

			if(!is_array($profile_fields)) { 
				$profile_fields = Array(); 
			} 

			if($_POST['card-visibility'] == 'on') { 
				$profile_fields[] = $field->id; 

				$profile_fields = array_unique($profile_fields); 
			} else { 

				$exists_key = array_search($field->id, $profile_fields); 

				if($exists_key !== false) unset($profile_fields[$exists_key]); 
			} 

			update_option('pp_profile_data_fields',$profile_fields); 
		} 
	} 

	public function select_profile_field_metabox($field) { 

		$enabled = false; 
		if($field->id) { 

			$profile_fields = get_option('pp_profile_data_fields'); 

			if ($profile_fields !== false) {
				
				$field_key = array_search($field->id,$profile_fields); 

				if($field_key !== false) $enabled = true; 
			}

		} 

?> 

<div class="postbox"> 
	<h2><label for="card-visibility"><?php esc_html_e( 'Show on card', 'BP-spdig'  ); ?></label></h2> 
	<div class="inside"> 
		<div> 
			<select name="card-visibility" id="card-visibility"> 
				<option <?php selected($enabled); ?> value="on"><?php esc_html_e( 'Enabled', 'BP-spdig'  ); ?></option> 
				<option <?php selected(!$enabled); ?> value=""><?php esc_html_e( 'Disabled', 'BP-spdig'  ); ?></option> 
			</select> 
		</div> 
	</div> 
</div> 
<?php 
	} 


	function bp_profile_data_network_admin_menu() { 
		add_submenu_page('settings.php', 'BuddyProfileData', 'BuddyProfileData', 'manage_options', 'bp-show-profile-data', array( $this, 'bp_profile_data_admin_screen'  ) ); 
	} 


	function bp_profile_data_admin_menu() { 
		add_options_page(  __( 'BuddyProfileData', 'BP-spdig' ), __( 'BuddyProfileData', 'BP-spdig'  ), 'manage_options', 'bp-show-profile-data', array( $this, 'bp_profile_data_admin_screen'  )  ); 
	} 

	function bp_profile_data_admin_screen() { 
?> 
<div class="wrap"> 

	<div id="icon-tools" class="icon32"><br /></div> 

	<h2><?php _e( 'BuddyProfileData', 'BP-spdig'  )?></h2> 
	<br /> 

	<?php if ( bp_is_active( 'xprofile'  )  ) {  ?> 

	<?php _e( 'Profile data is shown in various places in BuddyPress.', 'BP-spdig'  )?> 
	<br /> 
	<?php _e( 'This page controls the additional display of the selected profile fields on the selected pages.', 'BP-spdig'  )?> 

<?php 
		$this->bp_profile_data_forms(); 

	} 
else 
	echo '<br>Enable the BuddyPress Extended Profile component.'; 
?> 
</div> 
<?php 
	} 


	private function bp_profile_data_forms() { 
?> 
<table border="0" cellspacing="10" cellpadding="10"> 
	<tr> 
		<td style="vertical-align:top; border: 1px solid #ccc;" > 

			<h3><?php _e('Which pages should show profile data?', 'BP-spdig'); ?></h3> 

			<?php echo $this->pages_message; ?> 

			<form action="" name="profile-data-pages-form" id="profile-data-pages-form"  method="post" class="standard-form"> 

				<?php wp_nonce_field('profile-data-pages-action', 'profile-data-pages-field'); ?> 

				<ul id="pp-profile-data-pages-list"> 

<?php 
		$current_pages = explode(",", get_option( 'pp_profile_data_pages'  )); 

		foreach ($this->allowed_pages as $allowed_page => $value) { 

			if( in_array( $value, $current_pages  )  ) $checked = ' checked="checked"'; 
			else $checked = ''; 

			if( 'groups' == $value  ) $shown_value = 'Group Members'; 
			else $shown_value = ucfirst($value); 
?> 

					<li><label for="bp-allowed-pages-<?php echo $value; ?>"><input id="pp-allowed-pages-<?php echo $value; ?>" type="checkbox" name="pages[]" value="<?php echo $value; ?>" <?php echo  $checked; ?> /> <?php echo $shown_value; ?></label></li> 

					<?php } ?> 
				</ul> 

				<input type="hidden" name="profile-data-pages-access" value="1"/> 
				<input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Changes', 'BP-spdig'); ?>"/> 
			</form> 

		</td> 

		<td style="vertical-align:top; border: 1px solid #ccc;" > 

			<h3><?php _e('Which profile fields should be shown?', 'BP-spdig'); ?></h3> 

			<?php echo $this->fields_message; ?> 

			<?php _e('Drag checked boxes to set display order.', 'BP-spdig'); ?> 
			<br /> 
			<?php _e('Visibility settings will be honored.', 'BP-spdig'); ?> 

			<form action="" name="profile-data-fields-form" id="profile-data-fields-form"  method="post" class="standard-form"> 

				<?php wp_nonce_field('profile-data-fields-action', 'profile-data-fields-field'); ?> 

				<div id="pp-profile-data-fields"> 
					<ul id="pp-profile-data-fields-list" class="ui-sortable"> 

<?php 
			$current_fields = get_option( 'pp_profile_data_fields'  ); 

			if( $current_fields != false  ) { 

				foreach ($current_fields as $key => $value) { 
					if( !empty( $value  )  ) { 
						$field_name = $this->allowed_fields[$value]; 
						unset( $this->allowed_fields[$value]  ); 

?> 
						<li><label for="pp-allowed-fields-<?php echo $field_name; ?>"><input id="pp-allowed-fields-<?php echo $field_name; ?>" type="checkbox" name="fields[]" value="<?php echo $value; ?>"  checked="checked"'/> &nbsp;<?php echo stripslashes(ucfirst($field_name)); ?></label></li> 

<?php 
					} 
				} 
			} ?> 

						<?php foreach ($this->allowed_fields as $key => $value) :  ?> 

						<li><label for="pp-allowed-fields-<?php echo $value; ?>"><input id="pp-allowed-fields-<?php echo $value; ?>" type="checkbox" name="fields[]" value="<?php echo $key; ?>" /> &nbsp;<?php echo stripslashes( ucfirst($value)  ); ?></label></li> 

						<?php endforeach; ?> 
					</ul> 
				</div> 

				<input type="hidden" name="profile-data-fields-access" value="1"/> 
				<input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Changes', 'BP-spdig'); ?>"/> 
			</form> 
		</td> 
</tr></table> 

<?php 
	} 

	private function pages_update() { 

		if( isset( $_POST['profile-data-pages-access']  )  ) { 

			if( !wp_verify_nonce($_POST['profile-data-pages-field'],'profile-data-pages-action')  ) 
				die('Security check'); 

			$updated = 0; 

			if( isset( $_POST['pages']  )  ) { 

				foreach( $_POST['pages'] as $key => $value  ){ 
					if( in_array( $value, $this->allowed_pages  )  ) { 
						$new_pages[] = $value; 
					} 
				} 

				$new_pages = implode(",", $new_pages); 
				$updated = update_option( 'pp_profile_data_pages', $new_pages  ); 

			} else { 
				delete_option( 'pp_profile_data_pages'  ); 
				$updated = 1; 
			} 

			if( $updated  ) 
				$this->pages_message .= 
				"<div class='updated below-h2'>" . __('Pages have been updated.', 'BP-spdig') . "</div>"; 
			else 
				$this->pages_message .= 
				"<div class='updated below-h2' style='color: red'>" . __('No changes were detected re Pages.', 'BP-spdig') . "</div>"; 

		} 
	} 

	private function fields_update() { 

		if ( isset( $_POST['profile-data-fields-access']  )  ) { 

			if ( !wp_verify_nonce($_POST['profile-data-fields-field'],'profile-data-fields-action')  ) 
				die('Security check'); 

			if ( !is_super_admin()  ) 
				return; 

			$updated = 0; 

			if ( isset( $_POST['fields']  )  ) { 

				$new_fields = array(); 

				foreach( $_POST['fields'] as $key => $value  ){ 
					if( array_key_exists($value, $this->allowed_fields )  ) { 
						$new_fields[] = $value; 
					} 
				} 

				if( !empty( $new_fields  )  ) 
					$updated = update_option( 'pp_profile_data_fields', $new_fields  ); 

			} else { 
				delete_option( 'pp_profile_data_fields'  ); 
				$updated = 1; 
			} 

			if ( $updated  ) 
				$this->fields_message .= 
				"<div class='updated below-h2'>" . __('Fields have been updated.', 'BP-spdig') . "</div>"; 
			else 
				$this->fields_message .= 
				"<div class='updated below-h2' style='color: red'>" . __('No changes were detected re Fields.', 'BP-spdig') . "</div>"; 
		} 
	} 

	private function gather_profile_fields() { 

		$groups = BP_XProfile_Group::get( array( 'fetch_fields' => true  )  );    // from bp-xprofile/bp-xprofile-admin.php 

		if ( !empty( $groups  )  ) { 
			foreach ( $groups as $group  ) { 
				if ( !empty( $group->fields  )  ) { 
					foreach ( $group->fields as $field  ) { 
						$this->allowed_fields[$field->id] = $field->name; 
					} 
				} 
			} 
		} 
	} 
} 

function bp_show_profile_data_display() { 
	global $wpdb, $bp; 
	
	if ( bp_is_current_component( $bp->groups->slug  )  ) { 
		$user_id = bp_get_group_member_id(); 
		echo '<div id="pp-profile-data-groups" class="pp-profile-data">'; 
	} else  if ( bp_is_current_component( $bp->members->slug  )  ) { 
		$user_id = bp_get_member_user_id(); 
		echo '<div id="pp-profile-data-members" class="pp-profile-data">';
	} else 
		return; 

	
	$current_fields = get_option( 'pp_profile_data_fields'  ); 


	$hidden_fields = bp_xprofile_get_hidden_fields_for_user( $user_id, $current_user_id = bp_loggedin_user_id()  ); 

	if( $current_fields != false  ) { 
		foreach( $current_fields as $key => $value  ) { 

			if ( !in_array( $value, $hidden_fields  )  ) { 

				$field_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $value  )  ); 

				$field_value = xprofile_get_field_data( $value, $user_id, $multi_format = 'comma'  ); 

				$field_value = xprofile_filter_format_field_value( $field_value, $field_type = $field_data->type  ); 

				if( '' != $field_value  ) 
					echo '<div class="pp-profile-data-field-item">
						<b id=pp-xp-'. strtolower(str_replace(' ', '-', $field_data->name)) .'-label>' . stripslashes($field_data->name) . ':</b>
						<span id=pp-xp-'. strtolower(str_replace(' ', '-', $field_data->name)) .'-data>' . $field_value . '</span>
					</div>'; 
			} 
		} 
	} 
	echo '</div>'; 
} 
add_action( 'bp_group_members_list_item', 'bp_show_profile_data_display'  ); 
add_action( 'bp_directory_members_item', 'bp_show_profile_data_display'  ); 
?>
