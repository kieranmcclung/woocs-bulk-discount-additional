<?php
/**
 * Plugin Name: Woocommerce Bulk Discount Additional
 * Version: 1.0.0
 * Author: POP Branding
 * Author URI: http://pop-branding.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wbda_scripts()
{
	wp_register_script( 'wbda-price', plugin_dir_url( __FILE__ ) . '/js/wbda-ajax.js', array(), '1.0.0', true );
	wp_localize_script( 'wbda-price', 'wbda_ajax', array( 
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'security' => wp_create_nonce( 'wbda-super-secret' )
	));
	wp_enqueue_script( 'wbda-price' );
}
add_action( 'wp_enqueue_scripts', 'wbda_scripts' );

function wbda_update_price()
{
	check_ajax_referer( 'wbda-super-secret', 'security' );
	$product_id = $_POST['product_id'];
	$quantity = $_POST['quantity'];
	
	$price = wbda_get_price( $product_id, $quantity );
	
	echo $price;
	
	die();
}
add_action( 'wp_ajax_wbda_update_price', 'wbda_update_price' );

function wbda_get_price( $product_id, $quantity )
{
	if ( wbda_check_plugin_active() === false )
		return false;
	
	$product_meta = get_post_meta( $product_id );
	
	if ( $product_meta['_bulkdiscount_enabled'][0] !== 'yes' )
		return false;
	
	$discounts = wbda_get_discounts( $product_meta );
	
	if ( empty( $discounts ) ) 
		return false;
	
	$selector = 0;
	$i = 0;
	$total = count( $discounts['quantity'] );
	foreach ( $discounts['quantity'] as $key => $value )
	{
		$i++;
		if ( $i == $total )
		{
			$selector = $key;
		}
		else
		{
			if ( $i == 1 && $quantity < $value )
			{
				$selector = 0;
				break;
			}
			else
			{
				$next_value = $discounts['quantity'][$key+1];
				if ( $quantity >= $value && $quantity < $next_value )
				{
					$selector = $key;
					break;
				}
			}
		}
	}
	
	$_product = wc_get_product( $product_id );
	$price = $_product->get_price();
	
	if ( $selector === 0 )
		return wc_price( $price );
	
	$discount = $discounts['discount'][$selector];
	$price -= $discount;

	return wc_price( $price );
}

function wbda_get_discounts( $meta )
{
	$discount_option = get_option( 'woocommerce_t4m_discount_type' );
	$quantity_i = 1;
	$discount_i = 1;
	$discount_array = array();
	
	foreach ( $meta as $key => $data )
	{
		$value = $data[0];
		if ( $key == '_bulkdiscount_quantity_' . $quantity_i && $value != '' )
		{
			$discount_array['quantity'][$quantity_i] = $value;
			$quantity_i++;
		} 
		else if ( $key == '_bulkdiscount_discount_' . $discount_option . '_' . $discount_i && $value != '' ) 
		{
			$discount_array['discount'][$discount_i] = $value;
			$discount_i++;
		}
	}
	
	return $discount_array;
}

function wbda_display_discounts()
{
	global $post;
	$product_id = $post->ID;
	$_product = get_product( $product_id );
	$product_meta = get_post_meta( $product_id );
	$price = $_product->get_price();
	
	if ( $product_meta['_bulkdiscount_enabled'][0] !== 'yes' )
		return false;
	
	$view_data['discounts'] = wbda_format_discounts( wbda_get_discounts( $product_meta ), $price );
	$view_data['title'] = __( 'Quantity Discounts', 'wbda' );
	
	if ( ! empty( $view_data['discounts'] ) )
		return include_once( plugin_dir_path( __FILE__ ) . 'views/discount-table.php' );
}
add_action( 'woocommerce_single_product_summary', 'wbda_display_discounts', 15 );

function wbda_price_wrap_start()
{
	echo '<div id="wbda-dynamic-price">';
}
// Change to 8
add_action( 'woocommerce_single_product_summary', 'wbda_price_wrap_start', 8 );

function wbda_price_wrap_end()
{
	echo '</div>';
}
// Change to 12
add_action( 'woocommerce_single_product_summary', 'wbda_price_wrap_end', 12 );

function wbda_format_discounts( $discounts, $price )
{
	$separator = ' - ';
	$formatted_data = array();
	$formatted_data['table_header'][0] = '1';
	$formatted_data['table_body'][0] = wc_price( $price );
	
	$i = 1;
	$total = count( $discounts['quantity'] );
	
	foreach ( $discounts['quantity'] as $quantity )
	{
		if ( $i == $total )
		{
			$formatted_data['table_header'][$i] = $quantity . '+';
		}
		else
		{
			$next_discount = $discounts['quantity'][$i+1] - 1;
			$formatted_data['table_header'][$i] = $quantity . $separator . $next_discount;
		}
		
		$i++;
	}
	
	$i = 1;
	foreach ( $discounts['discount'] as $discount )
	{
		$formatted_data['table_body'][$i] = wc_price( $price - $discount );
		$i++;
	}
	
	return $formatted_data;
}

function wbda_check_plugin_active()
{
	if ( ! class_exists( 'Woo_Bulk_Discount_Plugin_t4m' ) ) {
		return false;
	}
	
	return true;
}

function wbda_admin_notice__warning() {
	$class = 'notice notice-warning';
	$message = __( 'This plugin requires WooCommerce Bulk Discount to be active', 'wbda' );

	if ( wbda_check_plugin_active() === false )
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
add_action( 'admin_notices', 'wbda_admin_notice__warning' );