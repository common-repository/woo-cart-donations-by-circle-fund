<?php
/*
Plugin Name: WooCommerce Cart Donations 
Plugin URI: https://www.circlefund.org/wordpress
Description: This plugin allows you to collect fixed or percentage donations from selected charities based on the shopping cart total.
Version: 1.0
Author: circlefund
Author URI: https://circlefund.org
Text Domain: circlefund-cart-donations
*/

define( 'CIRCLE_WOO_DONATIONS_URL', plugin_dir_url( __FILE__ ));

//load translatable files
add_action('plugins_loaded', 'circle_woo_donations_language');
function circle_woo_donations_language() {
	load_plugin_textdomain( 'circle-woo-donations', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
}


//Load JS/CSS for tabs on admin area 
add_action( 'admin_enqueue_scripts', 'circle_woo_donation_register_scripts_styles_admin' );
function circle_woo_donation_register_scripts_styles_admin(){
	wp_enqueue_style( 'circle-woo-donations-tabs-css-admin',CIRCLE_WOO_DONATIONS_URL.'assets/tabs/css/tabs.css' );
	
	wp_register_script( 'circle-woo-donations-tabs-js-admin',CIRCLE_WOO_DONATIONS_URL.'assets/tabs/js/cbpFWTabs.js',array( 'jquery' ),'1.0.0',true);
	wp_enqueue_script( 'circle-woo-donations-tabs-js-admin' );
	wp_localize_script( 'circle-woo-donations-tabs-js-admin', 'circle_woowcdonations',
		array( 
			'remove_campaign'   		=> __('Remove campaign','circle-woo-donations'),
			'enter_campaign_name'   	=> __('Enter campaign name','circle-woo-donations'),
			'enter_predefined_value'   	=> __('Enter predefined value','circle-woo-donations'),
			'remove_predefined_value'	=> __('Remove predefined value','circle-woo-donations'),
			'order_text'				=> __('Order','circle-woo-donations'),
			'no_orders_found'			=> __('No orders found','circle-woo-donations'),
			
		)
	);	
	
}

//Load frontend CSS files 
add_action( 'wp_enqueue_scripts', 'circle_woo_donation_register_scripts_styles_frontend' );
function circle_woo_donation_register_scripts_styles_frontend() {
    wp_enqueue_style( 'circle-woo-donation-frontend', CIRCLE_WOO_DONATIONS_URL . 'assets/css/frontend.css');
}

// add the subpages to Woocommerce on admin area 
add_action('admin_menu', 'circlefund_register_woocommerce_donation_submenu');

function circlefund_register_woocommerce_donation_submenu() {
	add_submenu_page( 'woocommerce', 'Donations', 'Donations', 'manage_options', 'donation-settings-page', 'circlefund_woocommerce_donation_submenu_callback' ); 
}

function circlefund_woocommerce_donation_submenu_callback() {


	//Create new product STARTS
	if(isset($_POST['woocommerce_donations_add_new_product_form'])){

			if($_POST['woocommerce_donations_new_product_title']!=""){
				$new_product_title = sanitize_text_field($_POST['woocommerce_donations_new_product_title']);				
			}
		
						$add_new_donation_product_array = array(

								  'post_title'     => $new_product_title ,
								  'post_status'    => 'publish' , 
								  'post_type'      => 'product'  

								);  
						$id_of_new_donation_product = wp_insert_post($add_new_donation_product_array);

						
						
						//update_post_meta($id_of_new_donation_product , '_visibility','hidden');		
						update_post_meta($id_of_new_donation_product , '_sku','checkout-donation-product');		
						update_post_meta($id_of_new_donation_product , '_tax_class','zero-rate');		
						update_post_meta($id_of_new_donation_product , '_tax_status','none');		
						update_post_meta($id_of_new_donation_product , '_sold_individually','yes');		
						update_post_meta($id_of_new_donation_product , '_virtual','yes');		

	}
	//Create new product ENDS
	
	
	//Save how the donation will be displayed START 
	
	if(isset($_POST['woocommerce_donations_display_donation_field_as_form'])){
		
		update_option( 'circle_woo_woocommerce_donations_show_donation_field_as', sanitize_text_field($_POST['woocommerce_donations_display_donation_field_as']));
		
		if($_POST['woocommerce_donations_display_donation_field_as'] == 'dropdown' ){
			
			//save the predefined values 
			if(isset($_POST['circle_woo_woocommerce_donations_predefined_donation_value'])){
				update_option( 'circle_woo_woocommerce_donations_predefined_values', sanitize_text_field($_POST['circle_woo_woocommerce_donations_predefined_donation_value']) );
			}else{
				update_option( 'circle_woo_woocommerce_donations_predefined_values', array() );
			}
		}else{
			update_option( 'circle_woo_woocommerce_donations_predefined_values', array() );
		}
		
		
		//currency symbol 
		update_option('circle_woo_woocommerce_donations_show_currency_field',sanitize_text_field($_POST['woocommerce_donations_show_currency_symbol']));
		// percent of cart
		update_option('woocommerce_donations_show_percent',sanitize_text_field($_POST['woocommerce_donations_show_percent']));
		update_option('woocommerce_donations_show_percent_label',sanitize_text_field($_POST['woocommerce_donations_show_percent_label']));
	}
	
	$get_saved_show_currency_symbol = get_option('circle_woo_woocommerce_donations_show_currency_field');
	$get_saved_show_percent = get_option('woocommerce_donations_show_percent');
	$get_saved_show_percent_label = get_option('woocommerce_donations_show_percent_label');
	$get_saved_display_donation_as = get_option ( 'circle_woo_woocommerce_donations_show_donation_field_as' );
	$get_saved_predefined_donation_value_options = get_option ( 'circle_woo_woocommerce_donations_predefined_values' );
	
	
	//Save how the donation will be displayed ENDS
	

	if(isset($_POST['woocommerce_donations_select_product_form'])){

		if ( !isset($_POST['woocommerce_donations_select_product_nonce_field']) || !wp_verify_nonce($_POST['woocommerce_donations_select_product_nonce_field'],'woocommerce_donations_select_product_nonce') )
		{
		   print 'Sorry, your nonce did not verify.';
		   
		   exit;
		}
		else
		{
			//PROCESS FORM DATA 
			
			//selected an existing product
			if($_POST['woocommerce_donations_select_product_id']!=""){
				
				//save selected product ID
				$donation_product_new_option_value =$_POST['woocommerce_donations_select_product_id'] ;

				if ( get_option( 'woocommerce_donations_product_id' ) !== false ) {
					update_option( 'woocommerce_donations_product_id', $donation_product_new_option_value );
				} else {
					// there is still no options on the database
					add_option( 'woocommerce_donations_product_id', $donation_product_new_option_value, null, 'no' );
				}

			}
		}
		
		
	}
	
	//save string translations 
	if(isset($_POST['woocommerce_donations_save_string_translation'])){
		if($_POST['woocommerce_donations_translations']['use_custom_translation']){
			update_option('woocommerce_donations_translations',sanitize_text_field($_POST['woocommerce_donations_translations']));
		}
	}
	$get_saved_translation = get_option('woocommerce_donations_translations');
	
	
	//save campaigns settings 
	if(isset($_POST['woocommerce_donations_campaigns_form'])){

		update_option('circle_woo_woocommerce_donations_campaigns',sanitize_text_field($_POST['circle_woo_woocommerce_donations_campaigns']));

	}
	$get_saved_campaign_options = get_option ( 'circle_woo_woocommerce_donations_campaigns' );

	?>

		<div class="circle_woo_tabs container">

			<section>
			
				<div class="tabs tabs-style-flip">
					<h3><?php _e('Donation Settings','circle-woo-donations'); ?></h3>
					<nav>
						<ul>
							
							<li><a href="#section-settings" ><span><?php _e('Settings','circle-woo-donations');?></span></a></li>
							<li><a href="#section-campaign" ><span><?php _e('Campaign','circle-woo-donations');?></span></a></li>
							<li><a href="#section-reports" ><span><?php _e('Reports','circle-woo-donations');?></span></a></li>
							
						</ul>
					</nav>
					<div class="content-wrap">

						<section id="section-settings">	
							<form  action="" method="post">
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row" class="titledesc"><label for="woocommerce_donations_select_product_id"><?php _e('Donation Product','circle-woo-donations'); ?></label></th>
											<td class="forminp">
												<?php _e('Select existing product','circle-woo-donations'); ?>
												
												<select name="woocommerce_donations_select_product_id" id="woocommerce_donations_select_product_id" style="" class="select email_type">
													<option value=""></option>
													
													<?php
													
													//read existing products that fullfill our needs
													$query_existing_hidden_products = new WP_Query( 
															array( 
																'posts_per_page' => -1,
																'post_type'      => array( 'product' ),
															)
													);	

													while ( $query_existing_hidden_products->have_posts() ) {

														$query_existing_hidden_products->the_post(); ?>
														
														<option value="<?php echo get_the_ID(); ?>" <?php selected( get_option('woocommerce_donations_product_id') ,get_the_ID()); ?> ><?php echo get_the_title() ;?> </option>
														
													<?php
													}
													wp_reset_postdata();
													?>
												</select>

												<p class="description"><?php _e('A non taxable,not shippable product needs to exists in woocommerce before using donations','circle-woo-donations'); ?></p>
											</td>
										</tr>			
									</tbody>
								</table>		
								<p class="submit">
									<input type="hidden" name="woocommerce_donations_select_product_form">
										<?php wp_nonce_field('woocommerce_donations_select_product_nonce','woocommerce_donations_select_product_nonce_field'); ?>
									<input name="save" class="button-primary" type="submit" value="<?php _e('Save changes','circle-woo-donations'); ?>">        			        
								</p>
							</form>	

							<table class="form-table">
								<tbody>
									<tr valign="top">
										<th scope="row" class="titledesc"><label for="woocommerce_donations_add_new_product_form"><?php _e('New donation product','circle-woo-donations'); ?></label></th>
										<td class="forminp">
											<form  action="" method="post">
												<?php _e('New product title','circle-woo-donations'); ?> <input name="woocommerce_donations_new_product_title" class="text" type="text" >
												<input name="woocommerce_donations_add_new_product_form" class="button button-primary" type="submit" value="<?php _e('Create Product','circle-woo-donations'); ?>">
												<p class="description"><?php _e('A non taxable,not shippable product will be created and you can select it on the Donation Product  above afterward. <br> Keep in mind that the new product title will be visible on the cart, the checkout page and invoice so name it something like "DONATIONS" .','circle-woo-donations'); ?></p>
											</form>
										</td>
									</tr>			
								</tbody>
							</table>									
							<h3><?php _e('Donation field','circle-woo-donations'); ?></h3>
							<form  action="" method="post">
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row" class="titledesc"><label for="woocommerce_donations_display_donation_field_as"><?php _e('Display as','circle-woo-donations'); ?></label></th>
											<td class="forminp">
												
												<select name="woocommerce_donations_display_donation_field_as" id="woocommerce_donations_display_donation_field_as" class="text" style="width:80%">
													<option value="text" <?php selected($get_saved_display_donation_as , 'text') ;?>><?php _e('Let the customer choose how much to donate','circle-woo-donations'); ?></option>
													<option value="dropdown"  <?php selected($get_saved_display_donation_as , 'dropdown') ;?>><?php _e('Dropdown with predefined values','circle-woo-donations'); ?></option>
													<option value="percent"  <?php selected($get_saved_display_donation_as , 'percent') ;?>><?php _e('Percentage of cart','circle-woo-donations'); ?></option>
												
												</select>

												<p class="description"><?php _e('Select how the donation field will be displayed. Free value input form or as dropdown of predefined values','circle-woo-donations'); ?></p>
												
												<div id="woocommerce_donations_display_donation_field_predefined_values_container" style="<?php if(get_option('circle_woo_woocommerce_donations_show_donation_field_as',true)!='dropdown'){ echo 'display:none'; } ?>">
													
													<div class="input_predefined_donation_values_fields_wrap">
														
														<?php 
															//list existing predefined donation values  if any 
															if(is_array($get_saved_predefined_donation_value_options)){
																foreach($get_saved_predefined_donation_value_options as $single_predefined_value){ ?>
																	<div>
																		<input type="text" name="circle_woo_woocommerce_donations_predefined_donation_value[]" placeholder="<?php _e('Enter predefined value','circle-woo-donations'); ?>" value="<?php echo $single_predefined_value;?>"> <a href="#" class="circle_woo_woocommerce_donations_campaigns_remove_field_button"><?php _e('Remove value','circle-woo-donations'); ?></a>
																	</div>
																<?php 
																}
																
															}
														?>
														
													</div>			
													
													<br><br>
													
													<button class="circle_woo_woocommerce_donations_campaigns_add_predefined_field_button"><?php _e('Add new value','circle-woo-donations'); ?></button>
												</div>												
												
											</td>
										</tr>		
													<tr valign="top">
											<th scope="row" class="titledesc"><label for="woocommerce_donations_show_currency_symbol"><?php _e('Show currency symbol','circle-woo-donations'); ?></label></th>
											<td class="forminp">
												
												<select name="woocommerce_donations_show_currency_symbol" id="woocommerce_donations_show_currency_symbol" class="text" style="width:80%">
													<option value="no" <?php selected($get_saved_show_currency_symbol , 'no') ;?>><?php _e('No','circle-woo-donations'); ?></option>
													<option value="before"  <?php selected($get_saved_show_currency_symbol , 'before') ;?>><?php _e('Yes, before the field','circle-woo-donations'); ?></option>
													<option value="after"  <?php selected($get_saved_show_currency_symbol , 'after') ;?>><?php _e('Yes, after the field','circle-woo-donations'); ?></option>
												</select>

												<p class="description"><?php _e('Should the currency symbol be visible. If yes, select where','circle-woo-donations'); ?></p>
										
											</td>
										</tr>										
									
										<tr valign="top">
											<th scope="row" class="titledesc"><label for="woocommerce_donations_percent"><?php _e('Cart percentage','circle-woo-donations'); ?></label></th>
											<td class="forminp">
												<input type="text"  name="woocommerce_donations_show_percent" id="woocommerce_donations_show_percent" value="<?php echo esc_html($get_saved_show_percent);?>" class="text" style="width:80%" >
												<p class="description"><?php _e('What donation percentage should be added to the shopping cart total?','circle-woo-donations'); ?></p>
												</td>
										</tr>		
											<tr valign="top">
											<th scope="row" class="titledesc"><label for="woocommerce_donations_percent_label"><?php _e('Cart percentage label','circle-woo-donations'); ?></label></th>
											<td class="forminp">
												<input type="text"  name="woocommerce_donations_show_percent_label" id="woocommerce_donations_show_percent_label" value="<?php echo esc_html($get_saved_show_percent_label);?>" class="text" style="width:80%" >
												<p class="description"><?php _e('What do you want to show in the shopping cart total, such as a charity or cause?','circle-woo-donations'); ?></p>
												</td>
										</tr>		
									</tbody>
								</table>		
								<p class="submit">
									<input type="hidden" name="woocommerce_donations_display_donation_field_as_form">
									<input name="save" class="button-primary" type="submit" value="<?php _e('Save changes','circle-woo-donations'); ?>">        			        
								</p>
							</form>	
							
							<h3><?php _e('String translations','circle-woo-donations'); ?></h3>

							<form  action="" method="post">
								<table class="form-table">
									
										<tbody>
										
											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_use_custom_translation"><?php _e('Use custom text','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<select name="woocommerce_donations_translations[use_custom_translation]" class="text" type="text" style="width:80%">
														<option value="no" <?php selected($get_saved_translation['use_custom_translation'],'no');?>><?php _e('No, use default strings','circle-woo-donations'); ?> </option>
														<option value="yes" <?php selected($get_saved_translation['use_custom_translation'],'yes');?>><?php _e('Yes ,I want to use the texts below instead of default text of plugin','circle-woo-donations'); ?></option>
													</select>
													<p class="description"><?php _e('Select "Yes" if you want to use the strings below instead of default plugin strings . Supports HTML','circle-woo-donations'); ?> </p>
												</td>
											</tr>				
										
											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_single_product_text"><?php _e('Single product','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[single_product_text]" class="text" type="text" style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('single_product_text');?>">
													<p class="description"><?php _e('Text "Enter the amount you wish to donate" located on single product page . Supports HTML','circle-woo-donations'); ?></p>
												</td>
											</tr>			
										
											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_single_product_text"><?php _e('Single product confirmation','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[donation_added_single_product_text]" class="text" type="text" style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('donation_added_single_product_text');?>">
													<p class="description"><?php _e('Text "Donation added" located on single product page . Shown once the customer adds the donation . Supports HTML ','circle-woo-donations'); ?></p>
												</td>
											</tr>				

											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_cart_header_text"><?php _e('Cart Text','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[cart_header_text]" class="text" type="text"  style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('cart_header_text');?>">
													<p class="description"><?php _e('Text "Add a donation to your order" located on cart before "Add Donation" . Supports HTML','circle-woo-donations'); ?></p>
												</td>
											</tr>
											
											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_cart_button_text"><?php _e('Cart Button','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[cart_button_text]" class="text" type="text"  style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('cart_button_text');?>">
													<p class="description"><?php _e('Text for button "Add Donation" located on cart . Supports HTML ','circle-woo-donations'); ?></p>
												</td>
											</tr>			

											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_checkout_title_text"><?php _e('Checkout Title Text','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[checkout_title_text]" class="text" type="text"  style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('checkout_title_text');?>">
													<p class="description"><?php _e('Add a donation to your order" header text located on checkout . Shown on checkout when user has not added a donation . Supports HTML','circle-woo-donations'); ?></p>
												</td>
											</tr>				
											
											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_checkout_text"><?php _e('Checkout  Text','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[checkout_text]" class="text" type="text"  style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('checkout_text');?>">
													<p class="description"><?php _e('If you wish to add a donation you can do so on the " text located on checkout . Shown on checkout when user has not added a donation . Supports HTML ','circle-woo-donations'); ?></p>
												</td>
											</tr>				
											
											<tr valign="top">
												<th scope="row" class="titledesc"><label for="woocommerce_donations_checkout_text"><?php _e('Select Campaign Text','circle-woo-donations'); ?></label></th>
												<td class="forminp">
													<input name="woocommerce_donations_translations[select_campaign_text]" class="text" type="text"  style="width:80%" value="<?php echo woocommerce_donations_get_saved_strings_admin('select_campaign_text');?>">
													<p class="description"><?php _e('Text shown on the top of the dropdown of campaigns','circle-woo-donations'); ?></p>
												</td>
											</tr>											
	
										</tbody>
									
								</table>		
								
								<p class="submit">
									<input name="woocommerce_donations_save_string_translation" class="button-primary" type="submit" value="<?php _e('Save translations','circle-woo-donations'); ?>">        			        
								</p>
							
							</form>							
						</section> <!-- general tab -->

						<section id="section-campaign">	
							<form  action="" method="post">
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row" class="titledesc"><label for="circle_woo_woocommerce_donations_campaigns"><?php _e('Enable Campaigns','circle-woo-donations'); ?></label></th>
											<td class="forminp">
												
												<select name="circle_woo_woocommerce_donations_campaigns[enable_campaign_support]" id="woocommerce_donations_enable_campaign_support" class="select email_type">
													<option value="no" <?php selected($get_saved_campaign_options['enable_campaign_support'],'no');?>><?php _e('No','circle-woo-donations'); ?></option>
													<option value="yes" <?php selected($get_saved_campaign_options['enable_campaign_support'],'yes');?>><?php _e('Yes','circle-woo-donations'); ?></option>
												</select>

												<p class="description"><?php _e('If enabled the customer can select for which campaign-cause the donation needs to go','circle-woo-donations'); ?></p>

											</td>
										</tr>			
										
										<tr valign="top">
											<th scope="row" class="titledesc"><label for="circle_woo_woocommerce_donations_campaigns"><?php _e('Campaigns','circle-woo-donations'); ?></label></th>
											<td class="forminp">
											
												<div class="input_fields_wrap">
												
													<?php 
														//list existing campaigns if any 
														if(isset($get_saved_campaign_options['campaign_list'])){
															foreach($get_saved_campaign_options['campaign_list'] as $single_campaign){ ?>
																<div>
																	<input type="text" name="circle_woo_woocommerce_donations_campaigns[campaign_list][]" placeholder="<?php _e('Enter campaign name','circle-woo-donations'); ?>" value="<?php echo esc_html($single_campaign);?>"> <a href="#" class="circle_woo_woocommerce_donations_campaigns_remove_field_button"><?php _e('Remove campaign','circle-woo-donations'); ?></a>
																</div>
															<?php 
															}
															
														}
													?>
													
												</div>			
												
												<br><br>
												
												<button class="circle_woo_woocommerce_donations_campaigns_add_field_button"><?php _e('Add new campaign','circle-woo-donations'); ?></button>
												
												<p class="description"><?php _e('Add or remove campaigns','circle-woo-donations'); ?></p>

											</td>
										</tr>
										
									</tbody>
								</table>		
								<p class="submit">
									<input type="hidden" name="woocommerce_donations_campaigns_form">
										
									<input name="save" class="button-primary" type="submit" value="<?php _e('Save changes','circle-woo-donations'); ?>">        			        
								</p>
							</form>								
						</section> <!-- campaign tab --> 	
						
						<section id="section-reports">	
							
							<form action="" method="post">
								<select name="circle_woo_donation_show_donations_for_campaign" id="circle_woo_donation_show_donations_for_campaign">
									<option value="show_all"><?php _e('All donations','circle-woo-donations');?></option>
									<?php 
										//list existing campaigns if any 
										if(isset($get_saved_campaign_options['campaign_list'])){
											foreach($get_saved_campaign_options['campaign_list'] as $single_campaign){ ?>
												<option value="<?php echo esc_html($single_campaign);?>"><?php echo esc_html($single_campaign);?></option>
											<?php 
											}
										}
									?>									
								</select>
								
								<input type="submit" value="<?php _e('Search','circle-woo-donations');?>" class="button-primary" name="circle_woo_donations_search_reports" id="circle_woo_donations_search_reports">
								
							</form>
							
							<div class="circle-woo-donation-reports-results">
								<table class="wp-list-table widefat fixed posts">
									<thead>
										<tr>
											<th scope="col"  class="check-column manage-column column-title sortable desc " style="padding-top:0px;width: 3em;">
												<a class="table_header_text_link"><span><strong>Order</strong></span></a>
											</th>
											<th scope="col"  class="check-column manage-column column-title sortable desc " style="padding-top:0px;width: 3em;">
												<a class="table_header_text_link"><span><strong>Donation </strong></span></a>
											</th>
											<th scope="col"  class="check-column manage-column column-title sortable desc " style="padding-top:0px;width: 3em;">
												<a class="table_header_text_link"><span><strong>Campaign</strong></span></a>
											</th>	
										</tr>
									<thead>
									<tbody id="the-list">	
									</tbody>
								</table>
							</div>
							
						</section> <!-- reports tab -->							

					</div>
				</div>
			</section>
			
		</div>	

<?php

} //woocommerce_donation_submenu_callback




//current product ID 
if ( get_option('woocommerce_donations_product_id' ) !== false ) {

	//defines the ID of the product to be used as donation
	define('DONATE_PRODUCT_ID', get_option( 'woocommerce_donations_product_id' )); 
}


	function circlefund_ok_donation_exists(){
	 
		global $woocommerce;
	 
		if( sizeof($woocommerce->cart->get_cart()) > 0){
	 
			foreach($woocommerce->cart->get_cart() as $cart_item_key => $values){
	 
				$_product = $values['data'];
	 
				if( circle_woo_donation_get_product_or_order_id($_product) == DONATE_PRODUCT_ID )
					return true;
			}
		}
		return false;
	}




// Avada and themes that uses avada as parent Fix
if(strtolower(wp_get_theme()->Template) == 'avada' || strtolower(wp_get_theme()->Name) == 'avada'){
	add_action('woocommerce_after_cart_contents','circlefund_ok_woocommerce_after_cart_table' , 1 );
	
	
	//add the extra classses to the SUBMIT button on CART for avada
	add_filter('circle_woo_donation_submit_button','circle_woo_donation_submit_button_avada_classes');
	function circle_woo_donation_submit_button_avada_classes($existing_classes){
		return $existing_classes . ' fusion-button fusion-button-default fusion-button-small button default small';
	}
	
}else{	
	//All other themes 
	add_action('woocommerce_cart_contents','circlefund_ok_woocommerce_after_cart_table');
}

//Add the theme name to the TR container on CART page
add_filter('circle_woo_donation_cart_tr_container','circle_woo_donation_cart_tr_container_class');
function circle_woo_donation_cart_tr_container_class($existing_class){
	return strtolower(wp_get_theme()->Template) . ' donation-block' ;
}


	
	function circlefund_ok_woocommerce_after_cart_table(){
	 
		global $woocommerce;
		$donate = isset($woocommerce->session->ok_donation) ? floatval($woocommerce->session->ok_donation) : 0;
	 
		if(!circlefund_ok_donation_exists()){
			unset($woocommerce->session->ok_donation);
		}
	  
		if(!circlefund_ok_donation_exists()){
			?>
			<tr class="<?php echo apply_filters('circle_woo_donation_cart_tr_container','donation-block');?>">
				<td colspan="6">
					<div class="donation">
						
						<p class="message"><strong><?php 
						if(woocommerce_donations_get_saved_strings('cart_header_text')){
							echo woocommerce_donations_get_saved_strings('cart_header_text');
						}else{
							_e('Add a donation to your order','circle-woo-donations'); 
						}
						
						?></strong></p>
						
						<form action="" method="post">
							<div class="input text">
							
								<?php do_action('circle_woo_donations_before_textbox_on_cart'); ?>
								
								<?php 
									//display as INPUT or SELECT 
									if(get_option('circle_woo_woocommerce_donations_show_donation_field_as',true) == 'dropdown'  && is_array( get_option('circle_woo_woocommerce_donations_predefined_values') )){ 
									?>
									<select  name="ok-donation" class="input-text">
									<?php foreach(get_option('circle_woo_woocommerce_donations_predefined_values') as $single_predefined_value){ ?>
										<option value="<?php echo esc_html($single_predefined_value);?>"><?php echo esc_html($single_predefined_value);?></option>
									<?php } //end foreach  ?>
									</select>
									
									<?php } else { ?>
								
									<input type="text" name="ok-donation" class="<?php echo apply_filters('circle_woo_donation_free_input_text_field','input-text');?>" value="<?php echo esc_html($donate);?>"/>
								
								<?php } ?>
								
								<?php do_action('circle_woo_donations_after_textbox_on_cart'); ?>
							
								<?php 
									$get_saved_campaign_options = get_option ( 'circle_woo_woocommerce_donations_campaigns' );
									if(isset($get_saved_campaign_options['enable_campaign_support'])){
										if($get_saved_campaign_options['enable_campaign_support'] == 'yes'){
										?>
										<select name="circle-woo-donation-campaign">
											<?php if( woocommerce_donations_get_saved_strings( 'select_campaign_text' ) ) { ?>
												<option value=""><?php echo woocommerce_donations_get_saved_strings( 'select_campaign_text' );?></option>
											<?php }else{ ?>
												<option value=""><?php _e('Select campaign','circle-woo-donations');?></option>
											<?php } ?>			
											<?php foreach($get_saved_campaign_options['campaign_list'] as $single_campaign){ ?>
												<option value="<?php echo esc_html($single_campaign);?>"> <?php echo esc_html($single_campaign);?> </option>
											<?php } ?>
										</select>
										
										<?php }
									
									}
								?>
								
								<?php if( woocommerce_donations_get_saved_strings( 'cart_button_text' ) ) { ?>
									<input type="submit" name="donate-btn" class="<?php echo apply_filters('circle_woo_donation_submit_button','button');?>" value="<?php echo woocommerce_donations_get_saved_strings('cart_button_text');?>"/>
								<?php }else{ ?>
									<input type="submit" name="donate-btn" class="<?php echo apply_filters('circle_woo_donation_submit_button','button');?>" value="<?php _e('Add Donation','circle-woo-donations');?>"/>
								<?php } ?>								
							</div>

						</form>
					</div>
				</td>
			</tr>
			<?php
		}
	}



//Associate the campaign name to the order when order is completed 
add_action('woocommerce_thankyou','circle_woo_donation_add_campaign_meta_to_order');
function circle_woo_donation_add_campaign_meta_to_order($order_id){
	if ( !$order_id ){
		return;
	}
	
	global $woocommerce;
	
	$order = new WC_Order( $order_id );

	$product_list = '';
	$order_item = $order->get_items();

	foreach( $order_item as $product ) {
		if(defined('DONATE_PRODUCT_ID')){
			if ($product['product_id'] == DONATE_PRODUCT_ID ){
				
				if($woocommerce->session->circle_woo_donation_campaign){
					
					update_post_meta($order_id , '_circle_woo_donation_campaign_name',$woocommerce->session->circle_woo_donation_campaign);
				}
			}			
			
		}
	}
	
	//unset the campaign 
	unset($woocommerce->session->circle_woo_donation_campaign); 
}

//Show the campaign name on the order details page on admin area 
add_action('woocommerce_after_order_itemmeta','circle_woo_donations_show_campaign_on_order_item_meta',10,3);
function circle_woo_donations_show_campaign_on_order_item_meta( $item_id, $item, $_product){
	
	global $post;
	
	
	
	if(defined('DONATE_PRODUCT_ID') && $_product){
		
		
	
		if (  circle_woo_donation_get_product_or_order_id($_product) == DONATE_PRODUCT_ID ){
		
			if(get_post_meta( $post->ID , '_circle_woo_donation_campaign_name')){
				
				printf( __( 'Campaign %s', 'circle-woo-donations' ), get_post_meta( $post->ID , '_circle_woo_donation_campaign_name',true) ); 
				
			}
		}	
	}
	
}





add_action('template_redirect','circlefund_ok_process_donation');
	
	function circlefund_ok_process_donation(){
	 
		global $woocommerce;
	 
		$donation = isset($_POST['ok-donation']) && !empty($_POST['ok-donation']) ? floatval($_POST['ok-donation']) : false;
		$campaign = isset($_POST['circle-woo-donation-campaign']) && !empty($_POST['circle-woo-donation-campaign']) ? $_POST['circle-woo-donation-campaign'] : false;

		if($donation && isset($_POST['donate-btn'])){

			// add item to basket
			$found = false;
	 
			// add to session
			if($donation > 0){
				
				$woocommerce->session->ok_donation = $donation;

				//check if product already in cart
				
				if( sizeof($woocommerce->cart->get_cart()) > 0){
	 
					
					foreach($woocommerce->cart->get_cart() as $cart_item_key=>$values){
	 
						$_product = $values['data'];
	 
						if(  circle_woo_donation_get_product_or_order_id($_product)  == DONATE_PRODUCT_ID){

							$found = true;

							//associate campaign with the donation  
							if($campaign){
								$woocommerce->session->circle_woo_donation_campaign = $campaign;
								
							}		
							
						}
						
					}
		
					// if product not found, add it
					if(!$found){
						$woocommerce->cart->add_to_cart(DONATE_PRODUCT_ID);
						
						//associate campaign with the donation  
						if($campaign){
							$woocommerce->session->circle_woo_donation_campaign = $campaign;
						}			
						
					}
				}else{
					// if no products in cart, add it
					$woocommerce->cart->add_to_cart(DONATE_PRODUCT_ID);
					
					//associate campaign with the donation  
					if($campaign){
						$woocommerce->session->circle_woo_donation_campaign = $campaign;
					}					
					
				}

			}
		}else{
			
			//if we dont have a donation then there is no point to have a campaign so remove that from session 
			if( !isset($woocommerce->session->ok_donation)) {
				
				if(isset($woocommerce->session->circle_woo_donation_campaign)){
					
					unset($woocommerce->session->circle_woo_donation_campaign);
					
				}
			}
		}
	}




/**
 * Add filter depending on the WC version  
 */
if(circle_woo_donation_woocommerce_version_check('3.0.5')){
	add_filter('woocommerce_product_get_price', 'circlefund_ok_get_price',10,2);
}else{
	add_filter('woocommerce_get_price', 'circlefund_ok_get_price',10,2);
}

	function circlefund_ok_get_price($price, $product){
	 
		global $woocommerce;
		
		if( circle_woo_donation_get_product_or_order_id($product) == DONATE_PRODUCT_ID){
			
			if(isset($_POST['ok-donation'])){
				return isset($woocommerce->session->ok_donation) ? floatval($woocommerce->session->ok_donation) : 0;
			}
			
			if(isset($_POST['circle_woo_donation_from_single_page'])){
				
				return ($_POST['circle_woo_donation_from_single_page']>0) ? floatval($_POST['circle_woo_donation_from_single_page']) : 0 ;
				
			}
			
			return isset($woocommerce->session->ok_donation) ? floatval($woocommerce->session->ok_donation) : 0;
			
		}
		
		return $price;
	}


//Change free text 
add_filter('woocommerce_free_price_html','circle_woo_change_free_text' , 12,2);


	function circle_woo_change_free_text($price,$product_object){
		
		global $woocommerce;
		
		if( !is_admin() && !( defined( 'WC_API_REQUEST' ) && WC_API_REQUEST == true)){
			
			if(isset($product_object->id)){
				
				if(defined('DONATE_PRODUCT_ID')){
				
					if ($product_object->id == DONATE_PRODUCT_ID ){
						
						if(isset($woocommerce->session->ok_donation )){
							if($woocommerce->session->ok_donation ){
								return __('Donation added','circle-woo-donations');
							}
						}
					
						
						if( woocommerce_donations_get_saved_strings( 'single_product_text' ) ) { 
							return '<span class="enter_donation_amount_single_page">'. woocommerce_donations_get_saved_strings( 'single_product_text' )  .'</span>' ;
						}

						return '<span class="enter_donation_amount_single_page">'. _e('Enter the amount you wish to donate','circle-woo-donations') .'</span>' ;
						
					}
				
				}
			}
		}
		
		return $price;
	}


//Add the input box on single product page 
add_action('woocommerce_before_add_to_cart_button','circle_woo_add_input_on_single_product_page');

	function circle_woo_add_input_on_single_product_page(){
		
		global $woocommerce,$post;
		
		$current_donation_value = 0;
		
		if(defined('DONATE_PRODUCT_ID')){
		
			if($post->ID == DONATE_PRODUCT_ID){
				
				
				if(!circlefund_ok_donation_exists()){ 
					unset($woocommerce->session->ok_donation); 
				}
				
				if( ! isset($woocommerce->session->ok_donation)){ ?>
					<p>
					
						<?php do_action('circle_woo_donations_before_textbox_on_single_product_page'); ?>
						<?php 
							//display as INPUT or SELECT 
							if(get_option('circle_woo_woocommerce_donations_show_donation_field_as',true) == 'dropdown'  && is_array( get_option('circle_woo_woocommerce_donations_predefined_values') )){ 
							?>
							<select  name="circle_woo_donation_from_single_page" class="input-text">
							<?php foreach(get_option('circle_woo_woocommerce_donations_predefined_values') as $single_predefined_value){ ?>
								<option value="<?php echo esc_html($single_predefined_value);?>"><?php echo esc_html($single_predefined_value);?></option>
							<?php } //end foreach  ?>
							</select>
							
							<?php } else { ?>
						
							<input name="circle_woo_donation_from_single_page" value="<?php echo esc_html($current_donation_value);?>">
						
						<?php } ?>					
					
						<?php do_action('circle_woo_donations_after_textbox_on_single_product_page'); ?>

						<?php 
							$get_saved_campaign_options = get_option ( 'circle_woo_woocommerce_donations_campaigns' );
							if(isset($get_saved_campaign_options['enable_campaign_support'])){
								if($get_saved_campaign_options['enable_campaign_support'] == 'yes'){ ?>
								<select name="circle-woo-donation-campaign">
								
									<?php if( woocommerce_donations_get_saved_strings( 'select_campaign_text' ) ) { ?>
										<option value=""><?php echo woocommerce_donations_get_saved_strings( 'select_campaign_text' );?></option>
									<?php }else{ ?>
										<option value=""><?php _e('Select campaign','circle-woo-donations');?></option>
									<?php } ?>			

									<?php foreach($get_saved_campaign_options['campaign_list'] as $single_campaign){ ?>
										<option value="<?php echo esc_html($single_campaign);?>"> <?php echo esc_html($single_campaign);?> </option>
									<?php } ?>
								</select>
								<?php }
							
							}
						?>

					</p>
				<?php
				}else{
					?>
					 <p class="circle_woo_donation_from_single_page_added">
						
						<?php 
						
							if( woocommerce_donations_get_saved_strings( 'single_product_text' ) ) {
						
								echo woocommerce_donations_get_saved_strings( 'donation_added_single_product_text' );
							
							}else {
								
								printf( __( 'Donation added . Check it on the  <a href="%s">cart page</a>', 'circle-woo-donations' ), $woocommerce->cart->get_cart_url()); 
								
							} 
						?>
						
					</p>
					<?php
				}
			} 
		
		} // if defined
	}



/*
* Change "add to cart" on single page
*/

add_filter( 'woocommerce_product_single_add_to_cart_text', 'circle_woo_custom_cart_button_text_single_page' );  

	
	function circle_woo_custom_cart_button_text_single_page($text) {
	 
		global $post,$woocommerce;

		if(defined('DONATE_PRODUCT_ID')){
		
			if($post->ID == DONATE_PRODUCT_ID) {
				if(isset($woocommerce->session->ok_donation )){
					$text =  _e('Donation added','circle-woo-donations') ;
				}
			}
		
		}
	 
		return $text;
	}


/*
* Hide  the "ADD TO CART" on single page if donations already added
*/
add_action('wp_head','circle_woo_donation_hide_add_to_cart_on_single_product');
	
	function circle_woo_donation_hide_add_to_cart_on_single_product(){
		
		global $woocommerce;
		
		if(defined('DONATE_PRODUCT_ID')){
		
			if(isset($woocommerce->session->ok_donation )){
				echo '<style>
						.woocommerce div.product.post-'.DONATE_PRODUCT_ID.' form.cart .button {
							display:none;
						}
					 </style>';
			}

		}
		
	}



add_filter('woocommerce_add_cart_item', 'circle_woo_donation_add_cart_item_data', 14, 2);

	function circle_woo_donation_add_cart_item_data($cart_item) {
		global $woocommerce;

		if(defined('DONATE_PRODUCT_ID')){
		
			if($cart_item['product_id'] == DONATE_PRODUCT_ID){

				//if the user is adding from single product page 
				if(isset($_POST['circle_woo_donation_from_single_page'])){
					
					$woocommerce->session->ok_donation =  floatval($_POST['circle_woo_donation_from_single_page']);
					
					//check if we have a campaign 
					if(isset($_POST['circle-woo-donation-campaign'])){
						$woocommerce->session->circle_woo_donation_campaign =  $_POST['circle-woo-donation-campaign'];
					}else{
						unset($woocommerce->session->circle_woo_donation_campaign);
					}
					
				}
			}
		
		}
		
		return $cart_item;
	}



//Append the campaign to the table of items on cart page 
add_filter('woocommerce_cart_item_name','circle_woo_donation_change_cart_item_name',10,3);
	function circle_woo_donation_change_cart_item_name($link, $cart_item, $cart_item_key ){
		
		global $woocommerce;
		
		if($cart_item['product_id'] == get_option( 'woocommerce_donations_product_id' ,true)){
			if(isset($woocommerce->session->circle_woo_donation_campaign)){
				return sprintf( __( '%s <br> Campaign %s', 'circle-woo-donations' ), $link , $woocommerce->session->circle_woo_donation_campaign );
			}
		}
		
		return $link ;
	}



//Show or hide currency symbol on product pages 
add_action('circle_woo_donations_before_textbox_on_single_product_page','circle_woo_donation_show_currency_symbol_before');
add_action('circle_woo_donations_before_textbox_on_cart','circle_woo_donation_show_currency_symbol_before');

	function circle_woo_donation_show_currency_symbol_before(){
		
		$get_saved_show_currency_symbol = get_option('circle_woo_woocommerce_donations_show_currency_field',true);
		
		if( $get_saved_show_currency_symbol == 'before'  ) {
			echo get_woocommerce_currency_symbol();
		}
		
	}



add_action('circle_woo_donations_after_textbox_on_single_product_page','circle_woo_donation_show_currency_symbol_after');
add_action('circle_woo_donations_after_textbox_on_cart','circle_woo_donation_show_currency_symbol_after');

	function circle_woo_donation_show_currency_symbol_after(){
		
		$get_saved_show_currency_symbol = get_option('circle_woo_woocommerce_donations_show_currency_field',true);
		
		if( $get_saved_show_currency_symbol == 'after'  ) {
			echo get_woocommerce_currency_symbol();
		}
		
	}


add_action('woocommerce_review_order_before_payment','circle_woo_donations_add_link_on_checkout');

	function circle_woo_donations_add_link_on_checkout(){ 

		global $woocommerce;

		$products_ids_in_cart=false;
		
		//check if donation is already in cart 
		foreach($woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			
			$_product = $values['data'];
		
			$products_ids_in_cart[circle_woo_donation_get_product_or_order_id($_product)]= circle_woo_donation_get_product_or_order_id($_product);

		}

		//if no donation found on cart ... show a link on checkout page
		if( is_array( $products_ids_in_cart ) ) {
			
			if( !in_array(DONATE_PRODUCT_ID,$products_ids_in_cart )){
				?>
					<div style="margin: 0 -1px 24px 0;">
					<h3><?php 
						if( woocommerce_donations_get_saved_strings( 'checkout_title_text' ) ) { 
							echo woocommerce_donations_get_saved_strings( 'checkout_title_text' );
						}else {
							_e('Add a donation to your order','circle-woo-donations');
						} 
					?></h3> 
					
					
					<?php 
						if( woocommerce_donations_get_saved_strings( 'checkout_text' ) ) { 
							echo woocommerce_donations_get_saved_strings( 'checkout_text' );
						}else {
							 printf( __( 'If you wish to add a donation you can do so on the <a href="%s">cart page</a>', 'circle-woo-donations' ), $woocommerce->cart->get_cart_url() ); 
						} 
					?>
					</div>
				<?php 
				
			} //end if "no donation found on cart"
		
		} //end if is array $products_ids_in_cart
		
	}
	



/*
* Add the donation campaign to the email sent to customer 
*/
add_action('woocommerce_order_item_meta_end','circle_woo_donation_add_campaign_details_to_email',999,3 );

	function circle_woo_donation_add_campaign_details_to_email($item_id, $item, $order){

		if(get_option( 'woocommerce_donations_product_id' )){
		
			if ($item['product_id'] == get_option( 'woocommerce_donations_product_id' ) ){
				
				if( get_post_meta( circle_woo_donation_get_product_or_order_id($order) , '_circle_woo_donation_campaign_name', true ) ) {
					
					printf( __( 'Campaign %s', 'circle-woo-donations' ), get_post_meta( circle_woo_donation_get_product_or_order_id($order) , '_circle_woo_donation_campaign_name',true) ); 
					
				}
			}	
		}

	}



/*
* Generate reports 
*/
add_action('wp_ajax_circle_woo_donations_search_reports','circle_woo_donations_search_reports_ajax_func');
	
	function circle_woo_donations_search_reports_ajax_func(){
		
		//get all orders that has donations ... will filter them later or not 
		global $wpdb;
		$produto_id = get_option( 'woocommerce_donations_product_id' ); 
		$consulta = "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_itemmeta woim LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON woim.order_item_id = oi.order_item_id WHERE meta_key = '_product_id' AND meta_value = %d GROUP BY order_id;";
		$order_ids = $wpdb->get_col( $wpdb->prepare( $consulta, $produto_id ) );		

		$return_array = array('count' => 0 , 'results' => array() );
		
		//show all orders that has the donation product 
		if($_POST['search_for']=='show_all'){


			if( $order_ids ) {
				$args = array(
							'post_type' =>'shop_order',
							'post__in' => $order_ids,
							'post_status' =>  array_keys( wc_get_order_statuses() ),
							'posts_per_page' => -1,
							'order' => 'DESC',

						);
				$orders = get_posts( $args );
				
				foreach($orders as $single_order){
					$order = new WC_Order( $single_order->ID );
					foreach ($order->get_items() as $key => $lineItem) {
						
						if($lineItem['product_id']== get_option( 'woocommerce_donations_product_id' ) ){
							$single_order_to_return['ID'] = $single_order->ID;
							$single_order_to_return['view_full_order_link'] =  '<a href="'.get_edit_post_link($single_order->ID).'">'. $single_order->ID .'</a>' ; 
							$single_order_to_return['donation_value'] = $lineItem['line_total'];
							$single_order_to_return['campaign'] = get_post_meta($single_order->ID , '_circle_woo_donation_campaign_name', true);
							$final_orders[] = $single_order_to_return;
						}
						
					}
					
				}
				
				$return_array['count'] = count($final_orders);
				$return_array['results'] = $final_orders;
				
			}

		}else {
			
			//show orders that has specific donation for X campaign 
			
			if( $order_ids ) {
				
				$final_orders = array();
				
				$args = array(
							'post_type' =>'shop_order',
							'post__in' => $order_ids,
							'post_status' =>  array_keys( wc_get_order_statuses() ),
							'meta_key'    => '_circle_woo_donation_campaign_name',
							'meta_value'  => esc_attr($_POST['search_for']),							
							'posts_per_page' => -1,
							'order' => 'DESC',

						);
				$orders = get_posts( $args );

				foreach($orders as $single_order){
					$order = new WC_Order( $single_order->ID );
					foreach ($order->get_items() as $key => $lineItem) {
						
						if($lineItem['product_id']== get_option( 'woocommerce_donations_product_id' ) ){
							$single_order_to_return['ID'] = $single_order->ID;
							$single_order_to_return['view_full_order_link'] =  '<a href="'.get_edit_post_link($single_order->ID).'">'. $single_order->ID .'</a>' ; 
							$single_order_to_return['donation_value'] = $lineItem['line_total'];
							$single_order_to_return['campaign'] = get_post_meta($single_order->ID , '_circle_woo_donation_campaign_name', true);
							$final_orders[] = $single_order_to_return;
						}
						
					}
					
				}
				
				$return_array['count'] = count($final_orders);
				$return_array['results'] = $final_orders;			
				
			}			

		}
		
		die (json_encode($return_array));
	}



/**
 *  Compare WC versions 
 */
function circle_woo_donation_woocommerce_version_check( $version = '3.0' ) {
	if ( class_exists( 'WooCommerce' ) ) {
		global $woocommerce;
		if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
			return true;
		}
	}
	return false;
}


