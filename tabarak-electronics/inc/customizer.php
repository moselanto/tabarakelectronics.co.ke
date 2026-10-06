<?php
/**
 * Theme Customizer options.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tabarak_customize_register' ) ) {
    /**
     * Register customizer settings.
     *
     * @param WP_Customize_Manager $wp_customize Customizer object.
     */
    function tabarak_customize_register( $wp_customize ) {
        $wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
        $wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';

        $wp_customize->add_section(
            'tabarak_topbar',
            array(
                'title'    => __( 'Tabarak: Top Bar', 'tabarak-electronics' ),
                'priority' => 30,
            )
        );

        $wp_customize->add_setting(
            'tabarak_topbar_text',
            array(
                'default'           => __( 'Free delivery within Nairobi on orders over KSh 20,000', 'tabarak-electronics' ),
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'refresh',
            )
        );
        $wp_customize->add_control(
            'tabarak_topbar_text',
            array(
                'label'   => __( 'Top bar announcement', 'tabarak-electronics' ),
                'section' => 'tabarak_topbar',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'tabarak_accent',
            array(
                'default'           => '#f7a81b',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                'tabarak_accent',
                array(
                    'label'   => __( 'Accent colour', 'tabarak-electronics' ),
                    'section' => 'colors',
                )
            )
        );
    }
}
add_action( 'customize_register', 'tabarak_customize_register' );

if ( ! function_exists( 'tabarak_customizer_css' ) ) {
    /**
     * Output the accent colour as a CSS variable.
     */
    function tabarak_customizer_css() {
        $accent = get_theme_mod( 'tabarak_accent', '#f7a81b' );
        if ( $accent ) {
            printf(
                '<style id="tabarak-customizer">:root{--tabarak-accent:%s;}</style>',
                esc_attr( $accent )
            );
        }
    }
}
add_action( 'wp_head', 'tabarak_customizer_css' );
