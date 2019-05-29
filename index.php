<?php
/*
title: [en_US:]Kuna[:en_US][ru_RU:]Kuna[:ru_RU]
description: [en_US:]Kuna merchant[:en_US][ru_RU:]мерчант Kuna[:ru_RU]
version: 1.5
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if(!class_exists('merchant_kuna')){
	class merchant_kuna extends Merchant_Exchangebox {

		function __construct($file, $title)
		{
			$map = array(
				'KEY', 'SECRET',
			);
			parent::__construct($file, $map, $title);

			add_filter('merchants_settingtext_'.$this->name, array($this, 'merchants_settingtext'));
			add_filter('exchangebox_merchant_paybutton_'.$this->name, array($this,'merchant_pay_button'),99,3);
			add_filter('get_merchant_admin_options_'.$this->name,array($this, 'get_merchant_admin_options'),1,2);
			add_action('myaction_merchant_'. $this->name .'_add', array($this,'myaction_merchant_add'));
			add_action('myaction_merchant_'. $this->name .'_status', array($this,'myaction_merchant_status'));

		}
		
		function merchants_settingtext(){
			$text = '| <span class="bred">'. __('Config file is not set up','pn') .'</span>';
			if(
				is_deffin($this->m_data,'KEY')
				and is_deffin($this->m_data,'SECRET')
			){
				$text = '';
			}

			return $text;
		}
		function get_merchant_admin_options($options, $data){
			if(isset($options['note'])){
				unset($options['note']);
			}

			return $options;
		}
		
		function merchant_pay_button($temp, $naps, $item){
			$temp = '
			<form action="'. get_merchant_link($this->name.'_add') .'" method="get" target="_blank">
				<input type="hidden" name="hash" value="'. is_bid_hash($item->hashed) .'" />
				<input type="submit" formtarget="_top" value="'. __('Continue','pn') .'" />
			</form>				
			';

			return $temp;
		}
		
		function myaction_merchant_add(){
			global $wpdb;	
			$hashed = is_bid_hash(is_param_get('hash'));
			$err = is_param_get('err');
			
			if($hashed){
				$item = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."bids WHERE hashed='$hashed'");
				if(isset($item->id)){
					$item_id = $item->id;
					$status = $item->status;
					$valut1i = intval($item->valut1i);
					$valut1 = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."valuts WHERE id='$valut1i'");
					$xzt = $valut1->xzt;
					$m_id = apply_filters('get_merchant_id','' , $xzt, $item);
					if($status=='new' and $m_id and $m_id == $this->name){
						$sum = pn_strip_input($item->summ1);
					?>
					
						<div style="border: 1px solid #8eaed5; padding: 10px 15px; font: 13px Arial; width: 400px; border-radius: 3px; margin: 0 auto; text-align: center;">
							
							<p><?php _e('In order to pay an ID order','pn'); ?> <b><?php echo $item_id; ?></b>,<br /> <?php _e('enter coupon code valued','pn'); ?> <b><?php echo $sum; ?> LiveCoin <?php echo is_site_value($item->valut1type); ?></b>:</p>
							<form action="<?php echo get_merchant_link($this->name.'_status'); ?>" method="post">
								<input type="hidden" name="hash" value="<?php echo $hashed; ?>" />
								<p><input type="text" placeholder="<?php _e('Code','pn'); ?>" required name="code" style="border: 1px solid #ddd; border-radius: 3px; padding: 5px 10px;" value="" /></p>
								<p><input type="submit" formtarget="_top" style="padding: 5px 10px;" value="<?php _e('Submit code','pn'); ?>" /></p>
							</form>				
							
							<?php if($err == '-1'){ ?>
								<div style="border: 1px solid #ff0000; padding: 10px 15px; font: bold 13px Arial; border-radius: 3px;">
									<?php _e('You have not entered a kuna code','pn'); ?>
								</div>
							<?php } ?>
							<?php if($err == '-2'){ ?>
								<div style="border: 1px solid #ff0000; padding: 10px 15px; font: bold 13px Arial; border-radius: 3px;">
									<?php _e('Kuna code is not valid','pn'); ?>
								</div>
							<?php } ?>
							<?php if($err == '-3'){ ?>
								<div style="border: 1px solid #ff0000; padding: 10px 15px; font: bold 13px Arial; border-radius: 3px;">
									<?php _e('Kuna code amount does not match the required amount','pn'); ?>
								</div>
							<?php } ?>
							<?php if($err == '-4'){ ?>
								<div style="border: 1px solid #ff0000; padding: 10px 15px; font: bold 13px Arial; border-radius: 3px;">
									<?php _e('Kuna code currency code does not match the required currency','pn'); ?>
								</div>
							<?php } ?>
							<?php if($err == '-5'){ ?>
								<div style="border: 1px solid #ff0000; padding: 10px 15px; font: bold 13px Arial; border-radius: 3px;">
									<?php _e('Kuna code is allready used','pn'); ?>
								</div>
							<?php } ?>							
						</div>
						
					<?php 
					} else {
						wp_redirect(get_bids_url($hashed));
						exit;
					}	 
				} else {
					pn_display_mess(__('Error!','pn'));
				}	
			} else {
				pn_display_mess(__('Error!','pn'));
			}			

		} 
		
		function myaction_merchant_status(){
		global $wpdb;
			$hashed = is_bid_hash(is_param_post('hash'));
			$code = trim(is_param_post('code'));
			if($hashed){
				$item = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."bids WHERE hashed='$hashed'");
				if(isset($item->id)){
					$item_id = $item->id;
					$status = $item->status;
					$valut1i = intval($item->valut1i);
					$valut1 = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."valuts WHERE id='$valut1i'");
					$xzt = $valut1->xzt;
					$m_id = apply_filters('get_merchant_id','' , $xzt, $item);
					if($status=='new' and $m_id and $m_id == $this->name){
						$bid_corr_sum = $item->summ1;
						$bid_currency = strtoupper(str_replace('UAH','UAH',$item->valut1type));
		
							if($code){
								try{
									$public_key = is_isset($this->m_data, 'KEY');
									$privat_key = is_isset($this->m_data, 'SECRET');
									$res = new KunaApi($public_key,$privat_key);
									$info = $res->check_voucher($code);
									if($info){
										$merch_redeem = is_isset($info,'status');
										$merch_sum = is_isset($info,'amount');
										$merch_currency = strtoupper(is_isset($info,'currency'));
										$merch_trans_id = strtoupper(is_isset($info,'id'));
										if($merch_redeem =='valid'){
										if($merch_sum == $bid_corr_sum){
											if($merch_currency == $bid_currency){
												$info = $res->redeem_voucher($code);
												if($info){
													the_merchant_bid_payed($item_id, $merch_sum, '', '', '', 'user');
													
													wp_redirect(get_bids_url($hashed));
													exit;	
												}else {
													$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-2';
													wp_redirect($back);
													exit;
												}
											} else {
												$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-4';
												wp_redirect($back);
												exit;
											}
										} else {
											$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-3';
											wp_redirect($back);
											exit;
										}
									}else{
										$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-5';
										wp_redirect($back);
										exit;
										}
									} else {
										$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-2';
										wp_redirect($back);
										exit;
									}
								}
								catch (Exception $e)
								{
									$show_error = intval(is_isset($m_data, 'show_error'));
									if($show_error){
										die($e);
									}
									$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-2';
									wp_redirect($back);
									exit;
								}
							} else {
								$back = get_merchant_link($this->name.'_add') .'?hash='. $hashed .'&err=-1';
								wp_redirect($back);
								exit;
							}
						} else {
							wp_redirect(get_bids_url($hashed));
							exit;
						}	 
					} else {
						pn_display_mess(__('Error!','pn'));
					}	
				} else {
					pn_display_mess(__('Error!','pn'));
				}	

			}	 	
			
		}
	}

new merchant_kuna(__FILE__, 'Kuna');
