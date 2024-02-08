=== Woocommerce Prometheus Metrics ===

* Contributors: rossigee
* Tags: wordpress
* Requires at least: 4.7.2
* Tested up to: 6.4.3
* Stable tag: 0.0.4
* License: GPLv2

This plugin provides a Prometheus-compatible metrics endpoint for Woocommerce.

The metrics it gathers are:

* `woocommerce_product_count` - a count of products listed.
* `woocommerce_order_count` - a count of orders on the system, by 'status'.
* `woocommerce_user_count` - a count of users on the system.
* `woocommerce_stock` - the number of items in each stock status (no, low, in stock)
* `woocommerce_revenue_sum` - The total net revenue (sum of all completed orders)
* `woocommerce_items_sold_sum` - The total number of sold items (sum of all completed orders)

We gather the metrics with the following section of Prometheus configuration:

```
- job_name: 'WoocommerceMetrics'
  scrape_interval: 60s
  honor_labels: true
  scheme: 'https'
  basic_auth:
    username: 'prometheus'
    password: 'secret_token_known_to_your_monitoring_system'
  metrics_path: '/'
  params:
    __woocommerce_metrics: [1]
  static_configs:
    - targets:
      - www.myecommercesite.com
      - www.myothersite.com

```


== Changelog ==

= 0.2 =
* More metrics (stock levels, total revenue, number of items sold)

= 0.1 =

* Initial version.
