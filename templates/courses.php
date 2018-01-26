<h2>Available Courses</h2>
<ul>
	<?php foreach ($courses as $course): ?>
		<li class='woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--dashboard'>
			<a href='<?php echo get_permalink($course) ?>'><?php echo get_the_title($course) ?></a>
		</li>
	<?php endforeach; ?>
</ul>