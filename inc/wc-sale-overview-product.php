<?php
/**
* Wrapping wc_get_product with various methods to speed up things
*/
class WC_Sale_Overview_Product{	

	/**
	 * Get all variable product id count
	 * 
	 * @return array 	IDs
	 */
	public function get_all_variable_products_ids(){
		global $wpdb;

		$transient_key = 'wc_sale_overview_variable_products_ids';

		$posts_ids = get_transient( $transient_key );

		if( ! $posts_ids ){
			$posts = $wpdb->get_results("SELECT posts.ID FROM 
									{$wpdb->posts} posts, 
									{$wpdb->term_relationships} term_relationships, 
									{$wpdb->term_taxonomy} term_taxonomy,
									{$wpdb->terms} terms
									WHERE terms.name = 'variable'
									AND terms.term_id = term_taxonomy.term_id
									AND term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
									AND term_relationships.object_id = posts.ID
									AND posts.post_type = 'product'
									AND posts.post_status = 'publish'");

			$posts_ids = array();

			// Prepare the result
			if( ! empty( $posts ) ){

				foreach ( $posts as $post ) {

					$posts_ids[] = intval( $post->ID );

				}
			}

			set_transient( $transient_key, $posts_ids, YEAR_IN_SECONDS );
		}

		return $posts_ids;
	}

	/**
	 * Get sale product ID
	 * Apparently wc_get_product_ids_on_sale() isn't as reliable as it should be.
	 * Sometimes when user deletes product, the transient weren't deleted
	 * Which causes a quite of a problem 
	 * 
	 * @return array
	 */
	public function get_sale_products_ids(){

		$transient_key = 'wc_sale_overview_sale_products_ids';

		$products_ids = get_transient( $transient_key );

		if( ! $products_ids ){

			$sale_products_ids = wc_get_product_ids_on_sale();

			$products_args = array(
				'post_type' 		=> array( 'product', 'product_variation' ),
				'post__in' 			=> $sale_products_ids,
				'posts_per_page'	=> -1
			);

			$products_obj = get_posts( $products_args );

			$products_ids = array();

			foreach ( $products_obj as $product ) {
				$products_ids[] = $product->ID;
			}

			$expiration = 60 * 60 * 24; // A day

			set_transient( $transient_key, $products_ids, $expiration );

		}

		return $products_ids;
	}

	/**
	 * Get scheduled product ID
	 * 
	 * @return array
	 */
	public function get_scheduled_products_ids(){

		$transient_key = 'wc_sale_overview_scheduled_products_ids';

		$posts_ids = get_transient( $transient_key );

		if( ! $posts_ids ){

			$current_time = current_time( 'timestamp' );

			$args = array(
				'post_type' 	=> array( 'product', 'product_variation' ),
				'post_status' 	=> 'publish',
				'posts_per_page'=> -1,
				'meta_key'		=> '_sale_price_dates_from',
				'meta_value'	=> $current_time,
				'meta_compare'	=> '>='
			);

			$products = get_posts( $args );

			$posts_ids = array();

			if( ! empty( $products ) ){
				foreach ( $products as $product ) {
					$posts_ids[] = $product->ID;
				}
			}

			$expiration = 60 * 60 * 24; // A day

			set_transient( $transient_key, $posts_ids, $expiration );

		}

		return $posts_ids;
	}

	/**
	 * Prepare products based on ids given
	 * 
	 * @access public
	 * @return obj
	 */	
	public function get_products( $products_ids = array() ){
		$products = array();

		if( ! empty( $products_ids ) ){

			foreach ( $products_ids as $product_id ) {

				$product = wc_get_product( $product_id );

				if( 'variation' == $product->product_type ){					
					$products[$product->id]['variations'][$product->get_variation_id()] = $product;
				} else {
					$products[$product->id] = $product;
				}

			}
		}

		return $products;
	}

	/**
	 * Get edit url to editor based on product ID given
	 * 
	 * @access public
	 * @param int  		product id
	 * @return string 	link to edit page
	 */
	public function get_edit_url( $product_id ){
		return admin_url( "post.php?post={$product_id}&action=edit" ); 
	}

	/**
	 * Get title wrapped with anchor tag to edit page
	 * 
	 * @access public
	 * @param obj 		product obj
	 * @return string  	link + title to edit page
	 */
	public function get_title( $product ){
		$title = '<strong><a href="'. $this->get_edit_url( $product->id ) .'" title="'. $product->get_title() .'" class="">'. $product->get_title() .'</a></strong>';

		return $title;
	}

	/**
	 * Get sale percentage
	 * 
	 * @access public
	 * @param obj 			product obj
	 * @return int|string 	percentage
	 */
	public function get_sale_percentage( $product ){
		/**
		 * Prevent division by zero
		 */
		if( 0 == $product->get_regular_price() )
			return '-';

		$decimal = ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100;

		return sprintf ("%.2f%%", $decimal );
	}

	/**
	 * Get sale time
	 * 
	 * @access public
	 * @param id 		product id
	 * @param string 	to|from
	 * @return string 	time
	 */
	public function get_sale_time( $product_id, $mode = 'from' ){
		
		$output = '';

		$current_time = current_time( 'timestamp' );
		$timestamp 	= get_post_meta( $product_id, "_sale_price_dates_{$mode}", true );

		if( $timestamp && $timestamp != '' ){
			$output .= date( 'l', $timestamp );
			$output .= '<br />';
			$output .= date( 'j M Y', $timestamp );
			$output .= '<br />';
			$output .= date( 'G:i', $timestamp );
			$output .= '<br /><br />';

			if( $current_time < $timestamp ){
				$output .= sprintf( __( '%s from now', 'woocommerce-sale-overview' ), human_time_diff( $current_time, $timestamp ) );
			} else {
				$output .= sprintf( __( '%s ago', 'woocommerce-sale-overview' ), human_time_diff( $current_time, $timestamp ) );
			}

		} else {
			$output .= '-';
		}		

		return $output;
	}

	/**
	 * Get attributes
	 * 
	 * @access public
	 * @param array 	attributes
	 * @return string 	attributes
	 */
	public function get_attributes( $variations ){

		$output = '<ul style="margin: 0;">';

		if( ! empty( $variations ) ) {

			foreach( $variations as $key => $variation ){

				$label = str_replace( 'attribute_pa_', '', $key );
				$label = str_replace( '_', ' ', $label );

				$output .= '<li style="margin: 0;">';

				$output .= '<span class="label"><strong>'. $label .':</strong> </span>';

				$output .= '<span class="label">'. $variation .'</span>';

				$output .= '</li>';

			}

		}

		$output .= '</ul>';

		return $output;
	}
}