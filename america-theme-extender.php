<?php

/**********************************************************************************************************
 Extends America Base Themes
  
 Plugin Name: 	  America Theme Extender
 Description:     This plugin allows the america base theme to be extended (i.e. grandchild theme)
 Version:         1.1.0
 Author:          Office of Design, Bureau of International Information Programs
 License:         GPL-2.0+
 Text Domain:     america
 Domain Path:     /languages
 
 ************************************************************************************************************/

//* Prevent loading this file directly
defined( 'ABSPATH' ) || exit;  

if ( ! class_exists( 'America_Theme_Extender' ) ) {
	
	class America_Theme_Extender {

		const VERSION = '1.1.0';

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
		 * Adds template filter if any theme has grandchild assets
		 * 
		 * @return void
		 */
		public function america_theme_init() {

			$this->america_initialize_assets();

			$templates = $this->get_templates( $this->site_dir );
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
			if ( file_exists ( $filename ) ) {
				wp_register_style ( 'grandchild_style',  $this->site_uri . '/style.css' );
			} 
		 }

		 /**
		 * Register custom js classess by recursing thru the folders in the /js/dist directory
		 * and filtering out .js files
		 * 
		 * @return void
		 */
		 public function america_enqueue_js() {		
			$dist = $this->site_dir . '/js/dist';
			
			if ( file_exists ( $dist ) ) {
				$dir = new RecursiveDirectoryIterator( $dist ); 
				foreach ( new JSFilter( new RecursiveIteratorIterator( $dir ) ) as $file ) {
				
					$dir_path = realpath($file);
					$site = basename($this->site_dir);
					$start = stripos( $dir_path, $site ) + strlen($site);
					$path = substr( $dir_path, $start );
					
					$fn = basename( $path, '.js' ); 
					$url = $this->site_uri . $path;
					wp_enqueue_script ( $fn,  $url );
				}
			} 
		}


		public function america_enqueue_css () {
			wp_enqueue_style( 'grandchild_style' );
		}


		/**
		 * Checks to see if template is in wp->templates array
		 * Include it if it is 
		 *
		 * Template Hierarchy
		 * is_archive : archive-category.{slug}.php -> archive-category.{tag}.php -> category.php -> archive.php 
		 * 		   	  : archive-tag.{slug}.php -> archive-tag.{tag}.php -> tag.php -> archive.php
		 * 		   	  : archive-{taxonomy-term}.php -> archive-{taxonomy}.php -> taxonomy.php -> archive.php
		 * 		   	  : archive-{post-type}.php -> archive.php  
		 * 		   	  : date.php
		 * 		   	  : author.php
		 * 
		 * @param string  $template template being included
		 * @return string template path
		 */
		public function america_include_template( $template ) {
			//echo 'incoming: ' . $template . '<br>';

			$filename = $this->search_for_template();
			$filename = ( trim($filename) != '' ) ? $filename : basename( $template );	
			
			if( in_array( $filename, $this->templates ) ) {
				$template = $this->site_dir . '/' . $filename; 
			}

			//echo 'outgoing: ' . $template . '<br><br>';

			return $template;
		}

		/**
		 * Determines which type of template is loaded and then looks for matching templates
		 * starting with more specific and working out according to the template hierarchy
		 * 
		 * @return string filename of matched template
		 */
		function search_for_template() {
			$obj = get_queried_object();
			$filename = '';
			
			// taxonomy archives
			if ( is_tax() ) {
				$term = 'taxonomy-' . $obj->slug . '.php';
				$taxonomy = 'taxonomy-' . $obj->taxonomy . '.php';

				if( $this->has_template( $term ) ) {
					$filename = $term;
				} 
				else if ( $this->has_template( $taxonomy ) ) {
					$filename = $taxonomy;
				} 
				else if ( $this->has_template( 'taxonomy.php' ) ) {
					$filename = 'taxonomy.php';
				}
			} 
			
			// custom post type archives
			else if ( is_post_type_archive() ) {
				$cpt = 'archive-' . $obj->name . '.php';
				if( $this->has_template( $cpt ) ) {
					$filename = $cpt;
				}
			} 

			// category archives
			else if ( is_category() ) {
				$slug = 'category-' . $obj->slug . '.php';
				$id = 'category-' . $obj->cat_ID . '.php';
				
				if( $this->has_template( $slug ) ) {
					$filename = $slug;
				}
				else if ( $this->has_template( $id ) ) {
					$filename = $id;
				} 
				else if ( $this->has_template( 'category.php' ) ) {
					$filename = 'category.php';
				}

			// tag archives
			else if ( is_tag() ) {

			}

			// single posts/pages
			// is_singular() : returns true for any is_single(), is_page(), or is_attachment()
			} else if ( is_singular() ) {
				
				// is_single() : returns true for single post of any post type (except attachment and page post types)				
				if ( is_single() ) {
					$post_type = $obj->post_type;
					$post = 'single-' . $post_type . '.php';
					
					if( $this->has_template( $post ) ) {
						$filename = $post;
					}
					
				} else if ( is_page() ) {
					// not sure if we need this as custom pages may be directly linked to
				}
			}

			return $filename;
		}

		/**
		 * Checks to see if template is in custom template array
		 * @param  string  $template filename to look for
		 * @return boolean           
		 */
		function has_template( $template ) {
			return in_array( $template, $this->templates ) ;
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

		/**
		 * Testing util method
		 */
		function debug_content_type ( $template, $filename ) {
			$obj = get_queried_object();

			echo 'outgoing: ' . $filename . '<br><br>';
			echo 'tax ' .	   is_tax() . '<br>';
			echo 'cpt ' .	   is_post_type_archive() . '<br>';
			echo 'cat ' .	   is_category() . '<br>';
			echo 'single post' .   is_single() . '<br>';
			echo 'page ' .	   is_page() . '<br>';
			echo 'tag ' .	   is_tag() . '<br>';
			echo 'singular of any type ' .	   is_singular() . '<br>';
		
			echo '<pre>';
			var_dump($obj);
			echo '</pre>';
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

