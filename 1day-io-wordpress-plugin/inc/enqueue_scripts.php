<?php
/**
 * To enqueue scripts and styles
 */
function odp_get_assets_file($name, $type)
{

    $assetsPath = __DIR__ . '/../build/assets.json';
    if (!file_exists($assetsPath)) {
        // return new \WP_Error( 'assets', 'The assets file can not be found.', $assetsPath );
        return;
    }

    $assets = (array)json_decode(file_get_contents($assetsPath, true));

    if (!array_key_exists($name, $assets)) {
        return;
    }

    if (!array_key_exists($type, (array)$assets[$name])) {
        return;
    }
    $assets = (array)$assets[$name];

    return plugin_dir_url(__FILE__) . '../build' . $assets[$type];
}


/**
 * Include JavaScript and CSS files.
 */
function odp_enqueue_js()
{
    wp_enqueue_style('one_day', odp_get_assets_file('app', 'css'), false, null);

    wp_register_script('google_maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr(odp_get_google_map_api_key()), array(), '', true);
    wp_enqueue_script('google_maps');

    wp_enqueue_script('one_day', odp_get_assets_file('app', 'js'), ['jquery'], null, true);

    wp_localize_script('one_day', 'one_day', array(
        'url' => home_url(),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

/**
 * Add the enqueue functions to their respective actions.
 */
add_action('wp_enqueue_scripts', 'odp_enqueue_js', 9999);
