<?php

/**
 * Template for individual similar property card
 * 
 * @var WP_Post $similar The property post object
 * @var int $similar_id The property post ID
 */

namespace SimilarProperties;

if (!defined('ABSPATH')) exit;

$location = fm_location_tax($similar_id);
?>
<div class="card card-property">

    <a class="permalink" href="<?php echo esc_url(get_permalink($similar_id)); ?>" rel="bookmark"></a>

    <figure role="img" aria-label="<?php echo esc_html($similar->post_title);?>" class="ratio ratio-1x1">
        <?php echo get_the_post_thumbnail($similar_id, 'medium_large', array('alt' => esc_html($similar->post_title), 'loading' => false)); ?>
    </figure>

    <div class="card-body">

        <h3 class="card-title no-glot"><?php echo esc_html($similar->post_title); ?></h3>

        <address>
            <span><i class="fui-location"></i></span>
            <span><?php echo $location['name'] ??= ''; ?></span>
        </address>

        <?php
        $pr_size     = get_field('property_size', $similar_id) ? round(floatval(get_field('property_size', $similar_id))) : 'n.a.';
        $l_size      = get_field('land_size', $similar_id) ? round(floatval(get_field('land_size', $similar_id))) : 'n.a.';
        $bedrooms    = get_field('bedrooms', $similar_id) ? floatval(get_field('bedrooms', $similar_id)) : 'n.a.';
        $baths       = get_field('bathrooms', $similar_id) ? floatval(get_field('bathrooms', $similar_id)) : 'n.a.';
        ?>

        <div class="pr-data">
            <div class="label"><span class="inline">Property Size</span></div>
            <div class="label right"><span class="inline">Land Size</span></div>
            <div class="unit" data-unit="<?php echo $pr_size; ?>"><span class="inline"><?php echo nFormat($pr_size); ?><br />sqm</span></div>
            <div class="unit right" data-unit="<?php echo $l_size; ?>"><span class="inline"><?php echo nFormat($l_size); ?><br />sqm</span></div>
            <div class="label"><span class="inline">Bedrooms</span></div>
            <div class="label right"><span class="inline">Bathrooms</span></div>
            <div class="unit"><span class="inline"><?php echo $bedrooms; ?></span></div>
            <div class="unit right"><span class="inline"><?php echo $baths; ?></span></div>
        </div>

    </div>

    <div class="card-footer">

        <?php
        if (get_field('hidden_price', $similar_id) || empty(get_field('price', $similar_id))) :
            echo '<span class="poa">';
            _e('Price on request', 'fmedia');
            echo '</span>';
        else :
            $price = get_field('price', $similar_id); ?>
            <span class="price number no-glot" data-price="<?php echo esc_html($price); ?>">
                <?php echo nFormat($price, 'el_EL') ?> <small>&euro;</small>
            </span>
        <?php
        endif;
        ?>

    </div>


</div>