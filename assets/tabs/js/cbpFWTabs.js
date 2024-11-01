/**
 * cbpFWTabs.js v1.0.0
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * Copyright 2014, Codrops
 * http://www.codrops.com
 */
;( function( window ) {
	
	'use strict';

	function extend( a, b ) {
		for( var key in b ) { 
			if( b.hasOwnProperty( key ) ) {
				a[key] = b[key];
			}
		}
		return a;
	}

	function CBPFWTabs( el, options ) {
		this.el = el;
		this.options = extend( {}, this.options );
  		extend( this.options, options );
  		this._init();
	}

	CBPFWTabs.prototype.options = {
		start : 0
	};

	CBPFWTabs.prototype._init = function() {
		// tabs elems
		this.tabs = [].slice.call( this.el.querySelectorAll( 'nav > ul > li' ) );
		// content items
		this.items = [].slice.call( this.el.querySelectorAll( '.content-wrap > section' ) );
		// current index
		this.current = -1;
		// show current content item
		this._show();
		// init events
		this._initEvents();
	};

	CBPFWTabs.prototype._initEvents = function() {
		var self = this;
		this.tabs.forEach( function( tab, idx ) {
			tab.addEventListener( 'click', function( ev ) {
				ev.preventDefault();
				self._show( idx );
			} );
		} );
	};

	CBPFWTabs.prototype._show = function( idx ) {
		if( this.current >= 0 ) {
			this.tabs[ this.current ].className = this.items[ this.current ].className = '';
		}
		// change current
		this.current = idx != undefined ? idx : this.options.start >= 0 && this.options.start < this.items.length ? this.options.start : 0;
		this.tabs[ this.current ].className = 'tab-current';
		this.items[ this.current ].className = 'content-current';
	};

	// add to global namespace
	window.CBPFWTabs = CBPFWTabs;

	
	//tabs 
	[].slice.call( document.querySelectorAll( '.tabs-style-flip' ) ).forEach( function( el ) {
		new CBPFWTabs( el );
	});
	
	
	
	//ADD-REMOVE CAMPAIGNS
    jQuery('.circle_woo_woocommerce_donations_campaigns_add_field_button').click(function(e){ //on add input button click
        e.preventDefault();
		jQuery(".input_fields_wrap").append('<div><input type="text" name="circle_woo_woocommerce_donations_campaigns[campaign_list][]" placeholder="'+ circle_woowcdonations.enter_campaign_name +'"> <a href="#" class="circle_woo_woocommerce_donations_campaigns_remove_field_button">'+ circle_woowcdonations.remove_campaign +'</a></div>'); //add input box
    });
    jQuery(".input_fields_wrap").on("click",".circle_woo_woocommerce_donations_campaigns_remove_field_button", function(e){ //user click on remove text
        e.preventDefault(); 
		jQuery(this).parent('div').remove(); 
	
    })	
	
	
	//Dropdown of predefined values 
	jQuery('#woocommerce_donations_display_donation_field_as').on('change',function(){
		//console.log(jQuery('#woocommerce_donations_display_donation_field_as option:selected').val());
		if(jQuery('#woocommerce_donations_display_donation_field_as option:selected').val() == 'dropdown'){
			jQuery('#woocommerce_donations_display_donation_field_predefined_values_container').show();
		}else{
			jQuery('#woocommerce_donations_display_donation_field_predefined_values_container').hide();
		}
	});
	
	
	//ADD-REMOVE PREDEFINED VALUES FOR DONATION 
    jQuery('.circle_woo_woocommerce_donations_campaigns_add_predefined_field_button').click(function(e){ 
        e.preventDefault();
		jQuery(".input_predefined_donation_values_fields_wrap").append('<div><input type="text" name="circle_woo_woocommerce_donations_predefined_donation_value[]" placeholder="'+ circle_woowcdonations.enter_predefined_value +'"> <a href="#" class="circle_woo_woocommerce_donations_campaigns_remove_field_button">'+ circle_woowcdonations.remove_predefined_value +'</a></div>');
    });
    jQuery(".input_predefined_donation_values_fields_wrap").on("click",".circle_woo_woocommerce_donations_campaigns_remove_field_button", function(e){
        e.preventDefault(); 
		jQuery(this).parent('div').remove(); 
	
    });
	
	// REPORTS SEARCH FORM DONATIONS 
	jQuery('#circle_woo_wc_donations_search_reports').click(function(e){
		e.preventDefault();
		//console.log('OKi');
		
		var search_campaign = jQuery('#circle_woo_wc_donation_show_donations_for_campaign option:selected').val();
		
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: 'json',
			data : { action: 'circle_woo_wc_donations_search_reports', search_for :  search_campaign },
			
			
			success:function(response){
				
				//console.log(response.count);
				
				if(response.count > 0 ){
					//console.log(response.results);
					
					var results_html = '';
					
					jQuery.map( response.results, function( n, i ) {
						results_html+= '<tr><td>' + n.view_full_order_link + '</td><td>'+ n.donation_value +'</td><td>'+ n.campaign +'</td></tr>';  
						//console.log(n);
					});					
					
					jQuery('.circle_woo-wc-donation-reports-results table tbody').html(results_html);
					
				}else{
					jQuery('.circle_woo-wc-donation-reports-results table tbody').html('<tr><td colspan=3>'+ circle_woowcdonations.no_orders_found + '</td></tr>');
				}
				
			}
		
		
		});
		
	});
	
	
})( window );