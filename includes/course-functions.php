<?php
/**
 * E-Course Functions
 *
 * @package   EDD\E-Course\Course\Functions
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 * @since     1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Insert Demo Course
 *
 * Adds an e-course, one module, and one associated lesson.
 *
 * @since 1.0
 * @return bool
 */
function edd_ecourse_insert_demo_course() {

	// Make sure we don't do this twice.
	if ( get_option( 'edd_ecourse_inserted_demo_content' ) ) {
		return false;
	}

	// Insert e-course.
	$course_id = edd_ecourse_insert_course( __( 'My First Course', 'edd-ecourse' ) );

	if ( ! $course_id ) {
		return false;
	}

	// Insert module.
	$module_id = edd_ecourse_insert_module( array(
		'title'    => __( 'Module 1', 'edd-ecourse' ),
		'course'   => absint( $course_id ),
		'position' => 1
	) );

	// Insert lesson.
	$post_data = array(
		'post_title'   => __( 'Lesson #1', 'edd-ecourse' ),
		'post_content' => __( 'This is your first e-course lesson.', 'edd-ecourse' ),
		'post_status'  => 'publish',
		'post_type'    => 'ecourse_lesson'
	);

	$lesson_id = wp_insert_post( $post_data );

	if ( is_wp_error( $lesson_id ) || ! $lesson_id ) {
		return false;
	}

	// Associate lesson with course and module.
	update_post_meta( $lesson_id, 'course', absint( $course_id ) );
	update_post_meta( $lesson_id, 'module', absint( $module_id ) );
	update_post_meta( $lesson_id, 'lesson_type', 'text' );

	// Mark this task as done.
	update_option( 'edd_ecourse_inserted_demo_content', current_time( 'timestamp' ) );

	return true;

}

/**
 * Get E-Courses
 *
 * @param array $args
 *
 * @since 1.0
 * @return array|false Array of course objects or false if none exist.
 */
function edd_ecourse_get_courses( $args = array() ) {

	$defaults = array(
		'post_status' => 'any',
		'post_type'   => 'ecourse',
		'number'      => 500,
		'nopaging'    => true
	);

	if ( current_user_can( 'manage_options' ) ) {
		$defaults['post_status'] = 'any';
	}

	$args = wp_parse_args( $args, $defaults );

	$courses = get_posts( $args );

	if ( ! is_array( $courses ) ) {
		return false;
	}

	return $courses;

}

/**
 * Insert a Course
 *
 * @param string $title Course title.
 * @param array  $args  Arguments to override the defaults.
 *
 * @since 1.0
 * @return int|bool Course ID on success or false on failure.
 */
function edd_ecourse_insert_course( $title, $args = array() ) {

	$defaults = array(
		'post_title'  => sanitize_text_field( wp_strip_all_tags( $title ) ),
		'post_name'   => sanitize_title( $title ),
		'post_type'   => 'ecourse',
		'post_status' => 'draft',
		'ping_status' => 'closed'
	);

	$args = wp_parse_args( $args, $defaults );

	$course_id = wp_insert_post( $args );

	return $course_id;

}

/**
 * Get Readable Course Date
 *
 * @param WP_Post|int $course Course ID or post object.
 *
 * @since 1.0
 * @return string
 */
function edd_ecourse_get_readable_course_date( $course ) {

	if ( is_numeric( $course ) ) {
		$course = get_post( $course );
	}

	$formatted_date = get_the_time( 'F jS Y, g:i A', $course );

	/**
	 * Filters the readable course data.
	 *
	 * @param string      $formatted_date Formatted date.
	 * @param WP_Post|int $course         Course ID or post object.
	 *
	 * @since 1.0
	 */
	return apply_filters( 'edd_ecourse_get_readable_course_date', $formatted_date, $course );

}

/**
 * Get Course Modules
 *
 * @param int   $course_id ID of the course.
 * @param array $args      Arguments to override the defaults.
 *
 * @since 1.0
 * @return array|false Array of module objects or false on failure.
 */
function edd_ecourse_get_course_modules( $course_id, $args = array() ) {

	$defaults = array(
		'course' => $course_id,
		'number' => - 1
	);
	$args     = wp_parse_args( $args, $defaults );

	$modules = edd_ecourse_load()->modules->get_modules( $args );

	if ( ! is_array( $modules ) || ! count( $modules ) ) {
		$modules = false;
	}

	/**
	 * Filters the modules for a course.
	 *
	 * @param array|false $modules   Array of module objects or false on failure.
	 * @param int         $course_id ID of the course.
	 *
	 * @since 1.0
	 */
	return apply_filters( 'edd_ecourse_get_course_modules', $modules, $course_id );

}

/**
 * Get E-Course Lessons
 *
 * @param int   $course_id  ID of the course.
 * @param array $query_args WP_Query arguments to override the defaults.
 *
 * @since 1.0
 * @return array
 */
