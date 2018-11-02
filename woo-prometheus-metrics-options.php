<?php

function woocommerce_prometheus_metrics_options_page() {
	global $woocommerce_prometheus_metrics_options;

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.') );
	}

	if (isset($_POST['submit']) && isset($_POST['metricsoptions'])) {
		check_admin_referer('woo-prometheus-metrics-options');
		woocommerce_prometheus_metrics_options_update();
	}

	?>
<style type="text/css">
p.error {
	color: red;
}
</style>

<div class="wrap">

<h2>Woocommerce Prometheus Metrics Settings</h2>

<p>Metrics Endpoint: <tt><a href="/?__woocommerce_metrics=1"><?php echo get_home_url() ?>/?__woocommerce_metrics=1</a></tt></p>

<form method="post" action="">
<?php
if(function_exists('wp_nonce_field') )
	wp_nonce_field('woo-prometheus-metrics-options');
?>
<input type="hidden" name="metricsoptions" value="true"/>

<h3>Authentication</h3>

<p>If you want to protect your metrics endpoint, please supply a username/password for Basic Authentication to be applied. Leave blank for no authentication to apply.</p>

<table class="form-table">
	<tr valign="top">
		<th scope="row">Username</th>
		<td>
      <input type="text" name="woocommerce_metrics_auth_username" value="<?php echo get_option('woocommerce_metrics_auth_username'); ?>" />
		</td>
  </tr>
	<tr valign="top">
		<th scope="row">Password</th>
		<td>
      <input type="text" name="woocommerce_metrics_auth_password" value="<?php echo get_option('woocommerce_metrics_auth_password'); ?>" />
		</td>
  </tr>
</table>

<p class="submit">
	<input type="submit" name="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
</p>

</form>
</div>
	<?php
}

function woocommerce_prometheus_metrics_options_update() {
	update_option('woocommerce_metrics_auth_username', sanitize_text_field($_REQUEST['woocommerce_metrics_auth_username']));
	update_option('woocommerce_metrics_auth_password', sanitize_text_field($_REQUEST['woocommerce_metrics_auth_password']));

	?>
	<div class="updated">
	<p>Configuration updated successfully.</p>
	</div>
	<?php
}

function woocommerce_prometheus_metrics_admin_init() {
	// Default settings
	if(!get_option('woocommerce_metrics_auth_username')) {
		update_option('woocommerce_metrics_auth_username', '');
	}
	if(!get_option('woocommerce_metrics_auth_password')) {
		update_option('woocommerce_metrics_auth_password', '');
	}
}

function woocommerce_prometheus_metrics_admin_menu() {
	global $woocommerce_prometheus_metrics_options_page;

	$woocommerce_prometheus_metrics_options_page = add_options_page(
		__('Woo Metrics', 'woo-prometheus-metrics'),
		__('Woo Metrics', 'woo-prometheus-metrics'),
		'manage_options',
		__FILE__,
		'woocommerce_prometheus_metrics_options_page');
}

// Hooks to allow configuration settings and options to be set
add_action('admin_init', 'woocommerce_prometheus_metrics_admin_init');
add_action('admin_menu', 'woocommerce_prometheus_metrics_admin_menu');
