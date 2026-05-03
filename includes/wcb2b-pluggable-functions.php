<?php

/**
 * WooCommerce B2B Pluggable functions
 *
 * @version 4.0.6
 */

defined( 'ABSPATH' ) || exit;

// Retrieve all groups
if ( ! function_exists( 'wcb2b_get_groups' ) ) {
	function wcb2b_get_groups() {
		return new WP_Query(
			array(
				'post_type'      => array( 'wcb2b_group' ),
				'post_status'    => array( 'publish' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}
}

// Display login message to guest users
if ( ! function_exists( 'wcb2b_login_message' ) ) {
	function wcb2b_login_message() {
		// If messages can be displayed
		if ( apply_filters( 'wcb2b_display_login_message', true ) ) {
			if ( ! is_user_logged_in() ) {
				echo '<p class="wcb2b_login_message"><a href="' . apply_filters( 'wcb2b_login_message_url', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) . '">' . apply_filters( 'wcb2b_login_message', esc_html__( 'Please, login to see prices and buy', 'woocommerce-b2b' ) ) . '</a><p>';
			}
		}
	}
}

// Validate VAT number by VIES
if ( ! function_exists( 'wcb2b_valid_vies' ) ) {
	function wcb2b_valid_vies( $country, $vat, $errors, $is_checkout ) {
		// Skip if SOAP is not enabled
		if ( ! extension_loaded( 'soap' ) ) {
			return true; }
		// Skip if country or VAT number aren't provided
		if ( empty( $country ) || empty( $vat ) ) {
			return true; }

		// Transcoding: WooCommerce => VIES
		$european = apply_filters(
			'wcb2b_vies_countries',
			array(
				'AT' => 'AT', // Austria
				'BE' => 'BE', // Belgium
				'BG' => 'BG', // Bulgaria
				'CY' => 'CY', // Cyprus
				'CZ' => 'CZ', // Czech Republic
				'DE' => 'DE', // Germany
				'DK' => 'DK', // Denmark
				'EE' => 'EE', // Estonia
				'GR' => 'EL', // Greece
				'ES' => 'ES', // Spain
				'FI' => 'FI', // Finland
				'FR' => 'FR', // France
				'HR' => 'HR', // Croatia
				'HU' => 'HU', // Hungary
				'IE' => 'IE', // Ireland
				'IT' => 'IT', // Italy
				'LT' => 'LT', // Lithuania
				'LU' => 'LU', // Luxembourg
				'LV' => 'LV', // Latvia
				'MT' => 'MT', // Malta
				'NL' => 'NL', // The Netherlands
				'PL' => 'PL', // Poland
				'PT' => 'PT', // Portugal
				'RO' => 'RO', // Romania
				'SE' => 'SE', // Sweden
				'SI' => 'SI', // Slovenia
				'SK' => 'SK',  // Slovakia
			)
		);
		// Skip if country is not EU
		if ( ! in_array( $country, array_keys( $european ) ) ) {
			return true; }

		$vies_validation = 'disabled';
		if ( $is_checkout ) {
			$vies_validation = get_option( 'wcb2b_vies_validation', 'disabled' );
		} else {
			$customer_group_id = get_option( 'wcb2b_guest_group', false );
			if ( $_POST['wcb2b_group'] ?? false ) {
				$customer_group_id = (int) $_POST['wcb2b_group'];
			}
			$vies_validation = get_post_meta( $customer_group_id, 'wcb2b_group_vies_validation', true );
		}
		if ( 'disabled' == $vies_validation ) {
			return true; }
		// VIES responses mapping
		$vies_responses = apply_filters(
			'wcb2b_vies_responses',
			array(
				'OK'                        => esc_html__( 'Valid', 'woocommerce-b2b' ),
				'WRONG'                     => esc_html__( 'VAT number is not verified by VIES', 'woocommerce-b2b' ),
				'INVALID_INPUT'             => esc_html__( 'The provided country is invalid or the VAT number is empty', 'woocommerce-b2b' ),
				'GLOBAL_MAX_CONCURRENT_REQ' => esc_html__( 'Your request cannot be processed due to high traffic, please try again', 'woocommerce-b2b' ),
				'MS_MAX_CONCURRENT_REQ'     => esc_html__( 'Your request cannot be processed due to high traffic from your country, please try again', 'woocommerce-b2b' ),
				'SERVICE_UNAVAILABLE'       => esc_html__( 'Server is currently unreachable, please try again', 'woocommerce-b2b' ),
				'MS_UNAVAILABLE'            => esc_html__( 'Server is currently unreachable from your country, please try again', 'woocommerce-b2b' ),
				'TIMEOUT'                   => esc_html__( 'Server did not respond within time limit, please try again', 'woocommerce-b2b' ),
				'UNKNOWN'                   => esc_html__( 'An unknown error has occurred, please try again', 'woocommerce-b2b' ),
			)
		);

		// Check if VAT has country code inside
		$vat = str_replace( $european[ $country ], '', $vat );

		$client = new SoapClient(
			'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl',
			array(
				'exception' => true,
			)
		);

		$params = array(
			'countryCode' => $european[ $country ],
			'vatNumber'   => $vat,
		);

		global $vies_validated_user, $vies_response;
		try {
			$vies_validated_user = true;
			$vies_response       = sprintf( '[OK] %s', $vies_responses['OK'] );

			$response = $client->__soapCall( 'checkVat', array( $params ) );
			if ( $response->valid != 1 ) {
				$vies_validated_user = false;
				$vies_response       = sprintf( '[WRONG] %s', $vies_responses['WRONG'] );
				if ( 'strict' == $vies_validation ) {
					$errors->add( 'invalid_woocommerce-b2b-vat-number', $vies_responses['WRONG'] );
				}
			}
		} catch ( SoapFault $e ) {
			$vies_validated_user = false;
			if ( $vies_responses[ $e->faultstring ] ?? false ) {
				$vies_response = sprintf( '[%s] %s', $e->faultstring, $vies_responses[ $e->faultstring ] );
			} else {
				$vies_response = sprintf( '[UNKNOWN] %s', $vies_responses['UNKNOWN'] );
			}
			// Do NOT try and catch "Exception" here
			if ( 'strict' == $vies_validation ) {
				$errors->add( 'invalid_woocommerce-b2b-vat-number', $vies_responses[ $e->faultstring ] ?? $vies_responses['UNKNOWN'] );
			}
		}
		return $errors;
	}
}

// Calculate prices by group
if ( ! function_exists( 'wcb2b_get_group_price' ) ) {
	function wcb2b_get_group_price( $default_price, $object_id, $type, $customer_id = false, $qty = 0 ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) && $customer_id === false ) {
			return $default_price;
		}

		// WPC Product Bundles for WooCommerce compatibility hook
		if ( apply_filters( 'wcb2b_ignored_calculate_price', false, $object_id, $type, $customer_id, $qty ) ) {
			return $default_price;
		}
		// /WPC Product Bundles for WooCommerce compatibility hook

		// If there is a customer group ID...
		if ( $customer_group_id = wcb2b_get_customer_group( $customer_id ) ) {
			// Check if group is not deleted
			if ( false !== get_post_status( $customer_group_id ) ) {
				// Get price visibility
				$product_group_hide_prices = get_post_meta( $object_id, 'wcb2b_product_group_hide_prices', true );
				if ( is_array( $product_group_hide_prices ) && in_array( $customer_group_id, $product_group_hide_prices ) ) {
					return '';
				}

				// Get prices rules settings
				$price_rule = get_post_meta( $customer_group_id, 'wcb2b_group_price_rule', true );

				// Fallback on default price
				$price = $default_price;

				if ( $price_rule && in_array( $price_rule, array( 'single', 'both' ) ) ) {
					// Retrieve dedicated prices configuration by product
					$product_group_prices = get_post_meta( $object_id, 'wcb2b_product_group_prices', true );

					if ( isset( $product_group_prices[ $customer_group_id ] ) && ! empty( $product_group_prices[ $customer_group_id ] ) ) {
						// Retrieve product dedicated prices configuration by group
						$group_regular_price = $product_group_prices[ $customer_group_id ]['regular_price'] ?? false;
						$group_sale_price    = $product_group_prices[ $customer_group_id ]['sale_price'] ?? false;

						// Which type of price I need?
						switch ( $type ) {
							case 'regular_price':
								// If there is a dedicated regular_price, get it
								if ( $group_regular_price ) {
									$price = $group_regular_price;
								}
								break;
							case 'sale_price':
								// If there is a dedicated regular_price, get related sale_price
								if ( $group_regular_price ) {
									$price = $group_sale_price;
								}
								break;
							case 'price':
								// If there is a dedicated regular_price, get related final price
								if ( $group_regular_price ) {
									$price = min( array_filter( array( $group_regular_price, $group_sale_price ) ) );
								}
								break;
						}
					}
				}

				if ( in_array( $price_rule, array( 'global', 'both' ) ) ) {
					// If current price is not empty...
					if ( ! empty( $price ) ) {
						// Retrieve group percentage discount
						$discount = get_post_meta( $customer_group_id, 'wcb2b_group_discount', true );
						if ( ! empty( $discount ) ) {
							$discount = wc_format_decimal( $discount );
							$price    = wc_format_decimal( $price );
							// Apply discount
							$price = $price - ( $price * $discount / 100 );
						}
					}
				}

				// Look for tier prices
				if ( ( is_admin() && ! wp_doing_ajax() ) || is_cart() || is_checkout() || ! did_action( 'woocommerce_before_main_content' ) ) {
					$product_group_tier_prices = get_post_meta( $object_id, 'wcb2b_product_group_tier_prices', true );
					if ( isset( $product_group_tier_prices[ $customer_group_id ] ) ) {
						$product_group_tier_price = $product_group_tier_prices[ $customer_group_id ];

						if ( is_admin() ) {
							foreach ( $product_group_tier_price as $tier_quantity => $tier_price ) {
								if ( $qty >= $tier_quantity ) {
									$price = $tier_price;
								}
							}
						} elseif ( ! is_null( WC()->cart ) ) {
							foreach ( WC()->cart->get_cart() as $cart_item ) {
								$cart_product_id = $cart_item['product_id'];
								if ( $cart_item['variation_id'] ) {
									$cart_product_id = $cart_item['variation_id'];
								}
								if ( $object_id == $cart_product_id ) {
									foreach ( $product_group_tier_price as $tier_quantity => $tier_price ) {
										if ( $cart_item['quantity'] >= $tier_quantity ) {
											$price = wc_format_decimal( $tier_price );
										}
									}
								}
							}
						}
					}
				}
				// Calculated price
				return $price;
			}
		}
		return $default_price;
	}
}

// Display tier prices including or excluding taxes
if ( ! function_exists( 'wcb2b_display_tier_price' ) ) {
	function wcb2b_display_tier_price( $price ) {
		return wc_price( wcb2b_adjust_price_tax( $price ) );
	}
}

// Display recommended retail price (simple)
if ( ! function_exists( 'wcb2b_display_rrp' ) ) {
	function wcb2b_display_rrp( $price_html, $object ) {
		global $woocommerce_loop;
		if ( is_product() && is_user_logged_in() && empty( $woocommerce_loop['name'] ) ) {
			$group_id = get_the_author_meta( 'wcb2b_group', get_current_user_id() );
			if ( get_option( 'wcb2b_guest_group', 0 ) != $group_id ) {
				if ( in_array( $object->get_type(), array( 'simple' ) ) ) {
					$regular_price = get_post_meta( $object->get_id(), '_regular_price', true );
					if ( ! empty( $regular_price ) ) {
						$rrp = wc_price( $regular_price );

						ob_start();
						wc_get_template(
							'single-product/rrp-price.php',
							array(
								'wcb2b_rrp' => $rrp,
							),
							WCB2B_OVERRIDES,
							WCB2B_ABSPATH . 'templates/'
						);
						$rrp_html = ob_get_contents();
						ob_end_clean();
						$price_html .= $rrp_html;
					}
				}
			}
		}
		return $price_html;
	}
}

// Display recommended retail price (variable)
if ( ! function_exists( 'wcb2b_display_variation_rrp' ) ) {
	function wcb2b_display_variation_rrp( $args, $product, $variation ) {
		global $woocommerce_loop;
		if ( is_product() && is_user_logged_in() && empty( $woocommerce_loop['name'] ) ) {
			$group_id = get_the_author_meta( 'wcb2b_group', get_current_user_id() );
			if ( get_option( 'wcb2b_guest_group', 0 ) != $group_id ) {
				if ( in_array( $product->get_type(), array( 'variable' ) ) ) {
					$regular_price = get_post_meta( $variation->get_id(), '_regular_price', true );
					if ( ! empty( $regular_price ) ) {
						$rrp = wc_price( $regular_price );

						ob_start();
						wc_get_template(
							'single-product/rrp-price.php',
							array(
								'wcb2b_rrp' => $rrp,
							),
							WCB2B_OVERRIDES,
							WCB2B_ABSPATH . 'templates/'
						);
						$rrp_html = ob_get_contents();
						ob_end_clean();
						$args['price_html'] .= $rrp_html;
					}
				}
			}
		}
		return $args;
	}
}

// Display barcode
if ( ! function_exists( 'wcb2b_display_barcode' ) ) {
	function wcb2b_display_barcode() {
		global $product;

		$barcode = get_post_meta( $product->get_id(), 'wcb2b_barcode', true );
		if ( apply_filters( 'wcb2b_show_barcode', true, $product->get_id() ) ) {
			ob_start();
			wc_get_template(
				'single-product/barcode.php',
				array(
					'wcb2b_barcode' => $barcode,
				),
				WCB2B_OVERRIDES,
				WCB2B_ABSPATH . 'templates/'
			);
			$barcode_html = ob_get_contents();
			ob_end_clean();
			echo $barcode_html;
		}
	}
}

// Get tier prices including or excluding taxes
if ( ! function_exists( 'wcb2b_adjust_price_tax' ) ) {
	function wcb2b_adjust_price_tax( $price ) {
		global $product;

		$fn = 'wc_get_price_excluding_tax';
		if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			$fn = 'wc_get_price_including_tax';
		}
		return (float) $fn( $product, array( 'price' => $price ) );
	}
}

