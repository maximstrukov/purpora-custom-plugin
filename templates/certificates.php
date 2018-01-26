<h2>Certificates</h2>
<h4>Active Certificates</h4>
<ul>
	<?php foreach ($certificates as $certificate): ?>
		<?php 
		$exp_date = get_post_meta($certificate['id'], 'exp_date', true); 
		if ($exp_date > date('Y-m-d H:i:s')):
		?>
			<li class='woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--dashboard'>
				<a href='<?php echo get_permalink($certificate['id']) ?>' target='_blank'><?php echo get_the_title($certificate['course']) ?></a>
			</li>
		<?php
		endif;
		?>
	<?php endforeach; ?>
</ul>

<h4>Expired Certificates</h4>
<ul>
	<?php foreach ($certificates as $certificate): ?>
		<?php 
		$exp_date = get_post_meta($certificate['id'], 'exp_date', true); 
		if ($exp_date <= date('Y-m-d H:i:s')):
		?>
			<li class='woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--dashboard'>
				<?php echo get_the_title($certificate['course']) ?>
			</li>
		<?php
		endif;
		?>
	<?php endforeach; ?>
</ul>