// JavaScript Document
(function($) {
    "use strict";
	
	$(document).ready(function() { wc_grand_total_calculations(); });
	
	function wc_grand_total_calculations() {
		var products_grand_total 	= 0;
		var parts_grand_total 		= 0;
		var service_grand_total 	= 0;
		var extra_grand_total 		= 0;
		var parts_tax_total 		= 0;
		var paroducts_tax_total 	= 0;
		var service_tax_total 		= 0;
		var extra_tax_total			= 0;
		var payment_grand_total 	= 0;
		var expense_grand_total 	= 0;

		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();

		var $tax_exc_type = 'exclusive';
		if ( $prices_inclu_exclu == 'inclusive' ) {
			$tax_exc_type = 'inclusive';
		}

		//Woo ?Products Calculations List.
		if ( $(".wc_product_price_total").length ) {
			var i = 1;
			$('.wc_product_price_total').each(function(i) {
				var quantity = $('[name="wc_product_qty[]"]').get(i).value;
				var price 	 = $('[name="wc_product_price[]"]').get(i).value;

				if(isNaN(quantity)) { 
					alert("Quantity needs to be a number!");
					$("[name='wc_product_qty[]']").get(i).value = 1;
					$("[name='wc_product_qty[]']").get(i).focus();
					return false;
				}
				if(isNaN(price)) { 
					alert("Price needs to be a number!");
					$("[name='wc_product_price[]']").get(i).value = 1;
					$("[name='wc_product_price[]']").get(i).focus();
					return false;
				}
		
				var total = parseFloat(price)*parseFloat(quantity);

				if(!isNaN(total)) {
					products_grand_total = parseFloat(total+products_grand_total);
				}

				//Calculate Parts Tax if exists
				if ( $(".wc_product_tax_price").length ) {
					var tax 	 = $('[name="wc_product_tax[]"]').get(i).value;

					if(isNaN(tax)) { 
						alert("Tax isn't a number!");
						$("[name='wc_product_tax[]']").get(i).focus();
						return false;
					}
					if ( $tax_exc_type == 'inclusive' ) {
						var $taxPrice = parseFloat(total)*parseFloat(tax)/(100+parseFloat(tax));
					} else {
						var $taxPrice = (parseFloat(total)/100)*parseFloat(tax);
					}
					if(!isNaN($taxPrice)) {
						paroducts_tax_total = parseFloat($taxPrice+paroducts_tax_total);
					}
				}
			});
		}

		//Parts Products.
		if ($(".wc_price_total").length) {
			var i = 1;
			$('.wc_price_total').each(function(i) {
				var quantity = $('[name="wc_part_qty[]"]').get(i).value;
				var price 	 = $('[name="wc_part_price[]"]').get(i).value;

				if(isNaN(quantity)) { 
					alert("Quantity needs to be a number!");
					$("[name='wc_part_qty[]']").get(i).value = 1;
					$("[name='wc_part_qty[]']").get(i).focus();
					return false;
				}
				if(isNaN(price)) { 
					alert("Price needs to be a number!");
					$("[name='wc_part_price[]']").get(i).value = 1;
					$("[name='wc_part_price[]']").get(i).focus();
					return false;
				}
		
				var total = parseFloat(price)*parseFloat(quantity);

				if(!isNaN(total)) {
					parts_grand_total = parseFloat(total+parts_grand_total);
				}

				//Calculate Parts Tax if exists
				if ($(".wc_part_tax_price").length) {
					var tax 	 = $('[name="wc_part_tax[]"]').get(i).value;
					if(isNaN(tax)) { 
						alert("Tax isn't a number!");
						$("[name='wc_part_tax[]']").get(i).focus();
						return false;
					}
					if ( $tax_exc_type == 'inclusive' ) {
						var $taxPrice = parseFloat(total)*parseFloat(tax)/(100+parseFloat(tax));
					} else {
						var $taxPrice = (parseFloat(total)/100)*parseFloat(tax);
					}
					if(!isNaN($taxPrice)) {
						parts_tax_total = parseFloat($taxPrice+parts_tax_total);
					}
				}
			});
		}

		//Services List.
		if ($(".wc_service_price_total").length) {
			var i = 1;
			$('.wc_service_price_total').each(function(i) {
				var quantity = $('[name="wc_service_qty[]"]').get(i).value;
				var price 	 = $('[name="wc_service_price[]"]').get(i).value;

				if(isNaN(quantity)) { 
					alert("Quantity needs to be a number!");
					$("[name='wc_service_qty[]']").get(i).value = 1;
					$("[name='wc_service_qty[]']").get(i).focus();
					return false;
				}
				if(isNaN(price)) { 
					alert("Price needs to be a number!");
					$("[name='wc_service_price[]']").get(i).value = 1;
					$("[name='wc_service_price[]']").get(i).focus();
					return false;
				}
		
				var total = parseFloat(price)*parseFloat(quantity);

				if(!isNaN(total)) {
					service_grand_total = parseFloat(total+service_grand_total);
				}

				//Calculate Parts Tax if exists
				if ($(".wc_service_tax_price").length) {
					var tax 	 = $('[name="wc_service_tax[]"]').get(i).value;
					if(isNaN(tax)) { 
						alert("Tax isn't a number!");
						$("[name='wc_service_tax[]']").get(i).focus();
						return false;
					}
					if ( $tax_exc_type == 'inclusive' ) {
						var $taxPrice = parseFloat(total)*parseFloat(tax)/(100+parseFloat(tax));
					} else {
						var $taxPrice = (parseFloat(total)/100)*parseFloat(tax);
					}
					if(!isNaN($taxPrice)) {
						service_tax_total = parseFloat($taxPrice+service_tax_total);
					}
				}
			});
		}

		//Extras Calculations List.
		if ($(".wc_extra_price_total").length) {
			var i = 1;
			$('.wc_extra_price_total').each(function(i) {
				var quantity = $('[name="wc_extra_qty[]"]').get(i).value;
				var price 	 = $('[name="wc_extra_price[]"]').get(i).value;

				if(isNaN(quantity)) { 
					alert("Quantity needs to be a number!");
					$("[name='wc_extra_qty[]']").get(i).value = 1;
					$("[name='wc_extra_qty[]']").get(i).focus();
					return false;
				}
				if(isNaN(price)) { 
					alert("Price needs to be a number!");
					$("[name='wc_extra_price[]']").get(i).value = 1;
					$("[name='wc_extra_price[]']").get(i).focus();
					return false;
				}
		
				var total = parseFloat(price)*parseFloat(quantity);

				if(!isNaN(total)) {
					extra_grand_total = parseFloat(total+extra_grand_total);
				}

				//Calculate Parts Tax if exists
				if ($(".wc_extra_tax_price").length) {
					var tax = $('[name="wc_extra_tax[]"]').get(i).value;
					if(isNaN(tax)) { 
						alert("Tax isn't a number!");
						$("[name='wc_extra_tax[]']").get(i).focus();
						return false;
					}
					if ( $tax_exc_type == 'inclusive' ) {
						var $taxPrice = parseFloat(total)*parseFloat(tax)/(100+parseFloat(tax));
					} else {
						var $taxPrice = (parseFloat(total)/100)*parseFloat(tax);
					}
					if(!isNaN($taxPrice)) {
						extra_tax_total = parseFloat($taxPrice+extra_tax_total);
					}
				}
			});
		}

		//Payments Calculations List.
		if ($('body [name="wcrb_payment_field[]"]').length) {
			var i = 1;
			$('body [name="wcrb_payment_field[]"]').each(function(i) {
				var payment = $('body [name="wcrb_payment_field[]"]').get(i).value;
				
				if(isNaN(payment)) { 
					return false;
				}
				payment = parseFloat(payment);
				if(!isNaN(payment)) {
					payment_grand_total = parseFloat(payment+payment_grand_total);
				}
			});
		}

		//Expense Calculations List.
		if ($('body [name="expense_for_job[]"]').length) {
			var i = 1;
			$('body [name="expense_for_job[]"]').each(function(i) {
				var expense = $('body [name="expense_for_job[]"]').get(i).value;
				
				if(isNaN(expense)) { 
					return false;
				}
				expense = parseFloat(expense);
				if(!isNaN(expense)) {
					expense_grand_total = parseFloat(expense+expense_grand_total);
				}
			});
		}

		if ( $tax_exc_type == 'inclusive' ) {
			var grand_total = parseFloat(products_grand_total)+parseFloat(service_grand_total)+parseFloat(parts_grand_total)+parseFloat(extra_grand_total);
		} else {
			var grand_total = parseFloat(products_grand_total)+parseFloat(service_grand_total)+parseFloat(parts_grand_total)+parseFloat(extra_grand_total)+parseFloat(parts_tax_total)+parseFloat(paroducts_tax_total)+parseFloat(service_tax_total)+parseFloat(extra_tax_total);
		}
		var $theBalance = parseFloat(grand_total).toFixed(2)-parseFloat(payment_grand_total).toFixed(2);

		$(".wc_products_grandtotal .amount").html(wc_rb_format_currency( products_grand_total, "NO" ));
		$(".wc_parts_grandtotal .amount").html(wc_rb_format_currency( parts_grand_total, "NO" ));
		$(".wc_services_grandtotal .amount").html(wc_rb_format_currency( service_grand_total, "NO" ));
		$(".wc_extras_grandtotal .amount").html(wc_rb_format_currency( extra_grand_total, "NO" ));
		$(".wc_jobs_payments_total .amount").html(wc_rb_format_currency( payment_grand_total, "NO" ));

		$(".wc_grandtotal_balance .amount").html(wc_rb_format_currency( $theBalance, "YES" ));

		if ($(".wc_part_tax_price").length){
			$(".wc_parts_tax_total .amount").html(wc_rb_format_currency( parts_tax_total, "NO" ) );
		}
		if ($(".wc_service_tax_price").length){
			$(".wc_services_tax_total .amount").html(wc_rb_format_currency( service_tax_total, "NO" ) );
		}
		if ($(".wc_extra_tax_price").length){
			$(".wc_extras_tax_total .amount").html(wc_rb_format_currency( extra_tax_total, "NO" ) );
		}
		if ($(".wc_product_tax_price").length){
			$(".wc_products_tax_total .amount").html(wc_rb_format_currency( paroducts_tax_total, "NO" ) );
		}

		if ($(".wcrb_amount_payable").length) {
			wc_update_payment_mode( $theBalance );
		}
		$(".wc_grandtotal .amount").html(wc_rb_format_currency( grand_total, "YES" ));

		$(".wc_job_expense_total .amount").html(wc_rb_format_currency( expense_grand_total, "YES" ));
	}

	function wc_update_payment_mode( grand_total ) {
		$(".wcrb_amount_payable_value").val(grand_total);
		$(".wcrb_amount_payable").html(wc_rb_format_currency( grand_total, "NO" ));
		var $wcRb_payment_amount = $('#wcRb_payment_amount').val();
		$(".wcrb_amount_paying").html(wc_rb_format_currency(parseFloat($wcRb_payment_amount), "NO"));
		var $theBalance = grand_total - parseFloat($wcRb_payment_amount);
		$(".wcrb_amount_balance").html(wc_rb_format_currency( $theBalance, "YES" ));
	}

	$(document).on("input", "#wcRb_payment_amount", function() {
		var grand_total = $(".wcrb_amount_payable_value").val();
		wc_update_payment_mode(grand_total);
	});

	function calculate_part_item_total(array_index) {
		var product_price 		= $("[name='wc_part_price[]']").get(array_index).value;
		var product_quantity 	= $("[name='wc_part_qty[]']").get(array_index).value;
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();

		var $tax_exc_type = 'exclusive';
		if ( $prices_inclu_exclu == 'inclusive' ) {
			$tax_exc_type = 'inclusive';
		}
		
		
		if(isNaN(product_quantity)) { 
			alert("Quantity needs to be a number!");
			$("[name='wc_part_qty[]']").get(array_index).value = 1;
			$("[name='wc_part_qty[]']").get(array_index).focus();
			return false;
		}
		
		if(isNaN(product_price)) { 
			alert("Price needs to be a number!");
			$("[name='wc_part_price[]']").get(array_index).value = 1;
			$("[name='wc_part_price[]']").get(array_index).focus();
			return false;
		}

		var total 		= parseFloat(product_price)*parseFloat(product_quantity);
		
		var $calculated_tax = 0;
		var $calculated_tax_dp = 0;

		if (undefined !== $("[name='wc_part_tax[]']").get(array_index)){
			var product_tax	= $("[name='wc_part_tax[]']").get(array_index).value;

			if($("[name='wc_part_tax[]']").get(array_index).value.length) {
				// do something here
				if(isNaN(product_tax)) { 
					alert("Your tax seems not a number.");
					return false;
				} else {
					if ( $tax_exc_type == 'inclusive' ) {
						$calculated_tax = parseFloat(total)*parseFloat(product_tax)/(100+parseFloat(product_tax));
					} else {
						$calculated_tax = (parseFloat(total)/100)*parseFloat(product_tax);
					}

					$calculated_tax_dp = wc_rb_format_currency( $calculated_tax, "NO" );

					$(".wc_part_tax_price").get(array_index).innerHTML = $calculated_tax_dp;
				}	
			} else {
				$(".wc_part_tax_price").get(array_index).innerHTML = wc_rb_format_currency( $calculated_tax, "NO" );	
			}
		}
		if ( $tax_exc_type == 'inclusive' ) {
			var grand_total = parseFloat(total);
		} else {
			var grand_total = parseFloat(total)+parseFloat($calculated_tax);
		}
		
		$(".wc_price_total").get(array_index).innerHTML = wc_rb_format_currency( grand_total, "NO" );
		
		wc_grand_total_calculations();
	}

	function calculate_product_item_total(array_index) {
		var product_price 		= $("[name='wc_product_price[]']").get(array_index).value;
		var product_quantity 	= $("[name='wc_product_qty[]']").get(array_index).value;
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();

		var $tax_exc_type = 'exclusive';
		if ( $prices_inclu_exclu == 'inclusive' ) {
			$tax_exc_type = 'inclusive';
		}
	

		if(isNaN(product_quantity)) { 
			alert("Quantity needs to be a number!");
			$("[name='wc_product_qty[]']").get(array_index).value = 1;
			$("[name='wc_product_qty[]']").get(array_index).focus();
			return false;
		}
		
		if(isNaN(product_price)) { 
			alert("Price needs to be a number!");
			$("[name='wc_product_price[]']").get(array_index).value = 1;
			$("[name='wc_product_price[]']").get(array_index).focus();
			return false;
		}

		var total 				= parseFloat(product_price)*parseFloat(product_quantity);
		
		var $calculated_tax 	= 0;
		var $calculated_tax_dp 	= 0;

		if (undefined !== $("[name='wc_product_tax[]']").get(array_index)){
			var product_tax			= $("[name='wc_product_tax[]']").get(array_index).value;

			if($("[name='wc_product_tax[]']").get(array_index).value.length) {
				// do something here
				if(isNaN(product_tax)) { 
					alert("Your tax seems not a number.");
					return false;
				} else {
					if ( $tax_exc_type == 'inclusive' ) {
						$calculated_tax = parseFloat(total)*parseFloat(product_tax)/(100+parseFloat(product_tax));
					} else {
						$calculated_tax = (parseFloat(total)/100)*parseFloat(product_tax);
					}
					$calculated_tax_dp = wc_rb_format_currency( $calculated_tax, "NO" );

					$(".wc_product_tax_price").get(array_index).innerHTML = $calculated_tax_dp;
				}	
			} else {
				$(".wc_product_tax_price").get(array_index).innerHTML = wc_rb_format_currency( $calculated_tax, "NO" );	
			}
		}
		if ( $tax_exc_type == 'inclusive' ) {
			var grand_total = parseFloat(total);
		} else {
			var grand_total = parseFloat(total)+parseFloat($calculated_tax);
		}
		
		$(".wc_product_price_total").get(array_index).innerHTML = wc_rb_format_currency( grand_total, "NO" );
		
		wc_grand_total_calculations();
	}
	
	function calculate_service_item_total(array_index) {
		var service_price 		= $("[name='wc_service_price[]']").get(array_index).value;
		var service_quantity 	= $("[name='wc_service_qty[]']").get(array_index).value;
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();

		var $tax_exc_type = 'exclusive';
		if ( $prices_inclu_exclu == 'inclusive' ) {
			$tax_exc_type = 'inclusive';
		}

		if(isNaN(service_quantity)) { 
			alert("Quantity needs to be a number!");
			$("[name='wc_service_qty[]']").get(array_index).value = 1;
			$("[name='wc_service_qty[]']").get(array_index).focus();
			return false;
		}
		
		if(isNaN(service_price)) { 
			alert("Price needs to be a number!");
			$("[name='wc_service_price[]']").get(array_index).value = 1;
			$("[name='wc_service_price[]']").get(array_index).focus();
			return false;
		}
		
		var total 		= parseFloat(service_price)*parseFloat(service_quantity);
		
		var $calculated_tax = 0;
		var $calculated_tax_dp = 0;

		if (undefined !== $("[name='wc_service_tax[]']").get(array_index)){
			var service_tax			= $("[name='wc_service_tax[]']").get(array_index).value;

			if($("[name='wc_service_tax[]']").get(array_index).value.length) {
				// do something here
				if(isNaN(service_tax)) { 
					alert("Your tax seems not a number.");
					return false;
				} else {
					if ( $tax_exc_type == 'inclusive' ) {
						$calculated_tax = parseFloat(total)*parseFloat(service_tax)/(100+parseFloat(service_tax));
					} else {
						$calculated_tax = (parseFloat(total)/100)*parseFloat(service_tax);
					}

					$calculated_tax_dp = wc_rb_format_currency( $calculated_tax, "NO" );

					$(".wc_service_tax_price").get(array_index).innerHTML = $calculated_tax_dp;
				}	
			} else {
				$(".wc_service_tax_price").get(array_index).innerHTML = wc_rb_format_currency( $calculated_tax, "NO" );	
			}
		}
		if ( $tax_exc_type == 'inclusive' ) {
			var grand_total = parseFloat(total);
		} else {
			var grand_total = parseFloat(total)+parseFloat($calculated_tax);
		}

		$(".wc_service_price_total").get(array_index).innerHTML = wc_rb_format_currency( grand_total, "NO" );
		
		wc_grand_total_calculations();
	}
	
	function calculate_extra_item_total(array_index) {
		var service_price 		= $("[name='wc_extra_price[]']").get(array_index).value;
		var service_quantity 	= $("[name='wc_extra_qty[]']").get(array_index).value;
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();

		var $tax_exc_type = 'exclusive';
		if ( $prices_inclu_exclu == 'inclusive' ) {
			$tax_exc_type = 'inclusive';
		}
		
		if(isNaN(service_quantity)) { 
			alert("Quantity needs to be a number!");
			$("[name='wc_extra_qty[]']").get(array_index).value = 1;
			$("[name='wc_extra_qty[]']").get(array_index).focus();
			return false;
		}
		
		if(isNaN(service_price)) { 
			alert("Price needs to be a number!");
			$("[name='wc_extra_price[]']").get(array_index).value = 1;
			$("[name='wc_extra_price[]']").get(array_index).focus();
			return false;
		}
		
		var total 	= parseFloat(service_price)*parseFloat(service_quantity);
		
		var $calculated_tax = 0;
		var $calculated_tax_dp = 0;

		if (undefined !== $("[name='wc_extra_tax[]']").get(array_index)){
			var extra_tax			= $("[name='wc_extra_tax[]']").get(array_index).value;

			if($("[name='wc_extra_tax[]']").get(array_index).value.length) {
				// do something here
				if(isNaN(extra_tax)) { 
					alert("Your tax seems not a number.");
					return false;
				} else {
					if ( $tax_exc_type == 'inclusive' ) {
						$calculated_tax = parseFloat(total)*parseFloat(extra_tax)/(100+parseFloat(extra_tax));
					} else {
						$calculated_tax = (parseFloat(total)/100)*parseFloat(extra_tax);
					}
					
					$calculated_tax_dp = wc_rb_format_currency( $calculated_tax, "NO" );
					
					$(".wc_extra_tax_price").get(array_index).innerHTML = $calculated_tax_dp;
				}	
			} else {
				$(".wc_extra_tax_price").get(array_index).innerHTML = wc_rb_format_currency( $calculated_tax, "NO" );	
			}
		}
		if ( $tax_exc_type == 'inclusive' ) {
			var grand_total = parseFloat(total);
		} else {
			var grand_total = parseFloat(total)+parseFloat($calculated_tax);
		}

		$(".wc_extra_price_total").get(array_index).innerHTML = wc_rb_format_currency( grand_total, "NO" );

		wc_grand_total_calculations();
	}
	
	$(document).on("change", "#wc_prices_inclu_exclu", function() {
		$("[name='wc_part_qty[]']").each(function(index){
			calculate_part_item_total(index);
		});

		$("[name='wc_product_qty[]']").each(function(index){
			calculate_product_item_total(index);
		});

		$("[name='wc_service_qty[]']").each(function(index){
			calculate_service_item_total(index);
		});

		$("[name='wc_extra_qty[]']").each(function(index){
			calculate_extra_item_total(index);
		});
	});

	//On Quantity Change call function
	$(document).on("change", "[name='wc_part_qty[]']", function(){
		var array_index = $(this).index("[name='wc_part_qty[]']");
		
		calculate_part_item_total(array_index);
	});
	
	//On Quantity Change call function
	$(document).on("change", "[name='wc_part_price[]']", function() {
		var array_index = $(this).index("[name='wc_part_price[]']");
		
		calculate_part_item_total(array_index);
	});

	//On Tax Change call function
	$(document).on("change", "[name='wc_part_tax[]']", function(){
		var array_index = $(this).index("[name='wc_part_tax[]']");
		
		calculate_part_item_total(array_index);
	});

	//On Quantity Change call function
	$(document).on("change", "[name='wc_product_qty[]']", function(){
		var array_index 		= $(this).index("[name='wc_product_qty[]']");
		
		calculate_product_item_total(array_index);
	});
	
	//On Quantity Change call function
	$(document).on("change", "[name='wc_product_price[]']", function(){
		var array_index 		= $(this).index("[name='wc_product_price[]']");
		
		calculate_product_item_total(array_index);
	});

	//On Product Tax Change
	$(document).on("change", "[name='wc_product_tax[]']", function(){
		var array_index 		= $(this).index("[name='wc_product_tax[]']");
		
		calculate_product_item_total(array_index);
	});
	
	//On Quantity Change call function
	$(document).on("change", "[name='wc_service_qty[]']", function(){
		var array_index 		= $(this).index("[name='wc_service_qty[]']");
		
		calculate_service_item_total(array_index);
	});
	
	//On Quantity Change call function
	$(document).on("change", "[name='wc_service_price[]']", function(){
		var array_index 		= $(this).index("[name='wc_service_price[]']");
		
		calculate_service_item_total(array_index);
	});
	
	//On Tax Change call function
	$(document).on("change", "[name='wc_service_tax[]']", function(){
		var array_index 		= $(this).index("[name='wc_service_tax[]']");
		
		calculate_service_item_total(array_index);
	});

	//On Quantity Change call function
	$(document).on("change", "[name='wc_extra_qty[]']", function(){
		var array_index 		= $(this).index("[name='wc_extra_qty[]']");
		
		calculate_extra_item_total(array_index);
	});
	
	//On Quantity Change call function
	$(document).on("change", "[name='wc_extra_price[]']", function(){
		var array_index 		= $(this).index("[name='wc_extra_price[]']");
		
		calculate_extra_item_total(array_index);
	});
	
	//On Tax Change call function
	$(document).on("change", "[name='wc_extra_tax[]']", function(){
		var array_index 		= $(this).index("[name='wc_extra_tax[]']");
		
		calculate_extra_item_total(array_index);
	});

	function wc_rb_format_currency( number, currency_display ) {
		var $wc_cr_selected_currency  = $('#wc_cr_selected_currency').val();
		var $wc_cr_currency_position  = $('#wc_cr_currency_position').val();
		var thouSep = $('#wc_cr_thousand_separator').val();
		var decSep  = $('#wc_cr_decimal_separator').val();
		var decPlaces = $('#wc_cr_number_of_decimals').val();

		var decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces;
		var decSep = typeof decSep === "undefined" ? "." : decSep;
		var thouSep = typeof thouSep === "undefined" ? "," : thouSep;
		var sign = number < 0 ? "-" : "";
		var i = String(parseInt(number = Math.abs(Number(number) || 0).toFixed(decPlaces)));
		var j = (j = i.length) > 3 ? j % 3 : 0;

		var $theREturn = 0;
		
		$theREturn = sign +
			(j ? i.substr(0, j) + thouSep : "") +
			i.substr(j).replace(/\B(?=(\d{3})+(?!\d))/g, thouSep) +
			(decPlaces ? decSep + Math.abs(number - i).toFixed(decPlaces).slice(2) : "");

		if ( currency_display == 'YES' ) {
			if ( $wc_cr_selected_currency != '' ) {
				switch( $wc_cr_currency_position ) {
					case 'right_space':
						$theREturn = $theREturn+' '+$wc_cr_selected_currency;		
						break;
					case 'left_space':
						$theREturn = $wc_cr_selected_currency+' '+$theREturn;
						break;
					case 'left':
						$theREturn = $wc_cr_selected_currency+$theREturn;
						break;	
					case 'right':
						$theREturn = $theREturn+$wc_cr_selected_currency;
						break;
					default:
						$theREturn = $theREturn+$wc_cr_selected_currency;
				}
			}
		}
		return $theREturn;
	}

	$("form[data-async]").on("submit",function(e) {
		e.preventDefault();
		return false;
	});

	$("form[data-async]").on("forminvalid.zf.abide", function(e,target) {
	  console.log("form is invalid");
	});

	$("form[data-async]").on("formvalid.zf.abide", function(e,target) {
		var $form = $(this);
		var formData = $form.serialize();
		var $input = $(this).find("input[name=form_type]");
		var $success_class = '.form-message';

		if ($form.attr('data-success-class') !== undefined) {
			$success_class = $form.attr('data-success-class');
		}

		var $perform_act;
		var useEditorData = false;
		var editorContent = '';

		// Determine the action and check if we need editor data
		if($input.val() == "tax_form") {
			$perform_act = "wc_post_taxes";	
		} else if($input.val() == "status_form") {
			$perform_act = "wc_post_status";
		} else if($input.val() == "update_user") {
			$perform_act = "wc_update_user_data";
		} else if ($input.val() == 'payment_status_form') {
			$perform_act = "wc_post_payment_status";
		} else if ($input.val() == 'submit_default_pages_WP') {
			$perform_act = "wc_post_default_pages_indexes";
		} else if ($input.val() == 'submit_the_sms_configuration_form') {
			$perform_act = "wc_post_sms_configuration_index";
		} else if ($input.val() == 'wc_rb_update_methods_ac') {
			$perform_act = "wc_rb_update_payment_methods";
		} else if ($input.val() == 'wc_rb_update_sett_devices_brands') {
			$perform_act = "wc_rb_update_device_settings";
		} else if ($input.val() == 'add_device_form') {
			$perform_act = "wc_add_device_for_manufacture";
		} else if ($input.val() == 'add_part_fly_form') {
			$perform_act = "wc_add_part_for_fly";
		} else if ($input.val() == 'add_service_fly_form') {
			$perform_act = "wc_add_service_for_fly";
		} else if ($input.val() == 'wc_rb_update_sett_bookings') {
			$perform_act = "wc_rb_update_booking_settings";
		} else if ($input.val() == 'wc_rb_update_sett_services') {
			$perform_act = "wc_rb_update_service_settings";
		} else if ($input.val() == 'wc_rb_update_sett_account') {
			$perform_act = "wc_rb_update_account_settings";
		} else if ($input.val() == 'wc_rb_update_sett_taxes') {
			$perform_act = "wc_rb_update_tax_settings";
		} else if ($input.val() == 'maintenance_reminder_form') {
			$perform_act = "wc_rb_update_maintenance_reminder";
		} else if ($input.val() == 'wcrb_main_setting_form') {
			$perform_act = "wc_rep_shop_settings_submission";
		} else if ($input.val() == 'wcrb_currency_setting_form') {
			$perform_act = "wc_cr_submit_currency_options";
		} else if ($input.val() == 'wcrb_report_setting_form') {
			$perform_act = "wc_rep_shop_report_labels_submission";
			useEditorData = true;
			
			// Get editor content
			if (typeof tinymce !== 'undefined' && tinymce.get('wcrb_invoice_disclaimer')) {
				// Save any unsaved changes first
				tinymce.triggerSave();
				editorContent = $('#wcrb_invoice_disclaimer').val();
			} else {
				editorContent = $('#wcrb_invoice_disclaimer').val();
			}
		} else {
			$perform_act = $(this).find("input[name=form_action]").val();
			if (typeof $perform_act === "undefined") {
				$perform_act = "wc_post_customer";
			}
		}

		// Prepare the final data for AJAX
		var ajaxData;
		if (useEditorData) {
			ajaxData = formData + '&action=' + $perform_act + '&wcrb_invoice_disclaimer=' + encodeURIComponent(editorContent);
		} else {
			ajaxData = formData + '&action=' + $perform_act;
		}
		$.ajax({
			type: $form.attr('method'),
			data: ajaxData, // Use the correct data variable here
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$($success_class).html("<div class='spinner is-active'></div>");
			},
			success: function(response) {
				// Your existing success handling code remains the same
				var message = response.message;
				var success = response.success;
				
				$($success_class).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				
				if(success == "YES" && ($perform_act == "wc_post_taxes" || $perform_act == "wc_post_status" || $perform_act == "wc_post_payment_status")) {
					$form.trigger("reset");	
				}

				if ($('#updateStatus').length) {
					location.reload();
				}

				if($perform_act == "wc_post_status") {
					$("#job_status_wrapper").load(window.location + " #status_poststuff");
				} else if ($perform_act == "wc_post_payment_status") {
					$("#payment_status_wrapper").load(window.location + " #paymentStatus_poststuff");
				} else if ($perform_act == "wc_rb_update_maintenance_reminder") {
					$("#reminder_status_wrapper").load(window.location + " #reminderStatus_poststuff");

					if (success == 'YES') {
						$($form).trigger('reset');
					}
				} else if ($perform_act == "wc_add_device_for_manufacture") {
					var device_id = response.device_id;
					$('#rep_devices_head').load(document.location + ' #rep_devices_head>*', function(){
						$("select#rep_devices").select2();
						$('select#rep_devices').val(device_id);
						$('select#rep_devices').trigger('change');
					});
				} else if ($perform_act == "wc_add_part_for_fly") {
					var part_id = response.part_id;
					reload_parts_dropdown(part_id);
				} else if ($perform_act == "wc_add_service_for_fly") {
					var service_id = response.service_id;
					$('#reloadServicesData').load(document.location + ' #reloadServicesData>*', function(){
						$("select#select_rep_services").select2();
						$('select#select_rep_services').val(service_id);
						$('select#select_rep_services').trigger('change');
					});
				} else {
					$("#poststuff_wrapper").load(window.location + " #poststuff");
				}
				
				if($perform_act == "wc_post_customer") {
					var user_id = response.user_id;
					var user_value = response.optionlabel;
					var newOption = new Option(user_value, user_id, true, true);
					$('#updatecustomer').append(newOption).trigger('change');
				}
			}
		});
	});

	$(document).on('change', 'input[name="reciepetAttachment"]', function(e) {
		e.preventDefault();

		var fd = new FormData();
		var file = $(document).find('input[type="file"]');
		var security = $(this).attr('data-security');

		$('.attachmentserror').html('');

		var individual_file = file[0].files[0];
		fd.append("file", individual_file);
		fd.append('action', 'wc_upload_file_ajax');  
		fd.append('data_security', security);

		$.ajax({
			type: 'POST',
			url: ajax_obj.ajax_url,
			data: fd,
			contentType: false,
			processData: false,
			dataType: 'json',
			success: function(response) {
				var message = response.message;
				var error   = response.error;

				$('#jobAttachments').append(message);
				$("#jobAttachments").removeClass('displayNone');
				$('.attachmentserror').html(error);
			}
		});
	});

	$(document).on("submit", "form[id='submitAdminExtraField']", function(e) {
		e.preventDefault();

		var $form 	= $(this);
		var formData = $form.serialize();
		var $perform_act = "wc_add_extra_field_admin_side";

		var $post_ID = $('#post_ID').val();
		if( $post_ID == '' ) {
			$('.extrafield-form-message').html('<div class="callout success" data-closable="slide-out-right">Missing Post ID<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
		}

		$.ajax({
			type: 'POST',
			data: formData + '&post_ID='+ $post_ID + '&action='+$perform_act,
			url: ajax_obj.ajax_url,
			async: true,
			mimeTypes:"multipart/form-data",
			dataType: 'json',
			beforeSend: function() {
				$('.extrafield-form-message').html("<div class='loader'></div>");
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;
				
				$('.extrafield-form-message').html('<div class="callout success" data-closable="slide-out-right">'+message+'</div>');

				if (success == 'YES') {
					$("form[id='submitAdminExtraField']").trigger('reset');
					$("#reloadTheExtraFields").load(window.location + " .extrafieldstable");
				}
			}
		});
	});

	$("#wc_rb_sms_gateway").on("change", function(e, target) {
		var $gateway_selected = $("#wc_rb_sms_gateway").val();
		var $wcrb_nonce_sms_field = $("#wcrb_nonce_sms_field").val();

		if ( $gateway_selected != "" ) {
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_rb_return_sms_api_fields',
					'form_type': 'wc_rb_update_sms_api_fields',
					'wc_rb_sms_gateway': $gateway_selected,
					'wcrb_nonce_sms_field': $wcrb_nonce_sms_field,
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
				beforeSend: function() {
					$('.form-message').html("<div class='spinner is-active'></div>");
				},
				success: function(response) {
					$('.form-message').html("");
					
					var row  = response.html;

					$('#authenticaion_api_data').html(row);
				}
			});
		}
	});
	
	$(document).on('change', 'select.addrepairproducttolist', function(e) {
		var product_id = $(this).val();
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();
		var device_id = $(this).attr('data-device-id');
		var data_security = $(this).attr('data-security');
		var theid = $(this).attr('id');
		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_update_parts_row',
				'product': product_id,
				'prices_inclu_exclu':$prices_inclu_exclu,
				'device_id': device_id,
				'data_security':data_security
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('.parts_body_message').html("<div class='spinner is-active'></div>");
			},
			success: function(response) {
				$('.parts_body_message').html("");
				
				var row  = response.row;

				$('.parts_body').append(row);
				
				$("#select_rep_products").select2('val', 'All');
				
				//Calculations update //function defined in my-admin.js
				wc_grand_total_calculations();
				update_devices_dropdown();

				//Get Device's Serial
				if (device_id != '') {
					var serialNumber = $('input[name="device_post_id_html[]"][value="' + device_id + '"]')
										.parent('td').parent('tr') // Replace 'div' with a selector for the common ancestor
										.find('input[name="device_serial_id_html[]"]')
										.val();
					if ( serialNumber != '' ) {
						device_id += '_'+serialNumber;
					}
					$('tbody.parts_body').children('tr').last().find('select[name="wc_part_device[]"]').val(device_id);
				}
				$('#'+theid).val('').select2();
			}
		});
	});

	$("#addProduct").on("click", function(e,target) {
		
		var product_id = $("#select_product").val();
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();
		
		if(product_id == "") {
			alert("Please select part to add");
		} else {
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_update_parts_row',
					'product': product_id,
					'product_type': 'woo',
					'prices_inclu_exclu':$prices_inclu_exclu
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',

				beforeSend: function() {
					$('.products_body_message').html("<div class='spinner is-active'></div>");
				},
				success: function(response) {
					$('.products_body_message').html("");
					
					var row  = response.row;

					$('.products_body').append(row);
					
					$("#select_product").select2('val', 'All');
					
					//Calculations update //function defined in my-admin.js
					wc_grand_total_calculations();
					update_devices_dropdown();
				}
			});
		}	
	});
	
	$("#addService").on("click", function(e,target) {
		var service_id  = $("#select_rep_services").val();
		var $devices_id = $('[name="device_post_id_html[]"]').serializeArray();
		var $prices_inclu_exclu = $("#wc_prices_inclu_exclu").val();

		if(service_id == "") {
			alert("Please select service to add");
		} else {
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_update_services_row',
					'devices': $devices_id,
					'service': service_id,
					'prices_inclu_exclu':$prices_inclu_exclu
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',

				beforeSend: function() {
					$('.services_body_message').html("<div class='spinner is-active'></div>");
				},
				success: function(response) {
					$('.services_body_message').html("");
					
					var row  = response.row;

					$('.services_body').append(row);
					
					$("#select_rep_services").select2('val', 'All');
					
					//Calculations update //function defined in my-admin.js
					wc_grand_total_calculations();

					update_devices_dropdown();
				}
			});
		}	
	});
	
	$("#addExtra").on("click", function(e,target) {
		
		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_update_extra_row',
				'extra': 'yes'
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('.extra_body_message').html("<div class='spinner is-active'></div>");
			},
			success: function(response) {
				$('.extra_body_message').html("");

				var row  = response.row;

				$('.extra_body').append(row);

				//Calculations update //function defined in my-admin.js
				wc_grand_total_calculations();

				update_devices_dropdown();
			}
		});
	});

	$("#addtheDevice").on("click", function(e,target) {
		e.preventDefault();

		var $device_post_id_html	= $('[name="device_post_id_html"]').val();
		var $device_serial_id_html	= $('[name="device_serial_id_html"]').val();
		var $device_login_html		= $('[name="device_login_html"]').val();
		var $device_note_html 		= $('[name="device_note_html"]').val();
		
		// Assume you have an array of data 
		var myData = { 
			'action': 'wc_add_device_row',
			'device_post_id_html': $device_post_id_html,
			'device_serial_id_html': $device_serial_id_html,
			'device_login_html': $device_login_html,
			'device_note_html': $device_note_html,
		}; 
 
		result = '';
		if ($('#extrafields_identifier').length) {
			var $extraFieldsIdent = $('#extrafields_identifier').val();
			var result = $extraFieldsIdent.split('|');
			if(result != '') {
				$.each(result , function(index, val) { 
					myData[val+'_html'] = $('[name="'+val+'_html"]').val();
				});
			}
		}

		$.ajax({
			type: 'POST',
			data: myData,
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('.device_body_message').html("<div class='spinner is-active'></div>");
			},
			success: function(response) {
				$('.device_body_message').html("");

				if ( $device_post_id_html == 'All' || $device_post_id_html == null ) {
					$('.device_body_message').html("Please select a device");
				} else {
					var row  = response.row;

					$('.devices_body').append(row);
					$('[name="device_post_id_html"]').select2('val', 'All');
					$('[name="device_serial_id_html"]').val('');
					$('[name="device_login_html"]').val('');
					$('[name="device_note_html"]').val('');
					if(result != '') {
						$.each(result , function(index, val) { 
							$('[name="'+val+'_html"]').val('');
						});
					}
					reload_parts_dropdown( '' );
					update_devices_dropdown();
				}
			}
		});
	});

	function reload_parts_dropdown( part_id ) {
		if ($('#selectpartscontainer').children().length > 1 || $('#wc_devices_holder .devices_body .wc_devices_row input[name="device_post_id_html[]"]').length >= 1 ) {
			$('#selectpartscontainer').html('');
		}
		var iampresent = 0;
		$('#wc_devices_holder .devices_body .wc_devices_row input[name="device_post_id_html[]"]').each(function() {
			var deviceID = $(this).val();
			if(deviceID) {
				// Assume you have an array of data 
				var myData = { 
					'action': 'add_device_based_parts_dropdown',
					'device_post_id_html': deviceID,
				}; 
				$.ajax({
					type: 'POST',
					data: myData,
					url: ajax_obj.ajax_url,
					dataType: 'json',
					success: function(response) {
						var row  = response.partsdropdown;
						$('#selectpartscontainer').append(row);
						//[data-device-id="'+deviceID+'"]
						$('#selectpartscontainer select').select2();
					}
				});
			}
			iampresent = 1;
		});
		if(iampresent == 1) {
			$('#selectpartscontainer .default_part').remove();
		}
	}

	function update_devices_dropdown() {
		var $available_devices = '';
		var $counter = 1;
		var $selectedSingle = '';

		if ( $('[name="device_post_name_html[]"]').length ) {
			var i = 0;
			$('[name="device_post_name_html[]"]').each(function(i) {
				var deviceName = $('[name="device_post_name_html[]"]').get(i).value;
				var deviceID   = $('[name="device_post_id_html[]"]').get(i).value;
				var deviceSerial = $('[name="device_serial_id_html[]"]').get(i).value;

				$selectedSingle = deviceID;

				if ( deviceSerial != '' ) {
					deviceID = deviceID+'_'+deviceSerial;
					deviceName = deviceName+' ('+deviceSerial+')';
				}
				if ( $counter > 1 ) {
					$selectedSingle = '';
				}
				if(deviceID) { 
					$available_devices += '<option value="'+deviceID+'">'+deviceName+'</option>';
				}
				$counter++;
			});
		}
		
		if ( $('select.thedevice_selecter_identity').length ) {
			var i = 0;
			$('select.thedevice_selecter_identity').each(function(i) {
				var currentSelected = $(this).val();
				var defaultOption   = $(this).attr('data-label');

				if ( defaultOption != '' && i == 0 ) {
					$available_devices = '<option value="">'+defaultOption+'</option>' + $available_devices;
				}
				$(this).empty().append($available_devices);

				if (currentSelected == '') {
					currentSelected = $selectedSingle;
				}
				$(this).val(currentSelected).change();
			});
		}
	}
	
	$(document).on('click', '.editmedevice', function(e) {
		e.preventDefault();

		var $device_post_id_html = $(this).parents('.item-row').find('[name="device_post_id_html[]"]').val();
		var $device_serial_id_html = $(this).parents('.item-row').find('[name="device_serial_id_html[]"]').val();
		var $device_login_html = $(this).parents('.item-row').find('[name="device_login_html[]"]').val();
		var $device_note_html = $(this).parents('.item-row').find('[name="device_note_html[]"]').val();

		$('#deviceselectrow [name="device_post_id_html"]').select2();
		$('#deviceselectrow [name="device_post_id_html"]').val($device_post_id_html);
		$('#deviceselectrow [name="device_post_id_html"]').trigger('change');
		
		$('#deviceselectrow [name="device_serial_id_html"]').val($device_serial_id_html);
		$('#deviceselectrow [name="device_login_html"]').val($device_login_html);
		$('#deviceselectrow [name="device_note_html"]').val($device_note_html);

		$(this).parents('.item-row').remove();
	});

	$(document).on('click', '.parts_body .delme, .services_body .delme, .extra_body .delme, .products_body .delme, .wc_devices_row .delme', function(e) {
		e.preventDefault();

		$(this).parents('.item-row').remove();

		if( $(this).attr('dt_brand_device') ) {
			//Remove part selection dropdown
			if ($('#selectpartscontainer').children().length > 1) {
				$('#selectpartscontainer div.device_id'+$(this).attr('dt_brand_device')).remove();
				update_devices_dropdown();
  		   }
		}
		wc_grand_total_calculations();
	});
	
	$(document).on("click", '[data-open="update_maintenance_reminder"]', function(e) {
		e.preventDefault();

		var recordID 	= $(this).attr("recordid");

		if (history.pushState) {
			var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=wc-computer-rep-shop-handle&reminder_id='+recordID;
			window.history.pushState({path:newurl},'',newurl);
		}
		$("p.addmaintenancereminderbtn").html('');
		$("#maintenancereminderReveal #replacement_part_reminder").html('');

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_reload_maintenance_update',
				'recordID': recordID 
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('#maintenancereminderReveal #replacement_part_reminder').html("<div class='spinner is-active'>Loading...</div>");
			},
			success: function(response) {
				var message 	= response.message;
				var success 	= response.success;
				
				$('#maintenancereminderReveal #replacement_part_reminder').html(message);
				$('#maintenancereminderReveal').foundation('toggle');
			}
		});
	});

	$(document).on("click", '[data-open="manualRequestFeedback"]', function(e) {
		e.preventDefault();

		var recordID 	= $(this).attr("recordid");
		var data_security = $(this).attr("data-security");

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_request_feedback',
				'recordID': recordID,
				'data_security': data_security
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('.request_feedback_message').html("<div class='spinner is-active'>Loading...</div>");
			},
			success: function(response) {
				var message 	= response.message;
				var success 	= response.success;
				
				$('.request_feedback_message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

				$("#reloadFeedbackRequestFields").load(window.location + " #reloadFeedbackRequestFields");
				$("#wc_order_notes").load(window.location + " #wc_order_notes");
			}
		});
	});

	$(document).on("click", '[data-open="addjoblistpaymentreveal"]', function(e) {
		e.preventDefault();

		var recordID 	= $(this).attr("recordid");

		$("#addjoblistpaymentreveal #replacementpart_joblist_formfields").html('');

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_add_joblist_payment_form_output',
				'recordID': recordID 
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('#addjoblistpaymentreveal #replacementpart_joblist_formfields').html("<div class='spinner is-active'>Loading...</div>");
			},
			success: function(response) {
				var message 	= response.message;
				var success 	= response.success;
				
				$('#addjoblistpaymentreveal #replacementpart_joblist_formfields').html(message);
				//$('#addjoblistpaymentreveal').foundation('toggle');
			}
		});
	});

	$(document).on("click", '[data-open="wcrbduplicatejob"]', function(e) {
		e.preventDefault();

		var recordID 	= $(this).attr("recordid");

		$("#wcrbduplicatejob #replacementpart_dp_page_formfields").html('');

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_return_duplicate_job_fields',
				'recordID': recordID 
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('#wcrbduplicatejob #replacementpart_dp_page_formfields').html("<div class='spinner is-active'>Loading...</div>");
			},
			success: function(response) {
				var message 	= response.message;
				var success 	= response.success;
				
				$('#wcrbduplicatejob #replacementpart_dp_page_formfields').html(message);
				//$('#addjoblistpaymentreveal').foundation('toggle');
			}
		});
	});

	//Change Tax Status Functionality
	$(document).on("click", ".change_tax_status", function(e, target){
		e.preventDefault();

		var recordID 	= $(this).attr("data-value");
		var recordType 	= $(this).attr("data-type");
		var security = $(this).attr('data-security');

		if(recordID == "" && recordType == "") {
			alert("Please select correct value");
		} else {
			
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_update_tax_or_status',
					'recordID': recordID, 
					'recordType': recordType,
					'data_security': security
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					if ( recordType == 'thePayment' ) {
						$('#paymentstatusmessage').html("<div class='spinner is-active'></div>");
					} else {
						$('.form-update-message').html("<div class='spinner is-active'></div>");
					}
				},
				success: function(response) {
					var message 	= response.message;
					var success 	= response.success;
					
					$('.form-update-message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
	
					if(recordType == "tax") {
						//$('#poststuff_wrapper').load(document.URL +  ' #poststuff_wrapper');
						$("#poststuff_wrapper").load(document.location + " #poststuff");
					} else if(recordType == "status" || recordType == "inventory_count") {
						$("#job_status_wrapper").load(document.location + " #status_poststuff");
					} else if(recordType == "paymentStatus") {
						$("#payment_status_wrapper").load(document.location + " #paymentStatus_poststuff");
					} else if ( recordType == 'thePayment' ) {
						$("#payments_received_INjob").load(window.location + " #payments_received_INjob", function() {
							wc_grand_total_calculations();
						});
						$("#wc_order_notes").load(window.location + " #wc_order_notes");

						$( "#paymentstatusmessage" ).html( message );

						$("#thepaymentstable").load(window.location + " #thepaymentstable");
					}
				}
			});
		}
	});

	//Change Tax Status Functionality
	$(document).on("submit", "#purchaseVerifiction", function(e, target){
		e.preventDefault();

		var $userEmail 		= $("#userEmail").val();
		var $SpurchaseCode 	= $("#purchaseCode").val();
		var $wcrb_nonce_activation_field = $("#wcrb_nonce_activation_field").val();

		if($userEmail == "" && $SpurchaseCode == "") {
			alert("Please enter both values!");
		} else {
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_check_and_verify_purchase',
					'purchaseCode': $SpurchaseCode, 
					'userEmail': $userEmail,
					'wcrb_nonce_activation_field': $wcrb_nonce_activation_field
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$('.purchase_verification_alert').html("<div class='spinner is-active'></div>");
				},
				success: function(response) {
					var message 	= response.message;
					var success 	= response.success;
					
					$('.purchase_verification_alert').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
					$("#purchase_box_update").load(window.location + " #purchase_box_update > *");
				}
			});
		}
	});
	
	//Change Tax Status Functionality
	$(document).on("change", ".update_status", function(e, target){
		e.preventDefault();

		var recordID 	= $(this).attr("data-post");
		var statusValue	= $(this).val();
		var wcrb_nonce_adrepairbuddy_field = $("#wcrb_nonce_adrepairbuddy_field").val();

		if(recordID == "") {
			alert("Please select correct value");
		} else {
			
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_update_job_status',
					'recordID': recordID,
					'orderStatus': statusValue,
					'wcrb_nonce_adrepairbuddy_field': wcrb_nonce_adrepairbuddy_field
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$( "table.wp-list-table" ).prepend( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message 	= response.data;
					
					$( "table.wp-list-table" ).prepend( message );

					//$('#poststuff_wrapper').load(document.URL +  ' #poststuff_wrapper');
					$("#wpbody").load(document.location + " #wpbody-content");
				}
			});
		}
	});

	$(document).on("change", "select[data-type='update_job_priority']", function(e, target){
		e.preventDefault();

		var recordID 		= $(this).attr("recordid");
		var priorityValue	= $(this).val();
		var nonce 			= $(this).attr('data-security');

		if(recordID == "") {
			alert("Please select correct value");
		} else {
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_update_job_priority',
					'recordID': recordID,
					'priority': priorityValue,
					'nonce': nonce
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',

				beforeSend: function() {
					$( "table.wp-list-table, .theajaxreturned" ).html( "<div class='priority_returnms spinner is-active'></div>" );
				},
				success: function(response) {
					var message = response.data;
					
					$( "table.wp-list-table, .theajaxreturned" ).html('<div class="priority_returnms ">'+ message + '</div>' );

					var wcStatusWrap = $('.wc_order_status_wrap');
					if (wcStatusWrap.length > 0) {
						//Do nothing here
						setTimeout(function() {
							$('.theajaxreturned').html('');
						}, 3000);
					} else {
						// Reload only if message wasn't prepended to .wc_order_status_wrap
						$("#wpbody").load(document.location + " #wpbody-content");
					}
				}
			});
		}
	});

	//Change Tax Status Functionality
	$(document).on("click", "[data-type='submit-wc-cr-history']", function(e, target){
		e.preventDefault();

		var recordID 		= $(this).attr("data-job-id");
		var recordName		= $('[name="add_history_note"]').val();
		var recordType	  	= $('[name="wc_history_type"]').val();
		var emailCustomer	= $('[name="wc_email_customer_manual_msg"]:checked').val();
		
		if(recordID == "") {
			alert("Please select correct value");
		} else {
			
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wc_add_job_history_manually',
					'recordID': recordID,
					'recordName': recordName,
					'emailCustomer': emailCustomer,
					'recordType': recordType
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$( ".add_history_log" ).html( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message 	= response.message;
				
					$( ".add_history_log" ).html( message );
					$("#wc_order_notes").load(window.location + " #wc_order_notes");
				}
			});
		}
	});

	$(document).on("click", 'button#WCRB_submit_device_prices', function(e) {
		e.preventDefault();

		var $wcRB_job_ID = $(this).attr('data-job-id');
		var $nonce = $("#wcrb_nonce_setting_device_field").val();
		var $device_id = $('[name="device_id[]"]').serializeArray();
		var $device_price = $('[name="device_price[]"]').serializeArray();
		var $device_status = $('[name="device_status[]"]').serializeArray();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_update_the_prices',
				'device_id': $device_id,
				'device_price': $device_price,
				'device_status': $device_status,
				'wcrb_nonce_setting_device_field': $nonce,
				'wcrb_job_id': $wcRB_job_ID,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".prices_message" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;

				$('.prices_message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				
				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("click", 'button#WCRB_submit_type_prices', function(e) {
		e.preventDefault();

		var $wcRB_job_ID = $(this).attr('data-job-id');
		var $nonce = $("#wcrb_nonce_setting_device_field").val();
		var $type_id = $('[name="type_id[]"]').serializeArray();
		var $type_price = $('[name="type_price[]"]').serializeArray();
		var $type_status = $('[name="type_status[]"]').serializeArray();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_update_the_prices',
				'type_id': $type_id,
				'type_price': $type_price,
				'type_status': $type_status,
				'wcrb_nonce_setting_device_field': $nonce,
				'wcrb_job_id': $wcRB_job_ID,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".prices_message" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;

				$('.prices_message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				
				$(".reloadthedevices").load(window.location + " .reloadthedevices");

				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("click", 'button#WCRB_submit_brand_prices', function(e) {
		e.preventDefault();

		var $wcRB_job_ID = $(this).attr('data-job-id');
		var $nonce = $("#wcrb_nonce_setting_device_field").val();
		var $brand_id = $('[name="brand_id[]"]').serializeArray();
		var $brand_price = $('[name="brand_price[]"]').serializeArray();
		var $brand_status = $('[name="brand_status[]"]').serializeArray();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_update_the_prices',
				'brand_id': $brand_id,
				'brand_price': $brand_price,
				'brand_status': $brand_status,
				'wcrb_nonce_setting_device_field': $nonce,
				'wcrb_job_id': $wcRB_job_ID,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".prices_message" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;

				$('.prices_message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				
				$(".reloadthedevices").load(window.location + " .reloadthedevices");

				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("click", '[target="wc_rb_generate_woo_order"]', function(e) {
		e.preventDefault();

		var $wcRB_job_ID = $(this).attr('recordid');
		var wcrb_nonce_adrepairbuddy_field = $("#wcrb_nonce_adrepairbuddy_field").val();
		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_generate_woocommerce_order',
				'wcrb_submit_type': 'create_the_order',
				'wcrb_job_id': $wcRB_job_ID,
				'wcrb_nonce_adrepairbuddy_field': wcrb_nonce_adrepairbuddy_field
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".order_action_messages" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;
				

				$('.order_action_messages').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				
				$("#payments_received_INjob").load(window.location + " #payments_received_INjob", function() {
					wc_grand_total_calculations();
				});
			
				$("#wc_order_notes").load(window.location + " #wc_order_notes");

				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("change", 'input[title_target]', function(e) {
		var $target = $(this).attr('title_target');
		var $value = $(this).val();
		$($target).html($value);
	});

	$( '.wcrb_select_customers' ).select2({
		ajax: {
			url: ajaxurl,
			dataType: 'json',
			delay: 250, // delay in ms while typing when to perform a AJAX search
			data: function( params ) {
				return {
					q: params.term, // search query
					security: $('#wcrb_nonce_adrepairbuddy_field').val(), // security nonce
					action: 'wcrb_return_customer_data_select2' // AJAX action for admin-ajax.php
				}
			},
			processResults: function( data ) {
				var options = []
				if( data ) {
					// data is the array of arrays with an ID and a label of the option
					$.each( data, function( index, text ) {
						options.push( { id: text[0], text: text[1] } )
					})
				}
				return {
					results: options
				}
			},
			cache: true
		},
		minimumInputLength: 3
	});

	jQuery(document).on('change', '#wc_booking_default_brand, #wc_booking_default_type', function() {
		var brand = jQuery('#wc_booking_default_brand').val();
		var type = jQuery('#wc_booking_default_type').val();
		var device = jQuery('#wc_booking_default_device').val();
		var data_security = jQuery(this).attr('data-security');

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_update_device_options',
				'brand': brand, 
				'type' : type,
				'device': device,
				'security' : data_security
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$('#wc_booking_default_device').html("<option>Loading ...</option>");
			},
			success: function(response) {
				var message 		= response.message;

				$('#wc_booking_default_device').html(message);
			}
		});
	});

	$(document).on("change", '#updatecustomer', function(e) {
		var $value = $(this).val();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_reload_customer_data',
				'wcrb_load_reminder_form': 'yes',
				'post_user_id': $value,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',
			success: function(response) {
				var message = response.message;

				$('.wcrb_customer_info').html(message);
			}
		});
	});

	$(document).on("click", '[target="wcrb_generate_estimate_to_order"]', function(e) {
		e.preventDefault();

		var $wcRB_job_ID = $(this).attr('recordid');
		var wcrb_nonce_adrepairbuddy_field = $("#wcrb_nonce_adrepairbuddy_field").val();
		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_generate_repair_order_from_estimate',
				'wcrb_submit_type': 'create_the_order',
				'wcrb_estimate_id': $wcRB_job_ID,
				'wcrb_nonce_adrepairbuddy_field': wcrb_nonce_adrepairbuddy_field
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".order_action_messages" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;
				
				$('.order_action_messages').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("click", '[target="wcrb_send_estimate_to_customer"]', function(e) {
		e.preventDefault();

		var $wcRB_job_ID = $(this).attr('recordid');

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_send_estimate_to_customer',
				'wcrb_submit_type': 'send_the_email',
				'wcrb_estimate_id': $wcRB_job_ID,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".order_action_messages" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;
				
				$('.order_action_messages').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("click", '[data-open="send_reminder_test"]', function(e) {
		e.preventDefault();

		var $reminderID = $(this).attr('recordid');

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_load_reminder_test_form',
				'wcrb_load_reminder_form': 'yes',
				'reminder_id': $reminderID,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".send_test_reminder" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;

				$('.send_test_reminder').html(message);
				
				if (success == 'YES') {
					//Something on Success
				}
			}
		});
	});

	$(document).on("submit", "#submitTestReminderForm", function(e) {
		e.preventDefault();

		var $reminderID	 = $('[name="testReminderID"]').val();
		var $emailTestTo = $('[name="testReminderMailTo"]').val();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wcrb_send_reminder_test_form',
				'wcrb_send_reminder_form': 'yes',
				'reminder_id': $reminderID,
				'testmailto': $emailTestTo,
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".submittheremindertest" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;

				$('.submittheremindertest').html(message);
				
				if (success == 'YES') {

				}
			}
		});
	});

	$(document).on("submit", 'form[name="wcrb_form_submit_payment"]', function(e) {
		e.preventDefault();

		var $wcrb_payment_note			 = $('[name="wcrb_payment_note"]').val();
		var $wcrb_payment_datetime 		 = $('[name="wcrb_payment_datetime"]').val();
		var $wcRB_payment_status 		 = $('[name="wcRB_payment_status"]').val();
		var $wcRB_payment_method 		 = $('[name="wcRB_payment_method"]').val();
		var $wcRb_payment_amount 		 = $('[name="wcRb_payment_amount"]').val();
		var $wcrb_transaction_id 		 = $('[name="wcrb_transaction_id"]').val();
		var $wcrb_job_id				 = $('[name="wcrb_job_id"]').val();
		var $wcrb_nonce_add_payment_field = $('[name="wcrb_nonce_add_payment_field"]').val();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_add_payment_into_job',
				'wcrb_payment_note': $wcrb_payment_note,
				'wcrb_payment_datetime': $wcrb_payment_datetime,
				'wcRB_payment_status': $wcRB_payment_status,
				'wcRB_payment_method': $wcRB_payment_method,
				'wcrb_transaction_id': $wcrb_transaction_id,
				'wcRb_payment_amount': $wcRb_payment_amount,	
				'wcrb_job_id': $wcrb_job_id,
				'wcrb_nonce_add_payment_field': $wcrb_nonce_add_payment_field
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".wcrb_payment_status_msg" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;
				
				$('.wcrb_payment_status_msg').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				$("#payments_received_INjob").load(window.location + " #payments_received_INjob", function() {
					wc_grand_total_calculations();
				});
				$("#wc_order_notes").load(window.location + " #wc_order_notes");

				if (success == 'YES') {
					$('form[name="wcrb_form_submit_payment"]').trigger('reset');
					$('.wcrb_amount_paying').html('0.00');
					$('select[name="wc_payment_status"]').val($wcRB_payment_status).change();
				}
			}
		});
	});

	$(document).on("submit", 'form[name="wcrb_jl_form_submit_payment"]', function(e) {
		e.preventDefault();

		var $wcrb_payment_note			 = $('[name="wcrb_payment_note"]').val();
		var $wcrb_payment_datetime 		 = $('[name="wcrb_payment_datetime"]').val();
		var $wcRB_payment_status 		 = $('[name="wcRB_payment_status"]').val();
		var $wcRB_payment_method 		 = $('[name="wcRB_payment_method"]').val();
		var $wcRb_payment_amount 		 = $('[name="wcRb_payment_amount"]').val();
		var $wcrb_transaction_id 		 = $('[name="wcrb_transaction_id"]').val();
		var $wcrb_job_id				 = $('[name="wcrb_job_id"]').val();
		var $wcRB_after_jobstatus 		 = $('[name="wcRB_after_jobstatus"]').val();
		var $wcrb_nonce_add_payment_field = $('[name="wcrb_nonce_add_payment_field"]').val();

		$.ajax({
			type: 'POST',
			data: {
				'action': 'wc_rb_add_payment_into_job',
				'wcrb_payment_note': $wcrb_payment_note,
				'wcrb_payment_datetime': $wcrb_payment_datetime,
				'wcRB_payment_status': $wcRB_payment_status,
				'wcRB_payment_method': $wcRB_payment_method,
				'wcRb_payment_amount': $wcRb_payment_amount,
				'wcrb_transaction_id': $wcrb_transaction_id,
				'wcRB_after_jobstatus': $wcRB_after_jobstatus,
				'wcrb_job_id': $wcrb_job_id,
				'wcrb_nonce_add_payment_field': $wcrb_nonce_add_payment_field
			},
			url: ajax_obj.ajax_url,
			dataType: 'json',

			beforeSend: function() {
				$( ".set_addpayment_joblist_message" ).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var success = response.success;
				
				$('.set_addpayment_joblist_message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

				if (success == 'YES') {
					$('form[name="wcrb_jl_form_submit_payment"]').trigger('reset');
					$('.wcrb_amount_paying').html('0.00');
					$("#post-"+$wcrb_job_id).load(document.location + " #post-"+$wcrb_job_id+">*");
				}
			}
		});
	});

	$(document).on("submit", 'form[name="wcrb_duplicate_page_return"]', function(e) {
		e.preventDefault();

		var $form 	= $(this);
		var formData = $form.serialize();
		var $perform_act = "wcrb_duplicate_page_perform";
		var $success_class = ".duplicate_page_return_message";

		$.ajax({
			type: 'POST',
			data: formData + '&action='+$perform_act,
			url: ajax_obj.ajax_url,
			async: true,
			mimeTypes:"multipart/form-data",
			dataType: 'json',
			beforeSend: function() {
				$($success_class).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				var redirect_url = response.redirect_url;
				
				$($success_class).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

				if (redirect_url != 'NO') {
					window.location.href = redirect_url;
				}
			}
		});
	});

	$(document).on("submit", 'form[name="wcrb_send_test_sms"]', function(e) {
		e.preventDefault();

		var $form 	= $(this);
		var formData = $form.serialize();
		var $perform_act = "wcrb_process_test_sms";
		var $success_class = ".smstest_response";

		$.ajax({
			type: 'POST',
			data: formData + '&action='+$perform_act,
			url: ajax_obj.ajax_url,
			async: true,
			mimeTypes:"multipart/form-data",
			dataType: 'json',
			beforeSend: function() {
				$($success_class).html( "<div class='spinner is-active'></div>" );
			},
			success: function(response) {
				var message = response.message;
				
				$($success_class).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
			}
		});
	});
	
	jQuery(document).ready(function() {
		$(document).on('click', 'a[data-alert-msg]', function(e) {
			// Get the alert message from the data attribute
			var alertMsg = $(this).data('alert-msg');
			
			// Show the alert message
			alert(alertMsg);
			
			// Prevent the default anchor behavior
			e.preventDefault();
			
			// Show status message (for demo purposes)
			$('#statusMessage').fadeIn().delay(2000).fadeOut();
		});
		
        $('.bc-product-search').select2({
            ajax: {
                url: ajaxurl,
                data: function (params) {
                    return {
                        term         : params.term,
                        action       : 'woocommerce_json_search_products_and_variations',
                        security: $(this).attr('data-security'),
						exclude_type : $( this ).data( 'exclude_type' ),
						display_stock: $( this ).data( 'display_stock' )
                    };
                },
                processResults: function( data ) {
                    var terms = [];
                    if ( data ) {
                        $.each( data, function( id, text ) {
                            terms.push( { id: id, text: text } );
                        });
                    }
                    return {
                        results: terms
                    };
                },
                cache: true
            }
        });

		$(document).on('click', '.wcRbJob_services_wrap #reloadTheExtraFields .delmeextrafield', function(e) {
			var $array_index = $(this).attr("recordid");
			var $post_value = $(this).attr("data-value");
	
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_delete_job_est_extra_field',
					'array_index': $array_index,
					'post_id':$post_value 
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$('.wcRbJob_services_wrap #reloadTheExtraFields .attachment_body_message').html("<div class='spinner is-active'>Loading...</div>");
				},
				success: function(response) {
					var message 	= response.message;
					var success 	= response.success;
					
					$('.wcRbJob_services_wrap #reloadTheExtraFields .attachment_body_message').html(message);
					
					if (success == 'YES') {
						$("#reloadTheExtraFields").load(window.location + " .extrafieldstable");
					}
				}
			});
		});

		$(document).on('click', 'a#addnewpartvariation', function(e) {
			e.preventDefault();
			var $_nonce   = $('#wc_parts_features_sub').val();
			var $_part_id = $(this).attr('data-job-id');

			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_append_new_part',
					'nonce': $_nonce,
					'part_id': $_part_id,
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$(".msgabovevar").html( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message = response.message;
					var success = response.success;
					var partdata = response.partdata;

					$(".msgabovevar").html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

					$('#parentofpartvariations').foundation('destroy');
					$("#parentofpartvariations").append(partdata);
					$("#parentofpartvariations").foundation();
					$("#parentofpartvariations").foundation('up', $("#parentofpartvariations .accordion-content"));

					// Get the last accordion item
					const $lastItem = $('#parentofpartvariations [data-accordion-item]').last();
        
					// Get the accordion container
					const $accordion = $lastItem.closest('[data-tab-content]');
					$("#parentofpartvariations").foundation('down', $accordion);	
				}
			});
		});

		$(document).on('click', 'button.WCRB_submit_device_part_prices', function(e) {
			e.preventDefault();
			var $_part_id = $(this).attr('data-job-id');
			var $_part 	  = $(this).attr('data-part-identifier');
			var $_nonce   = $('#wc_parts_features_sub').val();

			var $device_id 			= $('[name="'+$_part+'_device_id[]"]').serializeArray();
			var $manufacturing_code = $('[name="'+$_part+'_device_manufacturing_code[]"]').serializeArray();
			var $stock_code 		= $('[name="'+$_part+'_device_stock_code[]"]').serializeArray();
			var $device_price 		= $('[name="'+$_part+'_device_price[]"]').serializeArray();
			var $device_status 		= $('[name="'+$_part+'_device_status[]"]').serializeArray();

			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_update_parts_prices',
					'part_id': $_part_id,
					'part': $_part,
					'nonce': $_nonce,
					'device_id': $device_id,
					'manufacturing_code': $manufacturing_code,
					'stock_code': $stock_code,
					'device_price': $device_price,
					'device_status': $device_status,
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$( ".prices_message_"+$_part ).html( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message = response.message;
					var success = response.success;
					$(".prices_message_"+$_part).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				}
			});
		});

		$(document).on("click", 'button.WCRB_submit_brand_part_prices', function(e) {
			e.preventDefault();
			var $_part_id = $(this).attr('data-job-id');
			var $_part 	  = $(this).attr('data-part-identifier');
			var $_nonce   = $('#wc_parts_features_sub').val();

			var $brand_id 			= $('[name="'+$_part+'_brand_id[]"]').serializeArray();
			var $manufacturing_code = $('[name="'+$_part+'_brand_manufacturing_code[]"]').serializeArray();
			var $stock_code 		= $('[name="'+$_part+'_brand_stock_code[]"]').serializeArray();
			var $brand_price 		= $('[name="'+$_part+'_brand_price[]"]').serializeArray();
			var $brand_status 		= $('[name="'+$_part+'_brand_status[]"]').serializeArray();

			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_update_parts_prices',
					'part_id': $_part_id,
					'part': $_part,
					'nonce': $_nonce,
					'brand_id': $brand_id,
					'manufacturing_code': $manufacturing_code,
					'stock_code': $stock_code,
					'brand_price': $brand_price,
					'brand_status': $brand_status,
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$( ".prices_message_"+$_part ).html( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message = response.message;
					var success = response.success;
					$(".prices_message_"+$_part).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				}
			});
		});

		$(document).on("click", 'button.WCRB_submit_type_part_prices', function(e) {
			e.preventDefault();
			var $_part_id = $(this).attr('data-job-id');
			var $_part 	  = $(this).attr('data-part-identifier');
			var $_nonce   = $('#wc_parts_features_sub').val();

			var $type_id 			= $('[name="'+$_part+'_type_id[]"]').serializeArray();
			var $manufacturing_code = $('[name="'+$_part+'_type_manufacturing_code[]"]').serializeArray();
			var $stock_code 		= $('[name="'+$_part+'_type_stock_code[]"]').serializeArray();
			var $type_price 		= $('[name="'+$_part+'_type_price[]"]').serializeArray();
			var $type_status 		= $('[name="'+$_part+'_type_status[]"]').serializeArray();

			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_update_parts_prices',
					'part_id': $_part_id,
					'part': $_part,
					'nonce': $_nonce,
					'type_id': $type_id,
					'manufacturing_code': $manufacturing_code,
					'stock_code': $stock_code,
					'type_price': $type_price,
					'type_status': $type_status,
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$( ".prices_message_"+$_part ).html( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message = response.message;
					var success = response.success;
					$(".prices_message_"+$_part).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');
				}
			});
		});

		$(document).on("click", '#WCRB_update_default_prices', function(e) {
			e.preventDefault();
			var $_addition = '';
			var $_part_id = $(this).attr('data-job-id');
			var $_part 	  = $(this).attr('data-part-identifier');
			var $_nonce   = $('#wc_parts_features_sub').val();
			if ( $_part != 'default' ) {
				$_addition = $_part+'_';
			}
	
			var $part_title = $('[name="'+$_addition+'part_title"]').val();
			var $manufacturing_code = $('[name="'+$_addition+'manufacturing_code"]').val();
			var $stock_code = $('[name="'+$_addition+'stock_code"]').val();
			var $price = $('[name="'+$_addition+'price"]').val();
			var $wc_use_tax = $('[name="'+$_addition+'wc_use_tax"]').val();
			var $warranty = $('[name="'+$_addition+'warranty"]').val();
			var $core_features = $('[name="'+$_addition+'core_features"]').val();
			var $capacity = $('[name="'+$_addition+'capacity"]').val();
			var $installation_charges = $('[name="'+$_addition+'installation_charges"]').val();
			var $installation_message = $('[name="'+$_addition+'installation_message"]').val();
	
			$.ajax({
				type: 'POST',
				data: {
					'action': 'wcrb_update_part_meta',
					'part_id': $_part_id,
					'part': $_part,
					'nonce': $_nonce,
					'part_title': $part_title,
					'manufacturing_code': $manufacturing_code,
					'stock_code': $stock_code,
					'price': $price,
					'wc_use_tax': $wc_use_tax,
					'warranty': $warranty,
					'core_features': $core_features,
					'capacity': $capacity,
					'installation_charges': $installation_charges,
					'installation_message': $installation_message
				},
				url: ajax_obj.ajax_url,
				dataType: 'json',
	
				beforeSend: function() {
					$( ".updatepricing_"+$_part ).html( "<div class='spinner is-active'></div>" );
				},
				success: function(response) {
					var message = response.message;
					var success = response.success;
					$(".updatepricing_"+$_part).html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

					if (success == 'YES') {
						$('form#post').submit();
					}
				}
			});
		});
    });//document ready

    $(document).on('submit', '#addExpenseForm', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $messageBox = $('.addexpense-form-message');

        $.ajax({
            url: ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            beforeSend: function () {
                $messageBox.html('<div class="callout warning"><span class="spinner is-active"></span> Processing...</div>');
            },
            success: function (response) {

                if (response.success) {
                    $messageBox.html(
                        '<div class="callout success" data-closable>' +
                        response.data.message +
                        '<button class="close-button" aria-label="Dismiss alert" type="button" data-close>' +
                        '<span aria-hidden="true">&times;</span>' +
                        '</button></div>'
                    );
                    // Reset form
                    $form[0].reset();

                    $("#reloadExpensesTable").load(window.location + " #reloadExpensesTable");
					$("#wc_order_notes").load(window.location + " #wc_order_notes");
					
                    // Close modal after short delay
                    setTimeout(function () {
                        $('#addExpenseModal').foundation('close');
						wc_grand_total_calculations();
                    }, 1200);

                } else {
                    $messageBox.html(
                        '<div class="callout alert" data-closable>' +
                        response.data.message +
                        '<button class="close-button" aria-label="Dismiss alert" type="button" data-close>' +
                        '<span aria-hidden="true">&times;</span>' +
                        '</button></div>'
                    );
                }
            },
            error: function () {
                $messageBox.html(
                    '<div class="callout alert">' +
                    'Something went wrong. Please try again.' +
                    '</div>'
                );
            }
        });
    });

	$(document).on('click', '.delmedeviceextrafield', function(e) {
		e.preventDefault();
		$(this).closest('tr.wcrb_repater_field').remove();
	});

	$(document).on('click', '.adddeviceextrafield', function(e){
		e.preventDefault();

		var $original_html = $('.wcrb_additional_device_fields_wrap table tr.wcrb_original').html();
		var $produceHtml = '<tr class="wcrb_repater_field">'+$original_html+'</tr>';

		$('.wcrb_additional_device_fields_wrap table tbody').append( $produceHtml );
		addextradevicedelete();
	});

	function addextradevicedelete() {
		$(document).ready(function(){
			var $deleteoption = '<a class="delme delmedeviceextrafield" href="#" title="Remove row"><span class="dashicons dashicons-trash"></span></a>';
			$('.wcrb_additional_device_fields_wrap table tbody tr:last-child > td.wc_device_name').prepend($deleteoption);

			$('.wcrb_additional_device_fields_wrap table tbody tr:last-child > td input[name="rb_device_field_label[]"]').val('');
			$('.wcrb_additional_device_fields_wrap table tbody tr:last-child > td input[name="rb_device_field_id[]"]').val('');
	   });
	}

	jQuery( document ).ready( function( $ ) {
		$( '#post' ).submit( function( e ) {
			var $postType = $('#post_type').val();
			if ( $postType == 'rep_estimates' || $postType == 'rep_jobs' ) {
				var $pendingDevice = $('input[name="device_serial_id_html"]').val();
				if ( $pendingDevice.length ) {
					var $message = $('input[name="messageforadddevice"]').val();
					alert( $message );
					return false;
				}
			}
		} );
	} );
	
})(jQuery); //jQuery main function ends strict Mode on