// Return unallowed product categories ids, filtered by user (if logged in) or empty
if ( ! function_exists( 'wcb2b_get_unallowed_terms' ) ) {
	function wcb2b_get_unallowed_terms() {
		return WCB2B_Configuration::get_unallowed_terms();
	}
}

// Return unallowed products ids (belonging unallowed product categories), filtered by user (if logged in) or empty
if ( ! function_exists( 'wcb2b_get_unallowed_products' ) ) {
	function wcb2b_get_unallowed_products() {
		return WCB2B_Configuration::get_unallowed_products();
	}
}

// Set unallowed terms for current customer group in WooCommerce global class
if ( ! function_exists( 'wcb2b_set_unallowed_terms' ) ) {
	function wcb2b_set_unallowed_terms() {
		// Empty = All, so we fix with a fake ID
		$unallowed = array();
		if ( $user_group = wcb2b_get_customer_group() ) {
			// If no terms, return empty array
			if ( $terms = get_terms(
				array(
					'taxonomy'   => array( 'product_cat', 'product_brand' ),
					'hide_empty' => false,
				)
			) ) {
				if ( ! is_wp_error( $terms ) ) {
					// Check for each term if can be visible
					foreach ( $terms as $term ) {
						// If term meta doesn't exists, by default is visible
						if ( metadata_exists( 'term', $term->term_id, 'wcb2b_group_visibility' ) ) {
							$group_visibility = get_term_meta( $term->term_id, 'wcb2b_group_visibility', true );
							if ( ! in_array( (int) $user_group, (array) $group_visibility ) ) {
								$unallowed[] = $term->term_id;
							}
						}
					}
				}
			}
		}
		return $unallowed;
	}
}

