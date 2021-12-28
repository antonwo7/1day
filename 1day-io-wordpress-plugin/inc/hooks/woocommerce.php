<?php

function odp_confirm_booking($order_id)
{
    $booking_data = get_post_meta($order_id, 'odp_booking_data', true);

    if(!$booking_data)
        return;

    foreach ($booking_data->reservations->reservation as $index => $reservation)
    {
        if(!isset($reservation->status) || !isset($reservation->confirmed))
            continue;

        $booking_data->reservations->reservation[$index]->status = 'modify';
        $booking_data->reservations->reservation[$index]->confirmed = true;
    }


    $result = odp_api_connect(ODP_API_URL_BOOKING, 'post', $booking_data);
}

add_action('woocommerce_order_status_on-hold', 'odp_confirm_booking', 10, 1);
add_action('woocommerce_order_status_processing', 'odp_confirm_booking', 10, 1);

