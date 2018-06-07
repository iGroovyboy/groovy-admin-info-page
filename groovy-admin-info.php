<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
  # -*- coding: utf-8 -*-
/**
 * Plugin Name: Groovy Admin Info Page
 * Description: Use the plugin to make a special Info page in admin menu and place there a specified post/page content.
 Размещение содержимого выбранного материала в админ-панели, в меню с выбранным названием. Например, для создания пункта меню "Инструкция" для контент-менеджера.
 * Version:     2018.06.06
 * Author:      Anton Babintsev
 * Author URI:  groovyboy.ru
 * Text Domain: groovy-admin-info
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

/* call our code on admin pages only, not on front end requests or during
 * AJAX calls.
 * Always wait for the last possible hook to start your code.
 */


include_once "groovy-admin-info-options.php";
 
add_action( 'admin_menu',   array ( 'Groovy_Admin_Info_Page', 'admin_menu' ) );
add_filter( 'user_has_cap', array ( 'Groovy_Admin_Info_Page', 'prevent_default_theme_deletion' ), 10, 3 );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array('Groovy_Admin_Info_Page', 'gai_plugin_add_settings_link') );
add_action( 'wp_dashboard_setup', array('Groovy_Admin_Info_Page', 'gai_add_dashboard_widgets' ) );

/**
 * @author Anton babintsev
 * @copyright 2018
 */
class Groovy_Admin_Info_Page
{

	/**
	 * Register the pages and the style and script loader callbacks.
	 *
	 * @wp-hook admin_menu
	 * @return  void
	 */
	public static function admin_menu()
	{
		$enable = self::gai_get_option( 'gai_checkbox_menu_enable' );
		if ( ! empty(self::gai_get_option( 'gai_checkbox_menu_enable' )) ) {

				$menu_name = self::gai_get_option( 'gai_text_menu_name', 'Информация' );
				$main = add_menu_page(
					$menu_name,
					$menu_name,
					'read',      
					'g-admin-info',
					array ( __CLASS__, 'render_page' ),
					'dashicons-info'
				);

				foreach ( array ( $main, $sub ) as $slug )
				{

					add_action(
						"admin_print_styles-$slug",
						array ( __CLASS__, 'enqueue_style' )
					);

					//add_action(
					//	"admin_print_scripts-$slug",
					//	array ( __CLASS__, 'enqueue_script' )
					//);
				}
		}
	}

	/**
	 * Print page output.
	 *
	 * @return  void
	 */
	public static function render_page()
	{
		self::gai_render_post_content();
	}

	/**
	 * Load stylesheet on our admin page only.
	 *
	 * @return void
	 */
	public static function enqueue_style()
	{
		wp_register_style(
			'groovy_admin_info_css',
			plugins_url( 'groovy_admin_info.css', __FILE__ )
		);
		wp_enqueue_style( 'groovy_admin_info_css' );
	}

	/**
	 * Load JavaScript on our admin page only.
	 *
	 * @return void
	 */
	public static function enqueue_script()
	{
		wp_register_script(
			'groovy_admin_info_js',
			plugins_url( 'Groovy_Admin_Info.js', __FILE__ ),
			array(),
			FALSE,
			TRUE
		);
		wp_enqueue_script( 'groovy_admin_info_js' );
	}

	/**
	 * Destroys "delete/trash" post link under single concrete post
	 * @param  array  $allcaps All the capabilities of the user
	 * @param  array  $caps    [0] Required capability
	 * @param  array  $args    [0] Requested capability
	 *                         [1] User ID
 	 *                         [2] Associated object ID
	 * @return array  $allcaps
	 */
	public static function prevent_default_theme_deletion($allcaps, $caps, $args, $post_id = null) {
		$post_id = is_null($post_id) ? self::gai_get_option( 'gai_select_material', 0 ) : (int) $post_id;

		if ( isset( $args[0] ) && isset( $args[2] ) && $args[2] == $post_id && $args[0] == 'delete_post' ) {
			if ( $post_id > 0 AND get_option( 'gai_settings' )['gai_checkbox_lock_post'] ){
				$allcaps[ $caps[0] ] = false;
			}			
		}
		return $allcaps;
	}

	/**
	 * Creates a "Settings" link in the plugin's section at Plugins list page
	 * @param  array Array of links
	 * @return array
	 */
	public function gai_plugin_add_settings_link( $links ) {
	    $settings_link = '<a href="options-general.php?page=gai_options">' . __( 'Настройки', 'groovy-admin-info' ) . '</a>';
	    array_push( $links, $settings_link );
	  	return $links;
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 * This function is hooked into the 'wp_dashboard_setup' action below.
	 */
	public function gai_add_dashboard_widgets() {
		$enable = self::gai_get_option( 'gai_checkbox_dashboard_enable' );
		if ( ! empty(self::gai_get_option( 'gai_checkbox_dashboard_enable' )) ) {
			$title  = self::gai_get_option( 'gai_text_menu_name', 'Информация' );
			wp_add_dashboard_widget(
				'gai_dashboard_widget',
				$title,
				array(__CLASS__, 'gai_dashboard_widget_function')
	        );	
		}
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public function gai_dashboard_widget_function() {
		self::gai_render_post_content();
	}

	/**
	 * Gets option from db and returns value or null
	 * @param  string $option_name option name slug
	 * @return mixed returns value or $default_value if failed
	 */
	public function gai_get_option( $option_name = null, $default_value = null ) {
		if (is_null($option_name)) {
			return false;
		}

		$options = get_option( 'gai_settings' );

		if ( is_array( $options ) && array_key_exists($option_name, $options)) {
			$option_value = ( empty($options[$option_name]) ) ? $default_value : $options[$option_name];
		} else {
			$option_value = $default_value;
		}
		
		return $option_value;
	}

	/**
	 * Echoes specified post' content or dedault info
	 * @return void
	 */
	public function gai_render_post_content() {
		$post = "";
		$info_post_id = self::gai_get_option('gai_select_material', 0);
		$post = get_post( $info_post_id ); 
				
		if ( empty($post) ) {
			echo '<div class="groovy-admin-info-wrap">';
			echo '<p>' . __('Пожалуйста, выберите материал для отображения на странице настроек.', 'groovy-admin-info') . '</p>';
			echo '<a href="options-general.php?page=gai_options">' . __('Перейти к настройкам.', 'groovy-admin-info') . '</a>';
			echo '</div>';
		} else {
			echo '<div class="groovy-admin-info-wrap">';

				echo '<article class="info-content">';
					//echo "<pre>" ; var_dump($post); echo "</pre>";
					echo "<h1>$post->post_title</h1>";
					echo apply_filters('the_content', $post->post_content);
				echo '</article>';

			echo '</div>';
		}
	} 

}  // class