// Set unallowed products for current customer group in WooCommerce global class
if ( ! function_exists( 'wcb2b_set_unallowed_products' ) ) {
	function wcb2b_set_unallowed_products() {
		$args     = array(
			'post_type'      => 'product',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'tax_query'      => array(
				'relation' => 'OR',
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => wcb2b_get_unallowed_terms(),
					'operator'         => 'IN',
					'include_children' => false,
				),
				array(
					'taxonomy'         => 'product_brand',
					'field'            => 'term_id',
					'terms'            => wcb2b_get_unallowed_terms(),
					'operator'         => 'IN',
					'include_children' => false,
				),
			),
		);
		$products = new WP_Query( $args );
		return $products->post_count ? (array) $products->posts : array( -1 );
	}
}

// Format prices for display
if ( ! function_exists( 'wcb2b_price_format' ) ) {
	function wcb2b_price_format( $price ) {
		$price = number_format(
			floatval( $price ),
			wc_get_price_decimals(),
			wc_get_price_decimal_separator(),
			wc_get_price_thousand_separator()
		);
		if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && wc_get_price_decimals() > 0 ) {
			$price = wc_trim_zeros( $price );
		}
		return $price;
	}
}

// Get WooCommerce pages to hide with restricted catalog option enabled
if ( ! function_exists( 'wcb2b_get_restricted_pages' ) ) {
	function wcb2b_get_restricted_pages() {
		return apply_filters( 'wcb2b_restricted_pages', wcb2b_get_wc_pages() );
	}
}

