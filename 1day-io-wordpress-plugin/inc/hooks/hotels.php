<?php

//add_filter('document_title_parts', function($title){
//
//    if(get_queried_object_id() == get_hotel_page_id() && !empty($_GET['property_code']))
//    {
//        $property_code = $_GET['property_code'];
//
//        $hotel_term = get_hotel_term_by_property_code($property_code);
//
//        if(!is_wp_error($hotel_term) && !empty($hotel_term))
//        {
//            $title['title'] = $hotel_term->name;
//        }
//
//    }
//
//    return $title;
//}, 10);
