<?php

/*
Plugin Name: Jigoshop Mini Cart
Plugin URI: http://tormorten.no
Description: A mini version of the shopping cart for Jigoshop.
Version: 0.1
Author: Tor Morten Jensen
Author URI: http://tormorten.no
*/

/**
 * Copyright (c) 2014 Tor Morten Jensen. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

define( 'JSMC_NAME',                 'Foundation Columns' );
define( 'JSMC_REQUIRED_PHP_VERSION', '5' );
define( 'JSMC_REQUIRED_WP_VERSION',  '3.7' );

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function jsmc_requirements_met() {

	global $wp_version;

	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	if ( version_compare( PHP_VERSION, JSMC_REQUIRED_PHP_VERSION, '<' ) )
		return false;

	if ( version_compare( $wp_version, JSMC_REQUIRED_WP_VERSION, '<' ) ) 
		return false;

	if( ! is_plugin_active('jigoshop/jigoshop.php') )
		return false;

	return true;
}

if( jsmc_requirements_met() ) {

	/**
	 * Localize the plugin
	 *
	 * @return 
	 */

	function jsmc_textdomain() {
		
		$domain = 'jsmc';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
			
	}

	add_action( 'plugins_loaded', 'jsmc_textdomain' );

	/**
	 * Enqueue the styles and script
	 *
	 * @return 
	 */

	function jsmc_styles_and_scripts() {

		// find css in the theme folder first
		$template = get_stylesheet_directory_uri() . '/minicart.css'; 

		// if it's not there, go ahead and use the plugin default
		if( !locate_template( 'minicart.css', false ) )
			$template = plugins_url( 'minicart.css', __FILE__ );

		// enqueue the style
		wp_enqueue_style( 'jigoshop-mini-cart', $template );

		// enqueue the script
		wp_enqueue_script( 'jigoshop-mini-cart', plugins_url( 'jigoshop-mini-cart.js', __FILE__ ), array( 'jquery' ) );

	}

	add_action( 'wp_enqueue_scripts', 'jsmc_styles_and_scripts' );

	/**
	 * Generate the mini cart HTML
	 *
	 * @return string
	 */

	function jsmc_html_cart() {
	
		
		if ( !sizeof( jigoshop_cart::$cart_contents) == 0 ) :
			jigoshop_cart::calculate_totals();
		?>
		
		<table>
			<thead>
				<tr>
					<th class="product_title"><?=__('Product', 'jsmc');?></th>
					<th class="product_qty"><?=__('Quantity', 'jsmc');?></th>
					<th class="product_price"><?=__('Price', 'jsmc');?></th>
				</tr>
			</thead>
			
			<tbody>
			<?php

			if (sizeof(jigoshop_cart::$cart_contents) > 0) :
				
		       	foreach ( jigoshop_cart::$cart_contents as $cart_item_key => $values ) :

		        	$_product = $values['data'];

					?>
					<tr>
						<td>
		                	<a href="<?php echo esc_url( apply_filters( 'jigoshop_product_url_display_in_cart', get_permalink( $values['product_id'] ), $cart_item_key ) ); ?>">
		                		<?php echo apply_filters( 'jigoshop_cart_product_title', wp_trim_words( $_product->get_title(), 4 ), $_product ); ?>
		                	</a>
		                </td>
		                <td>
		                	<?php 

		                	// start output
		                	ob_start();

		                	// get the quantity
		                	echo esc_attr( $values['quantity'] ); 

		                	// save it
		                	$quantity_display = ob_get_contents();

		                	// end output
		                	ob_end_clean();

		                	// print output
		                    echo apply_filters( 'jigoshop_product_quantity_display_in_cart', $quantity_display, $values['product_id'], $values );
		                    
		                	?>
		                </td>
		                <td>
		                	<?php echo jigoshop_price( $_product->get_price_excluding_tax() * $values['quantity'] ); ?>
		                </td>
		            </tr>
					<?php

				endforeach;

			endif;

			// Cart Total
			
			$cartquantity = jigoshop_cart::$cart_contents_count;

			?>
			</tbody>
			<tfoot>
				<tr>
					<td><strong><?php _e( 'Total', 'jsmc' ); ?>:</strong></td>
					<td><strong><?php echo $cartquantity; ?></strong></td>
					<td><strong><?php echo jigoshop_cart::get_cart_subtotal( true, true, true ); ?></strong></td>
				</tr>
			</tfoot>
		</table>
		
		<a class="jsmc-cart-button" href="<?php echo esc_url( get_permalink( jigoshop_get_page_id( 'cart' ) ) ); ?>"><?php _e('Open Shopping Cart', 'jsmc' ); ?> &raquo;</a>

		<?php 
		endif;
		
	}

	/**
	 * Add HTML to the footer
	 *
	 * @return string
	 */

	function jsmc_html( $position = 'absoluted' ) {
	
		?> 
		<div class="jigoshop-mini-cart <?php echo $position; ?>">

			<div class="cart-content">

				<?php echo jsmc_html_cart(); ?>

			</div>

			<div class="cart-info">

				<?php
				if(is_user_logged_in()) :

					global $current_user;
      				get_currentuserinfo();
      				$name = ($current_user->user_firstname ? $current_user->user_firstname : $current_user->user_login); 
      				
      				?>
      				<span class="user-logged-in">
      					<a href="<?php echo esc_url( get_permalink( jigoshop_get_page_id( 'myaccount' ) ) ); ?>">
      						<?php _e( 'Hello', 'jsmc' ); ?>, <?php echo $name;?>
      					</a> 
      					(<strong>
      						<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" title="<?php _e( 'Log out', 'jsmc' ); ?>">
								<?php strtolower( _e( 'Log out', 'jsmc' ) ); ?>
      						</a>
      					</strong>)
      				</span> - 

      			<?php else : ?>

					<span class="user-log-in">
						<a href="<?echo esc_url( get_permalink( jigoshop_get_page_id( 'myaccount' ) ) ); ?>">
							<?php _e( 'Log in', 'jsmc' ); ?>
						</a>
					</span> 
					<?php if( get_option('users_can_register') ) : ?>
					- <span>
						<a href="<?php echo esc_url( wp_registration_url() ); ?>">
							<?php _e( 'Create Account', 'jsmc'); ?>
						</a>
					</span>
					<?php endif; ?>

				<?php endif; ?>

				<?php if ( !sizeof( jigoshop_cart::$cart_contents ) == 0 ) : ?>

					<a class="mini-cart-trigger" href="#">
						<?php _e( 'Cart', 'jsmc'); ?> (<strong><?php

							echo jigoshop_cart::$cart_contents_count;

						?></strong>) 
						<img src="<?php echo plugins_url( 'cart.png', __FILE__ ) ?>" alt="" />
					</a>

				<?php else: ?>

					<a href="<?php echo esc_url( get_permalink( jigoshop_get_page_id( 'cart' ) ) );?>">
						<?php _e( 'Cart is Empty', 'jsmc' ); ?> 
						<img src="<?php echo plugins_url( 'cart.png', __FILE__ ) ?>" alt="" />
					</a>

				<?php endif; ?>

			</div>
		</div>
		<?php
	}

	add_action( 'wp_footer', 'jsmc_html', 1 ); // insert early



}
else {

	add_action( 'admin_notices', 'jsmc_error' );

}



/**
 * Throw and error upon activation if requirements are not met
 *
 * @return 
 */

function jsmc_error() {

	global $wp_version;

	?>

	<div class="error">
		<p><?php echo SMC_NAME; ?> error: Your environment doesn't meet all of the system requirements listed below.</p>

		<ul class="ul-disc">
			<li>
				<strong>PHP <?php echo JSMC_REQUIRED_PHP_VERSION; ?>+</strong>
				<em>(You're running version <?php echo PHP_VERSION; ?>)</em>
			</li>

			<li>
				<strong>WordPress <?php echo JSMC_REQUIRED_WP_VERSION; ?>+</strong>
				<em>(You're running version <?php echo esc_html( $wp_version ); ?>)</em>
			</li>

			<li>
				<strong>Jigoshop</strong>
				<em>(You need to have Jigoshop installed)</em>
			</li>
		</ul>

		<p>If you need to upgrade your version of PHP you can ask your hosting company for assistance, and if you need help upgrading WordPress you can refer to <a href="http://codex.wordpress.org/Upgrading_WordPress">the Codex</a>.</p>

		<p>You might be getting this error if there is already an instance of the plugin installed.</p>
	</div>

	<?php

}

?>