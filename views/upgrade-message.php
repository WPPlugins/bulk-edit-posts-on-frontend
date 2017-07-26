<?php
if (!vgsefe_freemius()->is__premium_only()) {
	$frontend_editor = vgse_frontend_editor();
	?>

	<h2><?php _e('Edit Custom Post Types', $frontend_editor->textname); ?></h2>
	<?php _e('<p>Edit WooCommerce Products, WooCommerce variations and Attributes, and any Custom Post type on the FrontEnd.</p><p>Also update hundreds of posts at once using formulas, copy information between posts, edit images, edit custom fields, and more...', $frontend_editor->textname); ?> </p>		
	<?php
}