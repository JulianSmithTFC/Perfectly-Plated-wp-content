<?php
if ( ! defined( 'ABSPATH' ) )
exit;

/**
 * Update Product Into Quickbooks Online.
 *
 * @since    
 * Last Updated: 2019-01-25
*/

$include_this_function = true;

if($include_this_function){
	$manual = $this->get_array_isset($product_data,'manual',false);
	if($manual){
		$this->set_session_val('sync_window_push_manual_update',true);
	}

	if($this->is_connected()){
		global $wpdb;
		//$this->_p($product_data);			
		$wc_product_id = (int) $this->get_array_isset($product_data,'wc_product_id',0,true);
		if($wc_product_id && $this->if_sync_product($wc_product_id)){
			$is_variation = $this->get_array_isset($product_data,'is_variation',false,false);
			$sync_item = ($is_variation)?'Variation':'Product';

			$map_tbl = ($is_variation)?'mw_wc_qbo_sync_variation_pairs':'mw_wc_qbo_sync_product_pairs';
			$w_p_f = ($is_variation)?'wc_variation_id':'wc_product_id';

			$map_data = $this->get_row("SELECT `quickbook_product_id` FROM `".$wpdb->prefix."{$map_tbl}` WHERE `{$w_p_f}` = $wc_product_id AND `quickbook_product_id` > 0 ");
			$quickbook_product_id = 0;
			if(is_array($map_data) && count($map_data)){
				$quickbook_product_id = (int) $map_data['quickbook_product_id'];
			}
			if(!$quickbook_product_id){
				if($manual){
					$this->save_log('Update '.$sync_item.' Error #'.$wc_product_id,$sync_item.' not mapped.','Product',0);
				}
				return false;
			}

			$Context = $this->Context;
			$realm = $this->realm;

			$ItemService = new QuickBooks_IPP_Service_Item();
			$sql = "SELECT * FROM Item WHERE Type IN('Inventory','Service','NonInventory') AND Id = '$quickbook_product_id' ";
			$items = $ItemService->query($Context, $realm, $sql);

			if(!$items || empty($items)){
				if($manual){
					$this->save_log('Update '.$sync_item.' Error #'.$wc_product_id,'Invalid QuickBooks product. ','Product',0);
				}
				return false;
			}

			$item = $items[0];

			/*
			if($item->getType()=='Group'){
				$this->save_log('Update '.$sync_item.' Error #'.$wc_product_id,'Invalid QuickBooks product (Bundle). ','Product',0);
				return false;
			}
			*/

			$name_replace_chars = array(':');
			$name = $this->get_array_isset($product_data,'name','',true,100,false,$name_replace_chars);
			$sku = $this->get_array_isset($product_data,'_sku','',true);

			$_manage_stock = $this->get_array_isset($product_data,'_manage_stock','no',true);
			$_downloadable = $this->get_array_isset($product_data,'_downloadable','no',true);
			$_virtual = $this->get_array_isset($product_data,'_virtual','no',true);

			$_stock = $this->get_array_isset($product_data,'_stock',0,true);


			if($_manage_stock=='yes'){
				if($this->get_qbo_company_info('is_sku_enabled')){
					$type = 'Inventory';
				}else{
					$type = 'NonInventory';
				}

			}elseif($_virtual=='yes'){
				$type = 'Service';
			}else{
				$type = 'NonInventory';
			}

			$_sale_price = $this->get_array_isset($product_data,'_sale_price',0);
			$_min_variation_price = $this->get_array_isset($product_data,'_min_variation_price',0);

			$_max_variation_price = $this->get_array_isset($product_data,'_max_variation_price',0);

			$_price = $this->get_array_isset($product_data,'_price',0);

			$unitPrice = $_price;

			$_tax_class = $this->get_array_isset($product_data,'_tax_class','');

			$_tax_status = $this->get_array_isset($product_data,'_tax_status','');
			$taxable = ($_tax_status!='' && $_tax_status!='none')?true:false;

			$active = $this->get_array_isset($product_data,'active',true);

			//$qty = (int) $this->get_array_isset($product_data,'total_stock',0);
			$qty = $_stock;

			$item->setName($name);

			$mw_wc_qbo_sync_product_pull_desc_field = $this->get_option('mw_wc_qbo_sync_product_pull_desc_field');
			$desc = '';
			if($this->option_checked('mw_wc_qbo_sync_wc_qbo_product_desc')){
				$desc = $name;
			}else{
				if($mw_wc_qbo_sync_product_pull_desc_field=='short_description'){
					$desc = $this->get_array_isset($product_data,'short_description','',true,4000);
				}else{
					$desc = $this->get_array_isset($product_data,'description','',true,4000);
				}
			}
			
			if($this->is_plugin_active('woocommerce-cost-of-goods') && $this->option_checked('mw_wc_qbo_sync_wcogs_fiels')){
				$_wc_cog_cost = $this->get_array_isset($product_data,'_wc_cog_cost',0);
				$item->setPurchaseCost($_wc_cog_cost);
			}
			
			if($mw_wc_qbo_sync_product_pull_desc_field!='none' || $this->option_checked('mw_wc_qbo_sync_wc_qbo_product_desc')){
				$item->setDescription($desc);
			}
			

			$item->setSku($sku);

			//$unitPrice = number_format($unitPrice, 2);
			$unitPrice = str_replace(',','',$unitPrice);
			$unitPrice = floatval($unitPrice);

			$item->setUnitPrice($unitPrice);

			$item->setTaxable($taxable);
			$item->setActive($active);
			
			//
			if($this->is_wq_vendor_pm_enable()){
				$_supplier = (int) $this->get_array_isset($product_data,'_supplier','',true);
				if($_supplier>0){
					$sv_id = (int) get_post_meta($_supplier,'_default_settings_assigned_to',true);
					if($sv_id>0){
						$v_company = '';
						$qv_id = (int) $this->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_vendor_pairs','qbo_vendorid','wc_customerid',$sv_id);
						if($qv_id){
							$v_company = $this->get_field_by_val($wpdb->prefix.'mw_wc_qbo_sync_qbo_vendors','company','qbo_vendorid',$qv_id);
						}
						
						if(empty($v_company)){
							$v_company = get_user_meta($sv_id,'billing_company',true);
						}
						
						$item->setPurchaseDesc($v_company);
					}
				}
				
				$reorder_point = $this->get_array_isset($product_data,'reorder_point','',true);
				if($reorder_point){
					
				}
				
				$consignors_commission = $this->get_array_isset($product_data,'option_2_commission','',true);
				if($consignors_commission){
					//$item->setPurchaseDesc($consignors_commission);
					$item->setDescription($consignors_commission);
				}					
			}
			
			/*
			$qbo_product_account = (int) $this->get_option('mw_wc_qbo_sync_default_qbo_product_account');
			if(!$qbo_product_account){
				$this->save_log('Update '.$sync_item.' Error #'.$wc_product_id,'QuickBooks product account not selected.','Product',0);
				return false;
			}

			$item->setIncomeAccountRef($qbo_product_account);
			*/
			
			//
			if($manual){
				if($item->getType()=='Inventory'){
					$item->setQtyOnHand($qty);
				}
			}

			$qbo_item_type = $item->getType();
			/*
			$update_p_type = false;
			if($type=='Inventory' && $update_p_type){

				//$item->setQtyOnHand($qty);
				if($item->getType()!='Inventory'){
					$item->setType($type);
					$item->setTrackQtyOnHand(true);
					
					$qb_isd = $this->get_option('mw_wc_qbo_sync_qbo_inventory_start_date');
					if(empty($qb_isd)){
						$qb_isd = $this->now('Y-m-d');
					}
					
					$item->setInvStartDate($qb_isd);
				}

				$qbo_product_asset_account = (int) $this->get_option('mw_wc_qbo_sync_default_qbo_asset_account');
				if(!$qbo_product_asset_account){
					$this->save_log('Update '.$sync_item.' Error #'.$wc_product_id,'QuickBooks product inventory asset account not selected.','Product',0);
					return false;
				}
				$item->setAssetAccountRef($qbo_product_asset_account);

				$qbo_product_expense_account = (int) $this->get_option('mw_wc_qbo_sync_default_qbo_expense_account');
				if(!$qbo_product_expense_account){
					$this->save_log('Update '.$sync_item.' Error #'.$wc_product_id,'QuickBooks product expense account not selected.','Product',0);
					return false;
				}
				$item->setExpenseAccountRef($qbo_product_expense_account);

			}else{
				//$item->setType($type);
			}
			*/

			$log_title = "";
			$log_details = "";
			$log_status = 0;

			//$this->_p($product_data);
			//$this->_p($item);
			//die;
			//return false;
			
			if ($resp = $ItemService->update($Context, $realm, $item->getId(), $item)){
				$qbo_item_id = $this->qbo_clear_braces($item->getId());
				$log_title.="Update {$sync_item} #$wc_product_id\n";
				$log_details.="{$sync_item} #$wc_product_id has been updated, QuickBooks Product ID is #$qbo_item_id";
				$log_status = 1;
				$this->save_log($log_title,$log_details,'Product',$log_status,true);
				$this->add_qbo_item_obj_into_log_file(''.$sync_item.' Update',$product_data,$item,$this->get_IPP()->lastRequest(),$this->get_IPP()->lastResponse(),true);

				$this->save_qbo_item_local($qbo_item_id,$name,$sku,$type);
				return $qbo_item_id;

			}else{
				$res_err = $ItemService->lastError($Context);
				$log_title.="Update {$sync_item} Error #$wc_product_id\n";
				$log_details.="Error:$res_err";
				$this->save_log($log_title,$log_details,'Product',$log_status,true,true);
				$this->add_qbo_item_obj_into_log_file(''.$sync_item.' Update',$product_data,$item,$this->get_IPP()->lastRequest(),$this->get_IPP()->lastResponse());
				return false;
			}

		}
	}
	return false;
}