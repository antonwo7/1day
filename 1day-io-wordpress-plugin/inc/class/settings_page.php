<?php

class ODP_Settings_Page
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_menu_page(
            '1day options',
            '1day options',
            'edit_others_posts',
            'one_day_options',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('1day_options');
        ?>
        <div class="wrap">
            <h1>1day Options</h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('1day_option_group');
                do_settings_sections('1day-options');
                ?>

                <?php

                if(class_exists('ODP_Check_Hotels'))
                {
                    $review = (new ODP_Check_Hotels())->get_review();
                    require_once __DIR__ . '/../templates/admin/review.php';
                ?>

                <p class="submit">
                    <a href="<?php echo admin_url('/admin.php?page=one_day_options&sync'); ?>" class="button button-primary" ><?php echo esc_attr__('Synchronize', '1day'); ?></a>
                </p>

                <?php } ?>

                <?php
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            '1day_option_group', // Option group
            '1day_options', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            '1day_api_settings', // ID
            'API Settings', // Title
            array($this, 'print_section_info'), // Callback
            '1day-options' // Page
        );

        add_settings_field(
            'google_map_api_key', // ID
            'Google Map API key', // Title
            array($this, 'google_map_api_key_callback'), // Callback
            '1day-options', // Page
            '1day_api_settings' // Section
        );

        add_settings_field(
            'api_key', // ID
            'API key', // Title
            array($this, 'api_key_callback'), // Callback
            '1day-options', // Page
            '1day_api_settings' // Section
        );

        add_settings_field(
            'hotel_page_id', // ID
            'Hotel page id', // Title
            array($this, 'hotel_page_id_callback'), // Callback
            '1day-options', // Page
            '1day_api_settings' // Section
        );

        add_settings_field(
            'page_count',
            'Page items count',
            array($this, 'page_items_count_callback'), // Callback
            '1day-options', // Page
            '1day_api_settings' // Section
        );

        add_settings_field(
            'default_filter',
            'Default Filter',
            array($this, 'default_filter_setting_output'), // Callback
            '1day-options', // Page
            '1day_api_settings' // Section
        );


        add_settings_section(
            '1day_sync_settings', // ID
            'Sync With 1day io', // Title
            '', // Callback
            '1day-options' // Page
        ); ?>

    <?php
    }

    public function default_filter_setting_output()
    {

        $filters_query = new WP_Query([
            'post_type' => 'oneday_filter',
            'posts_per_page' => -1,
        ]); ?>

        <select name="1day_options[default_filter]" id="default_filter" >

            <?php
            if($filters_query->have_posts()) {
                foreach ($filters_query->posts as $filter) { ?>
                    <option <?php echo !empty($this->options['default_filter']) && $this->options['default_filter'] == $filter->ID ? 'selected' : ''?> value="<?php echo esc_attr($filter->ID); ?>"><?php echo esc_attr($filter->post_title); ?></option>
                <?php }
            }
            ?>

        </select>

        <?php
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['api_key']))
            $new_input['api_key'] = sanitize_text_field($input['api_key']);

        if (isset($input['google_map_api_key']))
            $new_input['google_map_api_key'] = sanitize_text_field($input['google_map_api_key']);

        if (isset($input['hotel_page_id']))
            $new_input['hotel_page_id'] = sanitize_text_field($input['hotel_page_id']);

        if (isset($input['page_items_count']))
            $new_input['page_items_count'] = sanitize_text_field($input['page_items_count']);

        if (isset($input['default_filter']))
            $new_input['default_filter'] = sanitize_text_field($input['default_filter']);

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="1day_options[api_key]" value="%s" />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    public function google_map_api_key_callback()
    {
        printf(
            '<input type="text" id="google_map_api_key" name="1day_options[google_map_api_key]" value="%s" />',
            isset($this->options['google_map_api_key']) ? esc_attr($this->options['google_map_api_key']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function hotel_page_id_callback()
    {
        printf(
            '<input type="text" id="hotel_page_id" name="1day_options[hotel_page_id]" value="%s" />',
            isset($this->options['hotel_page_id']) ? esc_attr($this->options['hotel_page_id']) : ''
        );
    }

    public function page_items_count_callback()
    {
        printf(
            '<input type="text" id="page_items_count" name="1day_options[page_items_count]" value="%s" />',
            isset($this->options['page_items_count']) ? esc_attr($this->options['page_items_count']) : ''
        );
    }
}

if (is_admin())
    $my_settings_page = new ODP_Settings_Page();