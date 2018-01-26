<?php
/**
 * Plugin Name: Miller Media Customizations
 * Description: Customizing the Sensei plugin's logic, My Account page, tabs etc.
 * Version: 1.0.2
 * Author: Max Strukov ( Miller Media )
 * Author URI: www.millermedia.io
 */
 
add_action( 'init', function() {

	/*Setting the number of failed attempts to post meta field*/
    add_action('sensei_user_quiz_submitted', function($user_id, $quiz_id, $grade, $quiz_pass_percentage, $quiz_grade_type) {
		$failed_attempts = (array) get_post_meta($quiz_id, 'failed_attempts', true);
		$update = true;
		if ($quiz_pass_percentage > $grade) {
			if ($failed_attempts) {
				if (!$failed_attempts[0]) unset($failed_attempts[0]);
				if (array_key_exists($user_id, $failed_attempts)) $failed_attempts[$user_id]++;
				else $failed_attempts[$user_id] = 1;
			} else {
				$failed_attempts = array($user_id => 1);
			}
		} else {
			if (array_key_exists($user_id, $failed_attempts)) unset($failed_attempts[$user_id]);
			else $update = false;
		}
		if ($update) update_post_meta($quiz_id, 'failed_attempts', $failed_attempts);
	}, 20, 5);
	
	/*Check if user can view the lesson due to failed attempts*/
	add_filter( 'sensei_can_user_view_lesson', function($can_user_view_lesson, $lesson_id, $user_id) {
		if ($can_user_view_lesson) {
			$quiz_id = get_post_meta($lesson_id, '_lesson_quiz', true);
			$failed_attempts = (array) get_post_meta($quiz_id, 'failed_attempts', true);
			if (isset($failed_attempts[$user_id]) && $failed_attempts[$user_id] >= 2) $can_user_view_lesson = false;
		}
		return $can_user_view_lesson;
	}, 20, 3);
	
	/*Clear the failed attempts on Reset course*/
	add_action('sensei_user_course_reset', function($user_id, $course_id) {
		$quizzes = Sensei()->course->course_quizzes($course_id);
		foreach ($quizzes as $quiz):
			$failed_attempts = (array) get_post_meta($quiz, 'failed_attempts', true);
			if ($failed_attempts && array_key_exists($user_id, $failed_attempts)) {
				unset($failed_attempts[$user_id]);
				update_post_meta($quiz, 'failed_attempts', $failed_attempts);
			}
		endforeach;
	}, 20, 2 );
	
	/*Adding the alert message if the user has 2 failed attempts*/
	add_filter('sensei_user_quiz_status',function($params, $lesson_id, $user_id, $is_lesson) {
		if ($params['status'] == 'failed') {
			$quiz_id = get_post_meta($lesson_id, '_lesson_quiz', true);
			$failed_attempts = (array) get_post_meta($quiz_id, 'failed_attempts', true);
			if (isset($failed_attempts[$user_id]) && $failed_attempts[$user_id] >= 2) {
				add_action('sensei_single_quiz_content_inside_before', function() {
					echo "<div class='sensei-message alert'>You have failed this course twice. Please contact Purpora Engineering for further instructions.</div>";
				}, 50 );
				add_action('sensei_single_lesson_content_inside_before', function() {
					echo "<div class='sensei-message alert'>You have failed this course twice. Please contact Purpora Engineering for further instructions.</div>";
				}, 21);
			}
		}
		return $params;
	}, 20, 4);
	
	/*Replace the shortcodes in certificate template*/
	add_filter('certificate_meta_filter', function($value, $course_id, $user_id) {
		$args = array(
			'post_type' => 'certificate',
			'post_status' => 'publish',
			'author' => $user_id,
			'meta_key' => 'course_id',
			'meta_value' => $course_id,
			'orderby' => 'date',
			'order' => 'desc'
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ($query->have_posts()) {
				$query->the_post();
				$exp_date = get_post_meta(get_the_ID(), 'exp_date', true);
				$certificate_number = get_the_title();
				break;
			}
		}
		wp_reset_postdata();
		$value = str_replace('{{exp_date}}',date('jS F Y', strtotime($exp_date)), $value);
		$company = get_user_meta($user_id, 'billing_company', true);
		$value = str_replace('{{company}}', $company, $value);
		$value = str_replace('{{certificate_number}}', $certificate_number, $value);
		return $value;
	}, 10, 3);
	
	/*Adding Company custom field on User registration form*/
	function wooc_extra_register_fields() {
		?>
		
		<p class="form-row form-row-first">
		<label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?><span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
		</p>

		<p class="form-row form-row-last">
		<label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
		</p>

		<div class="clear"></div>

		<p class="form-row form-row-wide">
		<label for="reg_billing_company"><?php _e( 'Company', 'woocommerce' ); ?><span class="required">*</span></label>
		<input type="text" class="input-text" name="billing_company" id="reg_billing_company" value="<?php if ( ! empty( $_POST['billing_company'] ) ) esc_attr_e( $_POST['billing_company'] ); ?>" />
		</p>

		<?php
	}
	add_action( 'woocommerce_register_form_start', 'wooc_extra_register_fields' );

	function wooc_validate_extra_register_fields( $username, $email, $validation_errors ) {
		if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
			$validation_errors->add( 'billing_first_name_error', __( 'First name is required!', 'woocommerce' ) );
		}
		if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
			$validation_errors->add( 'billing_last_name_error', __( 'Last name is required!.', 'woocommerce' ) );
		}
		if ( isset( $_POST['billing_company'] ) && empty( $_POST['billing_company'] ) ) {
			$validation_errors->add( 'billing_company_error', __( 'Company is required!.', 'woocommerce' ) );
		}
	}
	add_action( 'woocommerce_register_post', 'wooc_validate_extra_register_fields', 10, 3 );

	function wooc_save_extra_register_fields( $customer_id ) {
	   if ( isset( $_POST['billing_first_name'] ) ) {
			// WordPress default first name field.
			//update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );

			// WooCommerce billing first name.
			update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
			update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
		}

		if ( isset( $_POST['billing_last_name'] ) ) {
			// WordPress default last name field.
			//update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
				  
			// WooCommerce billing last name.
			update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
			update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
		}
		
		if ( isset( $_POST['billing_company'] ) ) {
			// WooCommerce billing company
			update_user_meta( $customer_id, 'billing_company', sanitize_text_field( $_POST['billing_company'] ) );
		}
	}
	add_action('woocommerce_created_customer', 'wooc_save_extra_register_fields');

	// Adding Company field on Edit Account Details form
	function my_woocommerce_edit_account_form() {
	 
		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );

		if ( !$user ) return;

		$company = get_user_meta( $user_id, 'billing_company', true );

		?>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="comapny">Company<span class="required">*</span></label>
			<input class="woocommerce-Input woocommerce-Input--company input-text" name="account_company" id="company" value="<?php echo esc_attr( $company ); ?>">
		</p>
	 
	  <?php
	 
	}
	add_action( 'woocommerce_edit_account_form', 'my_woocommerce_edit_account_form', 1);

	function my_woocommerce_save_account_details( $user_id ) {

		update_user_meta( $user_id, 'billing_company', htmlentities( $_POST[ 'account_company' ] ) );

	}
	add_action( 'woocommerce_save_account_details', 'my_woocommerce_save_account_details' );
	
	add_filter( 'woocommerce_save_account_details_required_fields', function($fields) {
		$fields['account_company'] = __( 'Company', 'woocommerce' );
		return $fields;
	}, 10, 1 );
	
	// Show Register form on Register page
	function show_register_form() {

		wc_print_notices();
		?>
		<form method="post" class="register">

			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_username"><?php _e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( $_POST['username'] ) : ''; ?>" />
				</p>

			<?php endif; ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email"><?php _e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( $_POST['email'] ) : ''; ?>" />
			</p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" />
				</p>

			<?php endif; ?>

			<!-- Spam Trap -->
			<div style="<?php echo ( ( is_rtl() ) ? 'right' : 'left' ); ?>: -999em; position: absolute;"><label for="trap"><?php _e( 'Anti-spam', 'woocommerce' ); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" autocomplete="off" /></div>

			<?php do_action( 'woocommerce_register_form' ); ?>

			<p class="woocomerce-FormRow form-row">
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<input type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>" />
			</p>

			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>
		<?php
	}
	add_shortcode( 'user_register', 'show_register_form' );
	
	// After registration redirect user to certifications category page
	function custom_registration_redirect() {
		$category = get_term_by( 'slug', 'certifications', 'product_cat');
		$cat_id = $category->term_id;
		$link = get_term_link( $cat_id, 'product_cat' );
		return $link;
	}
	add_filter('woocommerce_registration_redirect', 'custom_registration_redirect', 20);

	// Hide the Register menu item if the user is logged in
	function add_menu_atts( $atts, $item, $args ) {
		//var_dump($atts);
		if (stristr($atts['href'], 'register') && is_user_logged_in()) $atts['style'] = 'display:none';
		return $atts;
	}
	add_filter( 'nav_menu_link_attributes', 'add_menu_atts', 10, 3 );

	//Renaming the Sign In menu item label if user is logged in
	function modify_menu_item($item) {
		if (!is_user_logged_in() && $item->type_label=='Page' && $item->title=='My Account') return false;
		if (stristr($item->url, 'my-account') && is_user_logged_in()) $item->title = 'My Account';
		if (($item->title=='Reference Material' || stristr($item->url, 'reference')) && !is_user_logged_in()) $item->url = get_permalink(get_option('woocommerce_myaccount_page_id'));
		return $item;
	}
	add_filter( 'wp_setup_nav_menu_item', 'modify_menu_item', 10, 1 );
	
	//Redirect user to My Account page on clicking Log In link
	global $pagenow;
	if( ('wp-login.php' == $pagenow) && !is_user_logged_in()) {
		wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
		exit();
	}

	
	/*******************************************/
	/* START of My Account tabs customizations */
	/*******************************************/
	
	//Register new endpoints to use inside My Account page.
	add_rewrite_endpoint( 'available-courses', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'certificates', EP_ROOT | EP_PAGES );
	
	//Add new query vars.
	function my_custom_query_vars( $vars ) {
		$vars[] = 'available-courses';
		$vars[] = 'certificates';
		return $vars;
	}
	add_filter( 'query_vars', 'my_custom_query_vars', 0 );
	
	//Flush rewrite rules on plugin activation.
	function my_custom_flush_rewrite_rules() {
		add_rewrite_endpoint( 'available-courses', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'certificates', EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}
	register_activation_hook( __FILE__, 'my_custom_flush_rewrite_rules' );
	register_deactivation_hook( __FILE__, 'my_custom_flush_rewrite_rules' );
	
	//Insert the new endpoint into the My Account menu.
	function my_custom_my_account_menu_items( $items ) {
		global $current_user;
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		$items['available-courses'] = __( 'Available Courses', 'woocommerce' );
		
		//Add Certificates tab if user has any certificate
		$args = array(
			'post_type' => 'certificate',
			'post_status' => 'publish',
			'author' => $current_user->ID,
		);
		$query = new WP_Query( $args );
		if ($query->post_count > 0):
			$items['certificates'] = __( 'Certificates', 'woocommerce' );
		endif;
		$items['customer-logout'] = $logout;
		return $items;
	}

	add_filter( 'woocommerce_account_menu_items', 'my_custom_my_account_menu_items' );
	
	//Endpoint HTML content.
	function courses_endpoint_content() {
		$customer_memberships = wc_memberships_get_user_active_memberships();
		$courses = array();
		if ( ! empty( $customer_memberships ) ) {
			foreach ($customer_memberships as $membership):
				$membership_plan = $membership->get_plan();
				$rules = $membership_plan->get_content_restriction_rules();
				foreach ($rules as $rule):
					if ($rule->get_content_type_name()=='course'):
						$ids = $rule->get_object_ids();
						foreach ($ids as $id):
							$courses[] = $id;
						endforeach;
					endif;
				endforeach;
			endforeach;
		}
		wc_get_template('courses.php', array('courses' => $courses), '', plugin_dir_path(__FILE__) . 'templates/');
	}
	add_action( 'woocommerce_account_available-courses_endpoint', 'courses_endpoint_content' );
	
	function certificates_endpoint_content() {
		global $current_user;
		$args = array(
			'post_type' => 'certificate',
			'post_status' => 'publish',
			'author' => $current_user->ID,
		);
		$certificates = array();
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ($query->have_posts()) {
				$query->the_post();
				$id = get_the_ID();
				$course_id = get_post_meta($id, 'course_id', true);
				$certificates[] = array('id' => $id, 'course' => $course_id);
			}
		}
		wp_reset_postdata();
		
		wc_get_template('certificates.php', array('certificates' => $certificates), '', plugin_dir_path(__FILE__) . 'templates/');
	}
	add_action( 'woocommerce_account_certificates_endpoint', 'certificates_endpoint_content' );
	
	/*****************************************/
	/* END of My Account tabs customizations */
	/*****************************************/
	
	
	//Adding link to My Account page on Order Thank You page
	add_filter('woocommerce_thankyou_order_received_text', function($text) {
		$notice = 'You can access all of your available courses by going to your <a href="'.get_permalink(get_option('woocommerce_myaccount_page_id')).'">My Account</a> page.';
		wc_print_notice( __( $notice, 'woocommerce' ), 'notice' );
		return $text;
	});
	
	//Adding custom text to email header on completed order
	add_action('woocommerce_email_header', function($email_heading, $email) {
		if (!($email->object instanceof WC_Order)) return false;
		$order = $email->object;
		$order_status = $order->get_status();
		$template_html = $email->template_html;
		if ($order_status == 'completed' && !stristr($template_html, 'admin')):
			?>
			<div style="padding: 10px; margin-bottom: 25px; background-color: #F1F1F1; border-radius: 10px;">To access your courses go to your list of Available Courses<br>on your My Account page by <a href="<?php echo site_url() ?>/my-account/available-courses/">clicking here</a>.</div>
			<?php
		endif;
	}, 20, 2);
	
	//Adding link to the Course on completed quiz
	add_filter('sensei_user_quiz_status', function($data, $lesson_id, $user_id, $is_lesson) {
		global $wpdb;
		if ($data['status']=='passed') {
			$course_id = absint( get_post_meta( $lesson_id, '_lesson_course', true ) );
			$a_element = __( ' Back to: ', 'woothemes-sensei' );
			$a_element .= '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . __( 'Back to the course', 'woothemes-sensei' )  . '">';
			$a_element .= get_the_title( $course_id );
			$a_element .= '</a>';
			$data['message'] .= '<section class="sensei-breadcrumb" style="display: inline; margin-left: 10px">'.$a_element.'</section>';
			
			//Adding exp_date to the new certificate
			$args = array(
				'post_type' => 'certificate',
				'post_status' => 'publish',
				'author' => $user_id,
				'meta_key' => 'course_id',
				'meta_value' => $course_id
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$exp_date = date("Y-m-d H:i:s", strtotime("+2 years"));
				while ($query->have_posts()) {
					$query->the_post();
					update_post_meta(get_the_ID(), 'exp_date', $exp_date);
				}
			}
			wp_reset_postdata();
		}
		return $data;
	}, 20, 4);
	
	//Removing the messages with correct answers when user passes the quiz
	add_filter('sensei_question_show_answers', function($show_answers) {
		return false;
	}, 10, 1);
	
	//Certificate verification
	add_shortcode('certificate_verification', function() {
		$template = wc_get_template_html('certificate_verification.php', array(), '', plugin_dir_path(__FILE__) . 'templates/');
		return $template;
	});
	add_action('wp_enqueue_scripts', function() {
		wp_enqueue_script( 'sensei_custom_script', plugins_url( 'assets/js/custom.js', __FILE__ ) );
		wp_enqueue_style( 'sensei_custom_style', plugins_url('assets/css/custom.css', __FILE__) );
	});
	add_action('wp_head', function() {
		echo '<script type="text/javascript">var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
	});
	
	function verify_certificate_ajax() {
		$response = array();
		if (isset($_POST['certificate'])) {
			$valid = false;
			$args = array(
				'post_type' => 'certificate',
				'post_status' => 'publish',
				'title' => $_POST['certificate'],
				'posts_per_page' => 1
			);
			$the_query = new WP_Query( $args );
			while ( $the_query->have_posts() ) :
				$the_query->the_post();
				$certificate_id = get_the_ID();
				if ($certificate_id):
					$exp_date = get_post_meta($certificate_id, 'exp_date', true);
					if ($exp_date > date('Y-m-d H:i:s')) {
						$valid = true;
						$learner = get_userdata( get_post_meta($certificate_id, 'learner_id', true) );
						$response['username'] = $learner->first_name.' '.$learner->last_name;
						$response['exp_date'] = date('M. j, Y', strtotime($exp_date));
					}
				endif;
			endwhile;
			wp_reset_postdata();
			$response['valid'] = $valid;
		}
		wp_die(json_encode($response));
	}
	
	add_action( 'wp_ajax_verify_certificate', 'verify_certificate_ajax' );
	add_action( 'wp_ajax_nopriv_verify_certificate', 'verify_certificate_ajax' );
	
});