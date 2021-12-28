<?php

//add_filter('the_content', 'filter_the_content_in_the_main_loop', 1);
//
//function filter_the_content_in_the_main_loop($content) {
//
//    global $post;
//
//    $product = wc_get_product($post->ID);
//
//    if ( is_singular() && is_main_query() && get_post_type($post->ID) == 'product' && $product->is_type('room') ) {
//        ob_start();
//
//        require_once  __DIR__ . '/../templates/room.php';
//
//        $content = ob_get_contents();
//        ob_clean();
//
//        return $content;
//    }
//
//    return $content;
//}