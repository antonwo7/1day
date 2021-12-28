<?php

class ODP_Init_Room_Product_Type
{

    public function __construct()
    {
        /**
         * 1. Register a room product type in WooCommerce
         */
        add_action('init', [ $this, 'add_room_product_type_class' ]);

        /**
         * 2. Add room product type to the Product type Drop Down
         */
        add_filter('product_type_selector', [ $this, 'add_room_product_type' ]);

        /**
         * 3. Add a new tab for room product type
         */
        add_filter('woocommerce_product_data_tabs', [ $this, 'room_tab' ]);

        /**
         * 4. Hide product tabs if product type is room
         */
        add_action('woocommerce_product_data_tabs', [ $this, 'remove_product_tab_if_room' ]);

        /**
         * 5. Add fields / settings to the room tab
         */
        add_action('woocommerce_product_data_panels', [ $this, 'room_options_product_tab_content' ]);

        /**
         * 6. Saving the room product type Settings
         */
        add_action('woocommerce_process_product_meta', [ $this, 'save_room_fields' ]);

        /**
         * 7. Room rates and resources output
         */
        add_action('woocommerce_after_single_product_summary', [ $this, 'room_rates_output' ], 10);
        add_action('woocommerce_after_single_product_summary', [ $this, 'room_resources_output' ], 10);
        add_action('woocommerce_after_single_product_summary', [ $this, 'room_booking_button_output' ], 10);

        /**
         * 8. Redirect to cart after additing in cart
         */
//        add_action('option_woocommerce_cart_redirect_after_add', function(){
//            return 'yes';
//        });

        /**
         * 9. Make all the rooms parchasables
         */
        add_action('woocommerce_is_purchasable', function($purchasable, $product){
            return !$product->is_type('room') ? $purchasable : true;
        }, 10, 2);

        /**
         * 10. Add to cart validation
         */
        add_filter('woocommerce_add_to_cart_validation', [ $this, 'add_to_cart_validation' ], 10, 5);

        add_action('woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 20, 1);

//        add_filter('woocommerce_is_sold_individually', [ $this, 'custom_is_sold_individually' ], 10, 2);

        add_filter('woocommerce_cart_item_name', [ $this, 'add_rate_plan_to_product_name' ], 10, 3);

        add_action('woocommerce_single_product_summary', [ $this, 'clean_single_product_summary' ], 10);

        add_action('woocommerce_checkout_order_processed', [ $this, 'room_checkout_process' ], 10, 3);

    }

    public function room_booking_button_output()
    {
        global $product;

        if(!$product || !$product->is_type('room'))
            return;

        $room_id = odp_get_room_id($product->get_id());

        require_once __DIR__ . '/../../templates/rooms/booking_button.php';
    }


    public function room_resources_output()
    {
        global $product;

        if(!$product || !$product->is_type('room'))
            return; ?>

        <div class="room_resources_wrapper"><?php

            $room_resources = get_post_meta($product->get_id(), 'room_resources', true);
            $room_resources_title = esc_attr__('Choose room add-ons', '1day');

            include ODP_ONEDAY_PLUGIN_DIR . '/inc/templates/rooms/resources.php';

            $room_resources = get_post_meta($product->get_id(), 'room_deposits', true);
            $room_resources_title = esc_attr__('Choose room deposits', '1day');

            include ODP_ONEDAY_PLUGIN_DIR . '/inc/templates/rooms/resources.php'; ?>

        </div>

    <?php
    }

    public function room_data_output()
    {
        global $product;

        if(!$product || !$product->is_type('room'))
            return;

        $room_checkin = get_post_meta($product->get_id(), 'room_checkin', true);
        $room_checkout = get_post_meta($product->get_id(), 'room_checkout', true);

        $start_date = !empty(odp_gc('start_date')) ?
            date_create(odp_gc('start_date'))->format('D, M j Y') : '';

        $end_date = !empty(odp_gc('end_date')) ?
            date_create(odp_gc('end_date'))->format('D, M j, Y') : '';

        $guests = !empty($filter_id) ? odp_gc('guests') : '';

        $hotel_term = odp_get_hotel_term_by_room_product_id($product->get_id());
        $hotel_name = !empty($hotel_term) ? $hotel_term->name : '';

        include ODP_ONEDAY_PLUGIN_DIR . '/inc/templates/rooms/data.php';
    }

    public function clean_single_product_summary()
    {
        if(is_admin()) return;

        global $product;

        if($product && $product->is_type('room'))
        {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 20);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);


            add_action('woocommerce_single_product_summary', [ $this, 'room_data_output' ], 20);

        }
    }

    public function add_rate_plan_to_product_name($item_name, $cart_item, $cart_item_key)
    {
        return
            !empty($cart_item['rate_plan_name']) && $cart_item['data']->is_type('room')
                ? $item_name . " (" . esc_attr__('Rate plan:', '1day') . " " . esc_attr__($cart_item['rate_plan_name']) . ")"
                : $item_name;
    }

    public function before_calculate_totals($cart)
    {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return;

        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
            return;

        foreach ($cart->get_cart() as $cart_item) {
            if(!$cart_item['data']->is_type('room')) continue;

            $room_price = $cart_item['rate_plan_price'];

            if(!empty($cart_item['room_resources']))
            {
                $room_price += get_resources_total_price($cart_item['data']->get_id(), explode('||', $cart_item['room_resources']));
            }

            $cart_item['data']->set_price($room_price);
        }
    }

    public function custom_is_sold_individually($result, $product)
    {
        $found = false;

        foreach (WC()->cart->get_cart() as $ci_key => $ci)
        {
            if(!$ci['data']->is_type('room')) continue;

            if(!empty($ci['rate_plan_id']) && !empty($_POST['rate_plan_id']) && $ci['rate_plan_id'] == $_POST['rate_plan_id'] && !empty($_POST['quantity']))
            {
                $found = true;
                break;
            }
        }

        return $product->is_type('room') ? !$found : true;
    }

    public function add_to_cart_validation($passed, $product_id, $quantity)
    {
        $product = wc_get_product($product_id);

        if(!$product || !$product->is_type('room')) return $passed;

        $start_date = odp_gc('start_date');
        $end_date = odp_gc('end_date');
        $guests = odp_gc('guests');
        $room_type_id = !empty($_POST['room_type_id']) ? sanitize_text_field($_POST['room_type_id']) : '';
        $rate_plan_id = !empty($_POST['rate_plan_id']) ? sanitize_text_field($_POST['rate_plan_id']) : '';
        $room_resources = !empty($_POST['room_resources']) ? sanitize_text_field($_POST['room_resources']) : '';

        if(empty($start_date) || empty($end_date) || empty($guests) || empty($rate_plan_id)){
            wc_add_notice(esc_attr__('Session expired', '1day'), 'error');
            return false;
        }

        $args = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'guests' => $guests,
            'room_type_id' => $room_type_id
        ];

        $result = odp_api_connect(ODP_API_URL, 'get', $args);

        $room = !empty($result['content']->data[0]->room_types[0]) ? $result['content']->data[0]->room_types[0] : false;

        if(!$room)
        {
            wc_add_notice(esc_attr__('Incorrect data from API', '1day'), 'error');
            return false;
        }

        $in_cart = false;

        $cart_items = WC()->cart->cart_contents;

        if(!empty($cart_items))
        {
            foreach ($cart_items as $cart_item_key => $cart_item)
            {
                if(
                    $cart_item['room_type_id'] == $room_type_id &&
                    $cart_item['rate_plan_id'] == $rate_plan_id
                ){
                    $in_cart = true;
                    break;
                }
            }
        }

        if($in_cart)
        {
            wc_add_notice(esc_attr__('The room is already in the cart', '1day'), 'error');
            return false;
        }

        if($room->quantity < $quantity)
        {
            wc_add_notice(esc_attr__('All these rooms are booked out', '1day'), 'error');
            return false;
        }

        if(empty($room->rates))
        {
            wc_add_notice(esc_attr__('All these rooms are booked out', '1day'), 'error');
            return false;
        }

        $rate = array_filter($room->rates, function($rate) use ($rate_plan_id){
            return $rate->rate_plan_id == $rate_plan_id;
        });

        if(empty($rate))
        {
            wc_add_notice(esc_attr__('There no rate plans for this room', '1day'), 'error');
            return false;
        }

        $rate = array_values($rate);

        if(empty($rate[0]->room_total))
        {
            wc_add_notice(esc_attr__('Incorrect data from API', '1day'), 'error');
            return false;
        }

        add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id) use ($rate, $room_type_id, $start_date, $end_date, $room_resources, $guests) {

            remove_action('woocommerce_add_cart_item_data', __FUNCTION__);

            $_product = wc_get_product($product_id);

            if ($_product && $_product->is_type('room'))
            {
                if (!empty($_POST['rate_plan_id']))
                {
                    $cart_item_data['rate_plan_id'] = sanitize_text_field($_POST['rate_plan_id']);
                    $cart_item_data['rate_plan_name'] = sanitize_text_field($_POST['rate_plan_name']);
                    $cart_item_data['lowest_nightly_rate'] = $rate[0]->lowest_nightly_rate;
                    $cart_item_data['rate_plan_price'] = $rate[0]->room_total;

                    $cart_item_data['room_type_id'] = $room_type_id;

                    $cart_item_data['room_guests'] = $guests;

                    $cart_item_data['room_start_date'] = $start_date;
                    $cart_item_data['room_end_date'] = $end_date;

                    $cart_item_data['room_resources'] = $room_resources;

                }
            }

            return $cart_item_data;
        }, 10, 2);


        return true;
    }

    public function add_room_product_type_class()
    {
        include_once __DIR__ . '/../room_product_type_class.php';
    }

    public function add_room_product_type($type)
    {
        $type['room'] = esc_attr__('Room', '1day');
        return $type;
    }

    public function room_tab($tabs)
    {
        $tabs['room'] = array(
            'label' => esc_attr__('Room', '1day'),
            'target' => 'room_options',
            'class' => ('show_if_room'),
        );
        return $tabs;
    }

    public function remove_product_tab_if_room($tabs)
    {
        $tabs['attribute']['class'][] = 'hide_if_room';
        $tabs['shipping']['class'][] = 'hide_if_room';
        $tabs['linked_product']['class'][] = 'hide_if_room';
        $tabs['inventory']['class'][] = 'show_if_room';
        return $tabs;
    }

    public function room_options_product_tab_content()
    {
        global $post;
        $product = wc_get_product($post->ID); ?>

        <div id='room_options' class='panel woocommerce_options_panel'>


            <p class="form-field">
                <label for="room_features"><?php echo esc_attr__('Room features', '1day'); ?></label>

                <?php $room_features = get_post_meta($post->ID, 'room_features', true);

                wp_editor(
                    html_entity_decode($room_features),
                    'room_features',
                    $settings = [
                        'wpautop' => 1,
                        'media_buttons' => 1,
                        'textarea_name' => 'room_features',
                        'textarea_rows' => 8,
                        'quicktags' => 1,
                    ]
                ); ?>
            </p>

            <p class="form-field">
                <label for="room_checkin"><?php echo esc_attr__('Room checkin', '1day'); ?></label>
                <?php $room_checkin = get_post_meta($post->ID, 'room_checkin', true); ?>
                <input type="text" value="<?php echo esc_attr($room_checkin); ?>" name="room_checkin"/>
            </p>

            <p class="form-field">
                <label for="room_checkout"><?php echo esc_attr__('Room checkout', '1day'); ?></label>
                <?php $room_checkout = get_post_meta($post->ID, 'room_checkout', true); ?>
                <input type="text" value="<?php echo esc_attr($room_checkout); ?>" name="room_checkout"/>
            </p>

            <div class="form-field form_field_resources">

                <h4><?php echo esc_attr__('Room resources', '1day'); ?></h4>

                <div class="room_resources">
                    <?php if($product && $product->is_type('room')) : ?>

                        <?php $room_resources = get_post_meta($post->ID, 'room_resources' , true); ?>

                        <?php if(!empty($room_resources)) : $room_resources = json_decode($room_resources, true); ?>
                            <?php if(!empty($room_resources) && is_array($room_resources)) : ?>
                                <?php foreach ($room_resources as $room_resource) : ?>
                                    <?php echo $product->get_admin_room_resource($room_resource); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

                <div class="options_group">
                    <div class="footer">
                        <a class="button add_room_resource">
                            Add resource
                        </a>
                    </div>
                </div>
            </div>

            <div class="form-field form_field_deposits">

                <h4><?php echo esc_attr__('Room deposits', '1day'); ?></h4>

                <div class="room_deposits">
                    <?php if($product && $product->is_type('room')) : ?>

                        <?php $room_deposits = get_post_meta($post->ID, 'room_deposits' , true); ?>

                        <?php if(!empty($room_deposits)) : $room_deposits = json_decode($room_deposits, true); ?>
                            <?php if(!empty($room_deposits) && is_array($room_deposits)) : ?>
                                <?php foreach ($room_deposits as $room_deposit) : ?>
                                    <?php echo $product->get_admin_room_deposit($room_deposit); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

                <div class="options_group">
                    <div class="footer">
                        <a class="button add_room_deposit">
                            Add deposit
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php $resource_to_add = str_replace(['\n\r', '\n', '\r', PHP_EOL], '', $product->get_admin_room_resource());//echo preg_replace('/\s{2,}/', '', $product->get_admin_room_resource()); ?>
        <?php $deposit_to_add = str_replace(['\n\r', '\n', '\r', PHP_EOL], '', $product->get_admin_room_deposit());//echo preg_replace('/\s{2,}/', '', $product->get_admin_room_resource()); ?>

        <script>
            jQuery(document).ready(function ($) {
                $(".room_resources").sortable();
                $(".room_resources").disableSelection();

                $(document).on('click', '.remove_room_resource', function () {
                   $(this).closest('.room_resource').remove();
                });
                $('.add_room_resource').click(function () {
                    $('.room_resources').append('<?php echo $resource_to_add; ?>');
                });
            });

            jQuery(document).ready(function ($) {
                $(".room_deposits").sortable();
                $(".room_deposits").disableSelection();

                $(document).on('click', '.remove_room_deposit', function () {
                    $(this).closest('.room_deposit').remove();
                });
                $(document).on('click', '.add_room_deposit', function () {
                    $('.room_deposits').append('<?php echo $deposit_to_add; ?>');
                });

                $('.room_resources, .room_deposits').keydown(function(e){
                    if (e.keyCode === 65 && e.ctrlKey) {
                        e.target.select()
                    }

                })
            });
        </script>

        <style>
            #room_options h4{
                margin:0 10px 15px 10px;
            }
            .form_field_resources, .form_field_deposits{
                margin-bottom:20px;
                background: #cccccc;
                padding:20px 0 30px;
            }
            .room_resource, .room_deposit {
            }
            .room_resource .header, .room_deposit .header {
                display: flex;
                margin: 14px 0;
            }
            .room_resource .room_resource_inputs, .room_deposit .room_deposit_inputs{
                display:flex;
            }
            .room_resource .room_resource_inputs .header input, .room_deposit .room_deposit_inputs .header input {
                width: 100%;
            }
            .room_resource .header > div:first-child, .room_deposit .header > div:first-child {
                flex-grow: 1;
            }
            .room_resource .header > div, .room_deposit .header > div {
                padding: 0 10px;
            }
            .add_room_resource, .add_room_deposit {
                margin: 20px 10px 0 10px !important;
            }

        </style>

        <?php
    }

    public function save_room_fields($post_id)
    {
        $property_code = !empty($_POST['property_code']) ? sanitize_text_field($_POST['property_code']) : '';
        update_post_meta($post_id, 'property_code', $property_code);

        if (!empty($_POST['room_features']))
        {
            update_post_meta(
                $post_id,
                'room_features',
                sanitize_text_field(htmlentities($_POST['room_features']))
            );
        }

        if (!empty($_POST['room_checkin']))
        {
            update_post_meta(
                $post_id,
                'room_checkin',
                sanitize_text_field(htmlentities($_POST['room_checkin']))
            );
        }

        if (!empty($_POST['room_checkout']))
        {
            update_post_meta(
                $post_id,
                'room_checkout',
                sanitize_text_field(htmlentities($_POST['room_checkout']))
            );
        }

        if (!empty($_POST['room_resource_name']) && !empty($_POST['room_resource_value']) && !empty($_POST['room_resource_price']))
        {
            $room_resources = [];

            foreach($_POST['room_resource_name'] as $key => $room_resource_name)
            {
                $value = isset($_POST['room_resource_value'][$key]) ? sanitize_text_field($_POST['room_resource_value'][$key]) : '';
                $price = isset($_POST['room_resource_price'][$key]) ? sanitize_text_field($_POST['room_resource_price'][$key]) : '';

                if(!empty($value) && $price != '')
                {
                    $room_resources[$value] = [
                        'name' => $room_resource_name,
                        'value' => $value,
                        'price' => $price,
                    ];
                }
            }

            update_post_meta(
                $post_id,
                'room_resources',
                wp_json_encode($room_resources)
            );
        }

        if (!empty($_POST['room_deposit_name']) && !empty($_POST['room_deposit_value']) && !empty($_POST['room_deposit_price']))
        {
            $room_deposits = [];

            foreach($_POST['room_deposit_name'] as $key => $room_deposit_name)
            {
                $value = isset($_POST['room_deposit_value'][$key]) ? sanitize_text_field($_POST['room_deposit_value'][$key]) : '';
                $price = isset($_POST['room_deposit_price'][$key]) ? sanitize_text_field($_POST['room_deposit_price'][$key]) : '';

                if(!empty($value) && $price != '')
                {
                    $room_deposits[$value] = [
                        'name' => $room_deposit_name,
                        'value' => $value,
                        'price' => $price,
                    ];
                }
            }

            update_post_meta(
                $post_id,
                'room_deposits',
                wp_json_encode($room_deposits)
            );
        }
    }

    public function room_rates_output()
    {
        global $product;
        if(!$product->is_type('room')) return;

        $rates = [];

        $room_post_id = get_the_ID();

        if(!empty($room_post_id))
        {
            $rates = odp_get_room_rates($room_post_id);
        }

        require_once __DIR__ . '/../../templates/rooms/rates/rates-table.php';
    }

    private function prepare_cart_items_for_booking($order_id)
    {
        $order = wc_get_order($order_id);

        if(!$order)
            return false;

        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $company = $order->get_billing_company();
        $country = $order->get_billing_country();
        $address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
        $postcode = $order->get_billing_postcode();
        $city = $order->get_billing_city();
        $phone = $order->get_billing_phone();
        $email = $order->get_billing_email();

        $cart_items = WC()->cart->cart_contents;

        if(empty($cart_items))
            return false;

        $booking_data = (object)[
            'reservations' => (object)[
                'reservation' => []
            ]
        ];

        foreach($cart_items as $cart_item_key => $cart_item)
        {
            $room_type_id = !empty($cart_item['room_type_id']) ? $cart_item['room_type_id'] : '';
            $room_start_date = !empty($cart_item['room_start_date']) ? $cart_item['room_start_date'] : '';
            $room_end_date = !empty($cart_item['room_end_date']) ? $cart_item['room_end_date'] : '';
            $room_guests = !empty($cart_item['room_guests']) ? $cart_item['room_guests'] : 2;
            $room_quantity = !empty($cart_item['quantity']) ? $cart_item['quantity'] : 0;

            $rate_plan_id = !empty($cart_item['rate_plan_id']) ? $cart_item['rate_plan_id'] : '';
            $rate_plan_amount = !empty($cart_item['lowest_nightly_rate']) ? $cart_item['lowest_nightly_rate'] : '';

            if(
                empty($room_start_date)
                || empty($room_end_date)
                || empty($room_type_id)
                || $room_quantity == 0
                || empty($rate_plan_id)
                || empty($rate_plan_amount)
            ) continue;

            $hotel_term = odp_get_hotel_term_by_room_id($room_type_id);
            $hotel_name = $hotel_term ? $hotel_term->name : '';
            $hotel_property_code = $hotel_term ? odp_get_hotel_property_code($hotel_term->term_id) : '';

            if(empty($hotel_property_code) || empty($hotel_name))
                continue;

            $room_product_id = odp_get_room_product_id_by_sku($room_type_id);

            if(!$room_product_id)
                continue;

            $room_product = wc_get_product($room_product_id);

            if(!$room_product)
                continue;

            $room_name = $room_product->get_name();

            $cart_item_total = $rate_plan_amount * $room_quantity;

            $dates_list = get_array_dates($room_start_date, $room_end_date);

            if(empty($dates_list))
                continue;

            $reservation = (object)[
                'deposit' => '0.00',
                'hotel_name' => $hotel_name,
                'commissionamount' => '0.00',
                'customer' => (object)[
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'telephone' => $phone,
                    'address' => $address,
                    'city' => $city,
                    'zip' => $postcode,
                    'countrycode' => $country,
                    'remarks' => 'Mock user'
                ],
                'room' => [],
                'booking_id' => rand(),
                'hotel_id' => $hotel_property_code,
                'currencycode' => 'USD',
                'site_id' => 'website',
                'company' => $company,
                'channel_ref_id' => '',
                'booking_date' => date("Y-m-d"),
                'status' => 'new',
                'confirmed' => (bool)false,
                'totalprice' => $cart_item_total
            ];

            for($i = 1; $i <= $room_quantity; $i++)
            {
                $room = (object)[
                    'id' => $room_type_id,
                    'name' => $room_name,
                    'guest_firstname' => $first_name,
                    'guest_lastname' => $last_name,
                    'arrival_date' => $room_start_date,
                    'departure_date' => $room_end_date,
                    'currencycode' => 'USD',
                    'numberofguests' => $room_guests,
                    'numberofchild' => 0,
                    'totalprice' => $cart_item_total,
                    'remarks' => 'Mock Reservation',
                    'numberofadult' => $room_guests,
                    'price' => []
                ];

                foreach($dates_list as $date_item)
                {
                    $room->price[] = (object)[
                        'date' => $date_item,
                        'rate_id' => $rate_plan_id,
                        'amount' => $rate_plan_amount
                    ];
                }

                $reservation->room[] = $room;
            }

            $booking_data->reservations->reservation[] = $reservation;
        }


        return $booking_data;
    }

    public function room_checkout_process($order_id, $posted_data, $order)
    {
        $booking_data = $this->prepare_cart_items_for_booking($order_id);

        update_post_meta($order_id, 'odp_booking_data', $booking_data);

        $result = odp_api_connect(ODP_API_URL_BOOKING, 'post', $booking_data);
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

ODP_Init_Room_Product_Type::getInstance();