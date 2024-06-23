<?php 


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
//namespace Elementor;

class Elementor_Countdown_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'countdown_widget';
    }

    public function get_title() {
        return __('Countdown Widget', 'elementor-countdown-widget');
    }

    public function get_icon() {
        return 'eicon-countdown';
    }

    public function get_categories() {
        return ['basic'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'elementor-countdown-widget'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'message',
            [
                'label' => __('Custom Message', 'elementor-countdown-widget'),
                'type'  => \Elementor\Controls_Manager::TEXT,
                'default' => __('25% OFF sales ends', 'elementor-countdown-widget'),
            ]
        );

        $this->add_control(
            'start_date',
            [
                'label'   => __('Start Date', 'elementor-countdown-widget'),
                'type'    => \Elementor\Controls_Manager::DATE_TIME,
                'default' => date('Y-m-d H:i:s', strtotime('+1 week')), // Default to one week from now.
            ]
        );

        $this->add_control(
            'box_background_color',
            [
                'label' => __( 'Background Color', 'elementor-countdown-widget' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'scheme' => [                   
                     'type' => \Elementor\Core\Schemes\Color::get_type(),
                     'value' => \Elementor\Core\Schemes\Color::COLOR_1
                ],
                'selectors' => [
                    '{{WRAPPER}} .countdown-widget' => 'background-color: {{VALUE}};',
                ],
                'separator' => 'after',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $message = $settings['message'];
        $start_date = $settings['start_date'];
        $box_background_color = $settings['box_background_color']; 
        $containerStyle = ($box_background_color)? 'style=background-color:'.$box_background_color :'';
        ?>

        <div class="countdown-widget" <?php echo esc_html($containerStyle); ?> >
            
        <p class="countdown-message" ><?php echo esc_html($message); ?></p>
            <div class="htop__ctd-wrapper" id="countdown-timer-<?php echo esc_attr($this->get_id()); ?>">
                
                <div class="htop__ctd-counter htop__ctd-day top1">
                    <p class="line1">00</p>
                    <p class="line2">DAYS</p>
                </div>
                <div class="htop__ctd-counter htop__ctd-hr top1">
                    <p class="line1">00</p>
                    <p class="line2">HRS</p>
                </div>
                <div class="htop__ctd-counter htop__ctd-min top1">
                    <p class="line1">00</p>
                    <p class="line2">MN</p>
                </div>
                <div class="htop__ctd-counter htop__ctd-sec top1">
                    <p class="line1">00</p>
                    <p class="line2">SEC</p>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#countdown-timer-<?php echo esc_attr($this->get_id()); ?>').countdown('<?php echo esc_js($start_date); ?>', function(event) {
                    //$(this).html(event.strftime('%D days %H:%M:%S'));
                    $('.htop__ctd-day.top1 .line1').html(event.strftime('%D') );
                    $('.htop__ctd-hr.top1 .line1').html(event.strftime('%H') );
                    $('.htop__ctd-min.top1 .line1').html(event.strftime('%M') );
                    $('.htop__ctd-sec.top1 .line1').html( event.strftime('%S') );
                });
            });
        </script>

        <?php
    }
}