/**
 *  Get product ID depending on WC version . 
 *  Added since after WC 3.0 we cant use $product->id but we need to use $product->get_id()
 */

function circle_woo_donation_get_product_or_order_id( $product_or_order ) {
	
	if(circle_woo_donation_woocommerce_version_check('3.0')){
		return $product_or_order->get_id();
	}else{
		return $product_or_order->id;
	}
	
} 
 
 

/*
* Get translated texts for backend plugin options
*/
function woocommerce_donations_get_saved_strings_admin($key){
	$saved_strings_array = get_option('woocommerce_donations_translations');
	if(isset($saved_strings_array[$key])){
		return stripcslashes(esc_html($saved_strings_array[$key]));
	}
	
	return false;
}

/*
* Get translated texts for frontend
*/
function woocommerce_donations_get_saved_strings($key){
	
	$saved_strings_frontend_array = get_option('woocommerce_donations_translations');
	
	if($saved_strings_frontend_array['use_custom_translation']=='yes'){
		
		if($saved_strings_frontend_array[$key]){
			return stripcslashes($saved_strings_frontend_array[$key]);
		}
		}
	
	return false;
}


function donation_percent_add_cart_fee() {
global $woocommerce;
//$cart_total_donate = $woocommerce->cart->get_cart_total();
$cart_total_donate = 	WC()->cart->cart_contents_total;
    // Set here your percentage
	$get_saved_show_percent = get_option('woocommerce_donations_show_percent');
	$get_saved_show_percent_label = get_option('woocommerce_donations_show_percent_label');
	if (empty($get_saved_show_percent_label)) {
		$get_saved_show_percent_label = " Donation:";
	}
    $percentage = ($get_saved_show_percent)/100;
     $fee =  $cart_total_donate * $percentage;

    if ( !empty( $cart_total_donate ) &&  !empty( $get_saved_show_percent)) { 
        WC()->cart->add_fee( __($get_saved_show_percent.'% '.$get_saved_show_percent_label, 'your_theme_slug'), $fee, false );
    }
}
add_action( 'woocommerce_cart_calculate_fees','donation_percent_add_cart_fee' );