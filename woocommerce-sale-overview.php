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

			/**
			 * Variable products mess the counting mechanism: it doens't appear on the interface hence it need to be removed..
			 * from the product ids
			 * 
			 * Getting all variable product ids
			 */
			$variable_products_ids 		= $this->product->get_all_variable_products_ids();

			$sale_products_ids 			= array_diff( wc_get_product_ids_on_sale(), $variable_products_ids );

			$scheduled_products_ids 	= array_diff( $this->product->get_scheduled_products_ids(), $variable_products_ids );

			$sale_count 				= array( 
				'current' => count( $sale_products_ids ), 
				'scheduled' => count( $scheduled_products_ids ) 
			);

			/**
			 * Getting page variable
			 */
			if( isset( $_GET['page'] ) ){
				$page = intval( $_GET['page'] );
			} else {
				$page = 1;
			}

			$next_page = $page + 1;

			$products_per_page = 10;

			$products_start = ( $page * $products_per_page ) - $products_per_page - 1; // array_slice is zero based

			// Render wrapper
			$this->render_div( 'start', array( 'class' => 'wrap' ) );

			if( isset( $_GET['tab'] ) && 'scheduled' == $_GET['tab'] ){

				// Render tab
				$this->render_tab_nav( 'scheduled', $sale_count ); 

				// Get scheduled products
				$products_ids = $scheduled_products_ids;

			} else {
				
				// Render tab
				$this->render_tab_nav( 'current', $sale_count ); 

				// Get products ids
				$products_ids = $sale_products_ids;

			}

			// Get products
			$products = $this->product->get_products( array_slice( $products_ids, $products_start, $products_per_page ) );

			// Render table
			$this->render_table( $products, $next_page );

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
		private function render_tab_nav( $selected = 'current', $count = array() ){
			$default_count = array(
				'current' => 0,
				'scheduled' => 0
			);

			$count = wp_parse_args( $count, $default_count );

			$tabs = array(
				'current'	 	=> __( 'Currently on Sale', 'woocommerce-sale-overview' ),
				'scheduled' 	=> __( 'Scheduled for Sale', 'woocommerce-sale-overview' ),
			);

			echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

			foreach ( $tabs as $key => $label ) {
				if( $selected == $key ){
					echo '<a href="'. admin_url( "edit.php?post_type=product&page=woocommerce-sale-overview&tab=" . $key ) .'" class="nav-tab nav-tab-active">' . $label . ' ( '. $count[$key] .' )</a>';
				} else {					
					echo '<a href="'. admin_url( "edit.php?post_type=product&page=woocommerce-sale-overview&tab=" . $key ) .'" class="nav-tab">' . $label . ' ( '. $count[$key] .' )</a>';
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
							<th style="width: 110px;"><?php _e( 'Product Type', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Brand', 'woocommerce-sale-overview' ); ?></th>
							<th><?php _e( 'Name', 'woocommerce-sale-overview' ); ?></th>
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

					foreach( $products as $product ) :

						$no++;

						?>

						<tr <?php echo ( $no % 2 == 0 ) ? '' : 'class="alternate"' ?>>
					
							<?php 
								// Check whether currect product is the grouped product or not (such as variable - variations )
								if( is_array( $product ) ) : 
							?>
								
								<?php
									// Specific adjustment for variable product
									if ( isset( $product['variations'] ) && ! empty( $product['variations']) ) :

										// Calculate rowspan
										if( isset( $product['variations'] ) ){
											$rowspan = count( $product['variations'] );									
										} else {
											$rowspan = 0;
										}

										// Get first variation for reference, replacing the variable product
										$variable_product = current( $product['variations'] );
									?>

									<td class="product-type" rowspan="<?php echo $rowspan; ?>">
										<?php echo $variable_product->product_type; ?>
									</td>					

									<td class="brand" rowspan="<?php echo $rowspan; ?>">
										<?php the_terms( $variable_product->id, 'brand' ); ?>
									</td>	
								
									<td class="name column-name" rowspan="<?php echo $rowspan; ?>">

										<?php echo $this->product->get_title( $variable_product ); ?>

										<p>
											<a href="<?php echo get_permalink( $variable_product->id ); ?>"><?php _e( 'View', 'woocommerce-sale-overview' ); ?></a> |
											<a href="<?php echo $this->product->get_edit_url( $variable_product->id ); ?>"><?php _e( 'Edit', 'woocommerce-sale-overview' ); ?></a>
										</p>
									</td>

									<?php // VARIATIONS' LOOP STARTS ?>

									<?php if( isset( $product['variations'] ) && ! empty( $product['variations'] ) ) : ?>

										<?php $variation_index = 0; foreach( $product['variations'] as $variation ) : ?>

											<?php if ( $variation_index > 0 ) : ?>

												</tr><tr <?php echo ( $no % 2 == 0 ) ? '' : 'class="alternate"' ?>>

											<?php endif; ?>

											<td class="name">
												<p style="margin-bottom: 0; font-size: 13px; font-weight: bold;">
													<?php printf( __( 'ID: %d', 'woocommerce-sale-overview' ), $variation->get_variation_id() ); ?>											
												</p>

												<?php echo $this->product->get_attributes( $variation->get_variation_attributes() ); ?>
											</td>

											<td class="price">
												<?php echo wc_price( $variation->get_regular_price() ); ?>
											</td><!-- .price -->

											<td class="price">
												<?php echo wc_price( $variation->get_sale_price() ); ?>
											</td><!-- .price -->

											<td class="percentage">
												<?php echo $this->product->get_sale_percentage( $variation ); ?>
											</td>		

											<td class="time">
												<?php echo $this->product->get_sale_time( $variation->get_variation_id(), 'from' ); ?>
											</td>		

											<td class="time">
												<?php echo $this->product->get_sale_time( $variation->get_variation_id(), 'to' ); ?>
											</td>		

											<td class="thumb">
												<a href="<?php echo $this->product->get_edit_url( $variation->get_variation_id() ); ?>" class="thumb-wrap" style="display: inline-block; width:50px;">
													<?php echo $variation->get_image( 'shop_thumbnail', array( 'style' => 'width: 100%; height:auto;' ) ); ?>					
												</a>
											</td>

											<?php $variation_index++; ?>

										<?php endforeach; ?>

									<?php endif; ?>

									<?php // VARIATIONS' LOOP ENDS ?>

								<?php else : ?>

								<?php endif; // isset( $product['variable'] ) ?>

							<?php else : ?>			

								<td class="product-type">
									<?php echo $product->product_type; ?>
								</td>					

								<td class="brand">
									<?php the_terms( $product->id, 'brand' ); ?>
								</td>					

								<td class="name column-name">
									<?php echo $this->product->get_title( $product ); ?>

									<p>
										<a href="<?php echo get_permalink( $product->id ); ?>"><?php _e( 'View', 'woocommerce-sale-overview' ); ?></a> |
										<a href="<?php echo $this->product->get_edit_url( $product->id ); ?>"><?php _e( 'Edit', 'woocommerce-sale-overview' ); ?></a>
									</p>
								</td>		

								<td></td>

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
									<?php echo $this->product->get_sale_time( $product->id, 'from' ); ?>
								</td>		

								<td class="time">
									<?php echo $this->product->get_sale_time( $product->id, 'to' ); ?>
								</td>		

								<td class="thumb">
									<a href="<?php echo $this->product->get_edit_url( $product->id ); ?>" class="thumb-wrap" style="display: inline-block; width:50px;">
										<?php // echo $product->get_image( 'shop_thumbnail', array( 'style' => 'width: 100%; height:auto;' ) ); ?>					
									</a>
								</td>

							<?php endif; // isset( $product['variable'] ) ?>

						</tr>

						<?php

					endforeach; // foreach( $products )

					?>
					
					</tbody>
				</table>
				
				<p class="load-more-wrap" style="text-align: center; padding: 20px 0;">				
					<a href="#" class="button load-more"><?php _e( 'Load More', 'woocommerce-sale-overview' ); ?></a>
				</p>

				<?php

			endif; // ! empty( $products )					
		}

	}
	new Woocommerce_Sale_Overview;
}