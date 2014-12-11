<?php
/**
 * Plugin Name: WooCommerce Sale Overview
 * Description: Overviewing all sale schedule in a single glance
 * Version: 0.1
 * Author: Fikri Rasyid
 * Author URI: http://fikrirasyid.com
 * Requires at least: 3.9
 * Tested up to: 3.9
 *
 * Text Domain: woocommerce-sale-overview
 *
 * @package WooCommerce
 * @category Product
 * @author Fikri Rasyid
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    /**
     * If the plugin is called before woocommerce, we need to include it first
     */
    if( !class_exists( 'Woocommerce' ) ){
        require_once( plugin_dir_path( dirname( __FILE__ ) ) . '/woocommerce/woocommerce.php' );    	
    }

    /**
     * Load external files
     */
    require_once( plugin_dir_path( __FILE__ ) . 'inc/wc-sale-overview-product.php' );

    /**
     * Define plugin main class
     */
	class Woocommerce_Sale_Overview{

		var $plugin_url;
		var $plugin_dir;

		function __construct(){
			$this->plugin_url 	= untrailingslashit( plugins_url( '/', __FILE__ ) );
			$this->plugin_dir 	= dirname( __FILE__ );
			$this->product 		= new WC_Sale_Overview_Product;

			// Add submenu
			add_action( 'admin_menu', array( $this, 'add_page' ) );
		}

		/**
		 * Register the page
		 * 
		 * @access public
		 * @return void
		 */
		public function add_page(){
			add_submenu_page( 
				'edit.php?post_type=product', 
				__( 'Sale Overview', 'woocommerce-sale-overview' ), 
				__( 'Sale Overview', 'woocommerce-sale-overview' ), 
				'manage_woocommerce', 
				'woocommerce-sale-overview', 
				array( $this, 'render_page' ) 
			);			
		}

		/**
		 * Render page
		 * 
		 * @access public
		 * @return void
		 */
		public function render_page(){
			include_once( $this->plugin_dir . '/pages/sale-overview.php' );
		}

		/**
		 * Render tab
		 * 
		 * @access private
		 * @param string  	current|scheduled
		 * @return void
		 */
		private function render_tab_nav( $selected = 'current' ){

			$tabs = array(
				'current'	 	=> __( 'Currently on Sale', 'woocommerce-sale-overview' ),
				'scheduled' 	=> __( 'Scheduled for Sale', 'woocommerce-sale-overview' ),
			);

			echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

			foreach ( $tabs as $key => $label ) {
				if( $selected == $key ){
					echo '<a href="" class="nav-tab nav-tab-active">' . $label . '</a>';
				} else {					
					echo '<a href="" class="nav-tab">' . $label . '</a>';
				}
			}

			echo '</h2>';
		}

		/**
		 * Render sale table
		 * 
		 * @access private
		 * @param obj 	grouped product object
		 * @return void
		 */
		private function render_sale_table( $products ){

		}

	}
	new Woocommerce_Sale_Overview;
}