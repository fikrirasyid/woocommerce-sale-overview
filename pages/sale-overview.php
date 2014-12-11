<div class="wrap">
	
	<?php 

	// Render tab
	$this->render_tab_nav(); 

	// Get prepared products
	$products = $this->product->get_products();

	// Render table
	$this->render_table( $products );
	?>
	
</div><!-- .wrap -->