// Get always visibile pages
if ( ! function_exists( 'wcb2b_get_always_visible_pages' ) ) {
	function wcb2b_get_always_visible_pages() {
		$pages = wcb2b_get_wc_pages();
		array_push( $pages, get_option( 'page_on_front' ) );
		return apply_filters( 'wcb2b_always_visible_pages', $pages );
	}
}

// Get product quantity notices
if ( ! function_exists( 'wcb2b_get_quantity_notices' ) ) {
	function wcb2b_get_quantity_notices( $packages, $min, $max ) {
		$html = '';
		if ( ! $max && $min && $min > 1 ) {
			$html .= '<p class="wcb2b_minimum_message">' . apply_filters( 'wcb2b_minimum_message', sprintf( esc_html__( 'You must purchase at least %s of this product', 'woocommerce-b2b' ), $min ), $min ) . '</p>';
		}
		if ( ! $min && $max && $max > 1 ) {
			$html .= '<p class="wcb2b_maximum_message">' . apply_filters( 'wcb2b_maximum_message', sprintf( esc_html__( 'You can purchase at most %s of this product', 'woocommerce-b2b' ), $max ), $max ) . '</p>';
		}
		if ( $min && $min > 1 && $max && $max > 1 ) {
			$html .= '<p class="wcb2b_minmax_message">' . apply_filters( 'wcb2b_minmax_message', sprintf( esc_html__( 'You must purchase at least %1$s and at most %2$s of this product', 'woocommerce-b2b' ), $min, $max ), $min, $max ) . '</p>';
		}
		if ( $packages && $packages > 1 ) {
			$html .= '<p class="wcb2b_increment_message">' . apply_filters( 'wcb2b_increment_message', sprintf( esc_html__( 'This product can be purchased by increments of %s', 'woocommerce-b2b' ), $packages ), $packages ) . '</p>';
		}
		return $html;
	}
}


