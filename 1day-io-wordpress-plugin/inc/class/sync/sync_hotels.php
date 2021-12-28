<?php

class ODP_Sync_Hotels
{
    private $rooms_api_ids = [];

    public function __construct()
    {
//        add_filter( 'cron_schedules', 'cron_add_five_min' );
//        function cron_add_five_min( $schedules ) {
//            $schedules['five_min'] = array(
//                'interval' => 10,
//                'display' => 'Раз в 5 минут'
//            );
//            return $schedules;
//        }
//
//        if( defined('DOING_CRON') && DOING_CRON ){
//            add_action('eventSyncHotels', [$this, 'do_event_sync_hotels']);
//        }

        add_action('init', function(){
            remove_action('init', __FUNCTION__);

            $this->do_event_sync_hotels();
        }, 10);
    }


    public function get_hotels_from_api()
    {
        $result = odp_api_connect(ODP_API_URL_SYNC, 'get', []);

        if(!$result['state']) return false;

        return !empty($result['content']->data) ? $result['content']->data : false;
    }


    private function sync_hotels_and_rooms($hotels_from_api)
    {

        $hotel_terms = get_terms([
            'hide_empty' => false,
            'taxonomy' => 'hotel',
        ]);

        $hotels_api_ids = array_map(function($item){ return $item->property_code; }, $hotels_from_api);

        foreach($hotel_terms as $hotel_term)
        {
            $hotel_property_code = odp_get_hotel_property_code($hotel_term->term_id);

            if(!$hotel_property_code || !in_array($hotel_property_code, $hotels_api_ids))
            {
                odp_delete_rooms_from_hotel($hotel_term->term_id);
                wp_delete_term($hotel_term->term_id, 'hotel');
            }
        }

        foreach($hotels_from_api as $hotel_from_api)
        {
            $hotel_term_id = 1000;

            $hotel_term = odp_get_hotel_term_by_property_code($hotel_from_api->property_code);

            if (!$hotel_term) {

                $term = wp_insert_term(
                    sanitize_text_field($hotel_from_api->name),
                    'hotel',
                    [
                        'description' => ''
                    ]
                );

                if(is_wp_error($term) || empty($term['term_id'])) continue;

                update_term_meta($term['term_id'], 'property_code', sanitize_text_field($hotel_from_api->property_code));

                $hotel_term_id = $term['term_id'];

            }else{
                wp_update_term($hotel_term->term_id, 'hotel', [
                    'name' => sanitize_text_field($hotel_from_api->name)
                ]);

                $hotel_term_id = $hotel_term->term_id;
            }

            $this->sync_rooms($hotel_term_id, $hotel_from_api);
        }

        $this->clean_rooms();

    }

    private function sync_rooms($hotel_term_id, $hotel_from_api)
    {
        foreach ($hotel_from_api->room_types as $room_from_api)
        {
            $this->rooms_api_ids[] = (string) $room_from_api->id;

            $room_oneday_id = odp_get_room_product_id_by_room_id($room_from_api->id);

            if($room_oneday_id) {
                wp_update_post([
                    'ID' => sanitize_text_field($room_oneday_id),
                    'post_title' => sanitize_text_field($room_from_api->name)
                ]);
            }else{
                $room_id = wp_insert_post([
                    'post_type' => 'product',
                    'post_title' => sanitize_text_field($room_from_api->name),
                    'post_content' => '',
                    'post_status'   => 'publish',
                    'meta_input' => [
                        '_price' => odp_get_room_lowest_rate(sanitize_text_field($room_from_api)),
                        '_sku' => sanitize_text_field($room_from_api->id),
                        'property_code' => sanitize_text_field($hotel_from_api->property_code)
                    ],
                ]);

                if(!is_wp_error($room_id) && !empty($room_id))
                {
                    wp_set_object_terms(sanitize_text_field($room_id), [$hotel_term_id], 'hotel');
                    wp_set_object_terms(sanitize_text_field($room_id), 'room','product_type');
                }
            }
        }

    }

    private function clean_rooms()
    {
        $rooms_product_ids = odp_get_rooms_ids();

        if($rooms_product_ids)
        {
            foreach($rooms_product_ids as $room_product_id)
            {
                $room_id = odp_get_room_id($room_product_id);

                if(!in_array($room_id, $this->rooms_api_ids))
                {
                    wp_delete_post($room_product_id);
                }
            }
        }
    }

    public function do_event_sync_hotels()
    {
        $hotels_from_api = $this->get_hotels_from_api();

        if(!$hotels_from_api) return;

        $this->sync_hotels_and_rooms($hotels_from_api);

    }


    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}

ODP_Sync_Hotels::getInstance();