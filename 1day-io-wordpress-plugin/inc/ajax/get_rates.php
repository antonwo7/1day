<?php
add_action('wp_ajax_get_rates', 'get_rates');
add_action('wp_ajax_nopriv_get_rates', 'get_rates');


function get_rates()
{
    $product_id = !empty($_POST['room_product_id']) ? sanitize_text_field($_POST['room_product_id']) : "";

    $resources = !empty($_POST['room_resources']) ? sanitize_text_field($_POST['room_resources']) : "";

    $resources_total = 0;

    if(!empty($resources) && !empty($product_id))
    {
        $resources_total = get_resources_total_price($product_id, explode('||', $resources));
    }


    $args = [
        'start_date' => odp_gc('start_date'),
        'end_date' => odp_gc('end_date'),
        'guests' => odp_gc('guests'),
        'room_type_id' => !empty($_POST['room_type_id']) ? sanitize_text_field($_POST['room_type_id']) : ""
    ];

    $result = odp_api_connect(ODP_API_URL, 'get', $args);

    ob_start();

    if(!$result['state'])
    {
        echo wp_json_encode([
            'state' => false,
            'log' => $result,
            'start' =>  odp_gc('start_date'),
            'end' =>  odp_gc('end_date')
        ]);
        die();
    }

    $room = !empty($result['content']->data[0]->room_types[0]) ? $result['content']->data[0]->room_types[0] : false;

    include_once __DIR__ . '/../templates/rooms/rates/rates-list.php';

    $out = ob_get_contents();
    ob_clean();

    $response = [
        'state' => true,
        'content' => $out
    ];

    echo wp_json_encode($response);
    die();
}