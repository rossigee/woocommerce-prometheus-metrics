<?php

/**
Plugin Name: Woocommerce Prometheus Metrics
Plugin URI: https://wordpress.org/plugins/woo-prometheus-metrics
Description: Plugin to monitor the count of products and orders on a Woocommerce site.
Version: 0.0.2
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

function woocommerce_metrics_handler__handle_request($wp_query) {
  global $uris_to_check;

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
  $product_count = 0;
  $_product_count = wp_count_posts("product");
  if(isset($_product_count->publish)) {
    $product_count = $_product_count->publish;
  }

  // Gather count of orders
  $order_count = wc_orders_count("processed");

  // Gather count of users
  $_user_count = count_users();
  // Not sure why '$user_count = $_user_count['total_users']' doesn't work!
  foreach($_user_count as $k => $v) {
    if($k == 'total_users') {
      $user_count = $v;
    }
  }

  header("Content-Type: text/plain");
  header('Cache-Control: no-cache');


  woocommerce_metrics_output_metric("woocommerce_product_count",
    "The number of products.",
    "gauge",
    $product_count
  );

  woocommerce_metrics_output_metric("woocommerce_order_count",
    "The number of orders.",
    "gauge",
    $order_count
  );

  woocommerce_metrics_output_metric("woocommerce_user_count",
    "The number of users.",
    "gauge",
    $user_count
  );

}
