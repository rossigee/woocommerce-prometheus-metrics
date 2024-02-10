<?php

/**
Plugin Name: Woocommerce Prometheus Metrics
Plugin URI: https://wordpress.org/plugins/woo-prometheus-metrics
Description: Plugin to monitor the count of products and orders on a Woocommerce site.
Version: 0.0.4
Author: Ross Golder <ross@golder.org>
Author URI: http://www.golder.org/
License: GPLv2
 */

require_once(dirname(__FILE__) . "/woo-prometheus-metrics-options.php");


function woocommerce_metrics_handler_init() {
  add_rewrite_rule('^woocommerce-metrics/?', 'index.php?__woocommerce_metrics=1', 'top');
}
add_action('init', 'woocommerce_metrics_handler_init');

function woocommerce_metrics_query_vars($vars) {
  $vars[] = '__woocommerce_metrics';
  return $vars;
}
add_action('query_vars', 'woocommerce_metrics_query_vars');

function woocommerce_metrics_request_parser($wp_query) {
  global $wp;

  if(isset($wp->query_vars['__woocommerce_metrics'])) {
    woocommerce_metrics_handler__handle_request($wp_query);
    die(); // stop default WP behavior
  }
}
add_action("parse_request", "woocommerce_metrics_request_parser");

function woocommerce_metrics_output_metric($id, $desc, $type, $value) {
  echo "# HELP ".$id." ".$desc."\n";
  echo "# TYPE ".$id." ".$type."\n";
  echo $id." ".$value."\n";
  echo "\n";
}

function woocommerce_metrics_output_order_metrics($id, $desc, $type, $values) {
  echo "# HELP ".$id." ".$desc."\n";
  echo "# TYPE ".$id." ".$type."\n";
  foreach($values as $status => $value) {
    echo $id."{status=\"".$status."\"} ".$value."\n";
  }
  echo "\n";
}

function woocommerce_metrics_handler__handle_request($wp_query) {
  global $uris_to_check;
  global $wpdb;

  $auth_username = get_option("woocommerce_metrics_auth_username");
  $auth_password = get_option("woocommerce_metrics_auth_password");
  if($auth_username != "" && $auth_password != "") {
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    if($auth_username != $username || $auth_password != $password) {
      header("HTTP/1.1 401 Unauthorized");
      header('WWW-Authenticate: Basic realm="Woocommerce Metrics"');
      echo "Authorisation required.";
      exit(0);
    }
  }

  // Gather count of products
  $product_count = count(wc_get_products(['return' => 'ids']));

  woocommerce_metrics_output_metric("woocommerce_product_count",
    "The number of products.",
    "gauge",
    $product_count
  );

  // Gather count of orders by status

  $order_statuses = array_keys(wc_get_order_statuses());
  $order_counts = array();
  foreach($order_statuses as $order_status) {
    $s = substr($order_status, 3); // Drop the 'wc-' prefix
    $order_counts[$s] = wc_orders_count($s);
  }

  woocommerce_metrics_output_order_metrics("woocommerce_order_count",
    "The number of orders, by status.",
    "gauge",
    $order_counts
  );

  // Gather count of users
  $_user_count = count_users();
  $user_count = $_user_count['total_users'];

  header("Content-Type: text/plain");
  header('Cache-Control: no-cache');


  woocommerce_metrics_output_metric("woocommerce_user_count",
    "The number of users.",
    "gauge",
    $user_count
  );

  // Gather stock info
  $stock   = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
  $nostock = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );
  $stock_info = $wpdb->get_row($wpdb->prepare("SELECT
          sum(case when lookup.stock_quantity <= %d then 1 else 0 end) as no_stock,
          sum(case when lookup.stock_quantity > %d and lookup.stock_quantity < %d then 1 else 0 end) as low_stock,
          sum(case when lookup.stock_quantity >= %d then 1 else 0 end) as in_stock
        FROM {$wpdb->posts} as pos
        INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
        WHERE posts.post_type IN ( 'product', 'product_variation' )
          AND posts.post_status = 'publish'",
				$nostock,
				$nostock, $stock,
				$stock,
		),
		ARRAY_A);
  woocommerce_metrics_output_order_metrics("woocommerce_stock",
    "The number of products in each stock state (no, low, in stock).",
    "gauge",
    $stock_info
  );

  // Gather revenue
  $revenue = $wpdb->get_var("select sum(net_total) from wp_wc_order_stats where status='wc-completed'");
  woocommerce_metrics_output_metric("woocommerce_revenue_sum",
    "The total net revenue (sum of all completed orders).",
    "gauge",
    $revenue
  );

  // items sold
  $items_sold = $wpdb->get_var("select sum(num_items_sold) from wp_wc_order_stats where status='wc-completed'");
  woocommerce_metrics_output_metric("woocommerce_items_sold_sum",
    "The total number of sold items (sum of all completed orders).",
    "gauge",
    $items_sold
  );

}
