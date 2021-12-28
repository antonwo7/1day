<?php

class ODP_Init_Filter_Post_Type{

    public function __construct()
    {
        /**
         * 1. Register filter post type
         */
        add_action('init', [ $this, 'register_filter_post_type' ]);

        /**
         * 2. Add meta box to filter post type
         */
        add_action('add_meta_boxes', [ $this, 'add_filter_meta_boxes' ]);

        /**
         * 3. Save meta box to filter post type
         */
        add_action('save_post', [ $this, 'save_filter_post_type_meta' ]);

        add_filter('manage_oneday_filter_posts_columns' , function($columns){
            return array_merge(
                $columns, [ 'shortcodes' => __('Shortcodes', '1day') ]
            );
        });

        add_action('manage_oneday_filter_posts_custom_column' , function($column, $post_id){

            if($column == 'shortcodes')
            {
                $post_id = esc_attr($post_id);

                echo "[oneday_search_form filter_id=\"{$post_id}\"]<br/>";
                echo "[oneday_search_results filter_id=\"{$post_id}\"]<br/>";
                echo "[oneday_search_map filter_id=\"{$post_id}\"]<br/>";
            }
        }, 10, 2);

        add_action('admin_menu', function() {
            add_submenu_page('one_day_options', '1day filters', '1day filter', 'edit_pages' , 'edit.php?post_type=oneday_filter');
        }, 11);


    }

    public function register_filter_post_type()
    {
        register_post_type( 'oneday_filter', [
            'labels' => [
                'name'               => __('1day filters', '1day'),
                'singular_name'      => __('1day filter', '1day'),
                'add_new'            => __('Add filter', '1day'),
                'add_new_item'       => __('Add 1day filter', '1day'),
                'edit_item'          => __('Edit 1day filter', '1day'),
                'new_item'           => __('New filter', '1day'),
                'view_item'          => __('View filter', '1day'),
                'search_items'       => __('Search filter', '1day'),
                'not_found'          => __('Filters not found', '1day'),
                'not_found_in_trash' => __('Not found in trash', '1day'),
                'parent_item_colon'  => __('', '1day'),
                'menu_name'          => __('1day filters', '1day'),
            ],
            'public'              => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_nav_menus'   => false,
            'show_in_menu'        => false,
            'show_in_admin_bar'   => false,
            'capability_type'   => 'post',
            'hierarchical'        => false,
            'supports'            => ['title'],
            'has_archive'         => true,
            'rewrite'             => true,
            'query_var'           => true,
        ] );
    }

    public function add_filter_meta_boxes()
    {
        global $post;

        if($post && get_post_type($post->ID) != 'oneday_filter') return;

        $boxes = [
            'oneday_filter_form_fields' => __('Search for hotels (rooms) form settings', '1day'),
            'oneday_filter_results_fields' => __('Filter results fields', '1day'),
            'oneday_filter_map_fields' => __('Filter map fields', '1day'),
            'oneday_filter_shortcodes' => __('Shortcodes', '1day'),
        ];

        foreach($boxes as $name => $label)
        {
            $render_callback = str_replace('oneday', 'render', $name);
            $this->add_filter_meta_box($name, $label, $render_callback);
        }

        $render_callback = str_replace('oneday', 'render', 'oneday_filter_shortcodes');
        $this->add_filter_meta_box($name, $label, $render_callback, 'side');
    }
    private function add_filter_meta_box($name, $label, $render_callback, $context = 'advanced')
    {
        add_meta_box(
            $name,
            $label,
            [$this, $render_callback],
            null,
            $context
        );
    }

    public function render_filter_form_fields($filter)
    {
        ?>
        <?php wp_nonce_field('oneday_filter_fields_nonce', 'oneday_filter_fields_nonce'); ?>
        <p>
            <label for="oneday_filter_form_url"><?php echo esc_attr__('Url', '1day'); ?><span title="<?php echo esc_attr__('url of the page where you are going to display the search result', '1day'); ?>" class="odp_star">(<b>i</b>)</span></label>
            <input type="text" name="oneday_filter_form_url" id="oneday_filter_form_url" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_url', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_form_property_code"><?php echo esc_attr__('Property code', '1day'); ?><span title="<?php echo esc_attr__('add it if you wish to create a search form for rooms, take it from the hotel edit page', '1day'); ?>" class="odp_star">(<b>i</b>)</span></label>
            <input type="text" name="oneday_filter_form_property_code" id="oneday_filter_form_property_code" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_property_code', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_form_one_line"><?php echo esc_attr__('One line?', '1day'); ?><span title="<?php echo esc_attr__('to display the from fields in one line', '1day'); ?>" class="odp_star">(<b>i</b>)</span></label>
            <input type="checkbox" name="oneday_filter_form_one_line" id="oneday_filter_form_one_line" value="yes" <?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_one_line', true) == 'yes' ? 'checked="checked"' : ''); ?>>
        </p>
        <p>
            <label for="oneday_filter_form_widget_width"><?php echo esc_attr__('Form width', '1day'); ?></label>
            <input type="text" name="oneday_filter_form_widget_width" id="oneday_filter_form_widget_width" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_widget_width', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_form_bg_color"><?php echo esc_attr__('Background color', '1day'); ?></label>
            <input type="text" name="oneday_filter_form_bg_color" id="oneday_filter_form_bg_color" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_bg_color', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_form_font_color"><?php echo esc_attr__('Font color', '1day'); ?></label>
            <input type="text" name="oneday_filter_form_font_color" id="oneday_filter_form_font_color" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_font_color', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_form_btn_color"><?php echo esc_attr__('Button color', '1day'); ?></label>
            <input type="text" name="oneday_filter_form_btn_color" id="oneday_filter_form_btn_color" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_btn_color', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_form_button_font_color"><?php echo esc_attr__('Button font color', '1day'); ?></label>
            <input type="text" name="oneday_filter_form_button_font_color" id="oneday_filter_form_button_font_color" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_form_button_font_color', true)); ?>">
        </p>

        <style>
            .odp_star{
                padding:0 7px;
                color:red;
                font-size: 15px;
            }
            .odp_star b{
                font-weight: 800;
                margin:0 2px;
            }
        </style>
        <?php
    }

