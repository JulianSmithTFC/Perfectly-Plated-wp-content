<?php
if ( ! defined( 'ABSPATH' ) )
     exit;

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://myworks.design/software/wordpress/woocommerce/myworks-wc-qbo-sync
 * @since      1.0.0
 *
 * @package    MyWorks_WC_QBO_Sync
 * @subpackage MyWorks_WC_QBO_Sync/admin/partials
 */
?>
<?php
global $MSQS_QL;
global $MWQS_OF;

if($MSQS_QL->option_checked('mw_wc_qbo_sync_pause_up_qbo_conection')){
	$MSQS_QL = new MyWorks_WC_QBO_Sync_QBO_Lib(true);	
}

global $wpdb;

$page_url = 'admin.php?page=myworks-wc-qbo-sync-settings';
$selected_tab = (isset($_GET['selected_tab']))?$MSQS_QL->sanitize($_GET['selected_tab']):'';

$save_status = '';

if(isset($_POST['mw_wc_qbo_sync_settings']) && check_admin_referer( 'myworks_wc_qbo_sync_save_settings', 'map_wc_qbo_update_settings' )){
	MyWorks_WC_QBO_Sync_Admin::admin_settings_save($MWQS_OF->get_plugin_settings_post_data(),1);
	if($_POST['mw_wc_qbo_sync_update_option'] != $MSQS_QL->get_option('mw_wc_qbo_sync_update_option')){
		update_option('mw_wc_qbo_sync_update_option', $_POST['mw_wc_qbo_sync_update_option']);
		$date = strtotime("+8 day", strtotime(date('Y-m-d')));
		update_option('mw_wc_qbo_sync_update_option_date', date('Y-m-d', $date));
	}
	
	/**/
	update_option('mw_wc_qbo_sync_order_qbo_sync_as', isset($_POST['mw_wc_qbo_sync_order_qbo_sync_as'])?$_POST['mw_wc_qbo_sync_order_qbo_sync_as']:'');
	
	if(isset($_POST['mw_wc_qbo_sync_order_qbo_sync_as']) && $_POST['mw_wc_qbo_sync_order_qbo_sync_as'] == 'Per Role'){
		$mw_wc_qbo_sync_oqsa_pr_data = '';
		$mw_wc_qbo_sync_oqsa_pr_template_data = '';
		if(isset($_POST['vpr_wr']) && is_array($_POST['vpr_wr']) && isset($_POST['vpr_qost']) && is_array($_POST['vpr_qost'])){
			if(is_array($_POST['vpr_wr']) && !empty($_POST['vpr_wr']) && is_array($_POST['vpr_qost']) && !empty($_POST['vpr_qost'])){
				if(count($_POST['vpr_wr']) == count($_POST['vpr_qost'])){
					$vpr_wr = $_POST['vpr_wr'];
					$vpr_qost = $_POST['vpr_qost'];
					
					$qosa_pa_data = array();
					$qosa_pa_template_data = array();
					foreach($vpr_wr as $k => $v){
						if(!empty($v)){
							$v = trim($v);
							if(isset($vpr_qost[$k]) && !empty($vpr_qost[$k])){
								$qv = trim($vpr_qost[$k]);
								$qosa_pa_data[$v] = $qv;
							}								
						}
					}
					
					if(!empty($qosa_pa_data)){
						$mw_wc_qbo_sync_oqsa_pr_data = $qosa_pa_data;
					}						
					
				}
			}
		}
		//$MSQS_QL->_p($mw_wc_qbo_sync_oqsa_pr_data);die;
		update_option('mw_wc_qbo_sync_oqsa_pr_data',$mw_wc_qbo_sync_oqsa_pr_data);			
	}
	
	$save_status = 'admin-success-green';
	$MSQS_QL->set_session_val('settings_save_class',$save_status);
	$MSQS_QL->set_session_val('settings_current_tab',isset($_POST['mw_qbo_sybc_settings_current_tab'])?$_POST['mw_qbo_sybc_settings_current_tab']:'mw_qbo_sybc_settings_tab_one');
	
	$MSQS_QL->redirect($page_url);
}
$save_status = $MSQS_QL->get_session_val('settings_save_class','',true);
$settings_current_tab = $MSQS_QL->get_session_val('settings_current_tab','mw_qbo_sybc_settings_tab_one',true);

$option_keys = $MWQS_OF->get_plugin_option_keys();

$admin_settings_data = $MSQS_QL->get_all_options($option_keys);

/*
$admin_settings_data = MyWorks_WC_QBO_Sync_Admin::admin_settings_get($option_keys,1);
*/
$mw_qbo_product_list = '';
if(!$MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
	$mw_qbo_product_list = $MSQS_QL->get_product_dropdown_list('');
}

//
$qbo_customer_options = '';
if(!$MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){	
	$cdd_sb = 'dname';
	$mw_wc_qbo_sync_client_sort_order = $MSQS_QL->sanitize($MSQS_QL->get_option('mw_wc_qbo_sync_client_sort_order'));
	if($mw_wc_qbo_sync_client_sort_order!=''){
		$cdd_sb = $mw_wc_qbo_sync_client_sort_order;
		if($cdd_sb!='dname' && $cdd_sb!='first' && $cdd_sb!='last' && $cdd_sb!='company'){
			$cdd_sb = 'dname';
		}
	}
	$qbo_customer_options = $MSQS_QL->option_html('', $wpdb->prefix.'mw_wc_qbo_sync_qbo_customers','qbo_customerid','dname','',$cdd_sb.' ASC','',true);
}

$get_account_dropdown_list = $MSQS_QL->get_account_dropdown_list('',true);

$list_selected = '';
if(!$MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
	$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_qbo_item\').val('.$admin_settings_data['mw_wc_qbo_sync_default_qbo_item'].');';
	$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_coupon_code\').val('.$admin_settings_data['mw_wc_qbo_sync_default_coupon_code'].');';
	$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_shipping_product\').val('.$admin_settings_data['mw_wc_qbo_sync_default_shipping_product'].');';
	$list_selected.='jQuery(\'#mw_wc_qbo_sync_orders_to_specific_cust\').val('.$admin_settings_data['mw_wc_qbo_sync_orders_to_specific_cust'].');';
	
	$list_selected.='jQuery(\'#mw_wc_qbo_sync_otli_qbo_product\').val('.$admin_settings_data['mw_wc_qbo_sync_otli_qbo_product'].');';
	
}

$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_qbo_product_account\').val('.$admin_settings_data['mw_wc_qbo_sync_default_qbo_product_account'].');';
$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_qbo_asset_account\').val('.$admin_settings_data['mw_wc_qbo_sync_default_qbo_asset_account'].');';
$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_qbo_expense_account\').val('.$admin_settings_data['mw_wc_qbo_sync_default_qbo_expense_account'].');';
$list_selected.='jQuery(\'#mw_wc_qbo_sync_default_qbo_discount_account\').val('.$admin_settings_data['mw_wc_qbo_sync_default_qbo_discount_account'].');';

// PAGE SCRIPTS AND STYLES
MyWorks_WC_QBO_Sync_Admin::get_settings_assets(1);
MyWorks_WC_QBO_Sync_Admin::is_trial_version_check();

//19-06-2017
$order_statuses = wc_get_order_statuses();
$setting_removed = true;
?>

<?php 
	$wu_roles = get_editable_roles();
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="mw_wc_qbo_sync_container">
<form method="post">
<?php wp_nonce_field( 'myworks_wc_qbo_sync_save_settings', 'map_wc_qbo_update_settings' ); ?>
<input type="hidden" name="mw_qbo_sybc_settings_current_tab" id="mw_qbo_sybc_settings_current_tab" value="<?php echo $settings_current_tab; ?>">
<nav class="mw-qbo-sync-grey">
	<div class="nav-wrapper">
		<a class="brand-logo left" href="javascript:void(0)">
			<img src="<?php echo plugins_url( 'myworks-woo-sync-for-quickbooks-online/admin/image/mwd-logo.png' ) ?>">
		</a>
		<ul class="hide-on-med-and-down right">
			<li class="default-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_one"><?php echo __('Default','mw_wc_qbo_sync') ?></a></li>
			<li class="invoice-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_two"><?php echo __('Order','mw_wc_qbo_sync') ?></a></li>
			
			<li class="product-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_product"><?php echo __('Product','mw_wc_qbo_sync') ?></a></li>
			
			<?php /* <li class="payment-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_three"><?php echo __('Payment','mw_wc_qbo_sync') ?></a></li> */ ?>
			
			<?php if(!$MSQS_QL->get_qbo_company_setting('is_automated_sales_tax')):?>
			<li class="tax-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_four"><?php echo __('Taxes','mw_wc_qbo_sync') ?></a></li>
			<?php endif;?>
			
			<li class="mapping-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_five"><?php echo __('Mapping','mw_wc_qbo_sync') ?></a></li>
			<li class="pull-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_six"><?php echo __('Pull','mw_wc_qbo_sync') ?></a></li>
			
			<li class="webhook-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_wh"><?php echo __('Automatic Sync','mw_wc_qbo_sync') ?></a></li>
			
			<li style="display:none; class="dis-icon mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_seven"><?php echo __('Disable','mw_wc_qbo_sync') ?></a></li>
			<li style="display:none;" class="adv-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_eight"><?php echo __('Advanced','mw_wc_qbo_sync') ?></a></li>
			<li class="misc-menu mwqs_stb"><a href="javascript:void(0)" id="mw_qbo_sybc_settings_tab_nine"><?php echo __('Miscellaneous','mw_wc_qbo_sync') ?></a></li>			
		</ul>
	</div>
</nav>

