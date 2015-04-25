<?php

/**********************************************************************************************************
 Extends America Base Themes
  
 Plugin Name: 	  America Theme Extender
 Description:     This plugin allows the america base theme to be extended (i.e. grandchild theme)
 Version:         0.0.1
 Author:          Office of Design, Bureau of International Information Programs
 License:         GPL-2.0+
 Text Domain:     america
 Domain Path:     /languages
 
 ************************************************************************************************************/

//* Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'America_Theme_Extender' ) ) {
	
	class America_Theme_Extender {

		const VERSION = '1.0.0';

		// path to folder holding customized templates/assets
		public $site_path;

		// standard templates to customize
		public $templates = [ 	
			'404_template', 
			'archive_template'
		];

		// non-standard templates to customize
		public $custom_templates = [ 
			'front-page.php'
		];
 
		public function __construct() {
			
			add_action( 'init',						array( $this, 'america_theme_init' ) );
			add_action( 'wp_enqueue_scripts',		array( $this, 'america_enqueue_css' ) );
			
			$this->site_path = $this->america_get_site_path( get_current_blog_id() );  
		}

		public function america_theme_init() {
			$this->america_load_plugin_textdomain();
			$this->america_register_css();
			$this->america_add_templates();
		}

		//* Internationalization
		public function america_load_plugin_textdomain () {
			load_plugin_textdomain ( 'america', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Returns the sub site part of the url; used to locate correct asset folder (i.e. /climate or /disinfo)
		 * Folder names MUST match the path entered in the "path" field of the Edit Sites admin screen
		 * 
		 * @param  int 		$id current blog_id
		 * @return string   url part
		 */
		public function america_get_site_path( $id ) {
			$sites = wp_get_sites();

			foreach ( $sites as $site ) {
				foreach ( $site as $key => $value )  {
					if( $key == 'blog_id' ) {
						if( $value == $id ) {
							extract($site);
							return substr( $path , 1 );
						}
					}
				}
			}
		}

		/**
		 * Register custom css class if present
		 * @return void
		 */
		public function america_register_css() {
			$path = $this->site_path;

			$filename = $path . 'style.css';
			if ( file_exists ( plugin_dir_path( __FILE__ ) . $filename ) ) {
				wp_register_style ( 'grandchild_style',  plugins_url( $filename, __FILE__ ) );
			} 
		 }


		public function america_enqueue_css () {
			wp_enqueue_style( 'grandchild_style' );
		}

		/**
		 * Handles template requests for templates that do not have a template filter
		 * 
		 * @param string $template Template being served
		 */
		public function add_custom_template_filter( $template ) {

			$filename = basename( $template );
			$in_custom = in_array( $filename, $this->custom_templates );

			if( $in_custom ) {
				$template = plugin_dir_path( __FILE__ ) .  $this->site_path;

				if( is_front_page() ) { 
					$template .= 'front-page.php';
				}
			}

			return $template;
		}

		/**
		 * Handles template requests for templates that do not have a standard template filter
		 * 
		 * @param string $template Template being served
		 */
		public function add_template_filter( $template ) {
			$path = plugin_dir_path( __FILE__ ) . $this->site_path;

			$pos = strpos( $template, '_template' );
			$filename = $path . substr( $template, 0, $pos ) . '.php';
			
			// add_filter expecting a function for second argument, use closure to provide parameter
			// TODO :  Refactor using apply_filters, i.e. apply_filters('custom filter name', $filename);
			add_filter ( $template, function() use( $filename ) {
				return $filename;
			});
		}

		
		/**
		 * Adds filters to handle incoming template requests 
		 * If template is present in $templates array, then a customization is served
		 * Filter templates that do not have a standard template file with 'template_include' filter
		 * Standard template filters see: https://codex.wordpress.org/Plugin_API/Filter_Reference#Template_Filters
		 * For standard templates, the customized template should use the standard name, i.e. 404.php, archive.php
		 * 
		 * @return void
		 */
		function america_add_templates() {
			
			$templates = $this->templates;
			$path = plugin_dir_path( __FILE__ ) . $this->site_path;
		
			// Add filter to handle templates that do not have a standard filter
			add_filter( 'template_include', array( $this, 'add_custom_template_filter') );

			foreach ( $templates as $template ) {				
				$pos = strpos( $template, '_template' );
				$filename = $path . substr( $template, 0, $pos ) . '.php';
				
				if ( file_exists ( $filename ) ) {
					$this->add_template_filter ( $template ); 
				} 
			} 
		}

		// ADD ANY ADDITIONAL FUNCTIONS THAT WOULD GO IN functions.php HERE  //


	}
}

// Initialize plugin
$america_theme_extender = new America_Theme_Extender();