    public function render_filter_results_fields($filter)
    {
        ?>
        <p>
            <label for="oneday_filter_results_buttons_background"><?php echo esc_attr__('Buttons background', '1day'); ?></label>
            <input type="text" name="oneday_filter_results_buttons_background" id="oneday_filter_results_buttons_background" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_results_buttons_background', true)); ?>">
        </p>
        <p>
            <label for="oneday_filter_results_buttons_font_color"><?php echo esc_attr__('Buttons font color', '1day'); ?></label>
            <input type="text" name="oneday_filter_results_buttons_font_color" id="oneday_filter_results_buttons_font_color" value="<?php echo esc_attr(get_post_meta($filter->ID, 'oneday_filter_results_buttons_font_color', true)); ?>">
        </p>
        <?php
    }

    public function render_filter_map_fields($filter)
    {
        ?>

        <?php
    }

    public function render_filter_shortcodes($filter)
    {
        ?>
        <p>
            <label><?php echo esc_attr__('Search form shortcode', '1day'); ?></label><br/>
            <b>[oneday_search_form filter_id="<?php echo esc_attr($filter->ID); ?>"]</b>
        </p>
        <p>
            <label><?php echo esc_attr__('Search results shortcode', '1day'); ?></label><br/>
            <b>[oneday_search_results filter_id="<?php echo esc_attr($filter->ID); ?>"]</b>
        </p>
        <p>
            <label><?php echo esc_attr__('Search map shortcode', '1day'); ?></label><br/>
            <b>[oneday_search_map filter_id="<?php echo esc_attr($filter->ID); ?>"]</b>
        </p>

        <?php
    }

    public function save_filter_post_type_meta($filter_id)
    {
        if ( ! isset( $_POST['oneday_filter_fields_nonce'] ) ) {
            return;
        }

        if( ! wp_verify_nonce( sanitize_text_field($_POST['oneday_filter_fields_nonce']), 'oneday_filter_fields_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $filter_id ) ) {
                return;
            }
        }else {
            if ( ! current_user_can( 'edit_post', $filter_id ) ) {
                return;
            }
        }

        if( isset( $_POST['oneday_filter_form_url'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_url', sanitize_text_field($_POST['oneday_filter_form_url']) ); }

        if( isset( $_POST['oneday_filter_form_one_line'] ) ) {
            update_post_meta( $filter_id, 'oneday_filter_form_one_line', sanitize_text_field($_POST['oneday_filter_form_one_line']) );
        }else{
            delete_post_meta( $filter_id, 'oneday_filter_form_one_line' );
        }

        if( isset( $_POST['oneday_filter_form_bg_color'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_bg_color', sanitize_text_field($_POST['oneday_filter_form_bg_color']) ); }
        if( isset( $_POST['oneday_filter_form_btn_color'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_btn_color', sanitize_text_field($_POST['oneday_filter_form_btn_color']) ); }
        if( isset( $_POST['oneday_filter_form_widget_width'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_widget_width', sanitize_text_field($_POST['oneday_filter_form_widget_width']) ); }
        if( isset( $_POST['oneday_filter_form_font_color'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_font_color', sanitize_text_field($_POST['oneday_filter_form_font_color']) ); }
        if( isset( $_POST['oneday_filter_form_button_font_color'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_button_font_color', sanitize_text_field($_POST['oneday_filter_form_button_font_color']) ); }
        if( isset( $_POST['oneday_filter_form_property_code'] ) ){ update_post_meta( $filter_id, 'oneday_filter_form_property_code', sanitize_text_field($_POST['oneday_filter_form_property_code']) ); }

        if( !empty( $_POST['oneday_filter_results_buttons_background'] ) ){ update_post_meta( $filter_id, 'oneday_filter_results_buttons_background', sanitize_text_field($_POST['oneday_filter_results_buttons_background']) ); }
        if( !empty( $_POST['oneday_filter_results_buttons_font_color'] ) ){ update_post_meta( $filter_id, 'oneday_filter_results_buttons_font_color', sanitize_text_field($_POST['oneday_filter_results_buttons_font_color']) ); }

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

ODP_Init_Filter_Post_Type::getInstance();