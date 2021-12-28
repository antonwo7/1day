<?php

class ODP_Init_Hotel_Taxonomy{

    public function __construct()
    {
        add_action('init', [ $this, 'create_hotel_taxonomy' ]);

        add_action('hotel_add_form_fields', [ $this, 'create_hotel_fields' ]);

        add_action('hotel_edit_form_fields', [ $this, 'edit_hotel_fields' ]);

        add_action('created_hotel', [ $this, 'save_hotel_fields' ]);

        add_action('edited_hotel', [ $this, 'save_hotel_fields' ]);

        add_action('admin_enqueue_scripts', [ $this, 'admin_media_scripts']);

        add_action( "hotel_edit_form", [ $this, 'remove_default_description_from_edit' ]);
        add_action( "hotel_add_form", [ $this, 'remove_default_description_from_edit' ]);

        add_filter('manage_edit-hotel_columns', function ( $columns ) {
            if( isset( $columns['description'] ) )
                unset( $columns['description'] );
            return $columns;
        });

        add_filter('template_include', function($tax_template) {
            if (is_tax('hotel')) {
                $tax_template = ODP_ONEDAY_PLUGIN_DIR . '/inc/templates/hotel-template.php';
            }
            return $tax_template;
        }, 100, 1);


    }

    public function remove_default_description_from_edit()
    {
        echo "
            <style> 
                 .term-description-wrap { display:none; } 
                 #edittag { max-width: 100% !important; }
                 #edittag input { max-width: 500px; }
            </style>
        ";
    }

