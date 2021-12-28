<?php

function odp_get_room_lowest_rate($room)
{
    $lowest_room_nightly_rate = false;

    if(!empty($room->rates))
    {
        $lowest_room_nightly_rates = [];

        foreach ($room->rates as $rate)
        {
            $lowest_room_nightly_rates[] = $rate->lowest_nightly_rate;
        }

        $lowest_room_nightly_rate = min($lowest_room_nightly_rates);
    }

    return $lowest_room_nightly_rate;
}

function odp_get_room_product_id_by_room_id($id)
{

    $rooms_query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_sku',
                'value' => $id,
                'compare' => '='
            ],
            [
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => 'room'
            ]
        ]
    ]);

    if($rooms_query->have_posts())
        return $rooms_query->posts[0]->ID;

    return false;
}

function odp_get_room_img($room_post_id)
{
    return esc_url(get_the_post_thumbnail_url($room_post_id, 'thumb'));
}

function odp_get_rooms_ids_by_hotel_term_id($hotel_term_id)
{
    $rooms_query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'hotel',
                'field' => 'id',
                'terms' => $hotel_term_id
            ],
            [
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => 'room'
            ]
        ]
    ]);

    if($rooms_query->have_posts())
    {
        return wp_list_pluck($rooms_query->posts, 'ID');
    }

    return false;
}

function odp_get_rooms_ids()
{
    $args = array(
        'type' => 'room'
    );

    $rooms = wc_get_products($args);

    $rooms = array_map(function($room){
        return $room->get_id();
    }, $rooms);

    if(!empty($rooms))
    {
        return $rooms;
    }

    return false;
}

function odp_delete_rooms_from_hotel($hotel_term_id)
{
    $rooms_product_ids = odp_get_rooms_ids_by_hotel_term_id($hotel_term_id);

    if(!empty($rooms_product_ids))
    {
        foreach ($rooms_product_ids as $rooms_product_id)
        {
            wp_delete_post($rooms_product_id, true);
        }
    }
}

function odp_get_room_id($room_post_id)
{
    return esc_attr(get_post_meta($room_post_id, '_sku', true));
}

function odp_get_room_rates($room_post_id)
{
    return [
        (object)[
            'rate_plan_id' => 'jabce',
            'rate_plan_name' => 'Non-Refundable',
            'lowest_nightly_rate' => '580',
            'room_total' => '1180'
        ],
        (object)[
            'rate_plan_id' => '4355',
            'rate_plan_name' => 'Refundable',
            'lowest_nightly_rate' => '640',
            'room_total' => '1020'
        ],
    ];
}

function odp_get_room_page_url($room_product_id, $api_room_url)
{
    if(class_exists('WooCommerce') && $room_product_id)
    {
        return get_permalink($room_product_id);
    }

    return $api_room_url;
}

function odp_get_room_features($room_post_id)
{
    return html_entity_decode(get_post_meta($room_post_id, 'room_features', true));
}

function get_resources_total_price($product_id, $posted_resources)
{
    $resources = [];
    $resources_total = 0;

    $room_resources = get_post_meta($product_id, 'room_resources', true);
    $room_resources = !empty($room_resources) ? json_decode($room_resources, true) : [];

    $room_deposits = get_post_meta($product_id, 'room_deposits', true);
    $room_deposits = !empty($room_deposits) ? json_decode($room_deposits, true) : [];

    if(!empty($room_resources))
    {
        $resources = array_merge($resources, $room_resources);
    }

    if(!empty($room_deposits))
    {
        $resources = array_merge($resources, $room_deposits);
    }

    foreach ($posted_resources as $value)
    {
        $resources_total += isset($resources[$value]['price']) ? (float)$resources[$value]['price'] : 0;
    }

    return $resources_total;
}

function odp_get_hotel_term_by_room_product_id($room_product_id)
{
    $hotel_terms = get_the_terms($room_product_id, 'hotel');

    if(is_wp_error($hotel_terms) || empty($hotel_terms))
        return false;

    return $hotel_terms[0];
}

function odp_get_hotel_term_by_room_id($room_id)
{
    $room_product_id = odp_get_room_product_id_by_room_id($room_id);

    if(!$room_product_id)
        return false;

    return odp_get_hotel_term_by_room_product_id($room_product_id);
}


function odp_get_room_product_id_by_sku($sku)
{
    $args = array(
        'post_type' =>	'product',
        'meta_key' => '_sku',
        'meta_value' => $sku,
        'posts_per_page' => -1
    );
    $rooms_query = new WP_Query($args);

    if($rooms_query->have_posts())
    {
        return $rooms_query->posts[0]->ID;
    }

    return false;
}



//function get_room_page_id(){
//    $one_day_settings = get_option('1day_options');
//    return $one_day_settings['room_page_id'];
//}
//
//function get_room_page_url($property_code){
//    return get_permalink(get_room_page_id()) . '?room_id=' . $property_code;
//}