// Display "Already bought"
if ( ! function_exists( 'wcb2b_already_bought' ) ) {
	function wcb2b_already_bought() {
		global $product;

		if ( wc_customer_bought_product( false, get_current_user_id(), $product->get_id() ) ) {
			ob_start();
			wc_get_template( 'single-product/already-bought.php', array(), WCB2B_OVERRIDES, WCB2B_ABSPATH . 'templates/' );
			$html = ob_get_contents();
			ob_end_clean();
			echo $html;
		}
	}
}

// Display product sales
if ( ! function_exists( 'wcb2b_product_sales' ) ) {
	function wcb2b_product_sales() {
		global $product;

		if ( $sales = $product->get_total_sales() ) {
			ob_start();
			wc_get_template(
				'single-product/sales.php',
				array(
					'sales' => $sales,
				),
				WCB2B_OVERRIDES,
				WCB2B_ABSPATH . 'templates/'
			);
			$html = ob_get_contents();
			ob_end_clean();
			echo $html;
		}
	}
}

// Calculate unpaid orders total amount
if ( ! function_exists( 'wcb2b_get_total_unpaid' ) ) {
	function wcb2b_get_total_unpaid( $customer_id = 0 ) {
		$amount = 0;
		if ( 0 === $customer_id ) {
			$customer_id = get_current_user_id();
		}
		$pendings = wc_get_is_pending_statuses();
		$args     = apply_filters(
			'wcb2b_unpaid_orders_args',
			array(
				'limit'       => -1,
				'customer_id' => $customer_id,
				'type'        => 'shop_order',
				'status'      => array_filter(
					array_keys( wc_get_order_statuses() ),
					function ( $k, $v ) use ( $pendings ) {
						return in_array( str_replace( 'wc-', '', $k ), $pendings );
					},
					ARRAY_FILTER_USE_BOTH
				),
			),
			$customer_id
		);
		$orders   = new WC_Order_Query( $args );
		foreach ( $orders->get_orders() as $order ) {
			$amount += $order->get_total();
		}
		return $amount;
	}
}

