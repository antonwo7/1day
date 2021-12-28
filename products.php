<?php

function check_field_selected($value, $get){
    echo $value == $get ? 'selected' : '';
}



function get_products($logged_user_id, $vendor_id, $type, $count, $search, $category, $tag, $difficulty, $duration, $sort, $exclude_products = [], $exclude_terms = [], $include_terms = [], $only_simple_products = false, $offset = 0){
    global $wpdb;

    $count = (empty($count) || ($count == 1)) ? 5 : intval($count);

    $vendor_id = $vendor_id === 0 ? '' : $vendor_id;

    switch ($type) {
        case 'popular':
            $sort = "popularity_{$sort}";
            break;
        case 'trending':
            $sort = "trending_{$sort}";
            break;
        case 'toprated':
            $sort = "rating_{$sort}";
            break;
        case 'all':
            $sort = "rand";
            break;
        default:
            $sort = "newness_{$sort}";
            break;
    }

    $terms = [];
    if(!empty($category)) $terms[] = $category;
    if(!empty($tag)) $terms[] = $tag;
    if(!empty($difficulty)) $terms[] = $difficulty;
    if(!empty($duration)) $terms[] = $duration;
    if(!empty($include_terms)){
        foreach($include_terms as $term){
            $terms[] = $term;
        }
    }

    $terms_count = count($terms);

    $terms = implode(',', $terms);

    $use_orders = $logged_user_id != 0;

    if(!$use_orders){
        $sort = empty($sort) ? 'newness_desc' : $sort;
        $order_by = explode('_', $sort)[0];
        $order = explode('_', $sort)[1];
    }else{
        $order_by = 'order';
        $order = 'desc';
    }


    $exclude_products = !empty($exclude_products) ? implode(',', $exclude_products) : 0;
    $exclude_terms = !empty($exclude_terms) ? implode(',', $exclude_terms) : false;

    $query = "
            SELECT 
                SQL_CALC_FOUND_ROWS
                CONVERT(p.ID, UNSIGNED INTEGER) as product_id,
                p.post_title as product_title,
                CONVERT(ifnull(pmr.meta_value, 0), UNSIGNED INTEGER) as product_rating,
                CONVERT(ifnull(pmv.meta_value, 0), UNSIGNED INTEGER) as product_views,
                CONVERT(orders.orders_count, UNSIGNED INTEGER) as product_orders,
                CONVERT(st.vendor_id, UNSIGNED INTEGER) as vendor_id
                
                " . (!$use_orders ? "" : ", 
                CONVERT(product_orders.order_id, UNSIGNED INTEGER) as order_id,
                product_orders.created as order_date ")  . "
                
       
            FROM wp_posts as p
   
            LEFT JOIN wp_wcfm_marketplace_store_taxonomies as st ON st.product_id = p.ID
            
            LEFT JOIN wp_users as u ON u.ID = st.vendor_id

            LEFT JOIN wp_postmeta as pmr 
                ON pmr.post_id = p.ID AND (pmr.meta_key = '_wc_average_rating')
            
            LEFT JOIN wp_postmeta as pmv
                ON pmv.post_id = p.ID AND (pmv.meta_key = '_wcfm_product_views')    
            
            " . (!$use_orders ? "" : " 
            LEFT JOIN wp_postmeta as pmvideo
            ON pmvideo.post_id = p.ID ")  . "
            
            LEFT JOIN (
                SELECT count(o.order_id) as orders_count, o.product_id
                FROM wp_wcfm_marketplace_orders o
                GROUP BY o.order_id
            ) as orders ON orders.product_id = p.ID
            
            " . (!$use_orders ? "" : " 
            LEFT JOIN wp_wcfm_marketplace_orders as product_orders
            ON product_orders.product_id = p.ID")  . "
            
            " . (!$only_simple_products ? "" : " 
            LEFT JOIN wp_postmeta as pmsimple
            ON 
                pmsimple.post_id = p.ID 
                AND pmsimple.meta_key = 'wooco_components'
            ")  . "
           
            LEFT JOIN wp_usermeta as store_name
            ON store_name.user_id = u.ID
                
            WHERE 1 = 1
            
            AND p.post_type = 'product'
            
            " . (empty($search) ? "" : " 
            AND 
            ( 
                p.post_title LIKE '%{$search}%' OR 
                (
                    store_name.meta_key = 'store_name' AND store_name.meta_value LIKE '%{$search}%' OR
                    store_name.meta_key = 'first_name' AND store_name.meta_value LIKE '%{$search}%' OR
                    store_name.meta_key = 'full_name' AND store_name.meta_value LIKE '%{$search}%' OR
                    store_name.meta_key = 'last_name' AND store_name.meta_value LIKE '%{$search}%' OR
                    store_name.meta_key = 'nickname' AND store_name.meta_value LIKE '%{$search}%'
                )
            )")  . "
            
            AND p.ID NOT IN ({$exclude_products}) 
            
            " . (empty($vendor_id) ? "" : " AND st.vendor_id LIKE '{$vendor_id}'")

            . (!$use_orders ? "" : " AND product_orders.customer_id = {$logged_user_id}
                AND product_orders.order_status = 'successful' ")  . "
            
            " . ($terms_count == 0 ? "" : " AND p.ID IN (
                SELECT terms.object_id
                FROM wp_term_relationships terms
                WHERE 
                    terms.term_taxonomy_id IN ({$terms})
                GROUP BY terms.object_id 
                HAVING count(1)={$terms_count}
            )")  . "
            " . (!$exclude_terms ? "" : " AND p.ID NOT IN (
                SELECT terms.object_id
                FROM wp_term_relationships terms
                WHERE
                    terms.term_taxonomy_id IN ({$exclude_terms})
                GROUP BY terms.object_id
            )")  . "
            
            " . (!$use_orders ? "" : " 
            AND pmvideo.meta_key = 'full_video' ")  . "
            
            " . (!$only_simple_products ? "" : " 
            AND pmsimple.meta_value IS NULL ")  . "
         
            
            GROUP BY product_id
            ORDER BY
                " . ($order_by == 'order' ? "order_id {$order}" : "") . "
                " . ($order_by == 'newness' ? "product_id {$order}" : "") . "
                " . ($order_by == 'trending' ? "product_views {$order}" : "") . "
                " . ($order_by == 'rating' ? "product_rating {$order}" : "") . "
                " . ($order_by == 'popularity' ? "product_orders {$order}" : "") . "
                " . ($order_by == 'rand' ? "RAND()" : "") . "
            LIMIT {$count}
            OFFSET {$offset}";

    $count_query = "select FOUND_ROWS();";

    $results = $wpdb->get_results($query, ARRAY_A);

    $count = $wpdb->get_results($count_query, ARRAY_A)[0]["FOUND_ROWS()"];

    $products = [];

    foreach ($results as $item){

        $products[] = [
            'product_id' => $item['product_id'],
            'vendor_id' => $item['vendor_id'],
            'order' => [
                'order_id' => (!empty($item['order_id']) ? $item['order_id'] : ''),
                'order_date' => (!empty($item['order_date']) ? $item['order_date'] : ''),
            ]
        ];
    }

    return [ 'products' => $products, 'count' => $count ];
}


function get_product_rating($id){
    $product = wc_get_product($id);
    $rating = strip_tags($product->get_average_rating());
    return $rating ? ( ( $rating / 5 ) * 100 - 1 ) : 0;
}

function get_order_url($order_id){
    //return get_wcfm_view_order_url($order_id, wc_get_order($order_id));
    $order = wc_get_order($order_id);
    return esc_url( $order->get_view_order_url() );
}

function get_product_terms_for_widgets($term_type)
{
    $options = [];

    $categories = get_terms( array(
        'taxonomy' => $term_type,
        'hide_empty' => false,
    ) );

    foreach ($categories as $category) {
        $options[$category->term_id] = $category->name;
    }
    return $options;
}
