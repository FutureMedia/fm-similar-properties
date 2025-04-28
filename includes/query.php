<?php

namespace SimilarProperties;

if (!defined('ABSPATH')) exit;

function clean_price($price_raw) {
    if (!$price_raw) return 0;
    // Handle common currency formats
    $price_raw = str_replace(['$', '€', '£', ','], ['', '', '', ''], $price_raw);
    return floatval(trim($price_raw));
}

function get_similar_properties($post_id, $limit = MAX_SIMILAR_PROPERTIES) {
    // Validate current post first
    if (get_post_type($post_id) !== 'properties' 
        || get_post_status($post_id) !== 'publish'
        || post_password_required($post_id)
    ) {
        /*
        debug_log('Source property invalid or inaccessible', [
            'id' => $post_id,
            'type' => get_post_type($post_id),
            'status' => get_post_status($post_id)
        ], '⚠️');
        */
        return false;
    }

    if (!function_exists('get_field')) {
        // debug_log('ACF not active', null, '❌');
        return false;
    }

    // debug_log('Starting search', ['post_id' => $post_id, 'limit' => $limit], '🔄');
    
    // Check the cache first
    $cached = get_cached_properties($post_id);
    if ($cached) return $cached;

    // Get taxonomy terms
    $property_type = wp_get_post_terms($post_id, 'pr_type', ['fields' => 'ids']);
    $location = wp_get_post_terms($post_id, 'location', ['fields' => 'ids']);
    if (empty($property_type)) {
        // debug_log('No property type found', ['post_id' => $post_id], '⚠️');
        return false;
    }

    // Get and clean price
    $price_raw = get_field('price', $post_id);
    $price = clean_price($price_raw);
    /*
    debug_log('Price info', [
        'raw' => $price_raw,
        'cleaned' => $price,
        'type' => gettype($price)
    ], '💰');
    */

    // Bail out if price is too high (corrupt)
    if ($price > 1000000000) {
        error_log("❌ Price too high ($price) — aborting query for post ID $post_id");
        return false;
    }

    // Bail out if price invalid
    if (!$price || $price < 1) return false;

    // Load tolerance from settings (default 30000)
    $tolerance_raw = get_option('sp_price_tolerance', 3000);
    $tolerance = is_numeric($tolerance_raw) && $tolerance_raw > 0 && $tolerance_raw < 1000000 ? intval($tolerance_raw) : 30000;

    // error_log("📏 Using tolerance: $tolerance");

    $min_price = $price - $tolerance;
    $max_price = $price + $tolerance;

    // error_log("✅ Query range: $min_price - $max_price");

    // Main query args
    $args = [
        'post_type'      => 'properties',
        'posts_per_page' => $limit,
        'post__not_in'   => [$post_id],
        'post_status'    => 'publish',
        'has_password'   => false,
        'meta_query'     => [
            [
                'key'     => 'price',
                'value'   => [strval($min_price), strval($max_price)],
                'compare' => 'BETWEEN',
                //'type'    => 'DECIMAL(10,2)'
            ]
        ],
        'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'pr_type',
                'field'    => 'term_id',
                'terms'    => $property_type,
            ],
            [
                'taxonomy' => 'location',
                'field'    => 'term_id',
                'terms'    => $location,
                'operator' => 'IN',
            ]
        ],
    ];

    // Debug the query args and limit
    /*
    debug_log('Query configuration', [
        'requested_limit' => $limit,
        'posts_per_page' => $args['posts_per_page'],
        'price_range' => "$min_price - $max_price",
        'property_type' => $property_type,
        'location' => $location
    ], '🔍');
    */

    global $wpdb;
    error_log('📊 Sample prices in database:');
    $sample_prices = $wpdb->get_results("
        SELECT pm.post_id, pm.meta_value, p.post_title 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'price' 
        AND p.post_type = 'properties'
        LIMIT 5
    ");
    
    foreach ($sample_prices as $row) {
        error_log("Post {$row->post_id} ({$row->post_title}): Value in DB = '{$row->meta_value}'");
    }

    $query = new \WP_Query($args);
    /*
    debug_log('Query results', [
        'found_posts' => $query->found_posts,
        'post_count' => $query->post_count,
        'max_num_pages' => $query->max_num_pages
    ], '📊');
    */

    // Remove filters using namespace
    // remove_filter('posts_request', __NAMESPACE__ . '\log_sql_query', 999);
    // remove_filter('get_meta_sql', __NAMESPACE__ . '\log_meta_sql', 999);

    // Fallback: drop location filter if too few results
    if ($query->found_posts < $limit) {
        /*
        debug_log('Insufficient results, trying without location', [
            'found' => $query->found_posts,
            'needed' => $limit
        ], '⚠️');
        */
        
        $args['tax_query'] = [ $args['tax_query'][0] ]; // Keep only property_type
        $query = new \WP_Query($args);
        
        /*
        debug_log('Fallback query results', [
            'found_posts' => $query->found_posts,
            'post_count' => $query->post_count,
            'max_num_pages' => $query->max_num_pages
        ], '📊');
        */
    }

    // Cache result
    set_cached_properties($post_id, $query);

    return $query;
}