// Get estimated delivery date range for a product
if ( ! function_exists( 'wcb2b_estimated_delivery' ) ) {
	function wcb2b_estimated_delivery( $product_id ) {
		if ( $customer_group_id = wcb2b_get_customer_group() ) {
			if ( get_post_meta( $customer_group_id, 'wcb2b_group_show_deliverytime', true ) ) {
				$product           = wc_get_product( $product_id );
				$working_days      = get_option( 'wcb2b_delivery_weekdays', array() );
				$holidays          = explode( ',', get_option( 'wcb2b_delivery_holidays', array() ) );
				$shipping_class_id = $product->get_shipping_class_id();
				// Get delivery days
				$days_min = get_term_meta( $shipping_class_id, 'wcb2b_delivery_days_min', true );
				$days_max = get_term_meta( $shipping_class_id, 'wcb2b_delivery_days_max', true );
				$days_min = apply_filters( 'wcb2b_delivery_days_min', $days_min, $product_id );
				$days_max = apply_filters( 'wcb2b_delivery_days_max', $days_max, $product_id );

				// WooCommerce B2B Warehouses support
				$days     = apply_filters( 'wcb2b_compatibility__warehouse_extra_delivery_days', array( $days_min, $days_max ), $product_id, $product );
				$days_min = $days[0];
				$days_max = $days[1];
				if ( ! empty( $working_days ) ) {
					if ( ! empty( $days_min ) && ! empty( $days_max ) ) {
						// Delivery date (min)
						$date_min = new DateTime();
						while ( $days_min > 0 ) {
							$date_min->modify( '+1 days' );
							if ( in_array( $date_min->format( 'N' ), $working_days ) && ! in_array( $date_min->format( 'd-m' ), $holidays ) ) {
								--$days_min;
							}
						}
						// Delivery date (max)
						$date_max = new DateTime();
						while ( $days_max > 0 ) {
							$date_max->modify( '+1 days' );
							if ( in_array( $date_max->format( 'N' ), $working_days ) && ! in_array( $date_max->format( 'd-m' ), $holidays ) ) {
								--$days_max;
							}
						}
						// Is same date?
						$same = $date_min->format( 'U' ) == $date_max->format( 'U' );
						// Format
						$format_min = 'd M';
						$format_max = 'd M';
						if ( $date_min->format( 'M' ) == $date_max->format( 'M' ) ) {
							if ( ! $same ) {
								$format_min = 'd';
							}
						}
						// Calculate date range
						$delivery_date_min = date_i18n( $format_min, $date_min->format( 'U' ) );
						$delivery_date_max = date_i18n( $format_max, $date_max->format( 'U' ) );
						// Calculate extimate delivery range
						if ( $same ) {
							$estimated_delivery = sprintf(
								'%s',
								$delivery_date_min
							);
						} else {
							$estimated_delivery = sprintf(
								'%s - %s',
								$delivery_date_min,
								$delivery_date_max
							);
						}
						// Show
						return $estimated_delivery;
					}
				}
			}
		}
		return false;
	}
}

// When quotations are enabled with hidden prices, price text change
if ( ! function_exists( 'wcb2b_quotation_prices_placeholder' ) ) {
	function wcb2b_quotation_prices_placeholder() {
		return sprintf( '<em>(%s)</em>', esc_html__( 'To be quoted', 'woocommerce-b2b' ) );
	}
}

// When quotations are enabled with hidden prices, add to cart text change
if ( ! function_exists( 'wcb2b_custom_add_cart_button' ) ) {
	function wcb2b_custom_add_cart_button() {
		return esc_html__( 'Add to quotation', 'woocommerce-b2b' );
	}
}

// Get pages with loginform shortcode
if ( ! function_exists( 'wcb2b_loginform_pages' ) ) {
	function wcb2b_loginform_pages() {
		return get_posts(
			array(
				'fields'         => 'ids',
				'post_type'      => 'page',
				'meta_key'       => 'wcb2bloginform',
				'posts_per_page' => -1,
			)
		);
	}
}