    public function create_hotel_taxonomy()
    {
       $labels = array(
            'name'                       => _x( 'Hotels', 'taxonomy general name', '1day' ),
            'singular_name'              => _x( 'Hotel', 'taxonomy singular name', '1day' ),
            'search_items'               => __( 'Search Hotels', '1day' ),
            'popular_items'              => __( 'Popular Hotels', '1day' ),
            'all_items'                  => __( 'All Hotels', '1day' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Hotel', '1day' ),
            'update_item'                => __( 'Update Hotel', '1day' ),
            'add_new_item'               => __( 'Add New Hotel', '1day' ),
            'new_item_name'              => __( 'New Hotel Title', '1day' ),
            'separate_items_with_commas' => __( 'Separate hotels with commas', '1day' ),
            'add_or_remove_items'        => __( 'Add or remove hotels', '1day' ),
            'choose_from_most_used'      => __( 'Choose from the most used hotels', '1day' ),
            'not_found'                  => __( 'No hotels found.', '1day' ),
            'menu_name'                  => __( 'Hotels', '1day' ),
       );

       $args = array(
           'labels'                     => $labels,
           'hierarchical'               => false,
           'public'                     => true,
           'show_ui'                    => true,
           'show_admin_column'          => true,
           'show_in_nav_menus'          => true,
           'show_tagcloud'              => true,
       );

       register_taxonomy( 'hotel', 'product', $args );
       register_taxonomy_for_object_type('hotel', 'product', $args);
    }


    public function create_hotel_fields()
    {
        ?>
        <div class="form-field">
            <label for="hotel_short_description"><?php echo esc_attr__('Hotel short description', '1day'); ?></label><?php

            wp_editor(
                '',
                'hotel_short_description',
                $settings = [
                    'wpautop' => 1,
                    'media_buttons' => 1,
                    'textarea_name' => 'hotel_short_description',
                    'textarea_rows' => 8,
                    'quicktags' => 1,
                ]
            ); ?>
        </div>

        <div class="form-field">
            <label for="hotel_description"><?php echo esc_attr__('Hotel full description', '1day'); ?></label><?php

            wp_editor(
                '',
                'hotel_description',
                $settings = [
                    'wpautop' => 1,
                    'media_buttons' => 1,
                    'textarea_name' => 'hotel_description',
                    'textarea_rows' => 8,
                    'quicktags' => 1,
                ]
            ); ?>
        </div>

        <div class="form-field">
            <label for="property_code"><?php echo esc_attr__('Property code', '1day'); ?></label>
            <input type="text" name="property_code" id="property_code" />
            <p><?php echo esc_attr__('Hotel unique property code', '1day'); ?></p>
        </div>

        <div class="form-field">
            <label for="hotal_image"><?php echo  esc_attr__('Hotel image:', '1day'); ?></label>
            <input type="hidden" name="hotel_image" id="hotel_image" class="hotel-image" value="<?php echo esc_attr(isset($hotel_image) ? $hotel_image : ''); ?>">
            <input class="upload_image_button button" name="_add_hotel_image" id="_add_hotel_image" type="button" value="<?php echo esc_attr__('Select/Upload Image', '1day'); ?>" />

            <style>
                div.img-wrap {
                    background: #ccc;
                    background-size:contain;
                    max-width: 200px;
                    max-height: 200px;
                    width: 100%;
                    height: 100%;
                    overflow:hidden;
                    position:relative;
                    margin-top:20px;
                }
                div.img-wrap img {
                    display:none;
                    width: 200px;
                    object-fit: cover;
                    height: 200px;
                }
                div.img-wrap .remove-img{
                    display:none;
                    width:20px;
                    height:20px;
                    position:absolute;
                    top:5px;
                    right:5px;
                    background: url(<?php echo esc_url(plugin_dir_url(__DIR__) . '../../build/images/remove.png'); ?>) no-repeat center center;
                    background-size:100% 100%;
                    cursor:pointer;
                }

            </style>
            <div class="img-wrap">
                <img src="<?php echo esc_url($hotel_image); ?>" id="hotel-img">
                <div class="remove-img"></div>
            </div>
            <script>

                jQuery(document).ready(function() {
                    jQuery('.img-wrap .remove-img').click(function(){
                        let hotel_img = jQuery('#hotel-img')
                        jQuery('.hotel-image').val('')
                        jQuery(this).fadeOut(0)
                        hotel_img.fadeOut(0)
                        hotel_img.attr('src', '')
                    })


                    jQuery('#_add_hotel_image').click(function() {
                        wp.media.editor.send.attachment = function(props, attachment) {
                            jQuery('#hotel-img').attr('src', attachment.url)
                            if(attachment.url){
                                jQuery('#hotel-img').fadeIn(400)
                                jQuery('.img-wrap .remove-img').fadeIn(400)
                            }else{
                                jQuery('#hotel-img').fadeOut(0)
                            }
                            jQuery('.hotel-image').val(attachment.url)
                        }
                        wp.media.editor.open(this);
                        return false;
                    });
                });
            </script>
        </div>
        <?php
    }

    public function edit_hotel_fields($term)
    {
        $property_code = get_term_meta($term->term_id, 'property_code', true); ?>

        <tr class="form-field">
            <th>
                <label for="property_code"><?php echo esc_attr__('Property code', '1day'); ?></label>
            </th>
            <td><b><?php echo esc_attr($property_code); ?></b></td>
        </tr>

        <tr class="form-field">
            <th>
                <label for="hotel_short_description"><?php echo esc_attr__('Hotel short description', '1day'); ?></label>
            </th>
            <td>
                <?php

                wp_editor(
                    html_entity_decode(get_term_meta($term->term_id, 'hotel_short_description', true)),
                    'hotel_short_description',
                    $settings = [
                        'wpautop' => 1,
                        'media_buttons' => 1,
                        'textarea_name' => 'hotel_short_description',
                        'textarea_rows' => 8,
                        'quicktags' => 1,
                    ]
                );
                ?>
            </td>
        </tr>

        <tr class="form-field">
            <th>
                <label for="hotel_description"><?php echo esc_attr__('Hotel full description', '1day'); ?></label>
            </th>
            <td>
                <?php

                wp_editor(
                    html_entity_decode(get_term_meta($term->term_id, 'hotel_description', true)),
                    'hotel_description',
                    $settings = [
                        'wpautop' => 1,
                        'media_buttons' => 1,
                        'textarea_name' => 'hotel_description',
                        'textarea_rows' => 8,
                        'quicktags' => 1,
                    ]
                );
                ?>
            </td>
        </tr>

        <?php

        $hotel_image_term_meta_value = get_term_meta($term->term_id, 'hotel_image', true);

        ?>

        <tr class="form-field">
            <th scope="row" valign="top"><label for="_hotel_image"><?php echo esc_attr__( 'Hotel Image', '1day' ); ?></label></th>
            <td>
                <?php
                $hotel_image = $hotel_image_term_meta_value ? $hotel_image_term_meta_value : '';
                ?>
                <input type="hidden" name="hotel_image" id="hotel_image" class="hotel-image" value="<?php echo esc_attr($hotel_image); ?>">
                <input class="upload_image_button button" name="_hotel_image" id="_hotel_image" type="button" value="<?php echo esc_attr__('Select/Upload Image', '1day'); ?>" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"></th>
            <td style="padding-top:10px;">
                <style>
                    div.img-wrap {
                        background: #ccc;
                        background-size:contain;
                        max-width: 200px;
                        max-height: 200px;
                        width: 100%;
                        height: 100%;
                        overflow:hidden;
                        position:relative;
                    }
                    div.img-wrap img {
                        display:none;
                        width: 200px;
                        object-fit: cover;
                        height: 200px;
                    }
                    div.img-wrap .remove-img{
                        display:none;
                        width:20px;
                        height:20px;
                        position:absolute;
                        top:5px;
                        right:5px;
                        background: url(<?php echo esc_url(plugin_dir_url(__DIR__) . '../../build/images/remove.png'); ?>) no-repeat center center;
                        background-size:100% 100%;
                        cursor:pointer;
                    }

                </style>
                <div class="img-wrap">
                    <img src="<?php echo esc_url($hotel_image); ?>" id="hotel-img">
                    <div class="remove-img"></div>
                </div>
                <script>
                    jQuery(document).ready(function() {
                        if(jQuery('#hotel_image').val()){
                            jQuery('#hotel-img').fadeIn(400)
                            jQuery('.img-wrap .remove-img').fadeIn(400)
                        }

                        jQuery('.img-wrap .remove-img').click(function(){
                            let hotel_img = jQuery('#hotel-img')
                            jQuery('.hotel-image').val('')
                            jQuery(this).fadeOut(0)
                            hotel_img.fadeOut(0)
                            hotel_img.attr('src', '')
                        })

                        jQuery('#_hotel_image').click(function() {
                            wp.media.editor.send.attachment = function(props, attachment) {
                                jQuery('#hotel-img').attr('src', attachment.url)
                                if(attachment.url){
                                    jQuery('#hotel-img').fadeIn(400)
                                    jQuery('.img-wrap .remove-img').fadeIn(400)
                                }else{
                                    jQuery('#hotel-img').fadeOut(0)
                                }
                                jQuery('.hotel-image').val(attachment.url)
                            }
                            wp.media.editor.open(this);
                            return false;
                        });
                    });
                </script>
            </td>
        </tr>

        <?php
    }

    public function save_hotel_fields($term_id)
    {
        if (isset($_POST['hotel_image']))
        {
            update_term_meta(
                $term_id,
                'hotel_image',
                sanitize_text_field($_POST['hotel_image'])
            );
        }

        if (isset($_POST['property_code'])) {
            update_term_meta(
                $term_id,
                'property_code',
                sanitize_text_field($_POST['property_code'])
            );
        }

        if (isset($_POST['hotel_short_description'])) {
            update_term_meta(
                $term_id,
                'hotel_short_description',
                sanitize_text_field(htmlentities($_POST['hotel_short_description']))
            );
        }

        if (isset($_POST['hotel_description'])) {
            update_term_meta(
                $term_id,
                'hotel_description',
                sanitize_text_field(htmlentities($_POST['hotel_description']))
            );
        }
    }


    /**
     * Add Media scripts for hotel image choice
     */
    public function admin_media_scripts(){
        wp_enqueue_media();
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

ODP_Init_Hotel_Taxonomy::getInstance();