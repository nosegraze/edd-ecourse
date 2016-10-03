<?php
/**
 * Admin Pages
 *
 * @package   EDD\E-Course\Admin\Pages
 * @copyright Copyright (c) 2016, Ashley Gibson
 * @license   GPL2+
 * @since     1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks whether we're on an EDD E-Course page.
 *
 * @since 1.0.0
 * @return bool
 */
function edd_ecourse_is_admin_page() {
	$screen        = get_current_screen();
	$is_admin_page = false;

	if ( $screen->base == 'toplevel_page_ecourses' ) {
		$is_admin_page = true;
	}

	return apply_filters( 'edd_ecourse_is_admin_page', $is_admin_page, $screen );
}

/**
 * Filter EDD's Admin Page Function
 *
 * Load the Easy Digital Downloads files on a few of our own pages.
 *
 * @todo  Check this - I may not need it.
 *
 * @param $is_admin_page
 * @param $page
 * @param $view
 * @param $passed_page
 * @param $passed_view
 *
 * @since 1.0.0
 * @return bool
 */
function edd_ecourse_filter_edd_is_admin_page( $is_admin_page, $page, $view, $passed_page, $passed_view ) {
	return edd_ecourse_is_edit_course_page() ? true : $is_admin_page;
}

add_filter( 'edd_is_admin_page', 'edd_ecourse_filter_edd_is_admin_page', 10, 5 );

/**
 * Register Admin Menu Pages
 *
 * @since 1.0.0
 * @return void
 */
function edd_ecourse_admin_pages() {
	add_menu_page( __( 'E-Courses', 'edd-ecourse' ), __( 'E-Courses', 'edd-ecourse' ), 'manage_options', 'ecourses', 'edd_ecourse_render_page', 'dashicons-welcome-learn-more', 26 );
}

add_action( 'admin_menu', 'edd_ecourse_admin_pages' );

/**
 * Load Admin Scripts
 *
 * @global array  $edd_settings_page The slug for the EDD settings page
 * @global string $post_type         The type of post that we are editing
 *
 * @since 1.0.0
 * @return void
 */
function edd_ecourse_admin_scripts( $hook ) {

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	if ( edd_ecourse_is_admin_page() ) {
		$deps = array(
			'jquery',
			'jquery-ui-tooltip',
			'wp-util'
		);

		$settings = array(
			'l10n' => array(
				'cancel'                => __( 'Cancel', 'edd-ecourse' ),
				'confirm_delete_course' => __( 'Are you sure you want to delete this e-course and all associated lessons? This cannot be undone.', 'edd-ecourse' ),
				'save'                  => __( 'Save', 'edd-ecourse' ),
			)
		);

		wp_enqueue_media();

		wp_enqueue_script( 'edd-ecourse-admin', EDD_ECOURSE_URL . '/assets/js/admin' . $suffix . '.js', $deps );
		wp_localize_script( 'edd-ecourse-admin', 'edd_ecourse_vars', $settings );
		wp_enqueue_style( 'edd-ecourse-admin', EDD_ECOURSE_URL . '/assets/css/admin.css' );
	}

}

add_action( 'admin_enqueue_scripts', 'edd_ecourse_admin_scripts', 100 );