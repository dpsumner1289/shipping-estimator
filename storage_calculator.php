<?php 
	$rate_arrs = array(
		'short_term_storage_rates' => array('xs','s','m','l','xl','xxl','oversized'),
		'summer_storage_rates' => array('xs','s','m','l','xl','xxl','oversized'),
		'abroad_storage_rates' => array('xs','s','m','l','xl','xxl','oversized'),
	); 
?> 

<?php 
	// get the form redirect url for later
	$form = GFAPI::get_form(4);
	foreach($form['confirmations'] as $confirmation){
		$form_redirect_url = get_the_permalink($confirmation['pageId']);
		break;
	}
?>
<div class="storage-calculator">
	<div class="sc-title-wrap">
		<h1 class="sc-title">Storage Type</h1>
		<div class="sc-type-wrap">
			<div class="sc-type" data-type="short_term_storage_rates">
				<span class="type-name">Short Term Storage</span>
				<span class="description"><?php echo get_option('options_short_term_storage_rates_rate_description'); ?></span>
			</div>
			<div class="sc-type" data-type="summer_storage_rates">
				<span class="type-name">Summer Storage</span>
				<span class="description"><?php echo get_option('options_summer_storage_rates_rate_description'); ?></span>
			</div>
			<div class="sc-type" data-type="abroad_storage_rates">
				<span class="type-name">Abroad Storage</span>
				<span class="description"><?php echo get_option('options_abroad_storage_rates_rate_description'); ?></span>
			</div>
			<div class="sc-type" data-type="long_term_storage">
				<span class="type-name">Long Term Storage</span>
				<span class="description"><?php echo get_option('options_long_term_storage_rates_rate_description'); ?></span>
			</div>
		</div>
	</div>
	<div class="sc-contact-us" style="display:none;">
		<?php echo do_shortcode('[gravityform id="5" title="false" description="false" ajax="true"]'); ?>
	</div>
	<div class="sc-content-wrap" style="display:none;">
		<div class="sc-item-wrap">
			<?php $item_types = get_terms(array('taxonomy' => 'item_type')); ?>
			<?php if($item_types) : ?>
				<?php $item_count = 0; ?>
				<?php foreach($item_types as $item_type) : ?>
					<div class="item-type-wrap">
						<?php $open = get_term_meta($item_type->term_id, 'expanded_by_default', true); ?>
						<?php $arrow = $open ? '<i class="fal fa-angle-down"></i>': '<i class="fal fa-angle-up"></i>'; ?>
						<h2 class="item-category">
							<span>
								<?php echo $item_type->name; ?>
								<?php if($description = $item_type->description) : ?>
									<span class="item-description"><?php echo $description; ?></span>
								<?php endif; ?>
							</span>
							<?php echo $arrow; ?>
						</h2>
						<div class="item-category-wrap"<?php if(!$open){echo ' style="display:none;"';} ?>>
			
							<?php
								$args = array(
									'post_type' => 'storage_item',
									'posts_per_page' => -1,
									'post_status' => 'publish',
									'tax_query' => array(
										array(
											'taxonomy' => 'item_type',
											'field' => 'term_id',
											'terms' => $item_type->term_id
										)
									)
								);
							?>
							<?php $items = new WP_Query($args); ?>
							<?php if($items->found_posts) : ?>
								<?php foreach($items->posts as $item) :  
										$item_size = get_field('item_size', $item->ID);
										$flat_rates = get_field('flat_rates', $item->ID);
										$short = $flat_rates['short_term_storage_rates'];
										$summer = $flat_rates['summer_storage_rates'];
										$abroad = $flat_rates['abroad_storage_rates'];
								?>
									<div class="sc-item storageoption">
										<span class="item-title"><?php echo $item->post_title; ?></span>
										<div class="tooltipclass" style="display: none;">
											<span class="item_rate"></span>
										</div>
										<span class="dots"></span>
										<input type="number" min="0" step="1" value="0" id="item-<?php echo $item_count; $item_count++; ?>" data-size="<?php the_field('item_size', $item->ID); ?>" data-rate="<?php the_field('flat_rate', $item->ID); ?>" data-short_term_storage_rates="<?php echo $short;?>" data-summer_storage_rates="<?php echo $summer;?>" data-abroad_storage_rates="<?php echo $abroad;?>" data-name="<?php echo $item->post_title; ?>" data-low="" data-high="" />
										<script type="text/javascript"></script>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>	
		<div class="sc-estimate-wrap">
			<h3 class="sc-total">STORAGE ESTIMATE</h3>
			<p class="total-range">$0.00</p>
			<div class="sc-item-list-wrap"></div>
			<div class="sc-cta">
				<span class="sc-validation-message"></span>
				<input class="estimate-email" type="email" placeholder="Your Email" required />
				<button class="button orange save-estimate">Save My Estimate</button>
				<a href="<?php echo get_site_url(); ?>/book-now/" class="button orange">Book Now</a>
			</div>
		</div>
	</div>
	
	<div class="sc-rate-wrap">
		<?php foreach($rate_arrs as $k => $rate_arr) : ?>
			<?php foreach($rate_arr as $rate) : ?>
				<div class="sc-rate" data-type="<?php echo $k; ?>" data-size="<?php echo $rate; ?>" data-rate="<?php the_field($k.'_'.$rate, 'option'); ?>"></div>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
	<script>
		jQuery(document).ready(function($){

			// save estimate
			$('.save-estimate').on('click', function(e){
				e.preventDefault();
				var item_list = [];
				$('.sc-item-list-wrap li').each(function(){
					item_list.push($('span', this).text());
				});

				$(this).html('<i class="fas fa-spinner fa-pulse"></i>');
				var data = {
					action : 'save_estimate',
					email : $('.estimate-email').val(),
					estimate : $('.total-range').text(),
					type : $('.sc-type.selected .type-name').text(),
					items : item_list,
				}

				$.post("<?php echo admin_url('admin-ajax.php'); ?>", data, function(response){
					if(response.success){
						// redirect on success
						window.location.href = "<?php echo $form_redirect_url; ?>";
					}else{
						$('.save-estimate').html('Save My Estimate');
						$('.sc-validation-message').html(response.message);
					}
				}, 'json');
			});
			// scroll sidebar for non mobile
			var sidebar_start = $('.storage-calculator').offset().top;
			$(window).scroll(function(){
				if($(window).width() > 750){
					var margin = $(window).scrollTop() - sidebar_start;
					if($(window).scrollTop() > sidebar_start){
						$('.sc-estimate-wrap').stop().animate({
					    	'marginTop':  margin + 'px',
					    }, 'slow');
					}
				}else{
					$('.sc-estimate-wrap').removeAttr('style');
				}
			});

			// pick storage type
			$('.sc-type').on('click', function(){
				$('.sc-type').removeClass('selected');
				$(this).addClass('selected');

				// populate high/low rate range on load
				$('.sc-item input').each(function(){
					populate_high_low_rates(this);
				});

				do_calculation();
				if($(this).data('type') != 'long_term_storage'){
					$('.sc-contact-us').hide();
					$('.sc-content-wrap').show('fast');				
				}else{
					$('.sc-content-wrap').hide();	
					$('.sc-contact-us').show('fast');			
				}
			});

			// open an item category section
			$('.item-category').on('click', function(){
				$(this).toggleClass('open');
				$(this).next('.item-category-wrap').slideToggle('fast');
			});

			// update a storage item quantity
			$('.sc-item input').on('change', function(){
				do_calculation();
			});

			// handle cta link
			$('.sc-cta a').on('click', function(e){
				e.preventDefault();
			});

			// remove item 
			$(document).on('click', '.remove-item', function(e){
				$('#'+ $(this).parent().data('id')).val(0);
				do_calculation();
			});

			// populate rate for each item on mouseover
			$(document).on('mouseenter', '.storageoption', function(event){
				var low = $('input', this).attr('data-low');
				var high = $('input', this).attr('data-high');
				var range = low;
				if(low != high){
					range = low + ' - ' + high;
				}
				$(this).find('span.item_rate').text(range);
			});			

			function populate_high_low_rates(input){
				var type = $('.sc-type.selected').data('type');
				var item_sizes = $(input).data('size').split(', ');	
				
				var short = $(input).data('short_term_storage_rates');
				var summer = $(input).data('summer_storage_rates');
				var abroad = $(input).data('abroad_storage_rates');
				
				var item_low = 0.00;
				var item_high = 0.00;
				var total_range = 0.00;

				for(var key in item_sizes){

					// get the rate
					var item_rate = parseFloat($('.sc-rate[data-type="'+ type +'"][data-size="'+ item_sizes[key] +'"]').data('rate'));
					if(item_sizes[key] == 'flat-rate'){	
						if(type == 'short_term_storage_rates') {
							item_rate = parseFloat(short);
							$(input).parent().find('span.item_rate').html(' $' + item_rate);
							$(input).attr('data-rate', short);
						} else if(type == 'summer_storage_rates') {
							item_rate = parseFloat(summer);
							$(input).parent().find('span.item_rate').html(' $' + item_rate);
							$(input).attr('data-rate', summer);

						}else if(type == 'abroad_storage_rates') {
							item_rate = parseFloat(abroad);
							$(input).parent().find('span.item_rate').html(' $' + item_rate);
							$(input).attr('data-rate', abroad);

						}
						// item_rate = parseFloat($(input).data('rate'));
						// $(input).parent().find('span.item_rate').html(' $' + item_rate);	
					}

					// find the high/low
					if(key == 0){
						item_low = item_rate;
						item_high = item_rate;
					}

					if(item_rate < item_low){
						item_low = item_rate;
					}

					if(item_rate > item_high){
						item_high = item_rate;
					}
				}
				

				// set the attributes
				$(input).attr('data-high', '$'+item_high.toFixed(2));
				$(input).attr('data-low', '$'+item_low.toFixed(2));
			}

			// do the estimating
			function do_calculation(){

				var type = $('.sc-type.selected').data('type');
				var total_low = 0.00;
				var total_high = 0.00;
				var item_list = '<ul class="sc-item-list">';

				$('.sc-item input').each(function(){
					var item_total_low = 0.00;
					var item_total_high = 0.00;
					var item_qty = $(this).val();

					if(item_qty.length && item_qty > 0){
						item_list += '<li data-id="'+ $(this).attr('id') +'"><i class="remove-item fas fa-times-circle"></i> <span>'+ $(this).data('name') + ' x' + item_qty + '</span></li>';
						var item_sizes = $(this).data('size').split(', ');
						for(var key in item_sizes){

							// get the rate
							var item_rate = parseFloat($('.sc-rate[data-type="'+ type +'"][data-size="'+ item_sizes[key] +'"]').data('rate'));
							if(item_sizes[key] == 'flat-rate'){
								item_rate = parseFloat($(this).attr('data-rate'));
								item_total_low = item_rate;
								item_total_high = item_rate;
							}
							
							// find the high/low
							if(key == 0){
								item_total_low = item_rate;
								item_total_high = item_rate;
							}

							if(item_rate < item_total_low){
								item_total_low = item_rate;
							}

							if(item_rate > item_total_high){
								item_total_high = item_rate;
							}
						}

						// set the low/high
						total_low += item_qty * item_total_low;
						total_high += item_qty * item_total_high;

					}
				});

				item_list += '</ul>';

				// do the total
				var total_range = '$' + total_low.toFixed(2);
				if(total_low !== total_high){
					total_range = '$' + total_low.toFixed(2) + ' - $' + total_high.toFixed(2);
				}
				$('.sc-estimate-wrap .total-range').text(total_range);

				// do the itemized list
				$('.sc-item-list-wrap').html(item_list);
			}

		});

	</script>
</div>