<?php
/**
 * WC_Bundled_Item class
 *
 * @author   SomewhereWarm <info@somewherewarm.gr>
 * @package  WooCommerce Product Bundles
 * @since    4.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bundled Item Product Container class.
 *
 * The bunded item class is a product container that initializes and holds pricing, availability and variation/attribute-related data of a bundled product.
 *
 * @class    WC_Bundled_Item
 * @version  5.9.2
 */
class WC_Bundled_Item {

	/**
	 * Bundled item settings meta are copied from the low-level data object to this array - @see WC_Bundled_Item::load_data().
	 * @var array
	 */
	public $item_data = array();

	/**
	 * A reference to the bundled item data object - @see WC_Bundled_Item_Data.
	 * @var WP_Bundled_Item_Data
	 */
	public $data = null;

	/**
	 * Product instance of the associated bundled product.
	 * @var WC_Product
	 */
	public $product;

	/**
	 * Product instance of the parent Bundle.
	 * @var WC_Product_Bundle
	 */
	private $bundle;

	/**
	 * The title of the bundled item.
	 * @var string
	 */
	private $title;

	/**
	 * The short description of the bundled item.
	 * @var string
	 */
	private $description;

	/**
	 * Visibility of the bundled item in the single product, cart and order templates.
	 * @var array
	 */
	private $visibility;

	/**
	 * Price visibility of the bundled item in the single product, cart and order templates.
	 * @var array
	 */
	private $price_visibility;

	/**
	 * Optional status of the bundled item.
	 * @var string
	 */
	private $optional;

	/**
	 * Min quantity of the bundled item.
	 * @var boolean
	 */
	private $quantity_min;

	/**
	 * Max quantity of the bundled item.
	 * @var boolean
	 */
	private $quantity_max;

	/**
	 * Pricing scheme of the bundled item.
	 * @var string
	 */
	private $priced_individually;

	/**
	 * Shipping scheme of the bundled item.
	 * @var string
	 */
	private $shipped_individually;

	/**
	 * Bundled item price & recurring price discount when the bundled item is priced individually.
	 * @var double
	 */
	private $discount;

	/**
	 * Bundled item sign-up price discount when the bundled item is priced individually (unused).
	 * @var double
	 */
	private $sign_up_discount;

	/**
	 * Array of default variation attribute selections to override, or false when no overrides are defined.
	 * @var array|false
	 */
	private $default_variation_attributes;

	/**
	 * Array of variation ids to include, or false when no variation filters exist.
	 * @var array|false
	 */
	private $allowed_variations;

	/**
	 * True if the thumbnail is set to be hidden.
	 * @var boolean
	 */
	private $hide_thumbnail;

	/**
	 * True if the bundled product is a Name-Your-Price product.
	 * @var boolean
	 */
	private $is_nyp = false;

	/**
	 * Stock status of the bundled product.
	 * @var string
	 */
	private $stock_status = null;

	/**
	 * Maximum stock available in a single bundle purchase.
	 * Identical to the product stock for simple products. For variable items, it is the max stock-managed variation stock when all variations manage stock.
	 * @var mixed
	 */
	private $max_stock = null;

	/**
	 * Raw meta prices used in the min/max bundle price calculation.
	 * @var string
	 */
	public $min_price;
	public $max_price;
	public $min_regular_price;
	public $max_regular_price;
	public $min_recurring_price;
	public $max_recurring_price;
	public $min_regular_recurring_price;
	public $max_regular_recurring_price;

	/**
	 * Products corresponding to the min/max (regular) price at which the bundled product can be purchased. If the bundled product is variable, these will contain the associated variations, otherwise they are identical to the 'product' property.
	 * @var WC_Product
	 */
	public $min_price_product;
	public $max_price_product;
	public $min_regular_price_product;
	public $max_regular_price_product;

	/**
	 * Flag to indicate whether min/max props have been synced.
	 * @var boolean
	 */
	private $synced_prices = false;

	/**
	 * Runtime cache for 'get_variation_attributes()' calls.
	 * @var array
	 */
	private $product_attributes;

	/**
	 * Runtime cache for 'get_selected_product_variation_attributes()' calls.
	 * @var array
	 */
	private $selected_product_attributes;

	/**
	 * Runtime cache for 'get_product_variations()' calls.
	 * @var array
	 */
	private $product_variations;

