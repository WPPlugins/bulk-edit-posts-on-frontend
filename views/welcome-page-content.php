<?php 
$frontend_editor_instance = vgse_frontend_editor();
?>
<p><?php _e('Thank you for installing our plugin. You can start using it in 5 minutes. Please follow these steps:', $frontend_editor_instance->textname); ?></p>

<?php 

// Disable core plugin welcome page.
add_option('vgse_welcome_redirect', 'no');

$steps = array();

$missing_plugins = array();


$tgm = TGM_Plugin_Activation::get_instance();

if (!class_exists('ReduxFramework') && !$tgm->is_plugin_active('redux-framework')) {
	$missing_plugins[] = 'Redux Framework';
}

if (!class_exists('WP_Sheet_Editor')) {
	$missing_plugins[] = 'WP Sheet Editor';
}
if (!empty($missing_plugins)) {
	$steps['install_dependencies'] = '<p>' . sprintf(__('Install the free plugins: %s. <a href="%s" target="_blank" class="button">Click here</a>', $frontend_editor_instance->textname), implode(', ', $missing_plugins), $tgm->get_tgmpa_url()) . '</p>';
}

$frontend_editor_instance->auto_setup();
$first_editor_id = $frontend_editor_instance->_get_first_post();

if( $first_editor_id ){
	$steps['use_shortcode'] = '<p>' . sprintf(__('Add this shortcode to a full-width page: [vg_sheet_editor editor_id="%s"] and it works automatically.', $frontend_editor_instance->textname), $first_editor_id ) . '</p>';	
	$steps['settings'] = '<p>' . sprintf(__('<a href="%s" target="_blank" class="button">Settings</a>', $frontend_editor_instance->textname), admin_url('post.php?action=edit&post=' . $first_editor_id)) . '</p>';	
} else {
	$steps['create_first_editor'] = '<p>' . sprintf(__('Fill the settings. <a href="%s" target="_blank" class="button">Click here</a>', $frontend_editor_instance->textname), admin_url('post-new.php?post_type=' . VGSE_EDITORS_POST_TYPE)) . '</p>';
}

$steps = apply_filters('vg_sheet_editor/frontend_editor/welcome_steps', $steps);

if (!empty($steps)) {
	echo '<ol class="steps">';
	foreach ($steps as $key => $step_content) {
		?>
		<li><?php echo $step_content; ?></li>		
		<?php
	}

	echo '</ol>';
}
?>		