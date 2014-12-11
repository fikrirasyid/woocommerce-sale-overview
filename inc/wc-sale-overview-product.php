<?php
/**
* Wrapping wc_get_product with various methods to speed up things
*/
class WC_Sale_Overview_Product{
	var $product;
	var $product_ids_on_sale;

	public function __construct(){
		$this->product_ids_on_sale = wc_get_product_ids_on_sale();		
	}

	/**
	 * Prepare products based on ids given
	 * 
	 * @access public
	 * @return obj
	 */	
	public function get_products(){
		$products = array();

		if( ! empty( $this->product_ids_on_sale ) ){

			foreach ( $this->product_ids_on_sale as $product_id ) {
				$product = wc_get_product( $product_id );

				if( 'variation' == $product->product_type ){					
					$products[$product->id]['variations'][$product->get_variation_id()] = $product;

				} elseif( 'variable' == $product->product_type ) {
					$products[$product->id]['variable'] = $product;
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

		$timestamp 	= get_post_meta( $product_id, "_sale_price_dates_{$mode}", true );

		if( $timestamp && $timestamp != '' ){
			$output .= date( 'l', $timestamp );
			$output .= '<br />';
			$output .= date( 'j M Y', $timestamp );
			$output .= '<br />';
			$output .= date( 'G:i', $timestamp );
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