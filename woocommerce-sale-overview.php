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

			// Render wrapper
			$this->render_div( 'start', array( 'class' => 'wrap' ) );

			// Render title
			echo '<h2>'. __( "Sale Overview", "woocommerce-sale-overview" ) .'</h2><br />';

			// Get correct tabs data
			if( isset( $_GET['tab'] ) && 'scheduled' == $_GET['tab'] ){

				$products_ids = $this->product->get_scheduled_products_ids();

				$current_tab = 'scheduled';

			} elseif( isset( $_GET['tab'] ) && 'clear_cache' == $_GET['tab'] ) {

				$current_tab = 'clear_cache';

			} else {

				$products_ids = $this->product->get_sale_products_ids();

				$current_tab = 'current';

			}

			// Render tab nav
			$this->render_tab_nav( $current_tab );

			// render main view
			switch ( $current_tab ) {
				case 'clear_cache':

					// Removing Transient
					delete_transient( 'wc_sale_overview_variable_products_ids' );
					delete_transient( 'wc_sale_overview_sale_products_ids' );
					delete_transient( 'wc_sale_overview_scheduled_products_ids' );

					echo '<p style="margin: 40px 0;">';

					_e( 'All data cache has been deleted!', 'woocommerce-sale-overview' );

					echo '</p>';

					break;
				
				default:

					// Render table
					$this->render_table( $products_ids );

					break;
			}

			// Render wrapper
			$this->render_div( 'end' );
		}

		/**
		 * Adding div
		 * 
		 * @access private
		 * @param string 	start|end
		 * @param array 	attributes
		 * 
		 * @return void
		 */
		private function render_div( $mode = 'start', $attr = array() ){
			switch ( $mode ) {
				case 'end':
					$output = '</div>';
					break;
				
				default:

					$output = '<div';

					if( ! empty( $attr ) ){

						foreach( $attr as $key => $value ){
							$output .= " {$key}='{$value}'";
						}
					}

					$output .= '>';

					break;
			}

			echo $output;
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
				'clear_cache' 	=> __( 'Clear Cache', 'woocommerce-sale-overview' ),
			);

			echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

			foreach ( $tabs as $key => $label ) {
				if( $selected == $key ){
					echo '<a href="'. admin_url( "edit.php?post_type=product&page=woocommerce-sale-overview&tab=" . $key ) .'" class="nav-tab nav-tab-active">' . $label . '</a>';
				} else {					
					echo '<a href="'. admin_url( "edit.php?post_type=product&page=woocommerce-sale-overview&tab=" . $key ) .'" class="nav-tab">' . $label . '</a>';
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
		private function render_table( $products ){
			$no = 0;

			if( ! empty( $products ) ) :

				?>

				<table class="wp-list-table widefat fixed posts" style="margin-top: 20px;">
					<thead>
						<tr>
							<th style="width: 30px;"><?php _e( 'No.', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Name', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Brand', 'woocommerce-sale-overview' ); ?></th>
							<th style="width: 110px;"><?php _e( 'Product Type', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Variations', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Normal Price', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Sale Price', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Sale Percentage', 'woocommerce-sale-overview' ); ?></th>
							<th style="width: 150px;"><?php _e( 'Start Time', 'woocommerce-sale-overview' ); ?></th>
							<th style="width: 150px;"><?php _e( 'End Time', 'woocommerce-sale-overview' ); ?></th>
							<th style="width: 60px;"><?php _e( 'Image', 'woocommerce-sale-overview' ); ?></th>
						</tr>
					</thead>				
					<tbody id="the-list">

					<?php

					foreach( $products as $product_id ) :

						$product = wc_get_product( $product_id );

						// Skip variable product
						if( 'variable' == $product->product_type ){
							continue;
						}

						$no++;

						?>

						<tr <?php echo ( $no % 2 == 0 ) ? '' : 'class="alternate"' ?>>

							<td class="no">
								<?php echo $no; ?>.
							</td>

							<td class="name column-name">
								<?php echo $this->product->get_title( $product ); ?>

								<p>
									<a href="<?php echo get_permalink( $product->id ); ?>"><?php _e( 'View', 'woocommerce-sale-overview' ); ?></a> |
									<a href="<?php echo $this->product->get_edit_url( $product->id ); ?>"><?php _e( 'Edit', 'woocommerce-sale-overview' ); ?></a>
								</p>
							</td>	

							<td class="brand">
								<?php the_terms( $product->id, 'brand' ); ?>
							</td>		

							<td class="product-type">
								<?php echo $product->product_type; ?>
							</td>									

							<td>
								<?php 
									if( 'variation' == $product->product_type ) :
										echo $this->product->get_attributes( $product->get_variation_attributes() );										
									endif; 
								?>
							</td>

							<td class="price">
								<?php echo wc_price( $product->get_regular_price() ); ?>
							</td>					

							<td class="price">
								<?php echo wc_price( $product->get_sale_price() ); ?>
							</td>			

							<td class="percentage">
								<?php echo $this->product->get_sale_percentage( $product ); ?>
							</td>		

							<td class="time">
								<?php echo $this->product->get_sale_time( $product_id, 'from' ); ?>
							</td>		

							<td class="time">
								<?php echo $this->product->get_sale_time( $product_id, 'to' ); ?>
							</td>		

							<td class="thumb">
								<a href="<?php echo $this->product->get_edit_url( $product->id ); ?>" class="thumb-wrap" style="display: inline-block; width:50px;">
									<?php echo $product->get_image( 'shop_thumbnail', array( 'style' => 'width: 100%; height:auto;' ) ); ?>					
								</a>
							</td>

						</tr>

						<?php

					endforeach; // foreach( $products )

					?>
					
					</tbody>
				</table>

				<?php

			endif; // ! empty( $products )					
		}

	}
	new Woocommerce_Sale_Overview;
}