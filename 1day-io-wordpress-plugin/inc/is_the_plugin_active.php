<?php
/**
 * Verify if a plugin is active, if not deactivate the actual plugin an show an error
 * @param  [string]  $my_plugin_name
 *                   The plugin name trying to activate. The name of this plugin
 *                   Ex:
 *                   WooCommerce new Shipping Method
 *
 * @param  [string]  $dependency_plugin_name
 *                   The dependency plugin name.
 *                   Ex:
 *                   WooCommerce.
 *
 * @param  [string]  $path_to_plugin
 *                   Path of the plugin to verify with the format 'dependency_plugin/dependency_plugin.php'
 *                   Ex:
 *                   woocommerce/woocommerce.php
 *
 * @param  [string] $textdomain
 *                  Text domain to looking the localization (the translated strings)
 *
 * @param  [string] $version_to_check
 *                  Optional, verify certain version of the dependent plugin
 */
function bikecoders_is_this_plugin_active($my_plugin_name, $dependency_plugin_name, $path_to_plugin, $textdomain = '', $version_to_check = null) {

    # Needed to the function "deactivate_plugins" works
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    $error_content = '';

    if( !is_plugin_active( $path_to_plugin ) )
    {
        $error_content = sprintf(
            esc_attr__( 'The plugin "%s" recommend installing the plugin "%s" active', $textdomain ),
            $my_plugin_name, $dependency_plugin_name
        );

        if ( isset( $_GET['activate'] ) )
                unset( $_GET['activate'] );

    }
    else {

        # If version to check is not defined do nothing
        if($version_to_check === null)
            return;

        # Get the plugin dependency info
        $depPlugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $path_to_plugin);

        # Compare version
        $error = !version_compare ( $depPlugin_data['Version'], $version_to_check, '>=') ? true : false;

        if($error)
        {
            $error_content = sprintf(
                esc_attr__( 'The plugin "%s" recommend installing the version %s or newer of "%s"', $textdomain ),
                $my_plugin_name,
                $version_to_check,
                $dependency_plugin_name
            );

        }
    }

    if(!empty($error_content))
    {
        add_action( 'admin_notices', function() use($my_plugin_name, $dependency_plugin_name, $version_to_check, $textdomain, $error_content)
        {
            $user_id = get_current_user_id();

            if (!get_user_meta($user_id, '1day_notice_closed')) : ?>

                <div class="updated error oneday_notice is-dismissible">
                    <p>
                        <?php echo esc_attr($error_content); ?>
                    </p>

                    <a href="?1day-notice-closed" class="notice-dismiss">
                        <span class="screen-reader-text"><?php echo esc_attr__("Dismiss this notice.", "1day"); ?></span>
                    </a>
                </div>
                <style>
                    .oneday_notice{
                        position: relative;
                    }
                    .oneday_notice a{
                        text-decoration: none;
                    }
                </style>
                <?php
                if ( isset( $_GET['activate'] ) )
                    unset( $_GET['activate'] ); ?>

            <?php endif;
        } );


        add_action('admin_init', function() {

            $user_id = get_current_user_id();

            if (isset($_GET['1day-notice-closed']))
                add_user_meta($user_id, '1day_notice_closed', '1', true);
        });
    }

}