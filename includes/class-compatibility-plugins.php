<?php

/**
 * Class that provides compatibility code with other plugins
 *
 * @package Black_Studio_TinyMCE_Widget
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Black_Studio_TinyMCE_Compatibility_Plugins' ) ) {

	class Black_Studio_TinyMCE_Compatibility_Plugins {

		/**
		 * Class constructor
		 *
		 * @param string[] $compat_plugins
		 * @return void
		 * @since 2.0.0
		 */
		public function __construct( $compat_plugins ) {
			foreach ( $compat_plugins as $compat_plugin ) {
				if ( is_callable( array( $this, $compat_plugin ), false ) ) {
					$this->$compat_plugin();
				}
			}
		}

		/**
		 * Compatibility with WPML
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function wpml() {
			add_filter( 'black_studio_tinymce_widget_update', array( $this, 'wpml_widget_update' ), 10, 2 );
			add_filter( 'widget_text', array( $this, 'wpml_widget_text' ), 5, 3 );
		}

		/**
		 * Add widget text to WPML String translation
		 *
		 * @uses icl_register_string() Part of WPML
		 *
		 * @param mixed[] $instance
		 * @param object $widget
		 * @return mixed[]
		 * @since 2.0.0
		 */
		public function wpml_widget_update( $instance, $widget ) {
			if ( function_exists( 'icl_register_string' ) && ! empty( $widget->number ) ) {
				icl_register_string( 'Widgets', 'widget body - ' . $widget->id_base . '-' . $widget->number, $instance['text'] );
			}
			return $instance;
		}

		/**
		 * Translate widget text
		 *
		 * @uses icl_t() Part of WPML
		 *
		 * @param string $text
		 * @param mixed[] $instance
		 * @param object $widget
		 * @return string
		 * @since 2.0.0
		 */
		public function wpml_widget_text( $text, $instance, $widget = null ) {
			if ( bstw()->check_widget( $widget ) && ! empty( $instance ) ) {
				if ( function_exists( 'icl_t' ) ) {
					$text = icl_t( 'Widgets', 'widget body - ' . $widget->id_base . '-' . $widget->number, $text );
				}
			}
			return $text;
		}

		/**
		 * Compatibility for WP Page Widget plugin
		 *
		 * @uses add_filter
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function wp_page_widget() {
			add_filter( 'black_studio_tinymce_enable', array( $this, 'wp_page_widget_enable' ) );
		}

		/**
		 * Enable filter for WP Page Widget plugin
		 *
		 * @uses is_plugin_active()
		 *
		 * @global string $pagenow
		 * @param boolean $enable
		 * @return boolean
		 * @since 2.0.0
		 */
		public function wp_page_widget_enable( $enable ) {
			global $pagenow;
			if ( is_plugin_active( 'wp-page-widget/wp-page-widgets.php' ) ) {
				$is_post = in_array( $pagenow, array( 'post-new.php', 'post.php' ) );
				$is_tags = in_array( $pagenow, array( 'edit-tags.php' ) );
				$is_admin = in_array( $pagenow, array( 'admin.php' ) );
				if (
					$is_post ||
					( $is_tags  && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) ||
					( $is_admin && isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'pw-front-page', 'pw-search-page' ) ) )
				) {
					$enable = true;
				}
			}
			return $enable;
		}

		/**
		 * Compatibility with Page Builder
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function siteorigin_panels() {
			add_filter( 'siteorigin_panels_widget_object', array( $this, 'siteorigin_panels_widget_object' ), 10 );
			add_filter( 'black_studio_tinymce_container_selectors', array( $this, 'siteorigin_panels_container_selectors' ) );
			add_filter( 'black_studio_tinymce_activate_events', array( $this, 'siteorigin_panels_activate_events' ) );
			add_filter( 'black_studio_tinymce_deactivate_events', array( $this, 'siteorigin_panels_deactivate_events' ) );
			add_filter( 'black_studio_tinymce_enable_pages', array( $this, 'siteorigin_panels_enable_pages' ) );
			add_action( 'admin_init', array( $this, 'siteorigin_panels_disable_compat' ), 7 );
		}

		/**
		 * Remove widget number to prevent translation when using Page Builder + WPML String Translation
		 *
		 * @param object $the_widget
		 * @return object
		 * @since 2.0.0
		 */
		public function siteorigin_panels_widget_object( $the_widget ) {
			if ( isset($the_widget->id_base) && $the_widget->id_base == 'black-studio-tinymce' ) {
				$the_widget->number = '';
			}
			return $the_widget;
		}

		/**
		 * Add selector for widget detection for Page Builder
		 *
		 * @param string[] $selectors
		 * @return string[]
		 * @since 2.0.0
		 */
		public function siteorigin_panels_container_selectors( $selectors ) {
			$selectors[] = 'div.panel-dialog';
			return $selectors;
		}

		/**
		 * Add activate events for Page Builder
		 *
		 * @param string[] $events
		 * @return string[]
		 * @since 2.0.0
		 */
		public function siteorigin_panels_activate_events( $events ) {
			$events[] = 'panelsopen';
			return $events;
		}

		/**
		 * Add deactivate events for Page Builder
		 *
		 * @param string[] $events
		 * @return string[]
		 * @since 2.0.0
		 */
		public function siteorigin_panels_deactivate_events( $events ) {
			$events[] = 'panelsdone';
			return $events;
		}

		/**
		 * Add pages filter to enable editor for Page Builder
		 *
		 * @param string[] $pages
		 * @return string[]
		 * @since 2.0.0
		 */
		public function siteorigin_panels_enable_pages( $pages ) {
			$pages[] = 'post-new.php';
			$pages[] = 'post.php';
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'so_panels_home_page' ) {
				$pages[] = 'themes.php';
			}
			return $pages;
		}
		
		/**
		 * Disable old compatibility code provided by Page Builder
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function siteorigin_panels_disable_compat( ) {
			remove_action( 'admin_init', 'siteorigin_panels_black_studio_tinymce_admin_init' );
			remove_action( 'admin_enqueue_scripts', 'siteorigin_panels_black_studio_tinymce_admin_enqueue', 15 );
		}


		/**
		 * Compatibility with Jetpack After the deadline
		 *
		 * @uses add_action()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function jetpack_after_the_deadline() {
			add_action( 'black_studio_tinymce_load', array( $this, 'jetpack_after_the_deadline_load' ) );
		}

		/**
		 * Load Jetpack After the deadline scripts
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function jetpack_after_the_deadline_load() {
			add_filter( 'atd_load_scripts', '__return_true' );
		}

	} // END class Black_Studio_TinyMCE_Compatibility_Plugins

} // class_exists check
