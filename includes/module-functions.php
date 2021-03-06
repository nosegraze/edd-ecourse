<?php
/**
 * Module Functions
 *
 * @package   edd-ecourse
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 * @since     1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Insert Module
 *
 * @param array $args Module arguments, including `title` (required), `description`, `position`, and `course`
 *                    (required).
 *
 * @since 1.0
 * @return int|false Module ID on success or false on failure.
 */
function edd_ecourse_insert_module( $args = array() ) {
	$module_id = edd_ecourse_load()->modules->add( $args );

	return $module_id;
}

/**
 * Get Module Lessons
 *
 * Returns an array of WP_Post objects assigned to the given module.
 *
 * @param int   $module_id ID of the module to get lessons for.
 * @param array $args      WP_Query arguments to override the defaults.
 *
 * @since 1.0
 * @return array|false
 */
function edd_ecourse_get_module_lessons( $module_id, $args = array() ) {

	$default_args = array(
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'posts_per_page' => - 1,
		'post_type'      => 'ecourse_lesson',
		'meta_query'     => array(
			array(
				'key'   => 'module',
				'value' => absint( $module_id ),
				'type'  => 'NUMERIC'
			)
		)
	);

	if ( current_user_can( 'manage_options' ) ) {
		$default_args['post_status'] = 'any';
	}

	$query_args = wp_parse_args( $args, $default_args );

	$lessons = get_posts( $query_args );

	return apply_filters( 'edd_ecourse_get_module_lessons', $lessons, $module_id, $args );

}

/**
 * Get Number of Lessons in Module
 *
 * @param int $module_id ID of the module.
 *
 * @since 1.0
 * @return int
 */
function edd_ecourse_get_number_module_lessons( $module_id ) {

	$args    = array( 'fields' => 'ids' );
	$lessons = edd_ecourse_get_module_lessons( $module_id, $args );

	return is_array( $lessons ) ? count( $lessons ) : 0;

}

/**
 * Delete Module (and lessons)
 *
 * @param int  $module_id      ID of the module to delete.
 * @param bool $delete_lessons Whether to delete associated lessons as well.
 *
 * @since 1.0
 * @return bool
 */
function edd_ecourse_delete_module( $module_id, $delete_lessons = false ) {

	$deleted = edd_ecourse_load()->modules->delete( absint( $module_id ) );

	if ( false === $deleted ) {
		return false;
	}

	if ( $delete_lessons ) {
		$lessons = edd_ecourse_get_module_lessons( absint( $module_id ), array(
			'post_status' => 'any',
			'fields'      => 'ids'
		) );

		if ( $lessons ) {
			foreach ( $lessons as $lesson_id ) {
				wp_delete_post( $lesson_id, true );
			}
		}
	}

	return true;

}