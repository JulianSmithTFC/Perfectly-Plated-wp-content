<?php
namespace WPO\WC\PDF_Invoices\Documents;

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( '\\WPO\\WC\\PDF_Invoices\\Documents\\Proforma' ) ) :

/**
 * Proforma Invoice Document
 * 
 * @class       \WPO\WC\PDF_Invoices\Documents\Proforma
 * @version     2.0
 * @category    Class
 * @author      Ewout Fernhout
 */

class Proforma extends Pro_Document {
	/**
	 * Init/load the order object.
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		// set properties
		$this->type		= 'proforma';
		$this->title	= __( 'Proforma Invoice', 'wpo_wcpdf_pro' );
		$this->icon		= WPO_WCPDF_Pro()->plugin_url() . "/images/proforma.png";

		// Call parent constructor
		parent::__construct( $order );
	}

	public function get_title() {
		// override/not using $this->title to allow for language switching!
		return apply_filters( "wpo_wcpdf_{$this->slug}_title", __( 'Proforma Invoice', 'wpo_wcpdf_pro' ), $this );
	}

	public function get_filename( $context = 'download', $args = array() ) {
		$order_count = isset($args['order_ids']) ? count($args['order_ids']) : 1;

		$name = _n( 'proforma-invoice', 'proforma-invoices', $order_count, 'wpo_wcpdf_pro' );

		if ( $order_count == 1 ) {
			if ( isset( $this->settings['display_number'] ) ) {
				$suffix = (string) $this->get_number();
			} else {
				if ( empty( $this->order ) ) {
					$order = WCX::get_order ( $order_ids[0] );
					$suffix = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : '';
				} else {
					$suffix = method_exists( $this->order, 'get_order_number' ) ? $this->order->get_order_number() : '';
				}
			}
		} else {
			$suffix = date('Y-m-d'); // 2020-11-11
		}

		$filename = $name . '-' . $suffix . '.pdf';

		// Filter filename
		$order_ids = isset($args['order_ids']) ? $args['order_ids'] : array( $this->order_id );
		$filename = apply_filters( 'wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context );

		// sanitize filename (after filters to prevent human errors)!
		return sanitize_file_name( $filename );
	}


	/**
	 * Initialise settings
	 */
	public function init_settings() {
		// Register settings.
		$page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_proforma';

		$settings_fields = array(
			array(
				'type'			=> 'section',
				'id'			=> 'proforma',
				'title'			=> '',
				'callback'		=> 'section',
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'enabled',
				'title'			=> __( 'Enable', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'enabled',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'attach_to_email_ids',
				'title'			=> __( 'Attach to:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'multiple_checkboxes',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'attach_to_email_ids',
					'fields' 		=> $this->get_wc_emails(),
					'description'	=> !is_writable( WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ) ? '<span class="wpo-warning">' . sprintf( __( 'It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email invoices.', 'woocommerce-pdf-invoices-packing-slips' ), WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ).'</span>':'',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_shipping_address',
				'title'			=> __( 'Display shipping address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_shipping_address',
					'description'		=> __( 'Display shipping address (in addition to the default billing address) if different from billing address', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_email',
				'title'			=> __( 'Display email address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_email',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_phone',
				'title'			=> __( 'Display phone number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_phone',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_date',
				'title'			=> __( 'Display proforma invoice date', 'wpo_wcpdf_pro' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_date',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_number',
				'title'			=> __( 'Display proforma invoice number', 'wpo_wcpdf_pro' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_number',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'number_sequence',
				'title'			=> __( 'Number sequence', 'wpo_wcpdf_pro' ),
				'callback'		=> 'radio_button',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'number_sequence',
					'options' 			=> array(
						'invoice_number'		=> __( 'Main invoice numbering' , 'wpo_wcpdf_pro' ),
						'proforma_number'		=> __( 'Separate proforma numbering' , 'wpo_wcpdf_pro' ),
					),
					'default'			=> 'proforma_number',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'next_proforma_number',
				'title'			=> __( 'Next proforma invoice number (without prefix/suffix etc.)', 'wpo_wcpdf_pro' ),
				'callback'		=> 'next_number_edit',
				'section'		=> 'proforma',
				'args'			=> array(
					'store'			=> 'proforma_number',
					'size'			=> '10',
					'description'	=> __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'number_format',
				'title'			=> __( 'Number format', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'multiple_text_input',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'			=> $option_name,
					'id'					=> 'number_format',
					'fields'				=> array(
						'prefix'			=> array(
							'placeholder'	=> __( 'Prefix' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'			=> 20,
							'description'	=> __( 'to use the proforma invoice year and/or month, use [proforma_year] or [proforma_month] respectively' , 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'suffix'			=> array(
							'placeholder'	=> __( 'Suffix' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'			=> 20,
							'description'	=> '',
						),
						'padding'			=> array(
							'placeholder'	=> __( 'Padding' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'			=> 20,
							'type'			=> 'number',
							'description'	=> __( 'enter the number of digits here - enter "6" to display 42 as 000042' , 'woocommerce-pdf-invoices-packing-slips' ),
						),
					),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'reset_number_yearly',
				'title'			=> __( 'Reset proforma invoice number yearly', 'wpo_wcpdf_pro' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'reset_number_yearly',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'my_account_buttons',
				'title'			=> __( 'Allow My Account download', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'select',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'my_account_buttons',
					'options' 		=> array(
						'no_invoice'	=> __( 'Only when the final invoice is not available' , 'wpo_wcpdf_pro' ),
						'available'		=> __( 'Only when a proforma invoice is already created/emailed' , 'wpo_wcpdf_pro' ),
						'custom'		=> __( 'Only for specific order statuses (define below)' , 'woocommerce-pdf-invoices-packing-slips' ),
						'always'		=> __( 'Always' , 'woocommerce-pdf-invoices-packing-slips' ),
						'never'			=> __( 'Never' , 'woocommerce-pdf-invoices-packing-slips' ),
					),
					'default'		=> 'no_invoice',
					'custom'		=> array(
						'type'		=> 'multiple_checkboxes',
						'args'		=> array(
							'option_name'	=> $option_name,
							'id'			=> 'my_account_restrict',
							'fields'		=> $this->get_wc_order_status_list(),
						),
					),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'disable_free',
				'title'			=> __( 'Disable for free products', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'disable_free',
					'description'	=> __( "Disable automatic creation/attachment when only free products are ordered", 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'use_latest_settings',
				'title'			=> __( 'Always use most current settings', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'proforma',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'use_latest_settings',
					'description'	=> __( "When enabled, the document will always reflect the most current settings (such as footer text, document name, etc.) rather than using historical settings.", 'woocommerce-pdf-invoices-packing-slips' )
					                   . "<br>"
					                   . __( "<strong>Caution:</strong> enabling this will also mean that if you change your company name or address in the future, previously generated documents will also be affected.", 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
		);

		// allow plugins to alter settings fields
		$settings_fields = apply_filters( 'wpo_wcpdf_settings_fields_documents_proforma', $settings_fields, $page, $option_group, $option_name );
		WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
		return;

	}

}

endif; // class_exists

return new Proforma();