function edd_ecourse_get_course_lessons( $course_id, $query_args = array() ) {

	$default_args = array(
		'post_type'      => 'ecourse_lesson',
		'posts_per_page' => 500,
		'meta_query'     => array(
			array(
				'key'   => 'course',
				'value' => absint( $course_id ),
				'type'  => 'NUMERIC'
			)
		)
	);

	if ( current_user_can( 'manage_options' ) ) {
		$default_args['post_status'] = 'any';
	}

	$query_args = wp_parse_args( $query_args, $default_args );

	$lessons = get_posts( $query_args );

	return $lessons;

}

/**
 * Get Number of Lessons in an E-Course
 *
 * @param int   $course_id  ID of the course.
 * @param array $query_args WP_Query arguments to override the defaults.
 *
 * @uses  edd_ecourse_get_course_lessons()
 *
 * @since 1.0
 * @return int
 */
function edd_ecourse_get_number_course_lessons( $course_id, $query_args = array() ) {

	$default_args = array(
		'fields' => 'ids'
	);

	$query_args = wp_parse_args( $query_args, $default_args );

	$lessons        = edd_ecourse_get_course_lessons( $course_id, $query_args );
	$number_lessons = is_array( $lessons ) ? count( $lessons ) : 0;

	/**
	 * Filters the number of lessons in an e-course.
	 *
	 * @param int   $number_lessons Number of lessons.
	 * @param array $lessons        Array of lesson IDs.
	 * @param int   $course_id      ID of the course to check.
	 * @param array $query_args     Query args for getting the lessons.
	 *
	 * @since 1.0
	 */
	return apply_filters( 'edd_ecourse_number_course_lessons', $number_lessons, $lessons, $course_id, $query_args );

}

/**
 * Delete E-Course
 *
 * @param int  $course_id      ID of the course to delete.
 * @param bool $delete_modules Whether to delete modules in the course.
 * @param bool $delete_lessons Whether to delete lessons in the course.
 *
 * @since 1.0
 * @return bool Whether it was successfully deleted.
 */
function edd_ecourse_delete( $course_id, $delete_modules = false, $delete_lessons = false ) {

	/**
	 * Triggers before a course is deleted.
	 *
	 * @param int  $course_id      ID of the course that was deleted.
	 * @param bool $delete_modules Whether or not to delete modules.
	 * @param bool $delete_lessons Whether or not to delete lessons.
	 *
	 * @since 1.0
	 */
	do_action( 'edd_ecourse_before_delete_course', $course_id, $delete_modules, $delete_lessons );

	// Delete modules.
	if ( $delete_modules ) {
		edd_ecourse_load()->modules->delete_course_modules( $course_id );
	}

	// Delete lessons.
	if ( $delete_lessons ) {
		$lessons = edd_ecourse_get_course_lessons( $course_id, array( 'post_status' => 'any', 'fields' => 'ids' ) );

		if ( is_array( $lessons ) ) {
			foreach ( $lessons as $lesson_id ) {
				wp_delete_post( $lesson_id, true );
			}
		}
	}

	// Now delete the course itself.
	$result = wp_delete_post( $course_id, true );

	/**
	 * Triggers after a course is deleted.
	 *
	 * @param int  $course_id      ID of the course that was deleted.
	 * @param bool $delete_modules Whether or not to delete modules.
	 * @param bool $delete_lessons Whether or not to delete lessons.
	 *
	 * @since 1.0
	 */
	do_action( 'edd_ecourse_after_delete_course', $course_id, $delete_modules, $delete_lessons );

	if ( empty( $result ) ) {
		return false;
	}

	return true;

}

/**
 * Get Course Download
 *
 * Returns the EDD product associated with an e-course.
 *
 * @todo  I think it would be better to also store the EDD product ID in the course meta. Faster querying.
 *
 * @param int    $course_id ID of the course to get the downlaod for.
 * @param string $format    Format for the return value: `object` or `id`
 *
 * @since 1.0
 * @return WP_Post|int|bool Post object, post ID, or false if none.
 */
function edd_ecourse_get_course_download( $course_id, $format = 'object' ) {

	$args = array(
		'post_type'      => 'download',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'   => 'ecourse',
				'value' => absint( $course_id ),
				'type'  => 'NUMERIC'
			)
		)
	);

	if ( 'object' != $format ) {
		$args['fields'] = 'ids';
	}

	$downloads = get_posts( $args );

	if ( is_array( $downloads ) && array_key_exists( 0, $downloads ) ) {
		$download = $downloads[0];
	} else {
		$download = false;
	}

	/**
	 * Filters the associated course download.
	 *
	 * @param WP_Post|int|bool Post       object, post ID, or false if none.
	 * @param int              $course_id ID of the course to check.
	 * @param string           $format    Format to retrieve ('object' or 'id').
	 *
	 * @since 1.0
	 */
	return apply_filters( 'edd_ecourse_get_course_download', $download, $course_id, $format );

}

/**
 * Get Current Course
 *
 * @since 1.0
 * @return object|false Course object or false on failure.
 */
