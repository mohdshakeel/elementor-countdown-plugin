<?php
/**
 * Plugin Name: Elementor Count Down Timer Widget
 * Description: Add the countdown at anywhere on the page
 * Plugin URI:  https://www.wpdeft.com/
 * Version:     1.0.0
 * Author:      Mohammad
 * Author URI:  https://www.wpdeft.com/
 * Text Domain: elementor-countdown-widget
 * Elementor tested up to: 3.16.0
 * Elementor Pro tested up to: 3.16.0
 */

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

class CountdownTimerPlugin
{
    private $url;
    private $path;

    public function __construct()
    {
        $this->url = plugins_url("/", __FILE__);
        $this->path = plugin_dir_path(__FILE__);

        add_action("elementor/widgets/register", [
            $this,
            "registerCountdownWidget",
        ]);
        add_action("wp_enqueue_scripts", [$this, "enqueueScriptsStyles"]);
        add_action("plugins_loaded", [$this, "loadPlugin"]);

        add_action(
            "elementor/editor/after_save",
            [$this, "scheduleCustomCronJob"],
            10,
            2
        );
        add_action("my_next_schedule_sync", [$this, "scheduleSyncCronJob"]);
    }

    public function registerCountdownWidget($widgetsManager)
    {
        require_once $this->path . "widgets/countdown-widget.php";

        $widgetsManager->register( new \Elementor_Countdown_Widget() ); 

    }

    public function enqueueScriptsStyles()
    {
        wp_enqueue_script(
            "countdown-timer-script-new",
            $this->url . "assets/js/jquery.countdown.js",
            ["jquery"],
            "1.0.0",
            true
        );
        wp_enqueue_style(
            "countdown-timer-style-new",
            $this->url . "assets/css/countdown-timer-widget.css",
            true
        );
        wp_register_style(
            "fontawesome-all-css-new",
            "//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.css",
            [],
            "5.15.4"
        );
    }

    public function loadPlugin()
    {
        load_plugin_textdomain("elementor-countdown-widget");

        if (!$this->isElementorInstalled()) {
            add_action("admin_notices", [$this, "countdownWidgetFailLoad"]);
        }
    }

    private function isElementorInstalled()
    {
        $file_path = "elementor/elementor.php";

        $installed_plugins = get_plugins();
        return isset($installed_plugins[$file_path]);
    }

    public function countdownWidgetFailLoad()
    {
        $screen = get_current_screen();
        if (
            isset($screen->parent_file) &&
            "plugins.php" === $screen->parent_file &&
            "update" === $screen->id
        ) {
            return;
        }

        $plugin = "elementor/elementor.php";

        if ($this->isElementorInstalled()) {
            if (!current_user_can("activate_plugins")) {
                return;
            }
            $activation_url = wp_nonce_url(
                "plugins.php?action=activate&amp;plugin=" .
                    $plugin .
                    "&amp;plugin_status=all&amp;paged=1&amp;s",
                "activate-plugin_" . $plugin
            );

            $message =
                "<p>" .
                __(
                    "<strong>Countdown Timer<strong> widgets not working because you need to activate the Elementor plugin.",
                    "elementor-countdown-widget"
                ) .
                "</p>";
            $message .=
                "<p>" .
                sprintf(
                    '<a href="%s" class="button-primary">%s</a>',
                    $activation_url,
                    __("Activate Elementor Now", "elementor-countdown-widget")
                ) .
                "</p>";
        } else {
            if (!current_user_can("install_plugins")) {
                return;
            }

            $install_url = wp_nonce_url(
                self_admin_url(
                    "update.php?action=install-plugin&plugin=elementor"
                ),
                "install-plugin_elementor"
            );

            $message =
                "<p>" .
                __(
                    "<strong>Countdown Timer</strong> widgets not working because you need to install the Elemenor plugin",
                    "elementor-countdown-widget"
                ) .
                "</p>";
            $message .=
                "<p>" .
                sprintf(
                    '<a href="%s" class="button-primary">%s</a>',
                    $install_url,
                    __("Install Elementor Now", "elementor-countdown-widget")
                ) .
                "</p>";
        }

        echo '<div class="error"><p>' . $message . "</p></div>";
    }

