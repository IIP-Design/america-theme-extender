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

		// stores template files
		public $templates = array();
 
		public function __construct() {

			$this->site_path = $this->america_get_site_path_part( get_current_blog_id() );  
			
			add_action( 'init',	array( $this, 'america_theme_init' ) );
		}

		/**
		 * Adds template filter if theme has grandchild assets
		 * 
		 * @return void
		 */
		public function america_theme_init() {
			if( $this->has_templates() ) {
				add_filter( 'template_include', array( $this, 'america_include_template') );
			}
		}

		/**
		 * Initializes assets
		 * 
		 * @return void
		 */
		public function america_initialize_assets() {
			$this->america_load_plugin_textdomain();
			$this->america_register_css();

			add_action( 'wp_enqueue_scripts', array( $this, 'america_enqueue_css' ) );
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
			$path = $this->site_path;

			$filename = $path . '/style.css';
			if ( file_exists ( plugin_dir_path( __FILE__ ) . $filename ) ) {
				wp_register_style ( 'grandchild_style',  plugins_url( $filename, __FILE__ ) );
			} 
		 }


		public function america_enqueue_css () {
			wp_enqueue_style( 'grandchild_style' );
		}

		/**
		 * Returns the sub site part of the url; used to locate correct asset folder (i.e. /climate or /disinfo)
		 * Folder names MUST match the path entered in the "path" field of the Edit Sites admin screen
		 * 
		 * @param  int 		$id current blog_id
		 * @return string   url part
		 */
		public function america_get_site_path_part( $id ) {
			$sites = wp_get_sites();

			foreach ( $sites as $site ) {
				foreach ( $site as $key => $value )  {
					if( $key == 'blog_id' ) {
						if( $value == $id ) {
							extract($site);
							$len = strlen($path) - 2;
 							return substr( $path , 1, $len );
						}
					}
				}
			}
		}


		/**
		 * Checks to see if template is in wp->templates array
		 * Includes it if it is 
		 * 
		 * @param string $template template being included
		 * @return string template path
		 */
		public function america_include_template( $template ) {
			$filename = basename( $template );
			if( in_array( $filename, $this->templates ) ) {
				$template = plugin_dir_path( __FILE__ ) .  $this->site_path . '/' . $filename; 
			}

			return $template;
		}

		/**
		 * Checks grandchild folder for customized templates and adds templates to 
		 * templates array if present
		 * 
		 * @param  string $dir path to grandchild assets (i.e. /climate, /misinfo )
		 * @return array    array of templates null if none present
		 */
		function get_templates( $dir ) {
			foreach ( new TemplateFilter( new DirectoryIterator( $dir ) ) as $file ) {
				$this->templates[] = $file->getFileName();
			}
			return count( $this->templates ) ?  $this->templates : null;
		}

		/**
		 * Checks the file system for folder containing customized templates.  If customized
		 * template folder is present, initialize assets (css, internalization) 
		 * and check for templates
		 * 
		 * @return array array of templates in grandchild asset directory
		 */
		function has_templates () {
			foreach ( new DirectoryIterator( plugin_dir_path( __FILE__ ) ) as $file ) {
				if( $file->isDir() ) {
					if( $file->getFileName() == $this->site_path ) {
						$this->america_initialize_assets();
						return $this->get_templates( $file->getPathname() );
					}
				}
			}
		}

		// ADD ANY ADDITIONAL FUNCTIONS THAT WOULD GO IN functions.php HERE  //


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


// Initialize plugin
$america_theme_extender = new America_Theme_Extender();
