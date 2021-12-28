<?php

function odp_api_connect($api_url, $method = 'GET', $data = [])
{
    $method = mb_strtoupper($method);
    $api_url = $method == 'GET' ? $api_url . '?' . http_build_query($data) : $api_url;
    $body = $method == 'GET' ? null : json_encode($data);

    $response = wp_remote_request($api_url, [
        'headers'     => odp_get_header_for_api_request(),
        'method'      => $method,
        'body'        => $body
    ]);

    return [
        'state' => (!empty($response['response']['code']) && $response['response']['code'] == 200),
        'content' => json_decode($response['body'])
    ];
}