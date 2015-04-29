<?php

/**********************************************************************************************************
 Extends America Base Themes
  
 Plugin Name: 	  America Theme Extender
 Description:     This plugin allows the america base theme to be extended (i.e. grandchild theme)
 Version:         1.0.0
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

		// directory path to folder holding customized templates/assets
		public $site_dir;

		// url path to folder holding customized templates/assets
		public $site_uri;

		// stores template files
		public $templates = array();
 
		/**
		 * Constructor
		 * @param string $site_dir directory to grandchild assets
		 * @param string $site_uri url to grandchild assets
		 */
		public function __construct( $site_dir, $site_uri ) {

			$this->site_dir = $site_dir;
			$this->site_uri = $site_uri;

			add_action( 'init',	array( $this, 'america_theme_init' ) );
		}


		/**
		 * Adds template filter if theme has grandchild assets
		 * 
		 * @return void
		 */
		public function america_theme_init() {

			$this->america_initialize_assets();

			$templates = $this->get_templates( $this->site_dir );
			echo 'templates : ' . gettype($templates) . '<br>';
			if( $templates !== NULL ) {
				add_filter( 'template_include', array( $this, 'america_include_template' ) );
			}
		}

		/**
		 * Initializes register css and load text domain
		 * 
		 * @return void
		 */
		public function america_initialize_assets() {
			$this->america_load_plugin_textdomain();
			
			$this->america_register_css();
			add_action( 'wp_enqueue_scripts', array( $this, 'america_enqueue_css' ) );
			
			add_action( 'wp_enqueue_scripts', array( $this, 'america_enqueue_js' ) );
		}

		/**
		 * Adds internationalization support
		 * 
		 * @return void
		 */
		public function america_load_plugin_textdomain () {
			load_plugin_textdomain ( 'america', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Register custom css class
		 * 
		 * @return void
		 */
		public function america_register_css() {

			$filename = $this->site_dir . '/style.css';
			echo 'filename : ' . $filename . '<br>';
			if ( file_exists ( $filename ) ) {
				echo 'file exists : ' . $filename . '<br>';
				wp_register_style ( 'grandchild_style',  $this->site_uri . '/style.css' );
			} 
		 }

		 /**
		 * Register custom js classes
		 * 
		 * @return void
		 */
		public function america_enqueue_js() {		
			$jsDir = $this->site_dir . '/js';
			
			if ( file_exists ( $jsDir ) ) {
				foreach ( new JSFilter( new DirectoryIterator( $jsDir ) ) as $file ) {
					$path =  $jsDir . '/' . $file->getFileName();
					$fn = basename( $path, '.js' ); 
					$url = $this->site_uri . '/js/' . $file->getFileName();
					wp_enqueue_script ( $fn,  $url );
				}
			} 
		 }


		public function america_enqueue_css () {
			wp_enqueue_style( 'grandchild_style' );
		}


		/**
		 * Checks to see if template is in wp->templates array
		 * Includes it if it is 
		 * 
		 * @param string  $template template being included
		 * @return string template path
		 */
		public function america_include_template( $template ) {
			echo 'include : ' . $template . '<br>';
			$filename = basename( $template );
			if( in_array( $filename, $this->templates ) ) {
				$template = $this->site_dir . '/' . $filename; 
			}

			return $template;
		}

		/**
		 * Checks grandchild folder for customized templates and adds templates to 
		 * templates array if present
		 * 
		 * @param  string  dir path to grandchild assets (i.e. /climate, /misinfo )
		 * @return array   array of templates null if none present
		 */
		function get_templates( $dir ) {
			foreach ( new TemplateFilter( new DirectoryIterator( $dir ) ) as $file ) {
				$this->templates[] = $file->getFileName();
			}
			return count( $this->templates ) ?  $this->templates : NULL;
		}

	}
}

/**
 * Filter that returns only .php files
 */
class TemplateFilter extends FilterIterator { 
	public function accept() {
		return preg_match( '@\.php$@i', $this->current() ); 
	}
}

/**
 * Filter that returns only .js files
 */
class JSFilter extends FilterIterator { 
	public function accept() {
		return preg_match( '@\.js$@i', $this->current() ); 
	}
}

