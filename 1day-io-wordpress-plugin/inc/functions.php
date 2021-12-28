<?php

function odp_p($object){
    print("<pre>".print_r($object,true)."</pre>");
}

function odp_w($object, $type){
    fwrite(fopen(__DIR__ . '/test.txt', $type), wp_json_encode($object));
}

function odp_sc($key, $value)
{
    if (isset($_COOKIE[$key]))
    {
        unset($_COOKIE[$key]);
        setcookie("odp_{$key}", null, time() - 3600, '/');
    }

    setcookie("odp_{$key}", sanitize_text_field($value), time() + (60 * 60 * 24 * 30), '/');
}

function odp_gc($key)
{
    return !empty($_COOKIE["odp_{$key}"]) ? sanitize_text_field($_COOKIE["odp_{$key}"]) : '';
}

function odp_is_sync_url()
{
    return isset($_GET['sync']) && !empty($_GET['page']) && $_GET['page'] == 'one_day_options';
}

function odp_is_options_url()
{
    return is_admin() && !empty($_GET['page']) && $_GET['page'] == 'one_day_options';
}

function odp_current_url()
{
    global $wp;

    $params = $_SERVER['QUERY_STRING'] == '' ? '' : '?' . $_SERVER['QUERY_STRING'];

    return home_url($wp->request) . $params;
}

function odp_get_filter_form_settings($filter_id)
{
    if(get_post_type($filter_id) != 'oneday_filter') return [];

    return [
        'url' => get_post_meta($filter_id, 'oneday_filter_form_url', true),
        'one_line' => (bool)get_post_meta($filter_id, 'oneday_filter_form_one_line', true),
        'bg_color' => get_post_meta($filter_id, 'oneday_filter_form_bg_color', true),
        'btn_color' => get_post_meta($filter_id, 'oneday_filter_form_btn_color', true),
        'btn_font_color' => get_post_meta($filter_id, 'oneday_filter_form_button_font_color', true),
        'font_color' => get_post_meta($filter_id, 'oneday_filter_form_font_color', true),
        'widget_width' => get_post_meta($filter_id, 'oneday_filter_form_widget_width', true),
        'property_code' => get_post_meta($filter_id, 'oneday_filter_form_property_code', true)
    ];
}

function odp_get_filter_results_settings($filter_id, $field = false)
{
    if(get_post_type($filter_id) != 'oneday_filter') return [];

    $settings = [
        'buttons_background' => get_post_meta($filter_id, 'oneday_filter_results_buttons_background', true),
        'buttons_font_color' => get_post_meta($filter_id, 'oneday_filter_results_buttons_font_color', true),
        'property_code' => get_post_meta($filter_id, 'oneday_filter_form_property_code', true)
    ];

    if($field)
    {
        return !empty($settings[$field]) ? $settings[$field] : '';
    }

    return $settings;
}

function odp_get_api_key()
{
    $one_day_settings = get_option('1day_options');
    return $one_day_settings['api_key'];
}


function odp_get_header_for_api_request()
{
    return ['x-api-key' => odp_get_api_key(), 'Content-Type' => 'application/json'];
}

function odp_get_google_map_api_key()
{
    return get_option('1day_options')['google_map_api_key'];
}

function get_array_dates($start_date, $end_date)
{
    $dates = [$start_date];

    $start_date_object = date_create_from_format('Y-m-d', $start_date);
    $end_date_object = date_create_from_format('Y-m-d', $end_date);

    $interval = date_diff($start_date_object, $end_date_object, true);

    $nights_count = $interval->days > 0 ? $interval->days : 0;

    if(empty($nights_count))
        return [];

    for($i = 1; $i < $nights_count; $i++){
        $start_date_object->modify('+1 day');
        $dates[] = $start_date_object->format('Y-m-d');
    }

    return $dates;
}

require_once __DIR__ . '/functions/hotels.php';
require_once __DIR__ . '/functions/rooms.php';



