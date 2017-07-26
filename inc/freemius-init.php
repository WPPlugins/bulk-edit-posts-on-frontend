<?php

// Create a helper function for easy SDK access.
if ( !function_exists( 'vgsefe_freemius' ) ) {
    function vgsefe_freemius()
    {
        global  $vgsefe_freemius ;
        if ( !isset( $vgsefe_freemius ) ) {
            $vgsefe_freemius = fs_dynamic_init( array(
                'id'             => '1021',
                'slug'           => 'bulk-edit-posts-on-frontend',
                'type'           => 'plugin',
                'public_key'     => 'pk_5c389ae3fec7d724350dcbdd315ed',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'       => 'edit.php?post_type=vgse_editors',
                'first-path' => 'admin.php?page=vgsefe_welcome_page',
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        return $vgsefe_freemius;
    }

}
// Init Freemius.
vgsefe_freemius();
// Signal that SDK was initiated.
do_action( 'vgsefe_freemius_loaded' );