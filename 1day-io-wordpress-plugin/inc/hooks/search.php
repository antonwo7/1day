<?php

add_action('init', function(){
    if(is_admin()) return;
    if(!isset($_GET['search_submit'])) return;

    $filter_id = !empty($_GET['filter_id']) ? sanitize_text_field($_GET['filter_id']) : '';

    if(empty($filter_id)) return;

    $property_code = !empty($_GET['property_code']) ? sanitize_text_field($_GET['property_code']) : '';

    $start_date = !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    $guests = !empty($_GET['guests']) ? sanitize_text_field($_GET['guests']) : '';
    $sort = !empty($_GET['sort']) ? sanitize_text_field($_GET['sort']) : '';

//    sc('property_code', $property_code);
    odp_sc('start_date', $start_date, $filter_id);
    odp_sc('end_date', $end_date, $filter_id);
    odp_sc('guests', $guests, $filter_id);
    odp_sc('sort', $sort, $filter_id);

});