    public function scheduleCustomCronJob($post_id, $elementorData)
    {
        // Get the Elementor data for the post
        $elementor_data = json_decode(
            get_post_meta($post_id, "_elementor_data", true)
        );

        foreach ($elementor_data as $section) {
            // Loop through the columns
            foreach ($section->elements as $column) {
                // Loop through the widgets
                foreach ($column->elements as $widget) {
                    if ($widget->widgetType == "countdown_widget") {
                        // Get the widget settings, type, and ID
                        $widget_settings = $widget->settings;
                        $widget_type = $widget->widgetType;
                        $widget_id = $widget->id;
                        $start_date = $widget_settings->start_date;

                        //echo "Widget Type: $widget_type, Widget ID: $widget_id, Start Date: $start_date\n";
                        $next_schedule_time = strtotime($start_date);
                        //sync countdown time reset after expire
                        $widget_args_for_syn = [
                            $post_id,
                            $widget_id,
                            $start_date,
                        ];

                        //if(!wp_next_scheduled('nextScheduleSync',$widget_args_for_syn)){
                        //This will schedule the cron job to reset the time of count down widget.
                        try {
                            wp_schedule_single_event(
                                $next_schedule_time,
                                "scheduleSyncCronJob",
                                $widget_args_for_syn
                            );
                            error_log(
                                "|" .
                                    wp_date("d-m-y H:i:s") .
                                    "|" .
                                    $start_date .
                                    " | My custom function is being called! "
                            );
                        } catch (Exception $e) {
                            // Code to handle the exception
                            echo "Caught exception: " . $e->getMessage();
                        } finally {
                            // Code that will be executed regardless of whether an exception occurred or not
                            echo "This will always be executed.";
                        }
                        // }
                    }
                }
            }
        }
    }

    public function scheduleSyncCronJob($post_id, $widget_id, $start_date)
    {
        $countdown_end_date = strtotime($start_date);

        $current_time = strtotime(wp_date("Y-m-d h:i"));
        error_log(
            "I am inside scheduleSyncCronJob-" .
                $countdown_end_date .
                " - " .
                $current_time
        );

        // Check if the countdown has reached zero
        if ($current_time > $countdown_end_date) {
            // Set a new future date (e.g., 3 days from now)
            $new_countdown_end_date = strtotime("+2 days", $current_time);

            $updated_time = wp_date("Y-m-d h:i", $new_countdown_end_date);

            $new_settings["start_date"] = $updated_time;
            error_log(
                "I am inside scheduleSyncCronJob-UpdatedNewStartDate-" .
                    $new_settings["start_date"]
            );
        } else {
            $new_settings["start_date"] = $start_date;
        }
        //error_log('I am inside scheduleSyncCronJob-'.wp_date("Y-m-d h:i"));
        // Get the Elementor data for the post
        $elementor_data = json_decode(
            get_post_meta($post_id, "_elementor_data", true),
            true
        );

        foreach ($elementor_data as &$section) {
            // Loop through the columns
            foreach ($section["elements"] as &$column) {
                // Loop through the widgets
                foreach ($column["elements"] as &$widget) {
                    // Check if the current widget matches the specified widget ID
                    if ($widget["id"] === $widget_id) {
                        error_log(
                            "I am inside scheduleSyncCronJob-NewStartDate-" .
                                $new_settings["start_date"]
                        );
                        // Update the widget settings
                        $widget["settings"] = array_merge(
                            $widget["settings"],
                            $new_settings
                        );

                        //echo '<pre>';   print_r($widget); echo '</pre>';

                        // Perform additional actions based on the updated settings

                        break 3;
                    }
                }
            }
        }

        //echo '<pre>';   print_r($widget); echo '</pre>';

        // This will rest  the countdown
        update_post_meta(
            $post_id,
            "_elementor_data",
            json_encode($elementor_data)
        );
    }
}

new CountdownTimerPlugin();
