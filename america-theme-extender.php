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
		 * Register custom js classess
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
			$filename = $this->search_for_template();
			$filename = ( trim($filename) != '' ) ? $filename : basename( $template );	
			//echo 'filename ' . $filename;
			
			if( in_array( $filename, $this->templates ) ) {
				$template = $this->site_dir . '/' . $filename; 
			}
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

			else if ( is_post_type_archive() ) {
				$cpt = 'archive-' . $obj->name . '.php';
				if( $this->has_template( $term ) ) {
					$filename = $cpt;
				}
			} 

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