function edd_ecourse_get_current_course() {

	global $post;

	if ( is_object( $post ) && 'ecourse' == $post->post_type ) {
		$course = $post;
	} else {
		$course = false;
	}

	return $course;

}

/**
 * Get Current E-Course Title
 *
 * @global object $edd_ecourse
 *
 * @since 1.0
 * @return string|false
 */
function edd_ecourse_get_title() {

	global $edd_ecourse;

	return is_object( $edd_ecourse ) ? $edd_ecourse->post_title : false;

}

/**
 * Display Current E-Course Title
 *
 * @uses  edd_ecourse_get_title()
 *
 * @since 1.0
 * @return void
 */
function edd_ecourse_title() {
	echo edd_ecourse_get_title();
}

/**
 * Get Current E-Course ID
 *
 * @global WP_Post $edd_ecourse
 *
 * @since 1.0
 * @return int|false
 */
function edd_ecourse_get_id() {

	global $edd_ecourse;

	return is_object( $edd_ecourse ) ? $edd_ecourse->ID : false;

}

/**
 * Get Current E-Course Status
 *
 * @global WP_Post $edd_ecourse
 *
 * @since 1.0
 * @return int|false
 */
function edd_ecourse_get_status() {

	global $edd_ecourse;

	return is_object( $edd_ecourse ) ? $edd_ecourse->post_status : false;

}

/**
 * Display Current E-Course Permalink
 *
 * @param bool $escape Whether or not to escape the URL.
 *
 * @since 1.0
 * @return void
 */
function edd_ecourse_permalink( $escape = true ) {

	global $edd_ecourse;

	if ( ! is_object( $edd_ecourse ) ) {
		return;
	}

	$url = get_permalink( $edd_ecourse );

	if ( $escape ) {
		echo esc_url( $url );
	} else {
		echo $url;
	}

}

/**
 * Display Current E-Course ID
 *
 * @uses  edd_ecourse_get_id()
 *
 * @since 1.0
 * @return void
 */
function edd_ecourse_id() {
	echo edd_ecourse_get_id();
}

/**
 * Get Modules
 *
 * Returns an array of modules that are in the current course.
 *
 * @since 1.0
 * @return array|false
 */
function edd_ecourse_get_modules() {

	$course_id = edd_ecourse_get_id();
	$modules   = array();

	if ( $course_id ) {
		$modules = edd_ecourse_get_course_modules( $course_id );
	}

	return is_array( $modules ) ? $modules : array();

}

/**
 * Get Edit Course URL
 *
 * Returns the URL to the "Edit Course" page.
 *
 * @since 1.0
 * @return string
 */
function edd_ecourse_get_manage_course_url( $course_id = 0 ) {

	$url = add_query_arg( array(
		'page'   => 'ecourses',
		'view'   => 'edit',
		'course' => absint( $course_id )
	), admin_url( 'admin.php' ) );

	/**
	 * Filters the URL to the Edit Course page.
	 *
	 * @param string $url
	 * @param int    $course_id
	 *
	 * @since 1.0
	 */
	return apply_filters( 'edd_ecourse_get_manage_course_url', $url, $course_id );

}

/**
 * Get Add Lesson URL
 *
 * Returns the URL to the "Add Lesson" page.
 *
 * @param int $course_id ID of the course to add the lesson to.
 * @param int $module_id ID of the module to add the lesson to.
 *
 * @since 1.0
 * @return string
 */
function edd_ecourse_get_add_lesson_url( $course_id = 0, $module_id = 0 ) {

	$args = array(
		'post_type' => 'ecourse_lesson',
		'course'    => absint( $course_id ),
		'module'    => absint( $module_id )
	);

	$url = add_query_arg( $args, admin_url( 'post-new.php' ) );

	/**
	 * Filters the Add Lesson URL
	 *
	 * @param string $url       URL to the Add Lesson page.
	 * @param int    $course_id ID of the course to add the lesson to.
	 * @param int    $module_id ID of the module to add the lesson to.
	 *
	 * @since 1.0
	 */
	return apply_filters( 'edd_ecourse_get_add_lesson_url', $url, $course_id, $module_id );

}

/**
 * Admin Bar Node
 *
 * Adds a new node to the admin bar to "Manage Course". This only appears
 * on course archive pages and single lesson pages.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 *
 * @since 1.0
 * @return void
 */
function edd_ecourse_admin_bar_node( $wp_admin_bar ) {

	if ( edd_ecourse_is_course_page() ) {
		$course_id = edd_ecourse_get_id();

		if ( $course_id ) {
			$args = array(
				'id'    => 'ecourse_edit',
				'title' => __( 'Manage Course', 'edd-ecourse' ),
				'href'  => edd_ecourse_get_manage_course_url( $course_id )
			);

			$wp_admin_bar->add_node( $args );
		}
	}

}

add_action( 'admin_bar_menu', 'edd_ecourse_admin_bar_node', 999 );