<?php require_once 'myworks-wc-qbo-sync-admin-guidelines.php' ?>
<div class="container" id="mw_qbo_sybc_settings_tables">
	<div class="card">
		<div class="card-content">
			<div class="row">
				<div class="col s12 m12 l12">
					<div class="row">
						<div class="col s12 m12 l12">
                          	<div id="mw_qbo_sybc_settings_tab_one_body" style="display: none;">
							<h6><?php echo __('Default Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body_body">
							<tbody>                				
								<tr>
									<th class="title-description">
								    	<?php echo __('Default for unmatched products','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<?php
														$dd_options = '<option value=""></option>';
														$dd_ext_class = '';
														if($MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
															$dd_ext_class = 'mwqs_dynamic_select';
															if((int) $admin_settings_data['mw_wc_qbo_sync_default_qbo_item']){
																$itemid = (int) $admin_settings_data['mw_wc_qbo_sync_default_qbo_item'];
																$qb_item_name = $MSQS_QL->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_qbo_items','name','itemid',$itemid);
																if($qb_item_name!=''){
																	$dd_options = '<option value="'.$itemid.'">'.$qb_item_name.'</option>';
																}
															}
														}else{
															$dd_options.=$mw_qbo_product_list;
														}
													?>
													<select name="mw_wc_qbo_sync_default_qbo_item" id="mw_wc_qbo_sync_default_qbo_item" class="filled-in production-option mw_wc_qbo_sync_select <?php echo $dd_ext_class;?>">
														<?php echo $dd_options;?>
													</select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
									  <span class="tooltiptext"><?php echo __('This is a QuickBooks Online Product that is only used when syncing an order that contains line items not mapped to a QuickBooks product. Think of this as a fallback / miscellaneous type product.','mw_wc_qbo_sync') ?></span>
									</div>
                                    </td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Default QuickBooks Sales Account for New Products ','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_default_qbo_product_account" id="mw_wc_qbo_sync_default_qbo_product_account" class="filled-in production-option mw_wc_qbo_sync_select dd_dqsafnp">
													<option value=""></option>
										            <?php echo $get_account_dropdown_list ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Default account assigned to your WooCommerce products when pushing them over to QBO. This should be an income or expense account.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Default QuickBooks Inventory Asset Account for New Products','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_default_qbo_asset_account" id="mw_wc_qbo_sync_default_qbo_asset_account" class="filled-in production-option mw_wc_qbo_sync_select dd_dqiaafnp">
													<option value=""></option>
										            <?php echo $get_account_dropdown_list ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Default inventory asset account assigned to your WooCommerce products when pushing them over to QBO.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Default QuickBooks COGS Account for New Products','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_default_qbo_expense_account" id="mw_wc_qbo_sync_default_qbo_expense_account" class="filled-in production-option mw_wc_qbo_sync_select dd_dqcogsafnp">
													<option value=""></option>
										            <?php echo $get_account_dropdown_list ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Default Cost of Goods Sold account assigned to your WooCommerce products when pushing them over to QBO.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								
								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('Default QuickBooks Discount Account for New Products','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_default_qbo_discount_account" id="mw_wc_qbo_sync_default_qbo_discount_account" class="filled-in production-option mw_wc_qbo_sync_select">
													<option value=""></option>
										            <?php echo $get_account_dropdown_list ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Default Income Account in QuickBooks Online for unmapped Discounts in WooCommerce.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								
								
								<tr <?php //if($MSQS_QL->get_qbo_company_setting('is_discount_allowed')){echo '';}?> style="display:none;">
									<th class="title-description">
								    	<?php echo __('Default QuickBooks Coupon Code Product','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<?php
														/*
														$dd_options = '<option value=""></option>';
														$dd_ext_class = '';
														if($MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
															$dd_ext_class = 'mwqs_dynamic_select';
															if((int) $admin_settings_data['mw_wc_qbo_sync_default_coupon_code']){
																$itemid = (int) $admin_settings_data['mw_wc_qbo_sync_default_coupon_code'];
																$qb_item_name = $MSQS_QL->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_qbo_items','name','itemid',$itemid);
																if($qb_item_name!=''){
																	$dd_options = '<option value="'.$itemid.'">'.$qb_item_name.'</option>';
																}
															}
														}else{
															$dd_options.=$mw_qbo_product_list;
														}
														*/
													?>
													
													<select name="mw_wc_qbo_sync_default_coupon_code" id="mw_wc_qbo_sync_default_coupon_code" class="filled-in production-option mw_wc_qbo_sync_select <?php echo $dd_ext_class;?>">
														<?php //echo $dd_options;?>
													</select>
													
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose a QuickBooks Online Product to fallback to in invoice line items for unmapped Coupon Codes.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								
								<tr <?php if(!$MSQS_QL->option_checked('mw_wc_qbo_sync_odr_shipping_as_li') && $MSQS_QL->get_qbo_company_setting('is_shipping_allowed')){echo 'style="display:none;"';}?>>
									<th class="title-description">
								    	<?php echo __('Default QuickBooks Shipping Product','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<?php
														$dd_options = '<option value=""></option>';
														$dd_ext_class = '';
														if($MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
															$dd_ext_class = 'mwqs_dynamic_select';
															if((int) $admin_settings_data['mw_wc_qbo_sync_default_shipping_product']){
																$itemid = (int) $admin_settings_data['mw_wc_qbo_sync_default_shipping_product'];
																$qb_item_name = $MSQS_QL->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_qbo_items','name','itemid',$itemid);
																if($qb_item_name!=''){
																	$dd_options = '<option value="'.$itemid.'">'.$qb_item_name.'</option>';
																}
															}
														}else{
															//$dd_options.=$mw_qbo_product_list;
															$dd_options.=$MSQS_QL->option_html('', $wpdb->prefix.'mw_wc_qbo_sync_qbo_items','itemid','name'," product_type!='Inventory' ",'name ASC','',true);
														}
													?>													
													
													<select name="mw_wc_qbo_sync_default_shipping_product" id="mw_wc_qbo_sync_default_shipping_product" class="filled-in production-option mw_wc_qbo_sync_select <?php echo $dd_ext_class;?>">
														<?php echo $dd_options;?>
													</select>
													
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose a QuickBooks Online Product to fallback to for unmapped Shipping Methods.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								<?php //$MSQS_QL->is_plugin_active('woo-multi-currency')?>
								<?php if($MSQS_QL->get_qbo_company_setting('is_m_currency')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Enable currencies for your WooCommerce store','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_store_currency[]" id="mw_wc_qbo_sync_store_currency" class="filled-in production-option mw_wc_qbo_sync_select mqs_multi" multiple="multiple">
													<option value=""></option>
													<?php 
														$sel_cur_list = $admin_settings_data['mw_wc_qbo_sync_store_currency'];
														if($sel_cur_list!=''){
															$sel_cur_list = explode(',',$sel_cur_list);
														}
													?>
										            <?php $MSQS_QL->only_option($sel_cur_list,$MSQS_QL->get_world_currency_list()) ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select currencies for your WooCommerce store. You can select multiple currency as per requirement.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								<?php endif;?>
								
            				</tbody>
							</table>
							</div>
							<!-- Added by Peter -->
							
							<div class="row">
								<div class="input-field col s3 m3 l3">
									<p>Timezone</p>
									<p><?php if (date_default_timezone_get()) {
									    echo get_option('timezone_string');
									}?></p>
								</div>
								
								<?php //$MSQS_QL->is_plugin_active('woo-multi-currency')?>
								<?php if($MSQS_QL->get_qbo_company_setting('is_m_currency')):?>								
								<div class="input-field col s3 m3 l3">
									<p>Currency</p>
									<p><?php echo get_option('woocommerce_currency').' '.get_woocommerce_currency_symbol() ?></p>
								</div>
								<?php endif;?>
								
								<div class="input-field col s3 m3 l3">
									<p>QuickBooks Discount Field</p>
									<?php if($MSQS_QL->get_qbo_company_setting('is_discount_allowed')){echo 'Enabled';}else{echo 'Disabled <a href="https://docs.myworks.software/woocommerce-sync-for-quickbooks-online/discounts-coupons/getting-set-up-with-discounts" target="_blank"><div class="material-icons tooltipped right tooltip">?
										</div></a>';}?>
								</div>
								<div class="input-field col s3 m3 l3">
									<p>QuickBooks Tax Setup</p>
									<p><?php if($MSQS_QL->get_qbo_company_setting('is_automated_sales_tax')){echo 'Automated Sales Tax';}else{echo 'Normal Tax';}?></p>
								</div>
							</div>
							
							<!-- Added by Peter -->
							
							</div>
							
							<div id="mw_qbo_sybc_settings_tab_two_body" style="display: none;">
							<h6><?php echo __('Order Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body_body">
							<tbody>
								<!--mw_wc_qbo_sync_order_as_sales_receipt-->
								<?php
									$wo_qsa = $MSQS_QL->get_option('mw_wc_qbo_sync_order_qbo_sync_as');
									if($wo_qsa!='Invoice' && $wo_qsa!='SalesReceipt' && $wo_qsa!='Per Role' && $wo_qsa!='Per Gateway'){
										$wo_qsa = 'Invoice';
									}
								?>
								
								<tr>
									<th class="title-description"  width="35%">
								    	<?php echo __('Sync WooCommerce Orders as','mw_wc_qbo_sync') ?>
								    	
								    </th>									
									
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">												
												<div class="switch-toggle switch-3 switch-candy">
													<input id="wo_qsa_inv" value="Invoice" name="mw_wc_qbo_sync_order_qbo_sync_as" type="radio" <?php if($wo_qsa=='Invoice'){echo 'checked="checked"';}?>>
													<label for="wo_qsa_inv" onclick="">Invoice</label>
													
													<input id="wo_qsa_sr" value="SalesReceipt" name="mw_wc_qbo_sync_order_qbo_sync_as" type="radio" <?php if($wo_qsa=='SalesReceipt'){echo 'checked="checked"';}?>>
													<label for="wo_qsa_sr" onclick="">SalesReceipt</label>
													
													<?php if(is_array($wu_roles) && count($wu_roles)):?>
													<input id="wo_qsa_vpr" value="Per Role" name="mw_wc_qbo_sync_order_qbo_sync_as" type="radio" <?php if($wo_qsa=='Per Role'){echo 'checked="checked"';}?>>
													<label for="wo_qsa_vpr" onclick="">Per Role</label>
													<?php endif;?>
													
													<input disabled id="wo_qsa_pg" value="Per Gateway" name="mw_wc_qbo_sync_order_qbo_sync_as" type="radio" <?php if($wo_qsa=='Per Gateway'){echo 'checked="checked"';}?>>
													<label style="background:lightgray;" title="Coming Soon..." for="wo_qsa_pg" onclick="">Per Gateway</label>
													
													<a></a>
												</div>
												
												<div id="mwoqsa_rm">
													<?php
													if($wo_qsa == 'Per Gateway'){
														echo '<small style="font-size:100%;">Please select the order sync type per gateway in Map > Payment Method page.</small>';
													}
													?>
												</div>
												
											</div>
										</div>
									</td>
									
									<td width="5%">
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Turn on to sync WooCommerce orders as Sales Receipts into QuickBooks Online. Otherwise, they will be synced as an Invoice + Payment.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<!--**-->
								<?php
									$qost_arr = array(
										'Invoice' => 'Invoice',
										'SalesReceipt' => 'SalesReceipt',																		
									);
									
									$mw_wc_qbo_sync_oqsa_pr_data = get_option('mw_wc_qbo_sync_oqsa_pr_data');
									$mw_wc_qbo_sync_oqsa_pr_template_data = get_option('mw_wc_qbo_sync_oqsa_pr_template_data');
								?>
								<?php if(is_array($wu_roles) && count($wu_roles)):?>
								<tr id="wo_qsa_vpr_map_tr" <?php if($wo_qsa != 'Per Role'){echo 'style="display:none;"';}?>>
									<th class="title-description">
										<?php echo __('WooCommerce User Role -> Order Sync Type Mapping','mw_wc_qbo_sync') ?>
									</th>
									<td>
										<table>
											<?php foreach ($wu_roles as $role_name => $role_info):?>
											<?php 
												$qost_va = '';
												if(is_array($mw_wc_qbo_sync_oqsa_pr_data) && isset($mw_wc_qbo_sync_oqsa_pr_data[$role_name])){
													$qost_va = $mw_wc_qbo_sync_oqsa_pr_data[$role_name];
												}
											?>
											<tr style="border:none; background:none;">
												<td width="30%">
													<?php echo $role_info['name'];?>
													<input type="hidden" name="vpr_wr[]" value="<?php echo $role_name;?>">
												</td>
												
												<td>												
												<select name="vpr_qost[]" class="filled-in production-option mw_wc_qbo_sync_select">
													<?php echo $MSQS_QL->only_option($qost_va,$qost_arr);?>
												</select>
												</td>												
											</tr>
											<?php endforeach;?>
											<?php 
												$qost_va = '';
												if(is_array($mw_wc_qbo_sync_oqsa_pr_data) && isset($mw_wc_qbo_sync_oqsa_pr_data['wc_guest_user'])){
													$qost_va = $mw_wc_qbo_sync_oqsa_pr_data['wc_guest_user'];
												}
											?>
											<tr style="border:none; background:none;">
												<td>
													<strong>Guest User</strong>
													<input type="hidden" name="vpr_wr[]" value="wc_guest_user">
												</td>
												
												<td>
												<select name="vpr_qost[]" class="filled-in production-option mw_wc_qbo_sync_select">
													<?php echo $MSQS_QL->only_option($qost_va,$qost_arr);?>
												</select>
												</td>
												
											</tr>
										</table>
									</td>
									
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose Wocommerce Order Syns as QBO Invoice or SalesReceipt','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<?php if(!$MSQS_QL->get_qbo_company_setting('is_custom_txn_num_allowed')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Use Next QuickBooks Order # (Beta)','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_use_qb_next_ord_num_iowon" id="mw_wc_qbo_sync_use_qb_next_ord_num_iowon" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_use_qb_next_ord_num_iowon']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to sync orders to QuickBooks using the NEXT QuickBooks Invoice/Sales Receipt # - instead of the WooCommerce order number.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Sync Order Notes to Statement Memo','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_invoice_memo" id="mw_wc_qbo_sync_invoice_memo" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_memo']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable the syncing of the WooCommerce Order Note contents to the QBO Statement Memo field.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
                				<tr>
									<th class="title-description">
								    	<?php echo __('Void orders in QuickBooks when WooCommerce order is cancelled','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_invoice_cancelled" id="mw_wc_qbo_sync_invoice_cancelled" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_cancelled']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to mark orders as void in QBO when cancelled in WooCommerce. Works in real-time, not applicable to historical orders.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php if(!$MSQS_QL->is_plg_lc_p_l()):?>
								<?php 
								if($MSQS_QL->get_qbo_company_setting('ClassTrackingPerTxn') || $MSQS_QL->get_qbo_company_setting('ClassTrackingPerTxnLine')):
								?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Default QuickBooks Class','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_inv_sr_txn_qb_class" id="mw_wc_qbo_sync_inv_sr_txn_qb_class" class="filled-in production-option mw_wc_qbo_sync_select">
													<option value=""></option>
										            <?php echo $MSQS_QL->get_class_dropdown_list($admin_settings_data['mw_wc_qbo_sync_inv_sr_txn_qb_class'],true); ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select a QuickBooks Class to use by default for any line items NOT mapped to a specific class in MyWorks Sync > Map.','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								<?php endif;?>
								<?php endif;?>
								
								<?php 
								if($MSQS_QL->get_qbo_company_setting('TrackDepartments')):
								?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Select QuickBooks Department','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_inv_sr_txn_qb_department" id="mw_wc_qbo_sync_inv_sr_txn_qb_department" class="filled-in production-option mw_wc_qbo_sync_select">
													<option value=""></option>
										            <?php echo $MSQS_QL->get_department_dropdown_list($admin_settings_data['mw_wc_qbo_sync_inv_sr_txn_qb_department'],true); ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select the department associated with the transaction for invoice and salesreceipt','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								<?php endif;?>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Block syncing orders before ID','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="text" name="mw_wc_qbo_sync_invoice_min_id" id="mw_wc_qbo_sync_invoice_min_id" value="<?php echo $admin_settings_data['mw_wc_qbo_sync_invoice_min_id'] ?>">
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Disable/block syncing WooCommerce orders before this Order ID to QuickBooks Online. Default is 0 as previous orders will not be synced anyways unless edited and saved.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Do not Sync $0 Orders','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_null_invoice" id="mw_wc_qbo_sync_null_invoice" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_null_invoice']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select to disable the real-time syncing of invoices with a $0 total to QuickBooks Online.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php if(!$setting_removed):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Sync Notes Into Custom Field','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_invoice_notes" id="mw_wc_qbo_sync_invoice_notes" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_notes']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Sync WooCommerce Invoice Note into QuickBooks Online custom field.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr id="mw_wc_qbo_sync_invoice_notes_res1" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_notes']!='true') echo 'style="display: none;"' ?>>
									<th class="title-description">
								    	<?php echo __('QBO Custom Field ID for Note','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="text" name="mw_wc_qbo_sync_invoice_note_id" id="mw_wc_qbo_sync_invoice_note_id" value="<?php echo $admin_settings_data['mw_wc_qbo_sync_invoice_note_id'] ?>">
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select the ID of your QuickBooks Custom Invoice Field for WooCommerce Note above.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>								
								
								<tr id="mw_wc_qbo_sync_invoice_notes_res2" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_notes']!='true') echo 'style="display: none;"' ?>>
									<th class="title-description">
								    	<?php echo __('QBO Custom Field Name for Note','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="text" name="mw_wc_qbo_sync_invoice_note_name" id="mw_wc_qbo_sync_invoice_note_name" value="<?php echo $admin_settings_data['mw_wc_qbo_sync_invoice_note_name'] ?>">
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Enter the Name of your QuickBooks Custom Invoice Field for WooCommerce Note','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
                				<?php endif;?>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Copy First Line Desc to Statement Memo','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_invoice_memo_statement" id="mw_wc_qbo_sync_invoice_memo_statement" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_memo_statement']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable the syncing of the WooCommerce first order line item description contents to the QBO Statement Memo field.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr>
									<th class="title-description">
								    	<?php echo __('Sync WooCommerce Order Date to QBO Service Date','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_invoice_date" id="mw_wc_qbo_sync_invoice_date" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_invoice_date']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable the syncing of the WooCommerce Order Date or Due Date to the QuickBooks Online service date field in the invoice.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr>
									<th class="title-description">
								    	<?php _e('Automatically sync orders when they reach any of these statuses','mw_wc_qbo_sync') ?>
										</br><span style="font-size:10px;color:grey;">This field must not be blank. By default, Processing and Completed statuses are selected here. </br> The Processing status must be selected in order for orders to automatically sync to QuickBooks.</span> 
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<?php 
														$mw_wc_qbo_sync_specific_order_status = $admin_settings_data['mw_wc_qbo_sync_specific_order_status'];
														if($mw_wc_qbo_sync_specific_order_status!=''){
															$mw_wc_qbo_sync_specific_order_status = explode(',',$mw_wc_qbo_sync_specific_order_status);
														}
													?>
													<select name="mw_wc_qbo_sync_specific_order_status[]" id="mw_wc_qbo_sync_specific_order_status" class="filled-in production-option mw_wc_qbo_sync_select" multiple="multiple">								
														<?php echo  $MSQS_QL->only_option($mw_wc_qbo_sync_specific_order_status,$order_statuses);?>
													</select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose a/multiple WooCommerce status that will act as a trigger to real-time sync the order to QBO. Defaults are Processing and Completed.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr>
									<th class="title-description">
								    	<?php echo __('Set QuickBooks Online invoice date according to the most recent order push date','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_qbo_push_invoice_date" id="mw_wc_qbo_sync_qbo_push_invoice_date" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_qbo_push_invoice_date']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to set the QuickBooks Online invoice date to be the most recent date it was pushed from WooCommerce - instead of the original WooCommerce Order date.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr style="display: none">
									<th class="title-description">
								    	<?php echo __('QuickBooks Online force shipping charge to line item','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_force_shipping_line_item" id="mw_wc_qbo_sync_force_shipping_line_item" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_force_shipping_line_item']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to force shipping charge to line item.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Skip Line Item Description ','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_skip_os_lid" id="mw_wc_qbo_sync_skip_os_lid" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_skip_os_lid']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Turn on to Skip Invoice /Sales Receipts Line Item Description.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php
									$qb_inv_sr_lid_opt_arr = array();
									$qb_inv_sr_lid_opt_arr['default_wpvn'] = 'Name of WooCommerce Product/Variation (default)';
									$qb_inv_sr_lid_opt_arr['woo_pv_sdc'] = 'ShortDescription of WooCommerce Product/Variation';
									//$qb_inv_sr_lid_opt_arr['mp_qbp_dc'] = 'Mapped QuickBooks Product Description ';
								?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Value for QuickBooks Description Line Item','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_inv_sr_qb_lid_val" id="mw_wc_qbo_sync_inv_sr_qb_lid_val" class="filled-in production-option mw_wc_qbo_sync_select">
													<!--<option value=""></option>-->
										            <?php echo $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_inv_sr_qb_lid_val'],$qb_inv_sr_lid_opt_arr); ?>
										            </select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select the line item description value for QuickBooks invoice and salesreceipt','mw_wc_qbo_sync') ?></span>
										</div>
                                    </td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Add WooCommerce Custom Order Line Item Meta Into QuickBooks Line Item Description ','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_wolim_iqilid_desc" id="mw_wc_qbo_sync_wolim_iqilid_desc" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_wolim_iqilid_desc']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Turn on to add WooCommerce Custom Order line item meta into Invoice /Sales Receipts Line Item Description.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Send/Email an Invoice after syncing into QuickBooks','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_send_inv_sr_afsi_qb" id="mw_wc_qbo_sync_send_inv_sr_afsi_qb" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_send_inv_sr_afsi_qb']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Turn on to send an invoice after syncing into QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Sync order discounts within original line item','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_no_ad_discount_li" id="mw_wc_qbo_sync_no_ad_discount_li" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_no_ad_discount_li']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If left off, order discounts will be synced as normal discount line. If turned on, order discounts will be synced to QuickBooks within the original line item, as the discounted price - instead of the full price line item + dicount line item.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Apply Discount before Sales Tax','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_qb_ap_tx_aft_discount" id="mw_wc_qbo_sync_qb_ap_tx_aft_discount" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_qb_ap_tx_aft_discount']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If disabled (default), the discount will be applied after the sales tax calculation in QuickBooks. If enabled, the discount will applied before the sales tax calculation.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<!---->
								<?php if($MSQS_QL->get_qbo_company_setting('is_shipping_allowed')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Sync shipping charges as a line item','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_odr_shipping_as_li" id="mw_wc_qbo_sync_odr_shipping_as_li" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_odr_shipping_as_li']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If enabled, this will sync order shipping charges as a line item (set in MyWorks Sync > Settings > Default) instead of into the default shipping subtotal field in QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<!---->
								<tr>
									<th class="title-description">
								    	<?php echo __('Set BillEmail to the primary email address of the QuickBooks customer','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_set_bemail_to_cus_email_addr" id="mw_wc_qbo_sync_set_bemail_to_cus_email_addr" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_set_bemail_to_cus_email_addr']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If enabled, this will 
										  Set invoice / salesreceipt BillEmail to the primary email address of the QuickBooks customer.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								
            				</tbody>
							</table>
							</div>
							</div>
							
							<div id="mw_qbo_sybc_settings_tab_product_body" style="display: none;">
							<h6><?php echo __('Product / Inventory Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body_body">
								<tbody>
									<tr>
										<th class="title-description">
											<?php echo __('QuickBooks Inventory Sync StartDate','mw_wc_qbo_sync') ?>
											
										</th>
										<td>
											<div class="row">
												<div class="input-field col s12 m12 l12">
													<input placeholder="yyyy-mm-dd" class="mwqs_datepicker" type="text" name="mw_wc_qbo_sync_qbo_inventory_start_date" id="mw_wc_qbo_sync_qbo_inventory_start_date" value="<?php echo (empty($admin_settings_data['mw_wc_qbo_sync_qbo_inventory_start_date']))?$MSQS_QL->now('Y-m-d'):$admin_settings_data['mw_wc_qbo_sync_qbo_inventory_start_date']; ?>">
												</div>
											</div>
										</td>
										<td>
											<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
											  <span class="tooltiptext"><?php echo __('Add Inventory Sync StartDate for QuickBooks Online. Default is today.','mw_wc_qbo_sync') ?></span>
											</div>
										</td>
									</tr>
									
									<tr>
										<th class="title-description">
											<?php echo __('WooCommerce description field to use when syncing products','mw_wc_qbo_sync') ?>
											
										</th>
										<td>
											<div class="row">
												<div class="input-field col s12 m12 l12">
													<p>
														<select name="mw_wc_qbo_sync_product_pull_desc_field" id="mw_wc_qbo_sync_product_pull_desc_field" class="filled-in production-option mw_wc_qbo_sync_select">
														<option value=""></option>
														<?php $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_product_pull_desc_field'],$MSQS_QL->product_pull_desc_fields)?>
														</select>
													</p>
												</div>
											</div>
										</td>
										<td>
											<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
											  <span class="tooltiptext"><?php echo __('Choose the description field in WooCommerce to use when syncing products between WooCommerce and QuickBooks - for the QuickBooks description field.','mw_wc_qbo_sync') ?></span>
											</div>
										</td>
									</tr>
									
									<tr>
										<th class="title-description">
									    	<?php _e('Push WooCommerce product title as QuickBooks Online product description?','mw_wc_qbo_sync') ?>
									    </th>
										<td>
											<div class="row">
												<div class="input-field col s12 m12 l12">
													<p>													
														<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_wc_qbo_product_desc" id="mw_wc_qbo_sync_wc_qbo_product_desc" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_wc_qbo_product_desc']=='true') echo 'checked' ?>>
													</p>
												</div>
											</div>
										</td>
										<td>
											<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
											  <span class="tooltiptext"><?php echo __('Enable to have Product Description in QuickBooks Online be WooCommerce Product Title','mw_wc_qbo_sync') ?></span>
											</div>
										</td>
									</tr>
									
									<tr>
										<th class="title-description">
											<?php echo __('Show only mapped products with different inventory levels </br>in Push > Inventory Levels.','mw_wc_qbo_sync') ?>
											
										</th>
										<td>
											<div class="row">
												<div class="input-field col s12 m12 l12">
													<p>
														<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_os_mapped_not_matched_invt_lvl" id="mw_wc_qbo_sync_os_mapped_not_matched_invt_lvl" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_os_mapped_not_matched_invt_lvl']=='true') echo 'checked' ?>>
													</p>
												</div>
											</div>
										</td>
										<td>
											<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
											  <span class="tooltiptext"><?php echo __('Check to only show products with inventory levels that don\'t match in Push > Inventory Levels.','mw_wc_qbo_sync') ?></span>
											</div>
										</td>
									</tr>
									
									<tr>
										<th class="title-description">
											<?php echo __('Hide variable parent products from Map/Push > Products/Inventory','mw_wc_qbo_sync') ?>
											
										</th>
										<td>
											<div class="row">
												<div class="input-field col s12 m12 l12">
													<p>
														<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_hide_vpp_fmp_pages" id="mw_wc_qbo_sync_hide_vpp_fmp_pages" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_hide_vpp_fmp_pages']=='true') echo 'checked' ?>>
													</p>
												</div>
											</div>
										</td>
										<td>
											<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
											  <span class="tooltiptext"><?php echo __('Check to hide variable parent products from Map/Push > Products/Inventory','mw_wc_qbo_sync') ?></span>
											</div>
										</td>
									</tr>
									
								</tbody>
							</table>
							</div>
							</div>
							
							<div id="mw_qbo_sybc_settings_tab_three_body" style="display: none;">
							<h6><?php echo __('Mapping Settings','mw_wc_qbo_sync') ?></h6>
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								
            				</tbody>
							</table>
							</div>
							
							<div id="mw_qbo_sybc_settings_tab_four_body" style="display: none;">
							<h6><?php echo __('Tax Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
                				<tr <?php if($MSQS_QL->get_qbo_company_setting('is_automated_sales_tax')){echo 'style="display:none;"';}?>>
									<th class="title-description">
								    	<?php echo __('QuickBooks Tax 0% Rule','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<select name="mw_wc_qbo_sync_tax_rule" id="mw_wc_qbo_sync_tax_rule" class=" mw_wc_qbo_sync_select">
									            <option value=""></option>
												<?php echo $MSQS_QL->get_tax_code_dropdown_list($admin_settings_data['mw_wc_qbo_sync_tax_rule']);?>
									            </select>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose your QBO Tax rule with 0% tax for non-taxable items.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('QuickBooks Tax/Price Format','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_tax_format" id="mw_wc_qbo_sync_tax_format" class="filled-in production-option mw_wc_qbo_sync_select">
										            <option value=""></option>
													<?php $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_tax_format'],$MSQS_QL->tax_format)?>
										            </select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose whether your tax setup is Inclusive - prices already include the tax, or Exclusive - taxes are additionally added on.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php if(!$MSQS_QL->get_qbo_company_setting('is_automated_sales_tax')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Sync WooCommerce Order Tax as a Line Item','mw_wc_qbo_sync') ?>
										</br><span style="font-size:10px;color:grey;">If enabled, this will override/invalidate any tax mappings set in MyWorks Sync > Map > Taxes, </br>and sync order tax as a line item instead of assigning it to a rate in QuickBooks.</span> 
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_odr_tax_as_li" id="mw_wc_qbo_sync_odr_tax_as_li" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_odr_tax_as_li']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If enabled, this will override/invalidate any tax mappings set in MyWorks Sync > Map > Taxes, and sync order tax as a line item instead of assigning it to a rate in QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>								
								
								<tr id="otli_qp_tr" <?php if($admin_settings_data['mw_wc_qbo_sync_odr_tax_as_li']!='true'){echo 'style="display:none;"';}?>>
									<th class="title-description">
								    	<?php echo __('QuickBooks Product for Sales Tax line item','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<?php
														$dd_options = '<option value=""></option>';
														$dd_ext_class = '';
														if($MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
															$dd_ext_class = 'mwqs_dynamic_select';
															if((int) $admin_settings_data['mw_wc_qbo_sync_otli_qbo_product']){
																$itemid = (int) $admin_settings_data['mw_wc_qbo_sync_otli_qbo_product'];
																$qb_item_name = $MSQS_QL->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_qbo_items','name','itemid',$itemid);
																if($qb_item_name!=''){
																	$dd_options = '<option value="'.$itemid.'">'.$qb_item_name.'</option>';
																}
															}
														}else{
															$dd_options.=$mw_qbo_product_list;
														}
													?>
													<select name="mw_wc_qbo_sync_otli_qbo_product" id="mw_wc_qbo_sync_otli_qbo_product" class="filled-in production-option mw_wc_qbo_sync_select <?php echo $dd_ext_class;?>">
														<?php echo $dd_options;?>
													</select>
												</p>
											</div>
										</div>
									</td>
                                    <td>
                                        <div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
									  <span class="tooltiptext"><?php echo __('Choose a QuickBooks Product that will be the line item in the QuickBooks Invoice/Sales Receipt to represent the sales tax from the WooCommerce Order.','mw_wc_qbo_sync') ?></span>
									</div>
                                    </td>
								</tr>								
								<?php endif;?>
            				</tbody>
							</table>
							</div>
							</div>

							<div id="mw_qbo_sybc_settings_tab_five_body" style="display: none;">
							<h6><?php echo __('Mapping Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								
								<tr style="display:none;"> <!---->
									<th class="title-description">
								    	<?php _e('Recognize other Wordpress roles as a customer','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>	
													<?php
													$role_dd_options = '';
													$mw_wc_qbo_sync_wc_cust_role = $admin_settings_data['mw_wc_qbo_sync_wc_cust_role'];
													$mw_wc_qbo_sync_wc_cust_role_exp = explode(',',$mw_wc_qbo_sync_wc_cust_role);
													
													if(is_array($wu_roles) && count($wu_roles)){
														foreach ($wu_roles as $role_name => $role_info):
															if( $role_name != 'customer' ){
																$selected = '';
																if($mw_wc_qbo_sync_wc_cust_role != ''){
																	if( in_array( $role_name, $mw_wc_qbo_sync_wc_cust_role_exp ) ){
																		$selected = 'selected="selected"';
																	}else{
																		$selected = '';
																	}
																}
																$role_dd_options .= '<option value="'.$role_name.'" '.$selected.'>'.$role_name.'</option>';
															}
														endforeach;
													}
													
    												?>
													<select name="mw_wc_qbo_sync_wc_cust_role[]" id="mw_wc_qbo_sync_wc_cust_role" class="filled-in production-option mw_wc_qbo_sync_select mqs_multi" multiple="multiple">
														<?php echo $role_dd_options;?>
													</select>												
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Enable to map other custom customer roles with QuickBooks Online rather than only default "CUSTOMER". Please note that default customer will always be mapped.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>								
								
                				<tr>
									<th class="title-description">
								    	<?php echo __('Append User ID for duplicate customers','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_append_client" id="mw_wc_qbo_sync_append_client" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_append_client']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Append the WooCommerce Client ID to the QuickBooks Online Display Name if the Client already exists in QuickBooks Online. Prevents errors from occuring when a duplicate client is being synced.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('QuickBooks Display Name format for new customers','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<textarea name="mw_wc_qbo_sync_display_name_pattern" placeholder="Default: {firstname} {lastname}" id="mw_wc_qbo_sync_display_name_pattern"><?php if(isset($admin_settings_data['mw_wc_qbo_sync_display_name_pattern'])) echo $admin_settings_data['mw_wc_qbo_sync_display_name_pattern']; else '{firstname} {lastname} - {id}'; ?></textarea>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Leave blank by default, to sync customers as {firstname} {lastname).</br></br>Choose the WooCommerce client name values you would like to be assigned to the QBO "Display Name As" client field. This setting will determine the value in the QuickBooks Online Display Name for clients synced over. Choose either first/last name OR Company name - not both.<br><b>Available Tags: {firstname} , {lastname} , {companyname} , {id} ,{email}</b>','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Use Display Name (if no email match found) to determine a matching customer','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_customer_match_by_name" id="mw_wc_qbo_sync_customer_match_by_name" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_customer_match_by_name']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Use the customer Display Name (if no email match found) when checking QuickBooks to find/match an unmapped customer - before syncing in a new customer record.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Sort order in Map > Customers for QuickBooks Customer dropdown','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_client_sort_order" id="mw_wc_qbo_sync_client_sort_order" class="filled-in production-option mw_wc_qbo_sync_select">
										            <option value=""></option>
													<?php $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_client_sort_order'],$MSQS_QL->client_dropdown_sort_order)?>
										            </select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose the sort order for QuickBooks Online clients names. It will be applied in the QuickBooks Online client dropdown in the client mapping page.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Override customer mappings using Shipping Company','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_customer_qbo_check_ship_addr" id="mw_wc_qbo_sync_customer_qbo_check_ship_addr" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_customer_qbo_check_ship_addr']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Only turn on if fully understood. This setting will override the default mapping by email address and instead use the Shipping Company in the order to check if that company exists in QB, when syncing an order over into QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Override customer mappings using Billing Company','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_customer_qbo_check_billing_company" id="mw_wc_qbo_sync_customer_qbo_check_billing_company" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_customer_qbo_check_billing_company']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Only turn on if fully understood. This setting will override the default mapping by email address and instead use the Billing Company in the order to check if that company exists in QB, when syncing an order over into QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Override customer mappings using Billing First + Last Name','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_customer_qbo_check_billing_f_l_name" id="mw_wc_qbo_sync_customer_qbo_check_billing_f_l_name" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_customer_qbo_check_billing_f_l_name']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Only turn on if fully understood. This setting will override the default mapping, and checking matches by email address, and instead use the Billing First + Last Name in the order to check if that name exists in QuickBooks when syncing an order. If that name does not exist, a new customer will be created.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('Check Mapped Customer Directly From QuickBooks Online','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_customer_qbo_check" id="mw_wc_qbo_sync_customer_qbo_check" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_customer_qbo_check']=='true') echo 'checked' ?>>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check customer on QuickBooks by email if no record in local server.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr>
									<th class="title-description">
								    	<?php _e('Sync all WooCommerce orders to one QuickBooks Online Customer','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_orders_to_specific_cust_opt" id="mw_wc_qbo_sync_orders_to_specific_cust_opt" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_orders_to_specific_cust_opt']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check if you want to select a specific customer in QuickBooks to map/sync all orders in to.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr <?php if($admin_settings_data['mw_wc_qbo_sync_orders_to_specific_cust_opt']!='true') echo 'style="display: none;"' ?> id="mw_wc_qbo_sync_orders_to_specific_cust_opt_res1">
									<th class="title-description">
								    	<?php _e('QuickBooks Customer','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<?php
														$dd_options = '<option value=""></option>';
														$dd_ext_class = '';
														if($MSQS_QL->option_checked('mw_wc_qbo_sync_select2_ajax')){
															$dd_ext_class = 'mwqs_dynamic_select';
															if((int) $admin_settings_data['mw_wc_qbo_sync_orders_to_specific_cust']){
																$itemid = (int) $admin_settings_data['mw_wc_qbo_sync_orders_to_specific_cust'];
																$qb_item_name = $MSQS_QL->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_qbo_customers','dname','qbo_customerid',$itemid);
																if($qb_item_name!=''){
																	$dd_options = '<option value="'.$itemid.'">'.$qb_item_name.'</option>';
																}
															}
														}else{
															$dd_options.=$qbo_customer_options;
														}
													?>
													
													<select name="mw_wc_qbo_sync_orders_to_specific_cust" id="mw_wc_qbo_sync_orders_to_specific_cust" class="filled-in production-option mw_wc_qbo_sync_select <?php echo $dd_ext_class;?>">
														<?php echo $dd_options;?>
													</select>
													
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select a specific customer in QuickBooks to map and sync all orders in to.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								
								
								<tr <?php if($admin_settings_data['mw_wc_qbo_sync_orders_to_specific_cust_opt']!='true') echo 'style="display: none;"' ?> id="mw_wc_qbo_sync_orders_to_specific_cust_opt_res2">
									<th class="title-description">
								    	<?php _e('Ignore these roles / Sync to individual mapped customer','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>	
													<?php
													$role_dd_options = '';
													$mw_wc_qbo_sync_wc_cust_role_sync_as_cus = $admin_settings_data['mw_wc_qbo_sync_wc_cust_role_sync_as_cus'];
													$mw_wc_qbo_sync_wc_cust_role_exp = explode(',',$mw_wc_qbo_sync_wc_cust_role_sync_as_cus);
													
													if(is_array($wu_roles) && count($wu_roles)){
														foreach ($wu_roles as $role_name => $role_info):
															$selected = '';
															if($mw_wc_qbo_sync_wc_cust_role_sync_as_cus != ''){
																if( in_array( $role_name, $mw_wc_qbo_sync_wc_cust_role_exp ) ){
																	$selected = 'selected="selected"';							
																}else{
																	$selected = '';
																}
															}
															$role_dd_options .= '<option value="'.$role_name.'" '.$selected.'>'.$role_name.'</option>';
														endforeach;
													}
													
    												?>
													<select name="mw_wc_qbo_sync_wc_cust_role_sync_as_cus[]" id="mw_wc_qbo_sync_wc_cust_role_sync_as_cus" class="filled-in production-option mw_wc_qbo_sync_select mqs_multi" multiple="multiple">
														<?php echo $role_dd_options;?>
													</select>												
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('The user roles selected here will be ignored by the above setting to sync all orders to one QB customer. Orders for customers in the roles selected here will be synced to their own individual QuickBooks customer accounts.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('Use Email For Client Check','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_client_check_email" id="mw_wc_qbo_sync_client_check_email" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_client_check_email']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to use email along with other fields for check if client exists or for automap client.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>


            				</tbody>
							</table>
							</div>
							</div>

							<div id="mw_qbo_sybc_settings_tab_six_body" style="display: none;">
							<h6><?php echo __('Pull Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								<tr>
									<th class="title-description">
								    	<?php echo __('Show Pull section under MyWorks Sync sidebar','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_pull_enable" id="mw_wc_qbo_sync_pull_enable" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_pull_enable']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable the Customer, Order, Product & Payment Pull pages. This will enable you to use the manual pull pages to manually pull data into WooCommerce from QuickBooks Online.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php if($MSQS_QL->check_if_real_time_pull_enable_for_item('Product')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Product status once pulled into WooCommerce','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_product_pull_wc_status" id="mw_wc_qbo_sync_product_pull_wc_status" class="filled-in production-option mw_wc_qbo_sync_select">
										            <option value=""></option>
													<?php $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_product_pull_wc_status'],$MSQS_QL->product_pull_status)?>
										            </select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose the product status that products inherit when they are first pulled in WooCommerce.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<?php if($MSQS_QL->check_if_real_time_pull_enable_for_item('Payment')):?>
								<tr <?php if($MSQS_QL->option_checked('mw_wc_qbo_sync_order_as_sales_receipt')){echo 'style="display:none;"';}?>>
														
								
								<tr <?php if($MSQS_QL->option_checked('mw_wc_qbo_sync_order_as_sales_receipt')){echo 'style="display:none;"';}?>>
									<th class="title-description">
								    	<?php _e('Update order to this status when payment is added in QuickBooks','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<select name="mw_wc_qbo_sync_pmnt_pull_order_status" id="mw_wc_qbo_sync_pmnt_pull_order_status" class="filled-in production-option mw_wc_qbo_sync_select">
														<option value=""></option>
														<?php echo  $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_pmnt_pull_order_status'],$order_statuses);?>
													</select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Change the WooCommerce Order Status for orders in these statuses, when a payment is applied to the related invoice in QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
									<th class="title-description">
								    	<?php _e('Don\'t update orders in these statuses','mw_wc_qbo_sync') ?>
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>													
													<select name="mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses[]" id="mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses" class="filled-in production-option mw_wc_qbo_sync_select mqs_multi" multiple="multiple">
														<?php 
															$mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses = $admin_settings_data['mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses'];
															if($mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses!=''){
																$mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses = explode(',',$mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses);
															}
														?>
														<?php echo  $MSQS_QL->only_option($mw_wc_qbo_sync_pmnt_pull_prevent_order_statuses,$order_statuses);?>
													</select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Prevent pulling payments (changing WooCommerce order status) for orders in these statuses','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								
								<?php endif;?>
								
								<?php if($MSQS_QL->check_if_real_time_pull_enable_for_item('Inventory')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Update Stock Status field when inventory is synced into WooCommerce','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_invnt_pull_set_prd_stock_sts" id="mw_wc_qbo_sync_invnt_pull_set_prd_stock_sts" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_invnt_pull_set_prd_stock_sts']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If enabled, our sync will adjust Product Stock Status to \'Out of Stock\' if a 0-level inventory is synced into WooCommerce - and \'In Stock\', if product inventory is updated from 0 to a real number.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<?php /*
								<tr>
									<th class="title-description">
								    	<?php echo __('Client Auto-Pull','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_auto_pull_client" id="mw_wc_qbo_sync_auto_pull_client" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_auto_pull_client']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable automatically pulling in new QuickBooks Online clients in the auto-pull cron job.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Invoice Auto-Pull','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_auto_pull_invoice" id="mw_wc_qbo_sync_auto_pull_invoice" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_auto_pull_invoice']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable automatically pulling in new QuickBooks Online invoices in the auto-pull cron job.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Payment Auto-Pull','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_auto_pull_payment" id="mw_wc_qbo_sync_auto_pull_payment" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_auto_pull_payment']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable automatically pulling in new QuickBooks Online invoice payments in the auto-pull cron job.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Limit AutoPull Data to Existing WooCommerce Clients','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_auto_pull_limit" id="mw_wc_qbo_sync_auto_pull_limit" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_auto_pull_limit']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check this box to only AutoPull in new data from QuickBooks Online for clients that already exist in WooCommerce. For this setting to correctly work, the "Client Auto-Pull" setting above must be OFF.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('AutoPull Interval','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<input type="text" name="mw_wc_qbo_sync_auto_pull_interval" id="mw_wc_qbo_sync_auto_pull_interval" value="<?php echo $admin_settings_data['mw_wc_qbo_sync_auto_pull_interval'] ?>">
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Please enter auto pull interval in minutes','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								*/ ?>
            				</tbody>
							</table>
							</div>
							</div>

							<div id="mw_qbo_sybc_settings_tab_wh_body" style="display: none;">

							<?php if($MSQS_QL->option_checked('mw_wc_qbo_sync_pause_up_qbo_conection')):
								$syncopt1 = '';
								$syncopt2 = 'checked="checked"';
								$trstatus = 'style="display:block;"';
							else:
								$syncopt1 = 'checked="checked"';
								$syncopt2 = '';
								$trstatus = 'style="display:none;"';
							?>
							<?php endif;?>

                            <div class="tab_wrap">
							<div class="row">
								<div class="input-field col s12 m12 l12">												
									<div class="switch-toggle switch-3 switch-candy">
										<input id="mw_wc_qbo_sync_rt_sync_check" class="mw_sync_toggle" value="false" name="mw_wc_qbo_sync_pause_up_qbo_conection" type="radio" <?php echo $syncopt1 ?>>
										<label for="mw_wc_qbo_sync_rt_sync_check" onclick="">RealTime Sync</label>
										
										<input id="mw_wc_qbo_sync_que_sync_check" class="mw_sync_toggle" value="true" name="mw_wc_qbo_sync_pause_up_qbo_conection" type="radio" <?php echo $syncopt2 ?>>
										<label for="mw_wc_qbo_sync_que_sync_check" onclick="">Queue Sync</label>
										<a></a>
									</div>
								</div>
							</div>	
                            </div>
							<h6><?php echo __('Automatic Sync Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								
								<?php
									$cit_arr = array();
									$cit_arr['MWQBO_10min'] = '10 min';
									$cit_arr['MWQBO_30min'] = '30 min';
									$cit_arr['MWQBO_60min'] = '60 min';
								?>
								<tr id="mw_sync_toggle_res" <?php echo $trstatus ?>>
									<th class="title-description">
								    	<?php echo __('Queue Sync Interval','mw_wc_qbo_sync') ?>
										
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<select name="mw_wc_qbo_sync_queue_cron_interval_time" id="mw_wc_qbo_sync_queue_cron_interval_time" class=" mw_wc_qbo_sync_select">
									            <?php $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_queue_cron_interval_time'],$cit_arr)?>
									            </select>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose a time interval for the syncing activity in the Queue to be processed and sent to QuickBooks Online.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>								
								
								
								<tr>
									<th class="title-description">
								    	<?php echo __('<b>WooCommerce > QuickBooks Online</b>','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_rt_push_enable" id="mw_wc_qbo_sync_rt_push_enable" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_rt_push_enable']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable automatic sync for WooCommerce > QuickBooks Online.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Data Types','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<?php if(is_array($MSQS_QL->qbo_rt_push_items()) && count($MSQS_QL->qbo_rt_push_items())):?>
													<?php $rpi_val_arr = explode(',',$admin_settings_data['mw_wc_qbo_sync_rt_push_items']);?>
													<?php foreach($MSQS_QL->qbo_rt_push_items() as $rpi_key => $rpi_val):?>
													<?php
														$rpi_checked = '';
														if(is_array($rpi_val_arr) && in_array($rpi_key,$rpi_val_arr)){
															$rpi_checked = ' checked="checked"';
														}
													?>
													
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_rt_push_items[]" id="mw_wc_qbo_sync_rt_push_items" value="<?php echo $rpi_key;?>" <?php echo $rpi_checked;?>>
													&nbsp;<span class="rt_item_hd"><?php echo $rpi_val;?></span>
													<br /><br />
													<?php endforeach;?>
													<?php endif;?>										            
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext" style="top: -300;left: -410px;width: 400px;text-align: left;"><?php echo __('
											  <b>Customer</b></br>
											  Add/update QuickBooks customers when WooCommerce customers are added/updated.
											 </br></br><b>Order</b></br>
										  	Add/update QuickBooks invoices/sales receipts when WooCommerce orders are placed/updated.
										 	</br></br><b>Product</b></br>
										  	Add/update QuickBooks products when WooCommerce products are added/updated. This covers product title, description and price. Settings to control this are in Settings > Pull above.
										 	</br></br><b>Variation</b></br>
											Add/update QuickBooks products when WooCommerce variations are added/updated. This covers variation title, description and price. Settings to control this are in Settings > Pull above.
										 	</br></br><b>Inventory</b></br>
											Update QuickBooks inventory when WooCommerce product/variation inventory levels are updated.
										 	</br></br><b>Category</b></br>
											Add QuickBooks categories when WooCommerce categories are added.
										 	</br></br><b>Payment</b></br>
											Sync payments over to QuickBooks when they are made in WooCommerce.
										 	</br></br><b>Refund</b></br>
											Sync full refunds over to QuickBooks when they are made in WooCommerce
											  ','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>								
								
								<tr>
									<th class="title-description">
								    	<?php echo __('<b>QuickBooks Online -> WooCommerce</b>','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_webhook_enable" id="mw_wc_qbo_sync_webhook_enable" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_webhook_enable']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to enable automatic syncing from QuickBooks Online into WooCommerce.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Data Types','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<?php if(is_array($MSQS_QL->qbo_webhook_items()) && count($MSQS_QL->qbo_webhook_items())):?>
													<?php $qwi_val_arr = explode(',',$admin_settings_data['mw_wc_qbo_sync_webhook_items']);?>
													<?php foreach($MSQS_QL->qbo_webhook_items() as $qwi_key => $qwi_val):?>
													<?php
														//
														if($qwi_key == 'Payment' && $MSQS_QL->option_checked('mw_wc_qbo_sync_order_as_sales_receipt')){
															continue;
														}
														$qwi_checked = '';
														if(is_array($qwi_val_arr) && in_array($qwi_key,$qwi_val_arr)){
															$qwi_checked = ' checked="checked"';
														}
													?>
													
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_webhook_items[]" id="mw_wc_qbo_sync_webhook_items" value="<?php echo $qwi_key;?>" <?php echo $qwi_checked;?>>
													&nbsp;<span class="rt_item_hd"><?php echo $qwi_val;?></span>
													<br /><br />
													<?php endforeach;?>
													<?php endif;?>
													
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext" style="top: -123px;left: -410px;width: 400px;text-align: left;"><?php echo __('
											  <b>Product</b></br>
											  Add/update WooCommerce products when QuickBooks products are added/updated. This covers product title, description and price. Settings to control this are in Settings > Pull above.
											 </br></br><b>Inventory</b></br>
										  	Update WooCommerce inventory when QuickBooks inventory levels are updated.
										 	</br></br><b>Category</b></br>
										  	Add WooCommerce categories when QuickBooks categories are added.
										 	</br></br><b>Payment</b></br>
											Change a WooCommerce order status when payment is applied to the related invoice in QuickBooks. Settings to control this are in Settings > Pull above.
											  ','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>								
								
            				</tbody>
							</table>
							</div>
							</div>

							<div id="mw_qbo_sybc_settings_tab_seven_body" style="display: none;">
							<h6><?php echo __('Disable Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('Disable Real-Time Push (Queue)','mw_wc_qbo_sync') ?>
								    	 
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_disable_realtime_sync" id="mw_wc_qbo_sync_disable_realtime_sync" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_disable_realtime_sync']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><font color="red">OFF: Default</font> <?php echo __('Check to disable real time data syncing. This will speed up WooCommerce operations slightly and sync data using a cron job you need to set up on the Cron Setup page.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Disable Sync Status Icons','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_disable_sync_status" id="mw_wc_qbo_sync_disable_sync_status" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_disable_sync_status']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to disable Sync Statuses in Push Pages (invoice and payment). This will speed up the loading of these pages.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Block Real Time Client Update','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_disable_realtime_client_update" id="mw_wc_qbo_sync_disable_realtime_client_update" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_disable_realtime_client_update']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to block the update of client profile information in QuickBooks Online when it is updated in WooCommerce.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
            				</tbody>
							</table>
							</div>
							</div>

							<div id="mw_qbo_sybc_settings_tab_eight_body" style="display: none;">
							<h6><?php echo __('Advanced Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								<tr>
									<th class="title-description">
								    	<?php echo __('Enable Invoice Prefix','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_enable_invoice_prefix" id="mw_wc_qbo_sync_enable_invoice_prefix" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_enable_invoice_prefix']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wclp_qbo_sync') ?>
										  <span class="tooltiptext"><font color="red">OFF: Default</font> <?php echo __('Check to enable support for invoice prefixes. Only check this box if your WooCommerce invoices have custom prefixes.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Use QBO Invoice #s','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_qbo_invoice" id="mw_wc_qbo_sync_qbo_invoice" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_qbo_invoice']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><font color="red">OFF: Default</font> <?php echo __('Check to create WooCommerce Invoices with the next Invoice Number from QBO. Only check this box if you do not wish to use the WooCommerce numbering system.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
            				</tbody>
							</table>
							</div>
							</div>

							<div id="mw_qbo_sybc_settings_tab_nine_body" style="display: none;">
							<h6><?php echo __('Miscellaneous Settings','mw_wc_qbo_sync') ?></h6>
							<div class="myworks-wc-qbo-sync-table-responsive myworks-setting">
							<table class="mw-qbo-sync-settings-table mwqs_setting_tab_body">
							<tbody>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('WooCommerce Admin User','mw_wc_qbo_sync') ?>				    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<select name="mw_wc_qbo_sync_admin_email" id="mw_wc_qbo_sync_admin_email">
													<option value=""></option>
													<?php $MSQS_QL->get_admin_user_dropdown_list($admin_settings_data['mw_wc_qbo_sync_admin_email']); ?>
													</select>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Select admin email for daily report of syncing activity. This report is emailed when the module cron is run.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Email Log Daily','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_email_log" id="mw_wc_qbo_sync_email_log" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_email_log']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to email a daily report of syncing activity to the admin selected above. This report is emailed when the module cron is run.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr style="display: none;">
									<th class="title-description">
								    	<?php echo __('Auto Quick Refresh Daily','mw_wc_qbo_sync') ?>				    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_auto_refresh" id="mw_wc_qbo_sync_auto_refresh" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_auto_refresh']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to automatic quick refresh data on regular basis. This is done when the module cron is run.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
                				<tr>
									<th class="title-description">
								    	<?php echo __('Save Logs for Days','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<select name="mw_wc_qbo_sync_save_log_for" id="mw_wc_qbo_sync_save_log_for" class=" mw_wc_qbo_sync_select">
									            <?php $MSQS_QL->only_option($admin_settings_data['mw_wc_qbo_sync_save_log_for'],$MSQS_QL->log_save_days)?>
									            </select>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Choose how many days log entry you want to save','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php if($MSQS_QL->option_checked('mw_wc_qbo_sync_pause_up_qbo_conection')):?>
								<tr>
									<th class="title-description">
								    	<?php echo __('Catch recent unsynced orders','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_ca_ruso_dqs" id="mw_wc_qbo_sync_ca_ruso_dqs" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_ca_ruso_dqs']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Only enable if random new WooCommerce orders are not syncing to QuickBooks automatically. This is ususally a sign of a WooCommerce gateway/checkout not calling the correct hooks to trigger our sync - and this override will automatically catch these orders and sync to QuickBooks.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<tr height="50">
									<td colspan="3">
										<b><?php echo __('Plugin Debug Settings','mw_wc_qbo_sync') ?></b>										
									</td>									
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('QuickBooks Error Add/Update Item Object, Request/Response Into Log File ','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_err_add_item_obj_into_log_file" id="mw_wc_qbo_sync_err_add_item_obj_into_log_file" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_err_add_item_obj_into_log_file']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Only for debug, Add QuickBooks Item Object into log file (last 24 hours).','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('QuickBooks Success Add/Update Item Object, Request/Response Into Log File ','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_success_add_item_obj_into_log_file" id="mw_wc_qbo_sync_success_add_item_obj_into_log_file" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_success_add_item_obj_into_log_file']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Only for debug, Add QuickBooks Item Object into log file (last 24 hours).','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>

								<tr>
									<th class="title-description">
								    	<?php echo __('Enable Database Debug/Fix tool','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_db_fix" id="mw_wc_qbo_sync_db_fix" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_db_fix']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Turn it on if you are getting any issues with database','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('Enable QBO Connection Improvement (per session)','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_session_cn_ls_chk" id="mw_wc_qbo_sync_session_cn_ls_chk" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_session_cn_ls_chk']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('It will reduce the load time, please turn of this if you face any issues regarding QBO connection.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<tr height="50" style="display:none;">
									<td colspan="3">
										<b><?php echo __('Update preference','mw_wc_qbo_sync') ?></b>										
									</td>									
								</tr>

								<tr style="display:none;">
									<th class="title-description">
								    	<?php echo __('Accept Beta Update?','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_update_option" id="mw_wc_qbo_sync_update_option" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_update_option']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Check to get beta version updates too. If not, it will only update stable versions.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								
								<?php if(!$MSQS_QL->is_plg_lc_p_l()):?>
								<tr height="50">
									<td colspan="3">
										<b><?php echo __('Customer Account Area','mw_wc_qbo_sync') ?></b>										
									</td>									
								</tr>
								
								<tr>
									<th class="title-description">
								    	<?php echo __('Enable Invoices tab in the WooCommerce Account menu','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_wam_mng_inv_ed" id="mw_wc_qbo_sync_wam_mng_inv_ed" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_wam_mng_inv_ed']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('If enabled, an Invoices tab will be present in the front-end WooCommerce Account menu - where the customer can view/pay a list of invoices present in their QuickBooks Online customer account - based on their customer mapping in MyWorks Sync > Map > Customers.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<?php endif;?>
								
								<tr height="50">
									<td colspan="3">
										<b><?php echo __('Plugin Dropdown Settings','mw_wc_qbo_sync') ?></b>										
									</td>									
								</tr>

								<tr>
									<th class="title-description">
								    	<?php echo __('Enable Select2 searchable dropdown style','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_select2_status" id="mw_wc_qbo_sync_select2_status" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_select2_status']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('This setting is on by default - to enable the Select2 dropdown style. Turn this off to display a normal dropdown for the plugin.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
								<tr>
									<th class="title-description">
								    	<?php echo __('Enable Optimized AJAX-Search-Only for Select2 Dropdowns (customer and product)','mw_wc_qbo_sync') ?>
								    	
								    </th>
									<td>
										<div class="row">
											<div class="input-field col s12 m12 l12">
												<p>
													<input type="checkbox" class="filled-in mwqs_st_chk  production-option" name="mw_wc_qbo_sync_select2_ajax" id="mw_wc_qbo_sync_select2_ajax" value="true" <?php if($admin_settings_data['mw_wc_qbo_sync_select2_ajax']=='true') echo 'checked' ?>>
												</p>
											</div>
										</div>
									</td>
									<td>
										<div class="material-icons tooltipped right tooltip"><?php echo __('?','mw_wc_qbo_sync') ?>
										  <span class="tooltiptext"><?php echo __('Enable Optimized AJAX search only for Select2 dropdown styles. This option is applicable if Select2 is enabled on above setting. This is efficient if your install has huge customer and product data lists and will help avoid page load lags.','mw_wc_qbo_sync') ?></span>
										</div>
									</td>
								</tr>
            				</tbody>
							</table>
							</div>							
							
							<!--<>-->
							<br/>
							<!--<div class="mw_wc_qbo_sync_clear"></div>-->
							<div class="ms_vnu_cont_op row" style="display:none;">
								<button title="Re-Generate all incorrect variation names from parent product name and attribute values." class="waves-effect waves-light btn mw-qbo-sync-green" id="wp_avnu_btn_op" disabled>
									Adjust All Incorrect Variation Names
								</button>
								&nbsp;
								<span style="padding: 0px 20px;" id="wp_avnu_msg_op"></span>
								<?php //wp_nonce_field( 'myworks_wc_qbo_sync_rg_all_inc_variation_names', 'rg_all_inc_variation_names' ); ?>
							</div>							
							
							</div>
							
							
							<div class="mw_wc_qbo_sync_clear"></div>
							
							<div class="row">
								<div class="input-field col s12 m6 l4">
									<input type="submit" name="mw_wc_qbo_sync_settings" id="mw_wc_qbo_sync_settings" class="waves-effect waves-light btn save-btn mw-qbo-sync-green" value="Save All">
								</div>
							</div>
							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</form>
</div>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<?php MyWorks_WC_QBO_Sync_Admin::set_setting_alert($save_status) ?>

<script type="text/javascript">
jQuery(document).ready(function($){
	//
	$("input:radio[name=mw_wc_qbo_sync_order_qbo_sync_as]").click(function(){
		if($(this).attr('id') == 'wo_qsa_vpr'){
			$('#wo_qsa_vpr_map_tr').fadeIn("slow");
		}else{
			$('#wo_qsa_vpr_map_tr').fadeOut("slow");
		}
		
		if($(this).attr('id') == 'wo_qsa_pg'){
			$('#mwoqsa_rm').html('<small style="font-size:100%;">Please select the order sync type per gateway in Map > Payment Method page.</small>');
			$('#mwoqsa_rm').fadeIn("slow");
		}else{
			$('#mwoqsa_rm').fadeOut("slow");
		}
	});
	
	<?php echo $list_selected;?>
	<?php if($selected_tab!=''):?>
	if(jQuery('#mw_qbo_sybc_settings_tab_<?php echo $selected_tab;?>').length >0) {
		jQuery('#mw_qbo_sybc_settings_tab_<?php echo $selected_tab;?>').trigger( "click" );
	}	
	<?php endif;?>
		
	jQuery('input.mwqs_st_chk').attr('data-size','small');
	jQuery('input.mwqs_st_chk').bootstrapSwitch();
	//jQuery('input.mw_sync_toggle').bootstrapSwitch();
    jQuery('#mw_wc_qbo_sync_orders_to_specific_cust_opt').on('switchChange.bootstrapSwitch', function (event, state) {
		if(jQuery("#mw_wc_qbo_sync_orders_to_specific_cust_opt").is(':checked')) {
          	jQuery('#mw_wc_qbo_sync_orders_to_specific_cust_opt_res1').fadeIn("slow");
			jQuery('#mw_wc_qbo_sync_orders_to_specific_cust_opt_res2').fadeIn("slow");
        } else {
          	jQuery('#mw_wc_qbo_sync_orders_to_specific_cust_opt_res1').fadeOut("slow");
			jQuery('#mw_wc_qbo_sync_orders_to_specific_cust_opt_res2').fadeOut("slow");
        }
    });
    jQuery('#mw_wc_qbo_sync_que_sync_check').on('click', function (event, state) {
		if(jQuery("#mw_wc_qbo_sync_que_sync_check").is(':checked')) {
          	jQuery('#mw_sync_toggle_res').fadeIn("slow");
        } else {
          	jQuery('#mw_sync_toggle_res').fadeOut("slow");
        }
    });
    jQuery('#mw_wc_qbo_sync_rt_sync_check').on('click', function (event, state) {
		if(jQuery("#mw_wc_qbo_sync_rt_sync_check").is(':checked')) {
          	jQuery('#mw_sync_toggle_res').fadeOut("slow");
        } else {
        	jQuery('#mw_sync_toggle_res').fadeIn("slow");
        }
    });
	
	<?php if(!$setting_removed):?>
    jQuery('#mw_wc_qbo_sync_invoice_notes').on('switchChange.bootstrapSwitch', function (event, state) {
		if(jQuery("#mw_wc_qbo_sync_invoice_notes").is(':checked')) {
          	jQuery('#mw_wc_qbo_sync_invoice_notes_res1').fadeIn("slow");
			jQuery('#mw_wc_qbo_sync_invoice_notes_res2').fadeIn("slow");
        } else {
          	jQuery('#mw_wc_qbo_sync_invoice_notes_res1').fadeOut("slow");
			jQuery('#mw_wc_qbo_sync_invoice_notes_res2').fadeOut("slow");
        }
    });	
	<?php endif;?>	
	
	jQuery('#mw_wc_qbo_sync_odr_tax_as_li').on('switchChange.bootstrapSwitch', function (event, state) {		
		if(jQuery("#mw_wc_qbo_sync_odr_tax_as_li").is(':checked')) {			
			jQuery('#otli_qp_tr').fadeIn("slow");			
        } else {			
			jQuery('#otli_qp_tr').fadeOut("slow");
        }
	});
	
	//
	$('#wp_avnu_btn_op').removeAttr('disabled');
	$('#wp_avnu_btn_op').click(function(e){
		e.preventDefault();
		if(confirm('<?php echo __('Are you sure, you re-generate all incorrect variation names?','mw_wc_qbo_sync')?>')){
			$('#wp_avnu_msg_op').html('Loading...');
			var data = {
				"action": 'mw_wc_qbo_sync_rg_all_inc_variation_names',
				"rg_all_inc_variation_names": jQuery('#rg_all_inc_variation_names').val(),
			};
			
			jQuery.ajax({
			   type: "POST",
			   url: ajaxurl,
			   data: data,
			   cache:  false ,
			   //datatype: "json",
			   success: function(result){
				   if(result!=0 && result!=''){					 
					 jQuery('#wp_avnu_msg_op').html(result);
				   }else{					
					jQuery('#wp_avnu_msg_op').html('Error!');
				   }				  
			   },
			   error: function(result) {					
					jQuery('#wp_avnu_msg_op').html('Error!');
			   }
			});
		}
	});
	
	/**/
	$('.dd_dqsafnp option').filter(function() {
		//!this.value || $.trim(this.value).length == 0 || $.trim(this.text).length == 0 || 
		return $.trim(this.text).indexOf('(Income)') === -1;
	}).remove();
	$(".dd_dqsafnp").prepend('<option value=""></option>');
	
	$('.dd_dqiaafnp option').filter(function() {
		return $.trim(this.text).indexOf('(Other Current Asset)') === -1;
	}).remove();
	$(".dd_dqiaafnp").prepend('<option value=""></option>');
	
	$('.dd_dqcogsafnp option').filter(function() {
		return $.trim(this.text).indexOf('(Cost of Goods Sold)') === -1;
	}).remove();
	$(".dd_dqcogsafnp").prepend('<option value=""></option>');
	
});

jQuery( function($) {
$('.mwqs_datepicker').css('cursor','pointer');
$( ".mwqs_datepicker" ).datepicker(
	{ 
	dateFormat: 'yy-mm-dd',
	yearRange: "-50:+0",
	changeMonth: true,
	changeYear: true
	}
);
} );
</script>
<?php echo $MWQS_OF->get_select2_js('select','qbo_product');?>