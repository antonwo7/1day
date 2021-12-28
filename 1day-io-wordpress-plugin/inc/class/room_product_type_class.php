<?php

class WC_Product_Room extends WC_Product
{
    public function __construct($product)
    {
        parent::__construct($product);
    }

    public function get_type()
    {
        return 'room';
    }

    public function get_admin_room_resource($args = [])
    {
        ob_start();

        include ODP_ONEDAY_PLUGIN_DIR . '/inc/templates/admin/rooms/room_resource.php';

        $output = ob_get_contents();
        ob_clean();

        return $output;
    }

    public function get_admin_room_deposit($args = [])
    {
        ob_start();

        include ODP_ONEDAY_PLUGIN_DIR . '/inc/templates/admin/rooms/room_deposit.php';

        $output = ob_get_contents();
        ob_clean();

        return $output;
    }
}