<?php

class ODP_Check_Hotels{

    private $hotels_to_update = [];
    private $rooms_to_update = [];
    private $api_data;

    function __construct()
    {
        $this->api_data = $this->get_data_from_api();

        if($this->api_data)
        {
            $this->check_hotels();
            $this->check_rooms();
        }

    }

    private function get_data_from_api()
    {
        $result = odp_api_connect(ODP_API_URL_SYNC, 'get', []);

        if(!$result['state']) return false;

        return !empty($result['content']->data) ? $result['content']->data : false;
    }

    private function check_hotels()
    {
        $hotel_terms = get_terms([
            'hide_empty' => false,
            'taxonomy' => 'hotel',
        ]);

        $hotels_api_ids = array_map(function($item){ return $item->property_code; }, $this->api_data);

        foreach($hotel_terms as $hotel_term)
        {
            $hotel_property_code = odp_get_hotel_property_code($hotel_term->term_id);

            if(!$hotel_property_code || !in_array($hotel_property_code, $hotels_api_ids))
            {
                $this->hotels_to_update['delete'][] = $hotel_property_code;
            }
        }

        foreach($hotels_api_ids as $hotels_api_id)
        {
           $hotel_term = odp_get_hotel_term_by_property_code($hotels_api_id);

            if(!$hotel_term)
            {
                $this->hotels_to_update['add'][] = $hotels_api_id;
            }
        }
    }

    private function check_rooms()
    {
        $rooms_api_ids = [];

        foreach ($this->api_data as $hotel)
        {
            foreach ($hotel->room_types as $room)
            {
                $rooms_api_ids[] = (string) $room->id;

                $room_oneday_id = odp_get_room_product_id_by_room_id($room->id);

                if(!$room_oneday_id && !empty($room->name))
                {
                    $this->rooms_to_update['add'][] = $room->id;
                }
            }
        }

        $rooms_product_ids = odp_get_rooms_ids();

        if($rooms_product_ids)
        {
            foreach($rooms_product_ids as $room_product_id)
            {
                $room_id = odp_get_room_id($room_product_id);

                if(!$room_id || !in_array($room_id, $rooms_api_ids))
                {
                    $this->rooms_to_update['delete'][] = $room_product_id;
                }
            }
        }
    }

    public function get_review()
    {
        return [
            'hotels' => $this->hotels_to_update,
            'rooms' => $this->rooms_to_update,
        ];
    }
}