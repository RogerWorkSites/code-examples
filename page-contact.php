<?php

/**
 * Template Name: Contact Us
 */
if (!defined('ABSPATH')) exit;
get_header();

$contact_form_title = get_field('contact_form_title');
$contact_form = get_field('contact_form');
$contact_locations_title = get_field('contact_locations_title');
$contact_locations = get_field('contact_locations');
$contact_cta_title = get_field('contact_cta_title');
$contact_cta_btn_1 = get_field('contact_cta_btn_1');
$contact_cta_btn_2 = get_field('contact_cta_btn_2');
$contact_cta_color = get_field('contact_cta_color');
$contact_subscr = get_field('add_subscribe');

//--contact_form
if ($contact_form_title || $contact_form) {
    get_template_part('shortcodes/contact_form_html', null, array(
        'title' => $contact_form_title,
        'subtitle' => get_the_title(),
        'form_id' => $contact_form,
    ));
}

//--Office locations
if ($contact_locations_title || $contact_locations) {
    get_template_part('shortcodes/office_locations_html', null, array(
        'title' => $contact_locations_title,
        'locations' => $contact_locations,
    ));
}

//--be customer
if ($contact_cta_title || $contact_cta_btn_1 || $contact_cta_btn_2) {
    get_template_part('shortcodes/cta_block_html', null, array(
        'class'    => 'contact-mode',
        'color'    => $contact_cta_color,
        'title'    => $contact_cta_title,
        'button_1' => $contact_cta_btn_1,
        'button_2' => $contact_cta_btn_2,
    ));
}

//--SUBSCRIBE
if ($contact_subscr) get_template_part('shortcodes/subscribe_block');

get_footer();