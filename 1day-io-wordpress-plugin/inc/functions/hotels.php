<?php

function odp_get_hotel_lowest_rate($hotel)
{
    $lowest_hotel_nightly_rate = false;

    if(!empty($hotel->room_types))
    {
        $lowest_hotel_nightly_rates = [];

        foreach ($hotel->room_types as $room)
        {
            foreach ($room->rates as $rate){
                $lowest_hotel_nightly_rates[] = $rate->lowest_nightly_rate;
            }
        }

        $lowest_hotel_nightly_rate = min($lowest_hotel_nightly_rates);
    }

    return $lowest_hotel_nightly_rate;
}

function odp_get_hotel_term_by_property_code($property_code)
{
    $hotel_terms = get_terms([
        'hide_empty' => false,
        'meta_query' => [
            [
                'key' => 'property_code',
                'value' => $property_code,
                'compare' => '='
            ]
        ],
        'taxonomy'  => 'hotel',
    ]);

    if(!empty($hotel_terms) && !is_wp_error($hotel_terms)){
        return $hotel_terms[0];
    }

    return false;
}


function odp_get_hotel_property_code($term_id)
{
    return esc_attr(get_term_meta($term_id, 'property_code', true));
}

function odp_get_hotel_img($hotel_term_id)
{
    $img = esc_attr(get_term_meta($hotel_term_id, 'hotel_image', true));

    if(empty($img)) return false;

    return $img;
}

function odp_get_hotel_page_id()
{
    $one_day_settings = get_option('1day_options');
    return esc_attr($one_day_settings['hotel_page_id']);
}

function odp_get_hotel_page_url($property_code)
{
    return get_permalink(odp_get_hotel_page_id()) . "?property_code={$property_code}";
}

function odp_get_hotel_page_items_count()
{
    $one_day_settings = get_option('1day_options');
    $page_items_count = $one_day_settings['page_items_count'];
    return empty($page_items_count) ? 10 : $page_items_count;
}

function odp_get_hotel_short_description($hotel_term_id)
{
    return html_entity_decode(get_term_meta($hotel_term_id, 'hotel_short_description', true));
}

function odp_get_hotel_description($hotel_term_id)
{
    return html_entity_decode(get_term_meta($hotel_term_id, 'hotel_description', true));
}

function odp_get_current_hotel_property_code()
{
    global $wp_query;

    return is_tax( 'hotel' ) ? odp_get_hotel_property_code($wp_query->get_queried_object()->term_id) : '';
}

function odp_get_filter_property_code($filter_id){

}