	/**
	 * __construct method.
	 *
	 * @param  mixed  $bundled_item_id
	 * @param  mixed  $parent
	 */
	public function __construct( $bundled_item, $parent = false ) {

		if ( is_numeric( $bundled_item ) ) {
			$this->data = WC_PB_DB::get_bundled_item( absint( $bundled_item ) );
		} elseif ( $bundled_item instanceof WC_Bundled_Item_Data ) {
			$this->data = $bundled_item;
		}

		if ( is_object( $this->data ) ) {

			if ( is_object( $parent ) && $this->get_bundle_id() === $parent->get_id() ) {
				$this->bundle = $parent;
			}

			$this->load_data();

			/**
			 * 'woocommerce_before_init_bundled_item' action.
			 *
			 * @param  WC_Bundled_Item  $this
			 */
			do_action( 'woocommerce_before_init_bundled_item', $this );

			$bundled_product = wc_get_product( $this->get_product_id() );

			// if not present, item cannot be purchased.
			if ( $bundled_product ) {

				$this->product     = $bundled_product;
				$this->title       = 'yes' === $this->override_title ? $this->title : $bundled_product->get_title();
				$this->description = 'yes' === $this->override_description ? $this->description : $bundled_product->get_short_description();

				if ( false !== $parent && $this->is_purchasable() && $this->is_priced_individually() ) {
					$this->sync_prices();
				}
			}

			/**
			 * 'woocommerce_after_init_bundled_item' action.
			 *
			 * @param  WC_Bundled_Item  $this
			 */
			do_action( 'woocommerce_after_init_bundled_item', $this );
		}
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'item_id' :
				$value = $this->get_id();
				break;
			case 'product_id' :
				$value = $this->get_product_id();
				break;
			case 'bundle_id' :
				$value = $this->get_bundle_id();
				break;
			default :
				$value = null;
				break;
		}
		return $value;
	}

	/**
	 * Get bundled item ID.
	 * Returns the ID of the associated WC_Bundled_Item_Data object - @see WC_Bundled_Item_Data class and WC_PB_Install::get_schema().
	 *
	 * @return int|null
	 */
	public function get_id() {
		return is_object( $this->data ) ? $this->data->get_id() : null;
	}

	/**
	 * Get the ID of the associated product.
	 *
	 * @return int|null
	 */
	public function get_product_id() {
		return is_object( $this->data ) ? $this->data->get_product_id() : null;
	}

	/**
	 * Get the ID of the parent bundle.
	 *
	 * @return int|null
	 */
	public function get_bundle_id() {
		return is_object( $this->data ) ? $this->data->get_bundle_id() : null;
	}

	/**
	 * Returns the parent.
	 *
	 * @return WC_Product_Bundle|false
	 */
	public function get_bundle() {
		if ( is_null( $this->bundle ) ) {
			$this->bundle = wc_get_product( $this->get_bundle_id() );
		}
		return $this->bundle;
	}

	/**
	 * Initialize bundled item class props from bundled item data object.
	 *
	 * @since 5.0.0
	 */
	private function load_data() {

		// Defaults.
		$defaults = array(
			'quantity_min'                          => 1,
			'quantity_max'                          => 1,
			'priced_individually'                   => 'no',
			'shipped_individually'                  => 'no',
			'override_title'                        => 'no',
			'title'                                 => '',
			'override_description'                  => 'no',
			'description'                           => '',
			'optional'                              => 'no',
			'hide_thumbnail'                        => 'no',
			'discount'                              => '',
			'override_variations'                   => 'no',
			'override_default_variation_attributes' => 'no',
			'allowed_variations'                    => false,
			'default_variation_attributes'          => false,
			'single_product_visibility'             => 'visible',
			'cart_visibility'                       => 'visible',
			'order_visibility'                      => 'visible',
			'single_product_price_visibility'       => 'visible',
			'cart_price_visibility'                 => 'visible',
			'order_price_visibility'                => 'visible',
			'stock_status'                          => null,
			'max_stock'                             => null
		);

		// Set meta and properties.
		$this->item_data = wp_parse_args( $this->data->get_meta_data(), $defaults );
		// Added for back-compat.
		$this->item_data[ 'product_id' ] = $this->data->get_product_id();

		foreach ( $defaults as $key => $value ) {
			$this->$key = $this->item_data[ $key ];
		}

		$this->default_variation_attributes = 'yes' === $this->override_default_variation_attributes && is_array( $this->default_variation_attributes ) && ! empty( $this->default_variation_attributes ) ? $this->default_variation_attributes : false;
		$this->allowed_variations           = 'yes' === $this->override_variations && is_array( $this->allowed_variations ) && ! empty( $this->allowed_variations ) ? $this->allowed_variations : false;
		$this->visibility                   = array(
			'product' => $this->single_product_visibility,
			'cart'    => $this->cart_visibility,
			'order'   => $this->order_visibility
		);
		$this->price_visibility             = array(
			'product' => $this->single_product_price_visibility,
			'cart'    => $this->cart_price_visibility,
			'order'   => $this->order_price_visibility
		);
	}

	/**
	 * Get item data.
	 *
	 * @since  5.0.0
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->item_data;
	}

	/**
	 * Sync stock data if needed.
	 *
	 * @since 5.5.0
	 */
	private function maybe_sync_stock() {
		if ( is_null( $this->stock_status ) ) {
			$this->sync_stock();
		}
	}

	/**
	 * Keep bundled item stock status in sync with associated product, taking 'min_quantity' into account.
	 *
	 * @since 5.0.0
	 */
	public function sync_stock() {

		$bundled_product = $this->product;
		$quantity        = max( 1, $this->get_quantity() );

		/*------------------------------*/
		/*  Simple Products             */
		/*------------------------------*/

		if ( in_array( $bundled_product->get_type(), array( 'simple', 'subscription' ) ) ) {

			if ( false === $bundled_product->is_in_stock() ) {

				$this->stock_status = 'out_of_stock';
				$this->max_stock    = 0;

			} elseif ( false === $bundled_product->has_enough_stock( $quantity ) ) {

				$stock_quantity = $bundled_product->managing_stock() ? $bundled_product->get_stock_quantity() : '';

				$this->stock_status = 'out_of_stock';
				$this->max_stock    = ! is_null( $stock_quantity ) ? $stock_quantity : 0;

			} elseif ( $bundled_product->is_on_backorder( $quantity ) ) {

				$this->stock_status = 'on_backorder';
				$this->max_stock    = '';

			} elseif ( $bundled_product->backorders_allowed() ) {

				$this->stock_status = 'in_stock';
				$this->max_stock    = '';

			} else {

				$stock_quantity = $bundled_product->managing_stock() ? $bundled_product->get_stock_quantity() : '';

				$this->stock_status = 'in_stock';
				$this->max_stock    = ! is_null( $stock_quantity ) ? $stock_quantity : '';
			}

		/*------------------------------*/
		/*	Variable Products           */
		/*------------------------------*/

		} elseif ( in_array( $bundled_product->get_type(), array( 'variable', 'variable-subscription' ) ) ) {

			$variation_in_stock_exists     = false;
			$variation_on_backorder_exists = false;
			$all_variations_on_backorder   = true;

			foreach ( $bundled_product->get_children() as $child_id ) {

				// Do not continue if variation is filtered.
				if ( $this->has_filtered_variations() && ! in_array( $child_id, $this->allowed_variations ) ) {
					continue;
				}

				$variation = wc_get_product( $child_id );

				if ( ! $variation ) {
					continue;
				}

				if ( false === $variation->is_in_stock() ) {

					$variation_stock_qty = 0;

				} elseif ( false === $variation->has_enough_stock( $quantity ) ) {

					$stock_quantity      = $variation->managing_stock() ? $variation->get_stock_quantity() : $bundled_product->get_stock_quantity();
					$variation_stock_qty = ! is_null( $stock_quantity ) ? $stock_quantity : 0;

				} elseif ( $variation->is_on_backorder( $quantity ) ) {

					$variation_stock_qty           = '';
					$variation_in_stock_exists     = true;
					$variation_on_backorder_exists = true;

				} elseif ( $variation->backorders_allowed() ) {

					$variation_stock_qty         = '';
					$variation_in_stock_exists   = true;
					$all_variations_on_backorder = false;

				} else {

					$stock_quantity              = $variation->managing_stock() ? $variation->get_stock_quantity() : $bundled_product->get_stock_quantity();
					$variation_stock_qty         = ! is_null( $stock_quantity ) ? $stock_quantity : '';
					$variation_in_stock_exists   = true;
					$all_variations_on_backorder = false;
				}

				if ( '' === $variation_stock_qty ) {
					$this->max_stock = '';
					continue;
				}

				// Only calculate max stock if not already found infinite.
				if ( '' !== $this->max_stock ) {
					$this->max_stock = is_null( $this->max_stock ) ? $variation_stock_qty : max( $this->max_stock, $variation_stock_qty );
				}
			}

			$all_variations_on_backorder = $all_variations_on_backorder && $variation_on_backorder_exists;

			if ( false === $variation_in_stock_exists ) {
				$this->stock_status = 'out_of_stock';
			} elseif ( $all_variations_on_backorder ) {
				$this->stock_status = 'on_backorder';
			} else {
				$this->stock_status = 'in_stock';
			}
		}

		$this->data->update_meta( 'stock_status', $this->stock_status );
		$this->data->update_meta( 'max_stock', $this->max_stock );

		// Save changes if the bundle exists in the DB.
		if ( $this->get_bundle_id() && ! defined( 'WC_PB_DEBUG_STOCK_SYNC' ) ) {
			$this->data->save();
		}
	}

	/**
	 * Sync price data.
	 *
	 * @param  bool  $force
	 */
	protected function sync_prices( $force = false ) {

		if ( $this->synced_prices && false === $force ) {
			return false;
		}

		$bundled_product_id = $this->get_product_id();
		$bundled_product    = $this->product;

		$discount = $this->get_discount();

		/*------------------------------*/
		/*  Simple Subs                 */
		/*------------------------------*/

		if ( 'subscription' === $bundled_product->get_type() ) {

			// Recurring price.
			$regular_recurring_fee = $this->get_raw_regular_price();
			$recurring_fee         = $this->get_raw_price();

			$this->min_regular_recurring_price = $this->max_regular_recurring_price = $regular_recurring_fee;
			$this->min_recurring_price         = $this->max_recurring_price         = $recurring_fee;

			// Sign up price.
			$signup_fee   = WC_Subscriptions_Product::get_sign_up_fee( $bundled_product );
			$trial_length = WC_Subscriptions_Product::get_trial_length( $bundled_product );

			// Up-front price.
			$up_front_fee         = $trial_length > 0 ? $signup_fee : (double) $signup_fee + (double) $recurring_fee;
			$regular_up_front_fee = $trial_length > 0 ? $signup_fee : (double) $signup_fee + (double) $regular_recurring_fee;

			$this->min_regular_price = $this->max_regular_price = $regular_up_front_fee;
			$this->min_price         = $this->max_price         = $up_front_fee;

		/*----------------------------------*/
		/*  Simple Products                 */
		/*----------------------------------*/

		} elseif ( 'simple' === $bundled_product->get_type() ) {

			// Name your price support.
			if ( WC_PB()->compatibility->is_nyp( $bundled_product ) ) {

				$this->min_regular_price = $this->min_price = WC_Name_Your_Price_Helpers::get_minimum_price( $bundled_product_id ) ? WC_Name_Your_Price_Helpers::get_minimum_price( $bundled_product_id ) : 0;
				$this->max_regular_price = $this->max_price = INF;

				$this->product->set_price( $this->min_price );
				$this->product->set_regular_price( $this->min_price );

				$this->is_nyp = true;

			} else {

				$this->min_price         = $this->max_price         = $this->get_raw_price();
				$this->min_regular_price = $this->max_regular_price = $this->get_raw_regular_price();
			}

			$this->min_regular_price = $this->max_regular_price = $this->get_raw_regular_price();
			$this->min_price         = $this->max_price         = $this->get_raw_price();

		/*----------------------------------*/
		/*	Variable Products               */
		/*----------------------------------*/

		} elseif ( 'variable' === $bundled_product->get_type() || 'variable-subscription' === $bundled_product->get_type() ) {

			$min_variation = $max_variation = false;

			/*
			 * Find the the variations with the min & max price.
			 */

			$variation_prices_array = $bundled_product->get_variation_prices();

			if ( ! empty( $discount ) && false === $this->is_discount_allowed_on_sale_price() ) {
				$variation_prices = $variation_prices_array[ 'regular_price' ];
			} else {
				$variation_prices = $variation_prices_array[ 'price' ];
			}

			// Clean filtered-out variations.
			if ( $this->has_filtered_variations() ) {
				$variation_prices = array_intersect_key( $variation_prices, array_flip( $this->allowed_variations ) );
			}

			$variation_price_ids = array_keys( $variation_prices );

			$min_variation_price = current( $variation_prices );
			$max_variation_price = end( $variation_prices );

			$min_variation_price_id = current( $variation_price_ids );
			$max_variation_price_id = end( $variation_price_ids );

			$min_variation = wc_get_product( $min_variation_price_id );
			$max_variation = wc_get_product( $max_variation_price_id );

			if ( $min_variation && $max_variation ) {

				$this->min_price_product = $this->min_regular_price_product = $min_variation;
				$this->max_price_product = $this->min_regular_price_product = $max_variation;

				if ( 'variable-subscription' === $bundled_product->get_type() ) {

					$this->min_recurring_price         = $this->max_recurring_price         = $this->get_raw_price( $min_variation );
					$this->min_regular_recurring_price = $this->max_regular_recurring_price = $this->get_raw_regular_price( $min_variation );

					$min_signup_fee = WC_Subscriptions_Product::get_sign_up_fee( $min_variation );

					$min_regular_up_front_fee = $this->get_up_front_subscription_price( $this->min_regular_recurring_price, $min_signup_fee, $min_variation );
					$min_up_front_fee         = $this->get_up_front_subscription_price( $this->min_recurring_price, $min_signup_fee, $min_variation );

					$this->min_regular_price = $this->max_regular_price = $min_regular_up_front_fee;
					$this->min_price         = $this->max_price         = $min_up_front_fee;

				} else {

					$this->min_price             = $this->get_raw_price( $min_variation );
					$this->max_price             = $this->get_raw_price( $max_variation );
					$min_variation_regular_price = $this->get_raw_regular_price( $min_variation );
					$max_variation_regular_price = $this->get_raw_regular_price( $max_variation );

					// The variation with the lowest price may have a higher regular price then the variation with the highest price.
					if ( $max_variation_regular_price < $min_variation_regular_price ) {
						$this->min_regular_price_product = $max_variation;
						$this->max_regular_price_product = $min_variation;
					}

					$this->min_regular_price = min( $min_variation_regular_price, $max_variation_regular_price );
					$this->max_regular_price = max( $min_variation_regular_price, $max_variation_regular_price );
				}
			}
		}

		$this->synced_prices = true;

		return true;
	}

	/**
	 * Indicates whether discounts can be applied on sale prices.
	 *
	 * @since  5.0.3
	 */
	public function is_discount_allowed_on_sale_price() {

		$discount_from_regular = $this->product->is_type( 'variable-subscription' ) ? false : true;

		/**
		 * 'woocommerce_bundled_item_discount_from_regular' filter.
		 *
		 * Controls whether bundled item discounts will always be applied on the regular price (default), ignoring any defined sale price.
		 *
		 * @param  boolean          $discount_from_regular
		 * @param  WC_Bundled_Item  $this
		 */
		return false === apply_filters( 'woocommerce_bundled_item_discount_from_regular', $discount_from_regular, $this );
	}

	/**
	 * Get bundled product.
	 *
	 * @since  5.2.4
	 *
	 * @param  array  $args
	 * @return WC_Product|false
	 */
	public function get_product( $args = array() ) {

		$product = false;

		if ( $this->exists() ) {

			$product = $this->product;

			$what   = isset( $args[ 'what' ] ) && in_array( $args[ 'what' ], array( 'min', 'max' ) ) ? $args[ 'what' ] : '';
			$having = isset( $args[ 'having' ] ) && in_array( $args[ 'having' ], array( 'price', 'regular_price' ) ) ? $args[ 'having' ] : '';
			$prop   = $having && $what ? $what . '_' . $having . '_product' : false;

			if ( $prop && property_exists( $this, $prop ) ) {

				$this->sync_prices();

				if ( isset( $this->$prop ) ) {
					$product = $this->$prop;
				}
			}

		}

		return $product;
	}

	/**
	 * Get bundled product price after discount, price filters excluded.
	 *
	 * @param  mixed  $product
	 * @return mixed
	 */
	public function get_raw_price( $product = false, $context = '' ) {

		if ( ! $product ) {
			$product = $this->product;
		}

		$price = $product->get_price( 'edit' );

		if ( '' === $price ) {
			return $price;
		}

		if ( ! $this->is_priced_individually() ) {
			return 0;
		}

		if ( false === $this->is_discount_allowed_on_sale_price() ) {
			$regular_price = $product->get_regular_price( 'edit' );
		} else {
			$regular_price = $price;
		}

		$discount           = $this->get_discount();
		$bundled_item_price = empty( $discount ) ? $price : ( empty( $regular_price ) ? $regular_price : round( ( double ) $regular_price * ( 100 - $discount ) / 100, WC_PB_Product_Prices::get_discounted_price_precision() ) );

		/**
		 * 'woocommerce_bundled_item_raw_price' raw price filter.
		 *
		 * @param  mixed            $price
		 * @param  WC_Product       $product
		 * @param  mixed            $discount
		 * @param  WC_Bundled_Item  $this
		 */
		$price = apply_filters( 'woocommerce_bundled_item_raw_price' . ( $context ? '_' . $context : '' ), $bundled_item_price, $product, $discount, $this );

		return $price;
	}

	/**
	 * Get bundled product regular price before discounts, price filters excluded.
	 *
	 * @param  mixed  $product
	 * @return mixed
	 */
	public function get_raw_regular_price( $product = false ) {

		if ( ! $product ) {
			$product = $this->product;
		}

		$regular_price = $product->get_regular_price( 'edit' );

		if ( ! $this->is_priced_individually() ) {
			return 0;
		}

		$regular_price = empty( $regular_price ) ? $product->get_price( 'edit' ) : $regular_price;

		return $regular_price;
	}

	/**
	 * Get bundled item price, after discount, filters included.
	 *
	 * @since  5.0.0
	 *
	 * @param  string   $min_or_max
	 * @param  boolean  $display
	 * @return mixed
	 */
	public function get_price( $min_or_max = 'min', $display = false, $qty = 1 ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'qty'        => $qty,
			'calc'       => $display ? 'display' : '',
			'prop'       => 'price'
		) );
	}

	/**
	 * Get bundled item recurring price after discount, filters included.
	 *
	 * @since  5.0.0
	 *
	 * @param  string   $min_or_max
	 * @param  boolean  $display
	 * @return mixed
	 */
	public function get_recurring_price( $min_or_max = 'min', $display = false ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'calc'       => $display ? 'display' : '',
			'prop'       => 'price',
			'recurring'  => true
		) );
	}

	/**
	 * Get bundled item regular price after discount, filters included.
	 *
	 * @since  5.0.0
	 *
	 * @param  string   $min_or_max
	 * @param  boolean  $display
	 * @param  boolean  $strict
	 * @return mixed
	 */
	public function get_regular_price( $min_or_max = 'min', $display = false, $strict = false, $qty = 1 ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'qty'        => $qty,
			'calc'       => $display ? 'display' : '',
			'prop'       => 'regular_price',
			'strict'     => $strict
		) );
	}

	/**
	 * Get bundled item recurring price after discount, filters included.
	 *
	 * @since  5.0.0
	 *
	 * @param  string   $min_or_max
	 * @param  boolean  $display
	 * @return mixed
	 */
	public function get_regular_recurring_price( $min_or_max = 'min', $display = false ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'calc'       => $display ? 'display' : '',
			'prop'       => 'regular_price',
			'recurring'  => true
		) );
	}

	/**
	 * Min bundled item price incl tax.
	 *
	 * @since  5.0.0
	 *
	 * @param  string  $min_or_max
	 * @param  int     $qty
	 * @return mixed
	 */
	public function get_price_including_tax( $min_or_max = 'min', $qty = 1 ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'qty'        => $qty,
			'calc'       => 'incl_tax',
			'prop'       => 'price'
		) );
	}

	/**
	 * Min bundled item price excl tax.
	 *
	 * @since  5.0.0
	 *
	 * @param  string  $min_or_max
	 * @param  int     $qty
	 * @return mixed
	 */
	public function get_price_excluding_tax( $min_or_max = 'min', $qty = 1 ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'qty'        => $qty,
			'calc'       => 'excl_tax',
			'prop'       => 'price'
		) );
	}

	/**
	 * Min bundled item price incl tax.
	 *
	 * @since  5.5.0
	 *
	 * @param  string  $min_or_max
	 * @param  int     $qty
	 * @param  bool    $strict
	 * @return mixed
	 */
	public function get_regular_price_including_tax( $min_or_max = 'min', $qty = 1, $strict = false ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'qty'        => $qty,
			'strict'     => $strict,
			'calc'       => 'incl_tax',
			'prop'       => 'regular_price'
		) );
	}

	/**
	 * Min bundled item price excl tax.
	 *
	 * @since  5.5.0
	 *
	 * @param  string  $min_or_max
	 * @param  int     $qty
	 * @param  bool    $strict
	 * @return mixed
	 */
	public function get_regular_price_excluding_tax( $min_or_max = 'min', $qty = 1, $strict = false ) {
		return $this->calculate_price( array(
			'min_or_max' => $min_or_max,
			'qty'        => $qty,
			'strict'     => $strict,
			'calc'       => 'excl_tax',
			'prop'       => 'regular_price'
		) );
	}

	/**
	 * Min/max bundled item (regular) price incl/excl tax.
	 *
	 * @since  5.5.0
	 *
	 * @param  array  $args
	 * @return mixed
	 */
	public function calculate_price( $args ) {

		if ( ! $this->exists() ) {
			return false;
		}

		if ( ! $this->is_purchasable() ) {
			return '';
		}

		$min_or_max = isset( $args[ 'min_or_max' ] ) && in_array( $args[ 'min_or_max' ] , array( 'min', 'max' ) ) ? $args[ 'min_or_max' ] : 'min';
		$qty        = isset( $args[ 'qty' ] ) ? absint( $args[ 'qty' ] ) : 1;
		$price_prop = isset( $args[ 'prop' ] ) && in_array( $args[ 'prop' ] , array( 'price', 'regular_price' ) ) ? $args[ 'prop' ] : 'price';
		$price_calc = isset( $args[ 'calc' ] ) && in_array( $args[ 'calc' ] , array( 'incl_tax', 'excl_tax', 'display', '' ) ) ? $args[ 'calc' ] : '';
		$recurring  = isset( $args[ 'recurring' ] ) && $args[ 'recurring' ];
		$strict     = isset( $args[ 'strict' ] ) && $args[ 'strict' ] && 'regular_price' === $price_prop;

		if ( $this->is_nyp() && 'max' === $min_or_max ) {
			return '';
		}

		$this->sync_prices();

		$prop    = 'regular_price' === $price_prop && $strict ? ( $min_or_max . '_price_product' ) : ( $min_or_max . '_' . $price_prop . '_product' );
		$product = ! empty( $this->$prop ) ? $this->$prop : $this->product;

		$this->add_price_filters();

		$price_fn = 'get_' . $price_prop;
		$price    = $product->$price_fn();

		if ( ! $recurring && $this->is_subscription() ) {
			$signup_fee = WC_Subscriptions_Product::get_sign_up_fee( $product );
			$price      = $this->get_up_front_subscription_price( $price, $signup_fee, $product );
		}

		$this->remove_price_filters();

		return WC_PB_Product_Prices::get_product_price( $product, array(
			'price' => $price,
			'qty'   => $qty,
			'calc'  => $price_calc,
		) );
	}

	/**
	 * True if the bundled item has a price of its own.
	 *
	 * @return boolean
	 */
	public function is_priced_individually() {

		$is_priced_individually = 'yes' === $this->priced_individually;

		/**
		 * 'woocommerce_bundled_item_is_priced_individually' filter.
		 *
		 * @param  boolean          $is_priced_individually
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_bundled_item_is_priced_individually', $is_priced_individually, $this );
	}

	/**
	 * True if the bundled item is shipped individually.
	 *
	 * @return boolean
	 */
	public function is_shipped_individually( $product = false ) {

		$is_shipped_individually = 'yes' === $this->shipped_individually;

		/**
		 * 'woocommerce_bundled_item_is_shipped_individually' filter.
		 *
		 * @param  boolean          $is_shipped_individually
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_bundled_item_is_shipped_individually', $is_shipped_individually, $this );
	}

	/**
	 * Bundled item sale status.
	 *
	 * @return boolean
	 */
	public function is_on_sale() {

		$discount = $this->get_discount();
		$on_sale  = ! empty( $discount ) || $this->product->is_on_sale();

		return $on_sale;
	}

	/**
	 * Bundled item purchasable status.
	 *
	 * @return boolean
	 */
	public function is_purchasable() {
		if ( ! isset( $this->purchasable ) ) {
			$this->purchasable = $this->exists() && $this->product->is_purchasable();
		}
		return $this->purchasable;
	}

	/**
	 * Bundled item exists status.
	 *
	 * @return boolean
	 */
	public function exists() {

		$exists = true;

		if ( empty( $this->product ) ) {
			$exists = false;
		}

		if ( ! is_object( $this->product ) ) {
			$exists = false;
		}

		if ( $exists ) {
			if ( 'trash' === $this->product->get_status() ) {
				$exists = false;
			} elseif ( ! in_array( $this->product->get_type(), array( 'simple', 'variable', 'subscription', 'variable-subscription' ) ) ) {
				$exists = false;
			}
		}

		return $exists;
	}

	/**
	 * Bundled item stock status.
	 * Takes min quantity into account.
	 *
	 * @return boolean
	 */
	public function is_in_stock() {
		$this->maybe_sync_stock();
		return 'out_of_stock' !== $this->stock_status;
	}

	/**
	 * Evaluates whether or not the bundled item has enough stock to fulfill an arbitrary quantity requirement in a single bundle purchase.
	 *
	 * @param  mixed  $quantity
	 * @return boolean
	 */
	public function has_enough_stock( $quantity ) {
		return $this->is_in_stock() && ( '' === $this->get_max_stock() || $this->get_max_stock() >= $quantity );
	}

	/**
	 * Bundled item backorder status.
	 *
	 * @return boolean
	 */
	public function is_on_backorder() {
		$this->maybe_sync_stock();
		return 'on_backorder' === $this->stock_status;
	}

	/**
	 * Max stock available to the bundle before it becomes unavailable.
	 * An empty string is treated as infinite stock.
	 *
	 * @since  5.0.0
	 *
	 * @return mixed
	 */
	public function get_max_stock() {
		$this->maybe_sync_stock();
		return '' !== $this->max_stock ? absint( $this->max_stock ) : '';
	}

	/**
	 * Bundled item stock status.
	 *
	 * @since  5.0.0
	 *
	 * @return string
	 */
	public function get_stock_status() {
		$this->maybe_sync_stock();
		return $this->stock_status;
	}

	/**
	 * Bundled item sold individually status.
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {
		if ( ! isset( $this->sold_individually ) ) {
			$this->sold_individually = $this->exists() && $this->product->is_sold_individually();
		}
		return $this->sold_individually;
	}

	/**
	 * Bundled item name-your-price status.
	 *
	 * @return boolean
	 */
	public function is_nyp() {
		return $this->is_nyp;
	}

	/**
	 * Check if the product has variables/options to adjust before adding to cart.
	 *
	 * @return boolean
	 */
	public function requires_input() {

		$requires_input = false;

		if ( $this->is_nyp() ) {
			$requires_input = true;
		} elseif ( 'variable' === $this->product->get_type() || 'variable-subscription' === $this->product->get_type() ) {
			$requires_input = sizeof( $this->get_product_variation_attributes( true ) ) > 0;
		} elseif ( WC_PB()->compatibility->has_addons( $this->get_product(), true ) ) {
			$requires_input = true;
		}

		return $requires_input;
	}

	/**
	 * Check if the item is a subscription.
	 *
	 * @since  5.0.0
	 *
	 * @return boolean
	 */
	public function is_subscription() {
		return in_array( $this->product->get_type(), array( 'subscription', 'variable-subscription' ) );
	}

	/**
	 * Check if the item is a variable subscription.
	 *
	 * @since  5.0.0
	 *
	 * @return boolean
	 */
	public function is_variable_subscription() {
		return 'variable-subscription' === $this->product->get_type();
	}

	/**
	 * Returns the variation attributes array if this product is variable.
	 *
	 * @since  5.8.0
	 *
	 * @param  string  $variation_attribute_name
	 * @return array
	 */
	public function is_product_variation_attribute_configurable( $variation_attribute_name ) {

		$configurable_variation_attributes = $this->get_product_variation_attributes( true );

		return isset( $configurable_variation_attributes[ $variation_attribute_name ] );
	}

	/**
	 * Returns the variation attributes array if this product is variable.
	 *
	 * @since  5.8.0
	 *
	 * @param  string  $variation_attribute_name
	 * @return array
	 */
	public function display_product_variation_attribute_dropdown( $variation_attribute_name ) {

		$display_dropdown = $this->is_product_variation_attribute_configurable( $variation_attribute_name );

		/**
		 * 'woocommerce_force_show_bundled_variation_attribute_option_dropdown' filter.
		 *
		 * @param  boolean  $force_show
		 * @param  array    $args
		 */
		return $display_dropdown ? $display_dropdown : apply_filters( 'woocommerce_force_show_bundled_variation_attribute_option_dropdown', false, $variation_attribute_name, $this );
	}

	/**
	 * Returns the (configurable) variation attributes array if this product is variable.
	 *
	 * @param  bool  $return_configurable
	 * @return array
	 */
	public function get_product_variation_attributes( $return_configurable = false ) {

		$return_type = $return_configurable ? 'configurable' : 'all';

		if ( ! empty( $this->product_attributes ) && isset( $this->product_attributes[ $return_type ] ) ) {
			return $this->product_attributes[ $return_type ];
		}

		if ( 'variable' === $this->product->get_type() || 'variable-subscription' === $this->product->get_type() ) {

			if ( empty( $this->product_attributes ) ) {
				$this->product_attributes = array();
			}

			$this->product_attributes[ 'all' ] = $this->product->get_variation_attributes();

			if ( $return_configurable ) {

				$this->product_attributes[ 'configurable' ] = $this->product_attributes[ 'all' ];

				if ( $this->has_filtered_variations() ) {

					$variations = $this->get_children();

					// Non-configurable only when a single variation is active.
					if ( 1 === sizeof( $variations ) ) {

						$variation_id    = current( $variations );
						$variation       = wc_get_product( $variation_id );
						$variation_data  = $variation->get_variation_attributes();

						// Make sure all attributes of the single active variation have a value.
						foreach ( $this->product_attributes[ 'all' ] as $attribute_name => $options ) {
							if ( isset( $variation_data[ wc_variation_attribute_name( $attribute_name ) ] ) ) {
								$value = $variation_data[ wc_variation_attribute_name( $attribute_name ) ];
								if ( '' !== $value && '' !== $this->get_selected_product_variation_attribute( $attribute_name ) ) {
									unset( $this->product_attributes[ 'configurable' ][ $attribute_name ] );
								}
							}
						}
					}
				}
			}

			return $this->product_attributes[ $return_type ];
		}

		return false;
	}

	/**
	 * Returns the selected variation attribute if this product is variable.
	 *
	 * @return string
	 */
	public function get_selected_product_variation_attribute( $attribute_name ) {

		$defaults       = $this->get_selected_product_variation_attributes();
		$attribute_name = sanitize_title( $attribute_name );

		return isset( $defaults[ $attribute_name ] ) ? $defaults[ $attribute_name ] : '';
	}

	/**
	 * Returns the selected variation attributes if this product is variable.
	 *
	 * Ensures default attribute selections do not correspond to attribute values that have been filtered out.
	 *
	 * @return array
	 */
	public function get_selected_product_variation_attributes() {

		if ( ! empty( $this->selected_product_attributes ) ) {
			return $this->selected_product_attributes;
		}

		if ( 'variable' === $this->product->get_type() || 'variable-subscription' === $this->product->get_type() ) {

			if ( is_array( $this->default_variation_attributes ) ) {
				$selected_product_attributes = $this->default_variation_attributes;
			} else {
				$selected_product_attributes = $this->product->get_default_attributes();

				// Ensure default attribute selections do not correspond to attribute values that have been filtered out.
				if ( ! empty( $selected_product_attributes ) && $this->has_filtered_variations() ) {

					$variation_attribute_values = array();

					if ( ! empty( $this->product_variations ) ) {
						foreach ( $this->product_variations as $variation_data ) {
							if ( isset( $variation_data[ 'attributes' ] ) ) {
								foreach ( $variation_data[ 'attributes' ] as $attribute_key => $attribute_value ) {
									$variation_attribute_values[ $attribute_key ][] = $attribute_value;
									if ( in_array( '', $variation_attribute_values[ $attribute_key ] ) ) {
										break;
									}
								}
							}
						}
					}

					foreach ( $selected_product_attributes as $selected_attribute_key => $selected_attribute_value ) {
						if ( '' !== $selected_attribute_value && isset( $variation_attribute_values [ 'attribute_' . $selected_attribute_key ] ) && ! in_array( '', $variation_attribute_values[ 'attribute_' . $selected_attribute_key ] ) && ! in_array( $selected_attribute_value, $variation_attribute_values[ 'attribute_' . $selected_attribute_key ] ) ) {
							$selected_product_attributes[ $selected_attribute_key ] = '';
						}
					}
				}
			}

			$this->selected_product_attributes = $selected_product_attributes;

			return $this->selected_product_attributes;
		}

		return false;
	}

	/**
	 * Returns this product's available variations array.
	 *
	 * @return array
	 */
	public function get_product_variations() {

		if ( ! empty( $this->product_variations ) ) {
			return $this->product_variations;
		}

		if ( 'variable' === $this->product->get_type() || 'variable-subscription' === $this->product->get_type() ) {

			// Filter children to exclude filtered out variations.
			add_filter( 'woocommerce_get_children', array( $this, 'filter_children' ), 10, 2 );

			// Filter variations data.
			add_filter( 'woocommerce_available_variation', array( $this, 'filter_variation' ), 10, 3 );

			$this->add_price_filters();

			if ( 'variable-subscription' === $this->product->get_type() ) {
				WC_PB_Product_Prices::$bundled_item = $this;
			}

			$bundled_item_variations = $this->product->get_available_variations();

			if ( 'variable-subscription' === $this->product->get_type() ) {
				WC_PB_Product_Prices::$bundled_item = false;
			}

			$this->remove_price_filters();

			remove_filter( 'woocommerce_available_variation', array( $this, 'filter_variation' ), 10, 3 );

			remove_filter( 'woocommerce_get_children', array( $this, 'filter_children' ), 10, 2 );

			// Add only active variations.
			foreach ( $bundled_item_variations as $variation_data ) {
				if ( ! empty( $variation_data ) ) {
					$this->product_variations[] = $variation_data;
				}
			}

			return $this->product_variations;
		}

		return false;
	}

	/**
	 * True if the product has variation filters.
	 *
	 * @return boolean
	 */
	public function has_filtered_variations() {
		return is_array( $this->allowed_variations );
	}

	/**
	 * Get filtered (allowed) variation IDs.
	 *
	 * @return array
	 */
	public function get_filtered_variations() {
		return $this->has_filtered_variations() ? $this->allowed_variations : array();
	}

	/**
	 * Using ajax to fetch variations?
	 *
	 * False if the bundle has variation filters - otherwise ALL attribute options will show up in the dropdowns.
	 * If you still wish to enable ajax when using variation filters, use the 'woocommerce_bundled_item_filtered_variations_disable_ajax' filter to prevent ajax from being disabled.
	 *
	 * @return boolean
	 */
	public function use_ajax_for_product_variations() {

		$use_ajax = true;

		if ( ! $this->exists() ) {
			$use_ajax = false;
		} elseif ( did_action( 'woocommerce_composite_show_composited_product' ) ) {
			$use_ajax = false;
		} elseif ( $this->has_filtered_variations() && apply_filters( 'woocommerce_bundled_item_filtered_variations_disable_ajax', true, $this ) ) {
			$use_ajax = false;
		} elseif ( sizeof( $this->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $this->product ) ) {
			$use_ajax = false;
		}

		return $use_ajax;
	}

	/**
	 * Get bundled item children.
	 *
	 * @return array
	 */
	public function get_children() {

		$children = array();

		if ( $this->exists() ) {

			$children = $this->product->get_children();

			if ( ! empty( $children ) ) {
				$children = $this->filter_children( $children, $this->product );
			}
		}

		return $children;
	}

	/**
	 * Filter variable product children to exclude filtered out variations and improve the performance of 'WC_Product_Variable::get_available_variations()'.
	 *
	 * @param  array                $children
	 * @param  WC_Product_Variable  $bundled_product
	 * @return array
	 */
	public function filter_children( $children, $bundled_product ) {

		if ( $this->has_filtered_variations() ) {

			$filtered_children = array();

			foreach ( $children as $variation_id ) {
				// Remove if filtered.
				if ( in_array( $variation_id, $this->allowed_variations ) ) {
					$filtered_children[] = $variation_id;
				}
			}

			$children = $filtered_children;
		}

		return $children;
	}

	/**
	 * Modifies the results of get_available_variations() to implement variation filtering and bundle discounts for variable products.
	 * Also calculates variation prices incl. or excl. tax.
	 *
	 * @param  array                 $variation_data
	 * @param  WC_Product            $bundled_product
	 * @param  WC_Product_Variation  $bundled_variation
	 * @return array
	 */
	public function filter_variation( $variation_data, $bundled_product, $bundled_variation ) {

		$bundled_item_id = $this->get_id();

		// Disable if certain conditions are met...
		if ( $this->has_filtered_variations() ) {
			if ( ! in_array( $bundled_variation->get_id(), $this->allowed_variations ) ) {
				return false;
			}
		}

		if ( '' === $bundled_variation->get_price( 'edit' ) ) {
			return false;
		}

		// Add price data.

		$variation_data[ 'price' ]         = $bundled_variation->get_price();
		$variation_data[ 'regular_price' ] = $bundled_variation->get_regular_price();

		$variation_data[ 'price_tax' ] = WC_PB_Product_Prices::get_tax_ratios( $bundled_variation );

		$variation_data[ 'regular_recurring_price' ] = '';
		$variation_data[ 'recurring_price' ]         = '';

		$variation_data[ 'recurring_html' ] = '';
		$variation_data[ 'recurring_key' ]  = '';

		if ( 'variable-subscription' === $bundled_product->get_type() ) {

			$variation_data[ 'regular_recurring_price' ] = $variation_data[ 'regular_price' ];
			$variation_data[ 'recurring_price' ]         = $variation_data[ 'price' ];

			$signup_fee = WC_Subscriptions_Product::get_sign_up_fee( $bundled_variation );

			$variation_data[ 'regular_price' ] = $this->get_up_front_subscription_price( $variation_data[ 'regular_price' ], $signup_fee, $bundled_variation );
			$variation_data[ 'price' ]         = $this->get_up_front_subscription_price( $variation_data[ 'price' ], $signup_fee, $bundled_variation );

			$variation_data[ 'recurring_html' ] = WC_PB_Product_Prices::get_recurring_price_html_component( $bundled_variation );
			$variation_data[ 'recurring_key' ]  = str_replace( '_synced', '', WC_Subscriptions_Cart::get_recurring_cart_key( array( 'data' => $bundled_variation ), ' ' ) );
		}

		$quantity     = $this->get_quantity();
		$quantity_max = $this->get_quantity( 'max', array( 'bound_by_stock' => true, 'product' => $bundled_variation ) );

		if ( ! $this->is_in_stock() || ! $bundled_variation->is_in_stock() || ! $bundled_variation->has_enough_stock( $quantity ) ) {
			$variation_data[ 'is_in_stock' ] = false;
		}

		// Modify availability data.
		$variation_data[ 'availability_html' ] = $this->get_availability_html( $bundled_variation );

		$variation_data[ 'min_qty' ] = $quantity;
		$variation_data[ 'max_qty' ] = $quantity_max;

		if ( $variation_data[ 'min_qty' ] !== $variation_data[ 'max_qty' ] ) {
			$variation_data[ 'is_sold_individually' ] = false;
		}

		// Add flag for 3-p code.
		$variation_data[ 'is_bundled' ] = true;

		// Modify variation images as we don't want the single-product sizes here.
		$variation_thumbnail_size = $this->get_bundled_item_thumbnail_size();

		if ( ! in_array( $variation_thumbnail_size, array( 'single', 'shop_single', 'woocommerce_single' ) ) ) {

			if ( $variation_data[ 'image' ][ 'src' ] ) {

				$src = wp_get_attachment_image_src( $variation_data[ 'image_id' ], $variation_thumbnail_size );

				$variation_data[ 'image' ][ 'src' ]    = $src[0];
				$variation_data[ 'image' ][ 'src_w' ]  = $src[1];
				$variation_data[ 'image' ][ 'src_h' ]  = $src[2];
				$variation_data[ 'image' ][ 'srcset' ] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $variation_data[ 'image_id' ], $variation_thumbnail_size ) : false;
				$variation_data[ 'image' ][ 'sizes' ]  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $variation_data[ 'image_id' ], $variation_thumbnail_size ) : false;
			}
		}
		return $variation_data;
	}

	/**
	 * Add price filters to modify child product prices depending on the bundled item pricing setup.
	 * Applied i) when displaying single-product form content, ii) when initializing Product Bundles and iii) when calculating cart prices.
	 */
	public function add_price_filters() {
		WC_PB_Product_Prices::add_price_filters( $this );
	}

	/**
	 * Remove price filters after modifying child product prices depending on the bundled item pricing setup.
	 */
	public function remove_price_filters() {
		WC_PB_Product_Prices::remove_price_filters();
	}

	/**
	 * True if there is a title override.
	 *
	 * @return boolean
	 */
	public function has_title_override() {
		return 'yes' === $this->override_title;
	}

	/**
	 * Item title.
	 *
	 * @return string
	 */
	public function get_title() {
		/**
		 * 'woocommerce_bundled_item_title' filter.
		 *
		 * @param  string           $title
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_bundled_item_title', $this->title, $this );
	}

	/**
	 * Item raw item title.
	 *
	 * @param  bool    $strip_tags
	 * @return string
	 */
	public function get_raw_title( $strip_tags = false ) {

		$title = $this->get_title();

		if ( '' === $title ) {
			$title = $this->product->get_title();
		}

		/**
		 * 'woocommerce_bundled_item_raw_title' filter.
		 *
		 * @param  string           $title
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_bundled_item_raw_title', $strip_tags ? wp_strip_all_tags( $title ) : $title, $this );
	}

	/**
	 * Item description.
	 *
	 * @return string item title
	 */
	public function get_description() {
		/**
		 * 'woocommerce_bundled_item_description' filter.
		 *
		 * @param  string           $title
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_bundled_item_description', wpautop( do_shortcode( wp_kses_post( $this->description ) ) ), $this );
	}

	/**
	 * Item permalink.
	 *
	 * @since  5.5.0
	 *
	 * @return string
	 */
	public function get_permalink() {
		/**
		 * 'woocommerce_bundled_item_permalink' filter.
		 *
		 * @param  string           $permalink
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_bundled_item_permalink', $this->is_visible() && $this->product->is_visible() ? $this->product->get_permalink() : '', $this );
	}

	/**
	 * Visible or hidden in the product/cart/order templates.
	 *
	 * @return boolean
	 */
	public function is_visible( $where = 'product' ) {
		return isset( $this->visibility[ $where ] ) && 'hidden' !== $this->visibility[ $where ];
	}

	/**
	 * Visible or hidden in the product/cart/order templates.
	 *
	 * @return boolean
	 */
	public function is_price_visible( $where = 'product' ) {
		return isset( $this->price_visibility[ $where ] ) && 'hidden' !== $this->price_visibility[ $where ];
	}

	/**
	 * Item hidden from all templates.
	 *
	 * @return boolean
	 */
	public function is_secret() {
		return 'hidden' === $this->visibility[ 'product' ] && 'hidden' === $this->visibility[ 'cart' ] && 'hidden' === $this->visibility[ 'order' ];
	}

	/**
	 * Optional item.
	 *
	 * @return boolean
	 */
	public function is_optional() {
		return 'yes' === $this->optional;
	}

	/**
	 * Item min/max quantity.
	 *
	 * @return int
	 */
	public function get_quantity( $min_or_max = 'min', $args = array(), $deprecated = false ) {

		if ( ! is_array( $args ) ) {
			_deprecated_argument( __METHOD__ . '()', '5.5.0', 'Invalid argument: #2.' );
			$bound_by_stock = $args;
			$args           = array(
				'bound_by_stock' => $bound_by_stock
			);
		}

		if ( $deprecated ) {
			_deprecated_argument( __METHOD__ . '()', '5.5.0', 'Invalid argument: #3.' );
			if ( $deprecated instanceof WC_Product ) {
				$args[ 'product' ] = $deprecated;
			}
		}

		$bound_by_stock = isset( $args[ 'bound_by_stock' ] ) ? $args[ 'bound_by_stock' ] : false;
		$product        = isset( $args[ 'product' ] ) ? $args[ 'product' ] : false;
		$check_optional = isset( $args[ 'check_optional' ] ) ? $args[ 'check_optional' ] : false;

		$qty_min = $this->quantity_min;
		$qty_min = $check_optional && $this->is_optional() ? 0 : $qty_min;
		$qty_min = ( $qty_min > 1 && $this->is_sold_individually() ) ? 1 : $qty_min;

		/**
		 * 'woocommerce_bundled_item_quantity' filter.
		 *
		 * @param  mixed            $qty_min
		 * @param  WC_Bundled_Item  $this
		 * @param  array            $args
		 */
		$qty_min = apply_filters( 'woocommerce_bundled_item_quantity', $qty_min, $this, $args );
		$qty     = $qty_min;

		if ( 'max' === $min_or_max ) {

			$qty_max = $qty_min;

			if ( ! $product ) {
				$product = $this->product;
			}

			if ( isset( $this->quantity_max ) ) {
				if ( '' !== $this->quantity_max ) {
					$qty_max = max( $this->quantity_max, $qty_min );
				} else {
					$qty_max = '';
				}
			}

			$qty_max = $this->is_sold_individually() ? 1 : $qty_max;

			// Variations min/max quantity attributes handled via JS.
			if ( $bound_by_stock && ! in_array( $product->get_type(), array( 'variable', 'variable-subscription' ) ) ) {

				$qty_max_bound = '';

				if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
					$qty_max_bound = $product->get_stock_quantity();
				}

				// Max product quantity can't be greater than the bundled Max Quantity setting.
				if ( $qty_max > 0 ) {
					$qty_max_bound = '' !== $qty_max_bound ? min( $qty_max, $qty_max_bound ) : $qty_max;
				}

				// Max product quantity can't be lower than the min product quantity - if it is, then the product is not in stock.
				if ( '' !== $qty_max_bound ) {
					if ( $qty_min > $qty_max_bound ) {
						$qty_max_bound = $qty_min;
					}
				}

				$qty_max = $qty_max_bound;
			}

			/**
			 * 'woocommerce_bundled_item_quantity_max' filter.
			 *
			 * @param  mixed            $qty_max
			 * @param  WC_Bundled_Item  $this
			 * @param  array            $args
			 */
			$qty = apply_filters( 'woocommerce_bundled_item_quantity_max', $qty_max, $this, $args );
		}

		return '' !== $qty ? absint( $qty ) : '';
	}

	/**
	 * Item discount.
	 *
	 * @return double
	 */
	public function get_discount() {
		/**
		 * 'woocommerce_bundled_item_discount' filter.
		 *
		 * @param  mixed            $discount
		 * @param  WC_Bundled_Item  $this
		 */
		return $this->is_priced_individually() ? apply_filters( 'woocommerce_bundled_item_discount', $this->discount, $this ) : '';
	}

	/**
	 * Item sign-up discount.
	 *
	 * @return double
	 */
	public function get_sign_up_discount() {
		/**
		 * 'woocommerce_bundled_item_sign_up_discount' filter.
		 *
		 * @param  mixed            $sign_up_discount
		 * @param  WC_Bundled_Item  $this
		 */
		return $this->is_priced_individually() ? apply_filters( 'woocommerce_bundled_item_sign_up_discount', $this->sign_up_discount, $this ) : '';
	}

	/**
	 * Checkbox state for optional bundled items.
	 *
	 * @return boolean
	 */
	public function is_optional_checked() {

		if ( ! $this->is_optional() ) {
			return false;
		}

		/**
		 * 'woocommerce_bundled_item_is_optional_checked' filter.
		 *
		 * Use it to override the default 'checked' state of optional bundled items.
		 *
		 * @param  boolean          $checked
		 * @param  WC_Bundled_Item  $this
		 */
		$checked = apply_filters( 'woocommerce_bundled_item_is_optional_checked', false, $this );

		/**
		 * 'woocommerce_product_bundle_field_prefix' filter.
		 *
		 * Used to post unique bundle data when posting multiple bundle configurations that could include the same bundle multiple times.
		 *
		 * @param  string  $prefix
		 * @param  mixed   $product_id
		 */
		$posted_field_prefix = apply_filters( 'woocommerce_product_bundle_field_prefix', '', $this->get_bundle_id() );

		// When posting bundled item data, set the checked status accordingly.
		if ( isset( $_REQUEST[ $posted_field_prefix . 'bundle_quantity_' . $this->get_id() ] ) ) {
			if ( isset( $_REQUEST[ $posted_field_prefix . 'bundle_selected_optional_' . $this->get_id() ] ) ) {
				$checked = true;
			} else {
				$checked = false;
			}
		}

		return $checked;
	}

	/**
	 * Visible or hidden item thumbnail.
	 *
	 * @return boolean
	 */
	public function is_thumbnail_visible() {
		return 'yes' === $this->hide_thumbnail ? false : true;
	}

	/**
	 * Get classes for template use.
	 *
	 * @return string
	 */
	public function get_classes( $implode = true ) {

		$classes = array( 'bundled_product', 'bundled_product_summary', 'product' );

		if ( $this->get_quantity( 'min' ) !== $this->get_quantity( 'max' ) && $this->is_in_stock() ) {
			$classes[] = 'has_qty_input';
		}

		if ( ! $this->is_thumbnail_visible() ) {
			$classes[] = 'thumbnail_hidden';
		}

		if ( ! $this->is_visible() ) {
			$classes[] = 'bundled_item_hidden';
		}

		if ( $this->is_optional() ) {
			$classes[] = 'bundled_item_optional';
		}

		/**
		 * 'woocommerce_bundled_item_classes' filter.
		 *
		 * @param  array            $classes
		 * @param  WC_Bundled_Item  $this
		 */
		$classes = apply_filters( 'woocommerce_bundled_item_classes', $classes, $this );

		return $implode ? implode( ' ', $classes ) : $classes;
	}

	/**
	 * Get bundled item stock html.
	 *
	 * @since  5.2.0
	 *
	 * @param  WC_Product|false  $product
	 * @return string
	 */
	public function get_availability_html( $product = false ) {

		$availability = $this->get_availability( $product );

		if ( ! $product ) {
			$product = $this->product;
		}

		if ( ! empty( $availability[ 'availability' ] ) ) {

			ob_start();

			wc_get_template( 'single-product/stock.php', array(
				'product'      => $product,
				'class'        => $availability[ 'class' ],
				'availability' => $availability[ 'availability' ],
			) );

			$availability_html = ob_get_clean();

		} else {
			$availability_html = '';
		}

		/**
		 * 'woocommerce_get_bundled_item_stock_html' filter.
		 *
		 * Bundled items availability html that takes min_quantity into account.
		 *
		 * @param  string           $availability_html
		 * @param  array            $availability
		 * @param  WC_Bundled_Item  $this
		 */
		return apply_filters( 'woocommerce_get_bundled_item_stock_html', $availability_html, $availability, $this, $product );
	}

	/**
	 * Bundled product availability that takes min_quantity > 1 into account.
	 *
	 * @param  WC_Product|false  $product
	 * @return array
	 */
	public function get_availability( $product = false ) {

		if ( ! $product ) {
			$product = $this->product;
		}

		$quantity_min = max( 1, $this->get_quantity() );
		$stock_format = get_option( 'woocommerce_stock_format' );

		if ( ! $this->is_in_stock() || ( $product->is_type( 'variation' ) && ! $product->has_enough_stock( $quantity_min ) ) ) {

			$availability_class = 'out-of-stock';

			if ( $product->is_in_stock() ) {

				$availability_class .= ' insufficient-stock';
				$availability_text   = __( 'Insufficient stock', 'woocommerce-product-bundles' );

				if ( 'no_amount' !== $stock_format && false === $product->is_type( 'variable' ) ) {
					$stock_left = $product->get_stock_quantity();

					if ( $stock_left > 0 ) {
						$availability_text .= ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-product-bundles' ), $stock_left );
					}
				}

			} else {
				$availability_text = __( 'Out of stock', 'woocommerce' );
			}

		} elseif ( $product->backorders_require_notification() && ( $this->is_on_backorder() || ( $product->is_type( 'variation' ) && $product->is_on_backorder( $quantity_min ) ) ) ) {

			$availability_class = 'available-on-backorder';
			$availability_text  = __( 'Available on backorder', 'woocommerce' );

			if ( 'no_amount' !== $stock_format && false === $product->is_type( 'variable' ) && $product->is_in_stock() ) {
				$stock_left = $product->get_stock_quantity();

				if ( $stock_left > 0 ) {
					$availability_text .= ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-product-bundles' ), $stock_left );
				}
			}

		} else {

			$stock_notify_threshold = get_option( 'woocommerce_notify_low_stock_amount' );
			$filter_stock_display   = 'no_amount' !== $stock_format && $product->managing_stock() && $product->get_stock_quantity() < $quantity_min;

			if ( $filter_stock_display ) {

				if ( '' === $stock_format ) {
					add_filter( 'option_woocommerce_stock_format', array( $this, 'filter_stock_format' ) );
				}

				if ( $stock_notify_threshold ) {
					add_filter( 'option_woocommerce_notify_low_stock_amount', array( $this, 'filter_notify_low_stock_amount' ) );
				}
			}

			$availability       = $product->get_availability();
			$availability_class = isset( $availability[ 'class' ] ) ? $availability[ 'class' ] : '';
			$availability_text  = isset( $availability[ 'availability' ] ) ? $availability[ 'availability' ] : '';

			if ( $filter_stock_display ) {

				if ( '' === $stock_format ) {
					remove_filter( 'option_woocommerce_stock_format', array( $this, 'filter_stock_format' ) );
				}

				if ( $stock_notify_threshold ) {
					remove_filter( 'option_woocommerce_notify_low_stock_amount', array( $this, 'filter_notify_low_stock_amount' ) );
				}
			}
		}

		/**
		 * 'woocommerce_get_bundled_item_availability_class' filter.
		 *
		 * @param  string           $availability_class
		 * @param  WC_Bundled_Item  $this
		 * @param  WC_Product       $product
		 */
		$availability_class = apply_filters( 'woocommerce_get_bundled_item_availability_class', $availability_class, $this, $product );

		/**
		 * 'woocommerce_get_bundled_item_availability_text' filter.
		 *
		 * @param  string           $availability_text
		 * @param  WC_Bundled_Item  $this
		 * @param  WC_Product       $product
		 */
		$availability_text = apply_filters( 'woocommerce_get_bundled_item_availability_text', $availability_text, $this, $product );

		/**
		 * 'woocommerce_get_bundled_item_availability' filter.
		 *
		 * Bundled items availability needs to take min_quantity into account, hence the filter name change.
		 *
		 * @param  array            $availability
		 * @param  WC_Bundled_Item  $this
		 * @param  WC_Product       $product
		 */
		return apply_filters( 'woocommerce_get_bundled_item_availability', array(
			'availability' => $availability_text,
			'class'        => $availability_class,
		), $this, $product );
	}

	/**
	 * Changes stock format to "low_amount".
	 *
	 * @since  5.5.0
	 *
	 * @param  string  $format
	 * @return string
	 */
	public function filter_stock_format( $format ) {
		return 'low_amount';
	}

	/**
	 * Changes the low stock amount notification threshold.
	 *
	 * @since  5.5.0
	 *
	 * @param  int  $amount
	 * @return int
	 */
	public function filter_notify_low_stock_amount( $amount ) {
		return max( 1, $this->get_quantity() );
	}

	/**
	 * Get (synced) subscription up-front price.
	 *
	 * @since  4.14.6
	 *
	 * @param  double      $sign_up_fee
	 * @param  double      $recurring_price
	 * @param  WC_Product  $product
	 * @return double
	 */
	public function get_up_front_subscription_price( $recurring_price, $sign_up_fee, $product = false ) {

		if ( ! $product ) {
			$product = $this->product;
		}

		$price = $sign_up_fee;

		if ( WC_PB()->compatibility->is_subscription( $product ) ) {

			if ( 0 == WC_Subscriptions_Product::get_trial_length( $product ) ) {

				if ( WC_Subscriptions_Synchroniser::is_product_synced( $product ) ) {

					$next_payment_date = WC_Subscriptions_Synchroniser::calculate_first_payment_date( $product, 'timestamp' );

					if ( WC_Subscriptions_Synchroniser::is_today( $next_payment_date ) ) {

						$price = (double) $price + (double) $recurring_price;

					} elseif ( WC_Subscriptions_Synchroniser::is_product_prorated( $product ) ) {

						switch ( WC_Subscriptions_Product::get_period( $product ) ) {

							case 'week' :
								$days_in_cycle = 7 * WC_Subscriptions_Product::get_interval( $product );
								break;
							case 'month' :
								$days_in_cycle = date( 't' ) * WC_Subscriptions_Product::get_interval( $product );
								break;
							case 'year' :
								$days_in_cycle = ( 365 + date( 'L' ) ) * WC_Subscriptions_Product::get_interval( $product );
								break;
						}

						$days_until_next_payment = ceil( ( $next_payment_date - gmdate( 'U' ) ) / ( 60 * 60 * 24 ) );
						$price                   = (double) $sign_up_fee + $days_until_next_payment * ( (double) $recurring_price / $days_in_cycle );

					} elseif ( method_exists( 'WC_Subscriptions_Synchroniser', 'is_payment_upfront' ) && WC_Subscriptions_Synchroniser::is_payment_upfront( $product ) ) {
						$price = (double) $price + (double) $recurring_price;
					}

				} else {
					$price = (double) $price + (double) $recurring_price;
				}
			}
		}

		return round( $price, WC_PB_Core_Compatibility::wc_get_rounding_precision() );
	}

	/**
	 * Filters bundled product attributes, hiding attributes that correspond to filtered-out variations.
	 *
	 * @param  string  $output
	 * @param  array   $attribute
	 * @param  array   $values
	 * @return string
	 */
	public function filter_bundled_item_attribute( $output, $attribute, $values ) {

		if ( $attribute[ 'is_variation' ] ) {

			$variation_attribute_values = array();

			// We can only work past this point only when the variations count is acceptable.
			if ( $this->use_ajax_for_product_variations() ) {
				return $output;
			}

			$bundled_item_variations = $this->get_product_variations();

			if ( empty( $bundled_item_variations ) ) {
				return $output;
			}

			$attribute_key = wc_variation_attribute_name( $attribute[ 'name' ] );

			// Find active attribute values from the bundled item variation data.
			foreach ( $bundled_item_variations as $variation_data ) {
				if ( isset( $variation_data[ 'attributes' ][ $attribute_key ] ) ) {
					$variation_attribute_values[] = $variation_data[ 'attributes' ][ $attribute_key ];
					$variation_attribute_values   = array_unique( $variation_attribute_values );
				}
			}

			if ( ! empty( $variation_attribute_values ) && in_array( '', $variation_attribute_values ) ) {
				return $output;
			}

			$attribute_name = $attribute[ 'name' ];

			$filtered_values = array();

			if ( $attribute[ 'is_taxonomy' ] ) {

				$product_terms = wc_get_product_terms( $this->get_product_id(), $attribute_name, array( 'fields' => 'all' ) );

				foreach ( $product_terms as $product_term ) {
					if ( in_array( $product_term->slug, $variation_attribute_values ) ) {
						$filtered_values[] = $product_term->name;
					}
				}

				return wpautop( wptexturize( implode( ', ', $filtered_values ) ) );

			} else {

				foreach ( $values as $value ) {

					$check_value = $value;

					if ( in_array( $check_value, $variation_attribute_values ) ) {
						$filtered_values[] = $value;
					}
				}

				return wpautop( wptexturize( implode( ', ', $filtered_values ) ) );
			}
		}

		return $output;
	}

	/**
	 * Get image size.
	 *
	 * @since  5.7.3
	 *
	 * @return string
	 */
	public function get_bundled_item_thumbnail_size() {
		return apply_filters( 'bundled_product_large_thumbnail_size', WC_PB_Core_Compatibility::is_wc_version_gte( '3.3' ) ? 'woocommerce_thumbnail' : 'shop_catalog', $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	public function init() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::sync_prices()' );
		$this->sync_prices();
	}
	public function is_priced_per_product() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::is_priced_individually()' );
		$this->is_priced_individually();
	}
	public function get_bundled_item_price( $min_or_max = 'min', $display = false ) {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::get_price()' );
		return $this->get_price( $min_or_max, $display );
	}
	public function get_bundled_item_regular_price( $min_or_max = 'min', $display = false ) {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::get_regular_price()' );
		return $this->get_regular_price( $min_or_max, $display );
	}
	public function get_bundled_item_recurring_price( $min_or_max = 'min', $display = false ) {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::get_recurring_price()' );
		return $this->get_recurring_price( $min_or_max, $display );
	}
	public function get_bundled_item_regular_recurring_price( $min_or_max = 'min', $display = false ) {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::get_regular_recurring_price()' );
		return $this->get_regular_recurring_price( $min_or_max, $display );
	}
	public function get_bundled_item_price_including_tax( $min_or_max = 'min', $qty = 1 ) {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::get_price_including_tax()' );
		return $this->get_price_including_tax( $min_or_max, $qty );
	}
	public function get_bundled_item_price_excluding_tax( $min_or_max = 'min', $qty = 1 ) {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::get_price_excluding_tax()' );
		return $this->get_price_excluding_tax( $min_or_max, $qty );
	}
	public function is_out_of_stock() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::is_in_stock()' );
		return ! $this->is_in_stock();
	}
	public function is_sub() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::is_subscription()' );
		return $this->is_subscription();
	}
	public function is_variable_sub() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::is_variable_subscription()' );
		return $this->is_variable_subscription();
	}
	public function get_prorated_price_for_subscription( $recurring_price, $sign_up_fee, $product = false ) {
		_deprecated_function( __METHOD__ . '()', '4.14.6', __CLASS__ . '::get_up_front_subscription_price()' );
		return $this->get_up_front_subscription_price( $recurring_price, $sign_up_fee, $product );
	}
	public function get_sign_up_fee( $sign_up_fee, $product ) {
		_deprecated_function( __METHOD__ . '()', '4.14.1' );
		return $sign_up_fee;
	}
	public function has_variables() {
		_deprecated_function( __METHOD__ . '()', '4.11.7', __CLASS__ . '::requires_input()' );
		return $this->requires_input();
	}
}
