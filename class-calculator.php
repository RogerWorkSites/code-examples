<?php //--07.04 - 10:00

/**
 * Uploading Unilock Calculator Formulas
 */
require_once(__DIR__ . '/class-calculator-formulas.php');

/**
 *Upload Pdf generator
 */
require_once(__DIR__ . '/pdf/autoload.inc.php');

/**
 * MAIN CLASS CALCULATOR
 */
class UnilockProCalculator extends Unilock_Calculator_Formulas
{

    private $THEME_URL;
    private $THEME_URL_CALCULATOR;
    private $THEME_URL_CALCULATOR_ASSETS;
    private $SCRIPT_VER = '1.0.47.2';
    private $GET_REGION = 'Other';

    public function __construct()
    {
        /*Themes URLs*/
        $this->THEME_URL = CHILD_THEME_URI;
        $this->THEME_URL_CALCULATOR = CHILD_THEME_URI . '/calculator';
        $this->THEME_URL_CALCULATOR_ASSETS = CHILD_THEME_URI . '/calculator/assets';

        $this->GET_REGION = cont_check_region_user();

        if ($this->GET_REGION === 'Other') {
            $this->GET_REGION = 'Ontario';
        }

        /*Start Function*/
        $this->load();
    }

    /**
     * Loading Ajax Function
     */
    public function load()
    {
        add_action('wp_enqueue_scripts', array($this, 'uni_pro_contractor_scripts'), 12, 1);
        add_action('get_footer', array($this, 'uni_pro_contractor_scripts_footer'), 12, 1);

        /*ADD POPUP*/
        add_action('wp_ajax_uni_calc_add_prod_popup', array($this, 'uni_pro_add_prod_popup'));
        add_action('wp_ajax_nopriv_uni_calc_add_prod_popup', array($this, 'uni_pro_add_prod_popup'));

        /*ADD COMBINED POPUP*/
        add_action('wp_ajax_uni_calc_add_combined_select', array($this, 'uni_pro_add_combined_select'));
        add_action('wp_ajax_nopriv_uni_calc_add_combined_select', array($this, 'uni_pro_add_combined_select'));

        /*ADD CALCULATION*/
        add_action('wp_ajax_uni_calc_add_calculation', array($this, 'uni_pro_add_calculation'));
        add_action('wp_ajax_nopriv_uni_calc_add_calculation', array($this, 'uni_pro_add_calculation'));

        /*ADD TRANSIENT*/
        add_action('wp_ajax_uni_calc_transient_resave', array($this, 'uni_pro_transient_resave'));
        add_action('wp_ajax_nopriv_uni_calc_transient_resave', array($this, 'uni_pro_transient_resave'));

        /*RENAME PROJECT*/
        add_action('wp_ajax_uni_calc_rename_project', array($this, 'uni_pro_rename_project'));
        add_action('wp_ajax_nopriv_uni_calc_rename_project', array($this, 'uni_pro_rename_project'));

        /*change region*/
        add_action('wp_ajax_uni_calc_region_transient_delete', array($this, 'uni_pro_region_transient_delete'));
        add_action('wp_ajax_nopriv_uni_calc_region_transient_delete', array($this, 'uni_pro_region_transient_delete'));
    }

    /**
     *Add To Popup Product
     */
    public function uni_pro_add_prod_popup()
    {
        switch_to_blog(1);

        $cat_slug = (isset($_POST['cat_slug']) ? $_POST['cat_slug'] : '');
        //$region = check_region_user();
        //$state = (isset($_COOKIE['state']) ? $_COOKIE['state'] : 'New York');
        $state = $this->GET_REGION;
        $current_region_id = cont_unilock_get_region();

        $variations_args = array(
            'post_type' => 'variation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'Region_Name',
                    'value' => $state,
                    'compare' => 'LIKE'
                ),
            )
        );

        if (empty($cat_slug)) die(json_encode(array('success' => false)));
        $this_wall_panel = FALSE;
        if ($cat_slug == 'wall-panel') {
            $cat_slug = 'walls';
            $this_wall_panel = TRUE;
        }

        if ($cat_slug == 'border') {
            $term_cat = get_term_by('slug', $cat_slug, 'product_appl');
            $product_cat_id = $term_cat->term_id;

            $variations_args['meta_query'][] = array(
                'key' => 'Application',
                'value' => $product_cat_id,
                'compare' => 'LIKE'
            );
        } else if ($cat_slug == 'pillar') {
            $term_cat = get_term_by('slug', $cat_slug, 'product_appl');
            $product_cat_id = $term_cat->term_id;
            //            $variations_args['meta_query'][] = array(
            //                'key'     => 'Application',
            //                'value'   => $product_cat_id,
            //                'compare' => 'LIKE'
            //            );
            $term_pillar_cap = get_term_by('slug', 'pillar-cap', 'product_cat');
            $pillar_cap_id = $term_pillar_cap->term_id;

            $variations_args['meta_query'][] = array(
                'key' => 'Categories',
                'value' => $pillar_cap_id,
                'compare' => 'NOT LIKE'
            );
        } else if ($cat_slug == 'pavers') {
            $paver_term_cat = get_term_by('slug', $cat_slug, 'product_cat');
            $paver_cat_id = $paver_term_cat->term_id;

            $permeable_term_cat = get_term_by('slug', 'permeable-pavers', 'product_cat');
            $permeable_cat_id = $permeable_term_cat->term_id;

            $variations_args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => 'Categories',
                    'value' => $paver_cat_id,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'Categories',
                    'value' => $permeable_cat_id,
                    'compare' => 'LIKE'
                ),
            );
        } else {
            $term_cat = get_term_by('slug', $cat_slug, 'product_cat');
            $product_cat_id = $term_cat->term_id;

            $variations_args['meta_query'][] = array(
                'key' => 'Categories',
                'value' => $product_cat_id,
                'compare' => 'LIKE'
            );
        }

        $variations = get_posts($variations_args);

        if (empty($variations)) die(json_encode(array('success' => false)));

        $product_height = $product_family_code = $select = $use_calculator = $max_height = '';
        $select = '<button class="с-current-btn js_remove_prod"></button><div class="с-current-val"></div><ul>';
        $select_single = $select_comb = $walls_random_bundle_array = [];
        //
        foreach ($variations as $key => $variation_id) {
            $sailor_crate = $soldier_crate = $sailor_layer = $soldier_layer = '';

            $use_calculator_data = get_post_meta($variation_id, 'Use_Calculator');
            if (!empty($use_calculator_data)) $use_calculator = $use_calculator_data[0];
            $product_height = get_post_meta($variation_id, 'Height');
            $product_max_height = get_post_meta($variation_id, 'Max_Height');
            if (!empty($product_max_height)) $max_height = $product_max_height[0];
            $product_family = get_post_meta($variation_id, 'Product_Family');
            $product_SKU = get_post_meta($variation_id, 'SKU')[0];
            $product_family_code = get_post_meta($variation_id, 'Product_Family_Code');
            $product_cat = get_post_meta($variation_id, 'Categories');
            $random_bandle = get_post_meta($variation_id, 'Random_Configuration');
            $swatchs = get_post_meta($variation_id, 'Swatchs');
            $packaging_data = get_post_meta($variation_id, 'Packaging_Data');
            if (!empty($packaging_data)) {
                if (isset($packaging_data[0]['Sailor_Lnft_per_Unit'])) $sailor_crate = $packaging_data[0]['Sailor_Lnft_per_Unit'];
                if (isset($packaging_data[0]['Sailor_Lnft_per_Layer'])) $sailor_layer = $packaging_data[0]['Sailor_Lnft_per_Layer'];
                if (isset($packaging_data[0]['Soldier_Lnft_per_Layer'])) $soldier_layer = $packaging_data[0]['Soldier_Lnft_per_Layer'];
            }
            $Component_Configuration = get_post_meta($variation_id, 'Component_Configuration');
            $corner_height_increment = get_post_meta($variation_id, 'Corner_Height_Increment');

            $other_images = get_post_meta($variation_id, 'Other_Images');
            $prod_pdf_url = '';
            if (!empty($other_images)) {
                $prod_pdf_arr = $prod_pdf = [];
                $img_pdf_arr = $this->uni_pro_get_img_pdf($other_images);

                if (array_key_exists('calc_pdf', $img_pdf_arr)) {
                    $prod_pdf_url = $img_pdf_arr['calc_pdf'];
                }
            }

            if ($cat_slug === 'coping') {
                //file_put_contents(__DIR__ . '/A_res_variations_coping.txt', print_r($variation_id.',', true), FILE_APPEND);
            }

            if ($cat_slug === 'walls' && $product_family[0] != 'UNIVERSAL' && $use_calculator != 'false' && $product_height[0] != '0MM') {
                $jsonProdArr = array(
                    'id' => $variation_id,
                    'cat' => $product_cat[0][0],
                    'height' => $product_height[0],
                    'family' => $product_family_code[0],
                    'family_name' => $product_family[0],
                    'max_height' => $max_height,
                    'pdf' => $prod_pdf_url,
                    'use_calc' => $use_calculator,
                );


                if ($random_bandle[0] == 'true' && !$this_wall_panel) {
                    $product_max_height = $corner_id = '';
                    $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');

                    $this_title = str_replace('"', "in", $this_title);


                    $jsonProdArr['title'] = $this_title;
                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['random_bundle_id'] = $variation_id;
                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['title'] = $this_title;
                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['json_data'] = $jsonProdArr;
                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['family'] = $product_family_code[0];
                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['product_max_height'] = $max_height;
                } else {

                    $product_max_height = $corner_id = '';

                    if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture'])) {

                        foreach ($swatchs[0][0]['Component_Picture'] as $component) {
                            $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];
                            $this_title = str_replace('"', "in", $this_title);
                            $jsonProdArr['title'] = $this_title;

                            $coping_find = strpos(strtolower($component['Component_Description']), 'coping');
                            if ($product_family[0] == 'U-CARA®' && ($current_region_id == 1 || $current_region_id == 3 || $current_region_id == 4)) {
                                $corner_find = $pillar_find = false;
                            } else {
                                $corner_find = strpos(strtolower($component['Component_Description']), 'corner');
                                $pillar_find = strpos(strtolower($component['Component_Description']), 'pillar');
                                if ($product_family[0] == 'U-CARA®' && $corner_find !== false) {
                                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id'] = $variation_id;
                                    $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_title'] = $product_family[0] . ' ' . $component['Component_Description'];
                                }
                            }
                            // $corner_find = strpos(strtolower($component['Component_Description']), 'corner');
                            // $pillar_find = strpos(strtolower($component['Component_Description']), 'pillar');
                            $corner2_find = strpos(strtolower($component['Component_Description']), 'closed end');
                            $corner3_find = strpos(strtolower($component['Component_Description']), 'half');

                            $wall_panel_find = strpos(strtolower($component['Component_Description']), 'fascia panel');

                            if (!$this_wall_panel) {
                                if ($pillar_find === false && $coping_find === false && $wall_panel_find === false) {
                                    if ($corner_find === false && $corner2_find === false && $corner3_find === false) {
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['title'] = $this_title;
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['json_data'] = $jsonProdArr;
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['random_bundle_id'] = $variation_id;
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['family'] = $product_family_code[0];
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['product_max_height'] = $max_height;
                                    } else {
                                        if (!isset($walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id']) || empty($walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id'])) {
                                            $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id'] = $variation_id;
                                            $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_title'] = $product_family[0] . ' (' . $product_height[0] . ')';
                                        }
                                    }
                                }
                            } else {
                                if ($pillar_find === false && $wall_panel_find != false) {
                                    if ($corner_find === false && $corner2_find === false && $corner3_find === false) {
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['title'] = $this_title;
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['json_data'] = $jsonProdArr;
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['random_bundle_id'] = $variation_id;
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['family'] = $product_family_code[0];
                                        $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title]['product_max_height'] = $max_height;
                                    } else {
                                        if (!isset($walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id']) || empty($walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id'])) {
                                            $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id'] = $variation_id;
                                            $walls_random_bundle_array[$product_family_code[0] . '_' . $product_height[0]]['corner_title'] = $product_family[0] . ' (' . $product_height[0] . ')';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else if ($cat_slug !== 'walls' && $use_calculator != 'false') {
                if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture'])) {
                    $important_products_array = array(
                        'B0602012110016513619041',
                        'B06020121100165136190',
                        'B05020121100165136190',
                        'L05020121000165136110',
                        'O040201211002051430E0',
                        'U010201210002732511K0',
                        'U01020201000278309050',
                        'U01020205000278309050',
                        'U01020211000278309050',
                        'U0102505400078AVB30EBN4',
                    );

                    foreach ($swatchs[0][0]['Component_Picture'] as $component) {
                        if ($random_bandle[0] == 'true')
                            $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');
                        else
                            $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];

                        $this_title = str_replace('"', "in", $this_title);

                        $jsonProdArr = array(
                            'id' => $variation_id,
                            'title' => $this_title,
                            'cat' => $product_cat[0][0],
                            'height' => $product_height[0],
                            'family' => $product_family_code[0],
                            'family_name' => $product_family[0],
                            'use_calc' => $use_calculator,
                        );

                        $base_disable = strpos(strtolower($component['Component_Description']), 'universal base');

                        $checkOrientation = true;
                        if (($cat_slug === 'border')) {
                            if (empty($sailor_layer) || $sailor_layer == 0) $jsonProdArr['sailor_or'] = false;
                            else $jsonProdArr['sailor_or'] = true;
                            if (empty($soldier_layer) || $soldier_layer == 0) $jsonProdArr['soldier_or'] = false;
                            else $jsonProdArr['soldier_or'] = true;
                            if (empty($sailor_layer) && empty($soldier_layer)) $checkOrientation = false;
                        } else if ($cat_slug === 'coping') {
                            if (empty($sailor_crate) || $sailor_crate == 0) $jsonProdArr['sailor_or'] = false;
                            else $jsonProdArr['sailor_or'] = true;
                            if (empty($soldier_layer) || $soldier_layer == 0) $jsonProdArr['soldier_or'] = false;
                            else $jsonProdArr['soldier_or'] = true;
                            if ($jsonProdArr['sailor_or'] == false && $jsonProdArr['soldier_or'] == false) $checkOrientation = false;
                        }
                        $pillar_corner_find = $pillar_unit_find = $pillar_no_mackinaw = true;
                        if ($cat_slug === 'pillar') {
                            if (!empty($corner_height_increment)) $jsonProdArr['corner_incr'] = $corner_height_increment[0];
                            /* Adding product if they in array */
                            if (!in_array($product_SKU, $important_products_array)) {
                                $pillar_corner_find = strpos(strtolower($component['Component_Description']), 'corner');
                                $pillar_unit_find = strpos(strtolower($component['Component_Description']), 'pillar unit');
                                //--disabled Mackinaw product for pillars Mackinaw family code = M01
                                if ($product_family_code[0] == 'M01') $pillar_no_mackinaw = false;
                                if ($pillar_unit_find !== false) $jsonProdArr['pillar_unit'] = true;
                                else  $jsonProdArr['pillar_unit'] = false;
                            } else {
                                /* FIX 05.08.21 */
                                $jsonProdArr['pillar_unit'] = false;
                            }
                        }
                        $checkNaturalStones = true;
                        if ($cat_slug === 'natural-stones') {
                            if ($product_height[0] !== '22MM') {
                                $checkNaturalStones = false;
                            }
                        }
                        if ($cat_slug === 'steps') {
                            $Dimension_imperial = $Component_Configuration[0][0]['Dimension_imperial'];
                            $imperial_arr = explode('x', $Dimension_imperial);
                            $length_inh = str_replace('"', '', trim($imperial_arr[0]));
                            $height_inh = str_replace('"', '', trim($imperial_arr[1]));
                            $width_inh = str_replace('"', '', trim($imperial_arr[2]));

                            if (!empty($height_inh)) {
                                $height_res = explode(' ', $height_inh);
                                $height_inh_val = $height_res[0];
                                if (!empty($height_res[1])) {
                                    $height_dec = explode('/', $height_res[1]);
                                    $height_dec_res = round($height_dec[0] / $height_dec[1], 3);
                                    $height_inh_val = $height_inh_val + $height_dec_res;
                                }
                            } else {
                                $height_inh_val = 0;
                            }

                            $jsonProdArr['steps_info'] = ['height_inch_val' => $height_inh_val, 'height_inch' => $height_inh, 'width_inch' => $width_inh, 'length_inch' => $length_inh];
                        }

                        $jsonProdArr['pdf'] = $prod_pdf_url;
                        $jsonProd = json_encode($jsonProdArr);
                        //UNIVERSAL - BASE product
                        if (!array_key_exists($this_title, $select_single) && !empty($this_title) && $pillar_no_mackinaw !== false && ($pillar_corner_find !== false || $pillar_unit_find !== false) && $checkNaturalStones && $checkOrientation && $base_disable === false) {
                            $select_single[$this_title] = "<li data-id='" . $variation_id . "' data-product='" . $jsonProd . "'>" . $this_title . "</li>";
                        }
                    }
                }
            }
        }

        //file_put_contents(__DIR__ . '/ArrayCOMB.txt', print_r($select_single, true));
        //file_put_contents(__DIR__ . '/ArrayCOMB.txt', print_r($walls_random_bundle_array, true));


        if (!empty($select_single)) {
            ksort($select_single);
            foreach ($select_single as $select_item) {
                $select .= $select_item;
            }
        }

        if (!empty($walls_random_bundle_array)) {
            ksort($walls_random_bundle_array);
            foreach ($walls_random_bundle_array as $select_item) {
                if (isset($select_item['product'])) {
                    foreach ($select_item['product'] as $prod_variation) {
                        if (isset($prod_variation['random_bundle_id'])) {
                            if (!empty($prod_variation['title'])) {
                                $corner_id = '';
                                if (isset($select_item['corner_id'])) $corner_id = $select_item['corner_id'];
                                if (!empty($select_item['corner_title'])) {
                                    $prod_variation['json_data']['corner_title'] = $select_item['corner_title'];
                                }
                                $jsonProd = json_encode($prod_variation['json_data']);
                                $select .= "<li data-id='" . $prod_variation['random_bundle_id'] . "' data-corner-id='" . $corner_id . "' data-product='" . $jsonProd . "'>" . $prod_variation['title'] . "</li>";
                            }
                        }
                    }
                }
            }
        }

        $select .= '</ul>';

        restore_current_blog();
        echo json_encode(array('success' => true, 'select' => $select));
        die();
    }

    /**
     * Get Banner Image and PDFs
     */
    public function uni_pro_get_img_pdf($product_images)
    {
        $banner_image = $calc_pdf_url = '';
        $laying_pattern = $sell_sheet = $img_pdf_arr = [];
        if ($product_images) {
            switch_to_blog(1);
            //file_put_contents(__DIR__ . '/A-img-' . $var_id . '.txt', print_r($product_images[0], true));
            foreach ($product_images[0] as $image) {
                /*if ($image['Image_Type'] == 'HERO PICTURE')
                    $img_pdf_arr['banner_image'] = wp_get_attachment_url($image['Other_Picture']);
                if ($image['Image_Type'] == 'SELL SHEET' && !empty($image['Description']))
                    $sell_sheet[] = $image;
                if ($image['Image_Type'] == 'LAYING PATTERN' && !empty($image['Description']))
                    $laying_pattern[] = $image;*/
                if ($image['Image_Type'] == 'CALCULATOR LP FAMILY LEVEL' && $image['Contractor']) {
                    $calc_pdf_url = wp_get_attachment_url($image['Other_Picture']);
                    //file_put_contents(__DIR__ . '/A-res-true.txt', print_r($image, true));
                }
            }

            //if (!empty($laying_pattern)) $img_pdf_arr['laying_pattern'] = $laying_pattern;
            //if (!empty($sell_sheet)) $img_pdf_arr['sell_sheet'] = $sell_sheet;
            $img_pdf_arr['calc_pdf'] = $calc_pdf_url;
            restore_current_blog();
            return $img_pdf_arr;
        }
    }

    /**
     * Get Combined Select
     */
    public function uni_pro_add_combined_select()
    {
        switch_to_blog(1);

        $data_prod = (isset($_POST['data_prod']) ? $_POST['data_prod'] : '');
        $cat_slug = (isset($_POST['type']) ? $_POST['type'] : '');
        //$state = (isset($_COOKIE['state']) ? $_COOKIE['state'] : 'New York');
        //-- U-CARA® SURE TRACK STANDARD BACKER - disabled combined prod
        $wall_backer_find = strpos(strtolower($data_prod['title']), 'backer');
        $state = $this->GET_REGION;
        if (empty($data_prod) || empty($state) || $wall_backer_find !== false)
            die(json_encode(array('success' => false)));


        $combinedArgs = array(
            'post_type' => 'variation',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'Region_Name',
                    'value' => $state,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'Product_Family_Code',
                    'value' => $data_prod['family'],
                    'compare' => 'LIKE'
                ),
            )
        );

        if ($cat_slug == 'border' || $cat_slug == 'pillar') {
            $combinedArgs['meta_query'][] = array(
                'key' => 'Application',
                'value' => $data_prod['cat'],
                'compare' => 'LIKE'
            );
        } else {
            $combinedArgs['meta_query'][] = array(
                'key' => 'Categories',
                'value' => $data_prod['cat'],
                'compare' => 'LIKE'
            );
        }

        $combinedProds = get_posts($combinedArgs);
        if ($combinedProds) {
            $combined_select = "";
            $product_height = $product_family_code = '';
            $select_single = $select_comb = $walls_combined_array = [];
            foreach ($combinedProds as $key => $combined_id) {
                $product_height = get_post_meta($combined_id, 'Height');
                $product_family_code = get_post_meta($combined_id, 'Product_Family_Code');
                $product_family = get_post_meta($combined_id, 'Product_Family');
                $swatchs = get_post_meta($combined_id, 'Swatchs');
                $random_bandle = get_post_meta($combined_id, 'Random_Configuration');

                $other_images = get_post_meta($combined_id, 'Other_Images');
                $prod_pdf_arr = $prod_pdf = [];
                $prod_pdf_url = '';
                if (!empty($other_images)) {
                    $img_pdf_arr = $this->uni_pro_get_img_pdf($other_images);
                    if (array_key_exists('calc_pdf', $img_pdf_arr)) {
                        $prod_pdf_url = $img_pdf_arr['calc_pdf'];
                    }
                }


                if ($cat_slug === 'walls' && $product_family[0] != 'UNIVERSAL') {

                    $jsonCombArr = array(
                        'id' => $combined_id,
                        'height' => $product_height[0],
                        'family' => $product_family_code[0],
                        'family_name' => $product_family[0],
                        'pdf' => $prod_pdf_url
                    );

                    if ($random_bandle[0] == 'true') {
                        $product_max_height = $corner_id = '';
                        $this_title_walls = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');
                        $this_title_walls = str_replace('"', "in", $this_title_walls);

                        $jsonCombArr['title'] = $this_title_walls;
                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['combined_id'] = $combined_id;
                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['title'] = $this_title_walls;
                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['json_data_comb'] = $jsonCombArr;
                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['family'] = $product_family_code[0];
                    } else {

                        $product_max_height = $corner_id = '';

                        if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture'])) {

                            foreach ($swatchs[0][0]['Component_Picture'] as $component) {
                                $this_title_walls = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];
                                $this_title_walls = str_replace('"', "in", $this_title_walls);

                                $jsonCombArr['title'] = $this_title_walls;

                                $coping_find = strpos(strtolower($component['Component_Description']), 'coping');
                                $corner_find = strpos(strtolower($component['Component_Description']), 'corner');
                                $pillar_find = strpos(strtolower($component['Component_Description']), 'pillar');
                                $wall_panel_find = strpos(strtolower($component['Component_Description']), 'fascia panel');
                                if ($pillar_find === false && $coping_find === false && $wall_panel_find === false) {
                                    if ($corner_find === false) {

                                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['title'] = $this_title_walls;
                                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['json_data_comb'] = $jsonCombArr;
                                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['combined_id'] = $combined_id;
                                        $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['product'][$this_title_walls]['family'] = $product_family_code[0];
                                    } else {
                                        if (!isset($walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id']) || empty($walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id']))
                                            $walls_combined_array[$product_family_code[0] . '_' . $product_height[0]]['corner_id'] = $combined_id;
                                    }
                                }
                            }
                        }
                    }
                } else if ($cat_slug !== 'walls') {
                    if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture'])) {
                        foreach ($swatchs[0][0]['Component_Picture'] as $component) {

                            if ($random_bandle[0] == 'true')
                                $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');
                            else
                                $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];

                            $this_title = str_replace('"', "in", $this_title);

                            $jsonCombArr = array(
                                'id' => $combined_id,
                                'title' => $this_title,
                                'height' => $product_height[0],
                                'family' => $product_family_code[0],
                            );

                            $jsonCombArr['pdf'] = $prod_pdf_url;
                            $jsonCombined = json_encode($jsonCombArr);

                            // UNIVERSAL - BASE product
                            if (!array_key_exists($this_title, $select_comb) && stripcslashes($data_prod['title']) != $this_title && $product_height[0] == $data_prod['height'] && $product_family[0] != 'UNIVERSAL')
                                $select_comb[$this_title] = "<li data-id='" . $combined_id . "' data-combined='" . $jsonCombined . "'>" . $this_title . "</li>";
                        }
                    }
                }
            }

            //file_put_contents(__DIR__ . '/A_data_prod.txt', print_r(array($select_comb, $walls_combined_array), true));

            if (!empty($select_comb) || !empty($walls_combined_array)) {
                //$select_comb = array_unique($select_comb, SORT_STRING);
                if (!empty($select_comb)) {
                    foreach ($select_comb as $select_comb_item) {
                        $combined_select .= $select_comb_item;
                    }
                }

                if (!empty($walls_combined_array)) {
                    foreach ($walls_combined_array as $select_item) {
                        if ($select_item['product']) {
                            foreach ($select_item['product'] as $prod_variation) {
                                if (isset($prod_variation['combined_id'])) {
                                    if (!empty($prod_variation['title']) && stripcslashes($data_prod['title']) !== $prod_variation['title']) {
                                        $jsonProd = json_encode($prod_variation['json_data_comb']);
                                        $combined_select .= "<li data-id='" . $prod_variation['combined_id'] . "' data-corner-id='" . $select_item['corner_id'] . "' data-combined='" . $jsonProd . "'>" . $prod_variation['title'] . "</li>";
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                die(json_encode(array('success' => false)));
            }

            if (empty($combined_select)) die(json_encode(array('success' => false)));
            $combined_select = '<ul>' . $combined_select . '</ul>';
            //$combined_select .= "</ul>";


            echo json_encode(array('success' => true, 'combined_select' => $combined_select));
        } else {
            echo json_encode(array('success' => false));
        }
        restore_current_blog();
        die();
    }

    /**
     * Add To Table
     */
    public function uni_pro_add_calculation()
    {
        $data_prod = (isset($_POST['data_prod']) ? $_POST['data_prod'] : '');
        $transient_name = (isset($_COOKIE['project_trans']) ? $_COOKIE['project_trans'] : '');

        if (!empty($transient_name)) {
            $all_prod_trans = get_transient($transient_name);
            $transient_val = [];
            //file_put_contents(__DIR__ . '/data_prod.txt', print_r($data_prod, true));
            if ($all_prod_trans == false || $all_prod_trans == '') {
                $transient_val[] = $data_prod;
                set_transient($transient_name, json_encode($transient_val), 2 * HOUR_IN_SECONDS);
            } else {
                $all_prod_trans = json_decode($all_prod_trans);
                $all_prod_trans[] = $data_prod;
                set_transient($transient_name, json_encode($all_prod_trans), 2 * HOUR_IN_SECONDS);
            }
            //file_put_contents(__DIR__ . '/a_all.txt', print_r($all_prod_trans, true));
        }


        if (empty($data_prod))
            die(json_encode(array('success' => false)));

        $table_tr = $this->uni_pro_render_prod_table($data_prod);

        //

        if (!empty($table_tr))
            echo json_encode(array('success' => true, 'table_html' => $table_tr));
        else
            echo json_encode(array('success' => false));
        die();
    }

    /**
     * Render Product Table Before Calculate
     */
    public function uni_pro_render_prod_table($data_prod)
    {
        restore_current_blog();
        switch_to_blog(1);
        $table = $table_tr = '';
        if ($data_prod['type'] == 'border' || $data_prod['type'] == 'steps') {

            $data_prod_type = $data_prod['type'];
            if ($data_prod['type'] == 'border') {
                $icon = '/img/border-icon.svg';
                $data_prod_type = 'Border';
            } elseif ($data_prod['type'] == 'steps') $icon = '/img/steps-icon.svg';
            else $icon = '/img/alert-icon.svg';
            $icon = $this->THEME_URL_CALCULATOR_ASSETS . $icon;

            if (isset($data_prod['total_sq'])) $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
            if (isset($data_prod['total_un_req'])) $data_prod['total_un_req'] = round($data_prod['total_un_req'], 2);
            ob_start(); ?>
            <tr data-id="<?php echo $data_prod['id'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-type="<?php echo $data_prod['type'] ?>" data-add-prod='<?php echo json_encode($data_prod); ?>'>
                <td class="category"><img src="<?php echo esc_url($icon); ?>" alt="prod-icon_<?php $data_prod['id']; ?>"><span><?php echo $data_prod_type . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                <?php if ($data_prod['type'] == 'steps') : ?>
                    <td class="descr"><?php echo __('Elevation', 'unilock') . ' ' . $data_prod['total_sq'] . ' ' . __('inches', 'unilock') . ' | ' . $data_prod['total_un_req'] . ' ' . __('Units Required', 'unilock'); ?></td>
                <?php else : ?>
                    <td class="descr"><?php echo ucwords($data_prod_type) . ' - ' . ucfirst($data_prod['orientation']) . ' | ' . $data_prod['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>
                <?php endif; ?>

                <td class="control-btn">
                    <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                    <div class="control-panel">
                        <ul>
                            <li class="js_edit_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                            <li class="js_remove_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?>
                            </li>
                        </ul>
                    </div>
                    <div class="layer-close"></div>
                </td>
            </tr>
        <?php
            $table_tr = ob_get_clean();
        } else if ($data_prod['type'] == 'coping') {
            $data_prod_type = $data_prod['type'];
            $icon = $this->THEME_URL_CALCULATOR_ASSETS . '/img/coping-icon.svg';

            if (isset($data_prod['total_sq'])) $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
            if (isset($data_prod['total_un_req'])) $data_prod['total_un_req'] = round($data_prod['total_un_req'], 2);
            ob_start(); ?>
            <tr data-id="<?php echo $data_prod['id'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-coping-type="true" data-type="<?php echo $data_prod['type'] ?>" data-add-prod='<?php echo json_encode($data_prod); ?>'>
                <td class="category"><img src="<?php echo esc_url($icon); ?>" alt="prod-icon_<?php $data_prod['id']; ?>"><span><?php echo __('Wall, Step & Pool Coping', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                <td class="descr"><?php echo ucwords($data_prod_type) . ' - ' . ucfirst($data_prod['orientation']) . ' | ' . $data_prod['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>

                <td class="control-btn">
                    <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                    <div class="control-panel">
                        <ul>
                            <li class="js_edit_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                            <li class="js_remove_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?>
                            </li>
                        </ul>
                    </div>
                    <div class="layer-close"></div>
                </td>
            </tr>
            <?php
            $table_tr = ob_get_clean();
        } else if ($data_prod['type'] == 'pavers') {
            if (!isset($data_prod['combined'])) {

                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                ob_start(); ?>
                <tr data-id="<?php echo $data_prod['id'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-type="<?php echo $data_prod['type'] ?>" data-add-prod='<?php echo json_encode($data_prod); ?>'>
                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/paver-icon.svg" alt="border-icon"><span><?php echo __('Pavers And Slabs', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                    <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                    <td class="descr"><?php echo __('Pavers And Slabs Area', 'unilock') . ' | ' . $data_prod['total_sq'] . ' ' . __('Sq.Ft', 'unilock'); ?></td>
                    <td class="control-btn">
                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                        <div class="control-panel">
                            <ul>
                                <li class="js_edit_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                <li class="js_remove_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                            </ul>
                        </div>
                        <div class="layer-close"></div>
                    </td>
                </tr>
            <?php
                $table_tr = ob_get_clean();
            } else if (isset($data_prod['combined']) && count($data_prod['combined']) > 0) {
                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                ob_start(); ?>
                <tr class="nested-tr" data-id="<?php echo $data_prod['id'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-type="<?php echo $data_prod['type']; ?>" data-add-prod='<?php echo json_encode($data_prod); ?>' data-combined="true">
                    <td colspan="8">
                        <table class="table nested-table">
                            <?php foreach ($data_prod['combined'] as $combined) { ?>
                                <tr data-id="<?php echo $combined['id'] ?>" class="active">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/paver-icon.svg" alt="border-icon"><span><?php echo __('Pavers And Slabs', 'unilock') . ' <i>' . $data_prod['num'] . '</i> (' . __('Combined', 'unilock') . ')' ?></span>
                                    </td>
                                    <td class="name"><?php echo stripcslashes($combined['title']); ?></td>
                                    <td class="descr"><?php echo __('Pavers And Slabs Area', 'unilock') . ' | ' . $combined['persent'] . '% ' . ' ' . __('of', 'unilock') . ' ' . $data_prod['total_sq'] . ' ' . __('Sq.Ft', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </td>
                </tr>
            <?php $table_tr = ob_get_clean();
            }
        } else if ($data_prod['type'] == 'natural-stones') {
            if (!isset($data_prod['combined'])) {

                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                ob_start(); ?>
                <tr data-id="<?php echo $data_prod['id'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-type="<?php echo $data_prod['type'] ?>" data-add-prod='<?php echo json_encode($data_prod); ?>'>
                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/stone-icon.svg" alt="stone-icon"><span><?php echo __('Natural Stone Slabs', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                    <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                    <td class="descr"><?php echo __('Natural Stone Slabs Area', 'unilock') . ' | ' . $data_prod['total_sq'] . ' ' . __('Sq.Ft', 'unilock'); ?></td>
                    <td class="control-btn">
                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                        <div class="control-panel">
                            <ul>
                                <li class="js_edit_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                <li class="js_remove_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                            </ul>
                        </div>
                        <div class="layer-close"></div>
                    </td>
                </tr>
            <?php
                $table_tr = ob_get_clean();
            } else if (isset($data_prod['combined']) && count($data_prod['combined']) > 0) {
                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                ob_start(); ?>
                <tr class="nested-tr" data-id="<?php echo $data_prod['id'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="<?php echo $data_prod['type']; ?>" data-add-prod='<?php echo json_encode($data_prod); ?>' data-combined="true">
                    <td colspan="8">
                        <table class="table nested-table">
                            <?php foreach ($data_prod['combined'] as $combined) { ?>
                                <tr data-id="<?php echo $combined['id'] ?>" class="active">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/stone-icon.svg" alt="stone-icon"><span><?php echo __('Natural Stone Slabs', 'unilock') . ' <i>' . $data_prod['num'] . '</i> (' . __('Combined', 'unilock') . ')' ?></span>
                                    </td>
                                    <td class="name"><?php echo stripcslashes($combined['title']); ?></td>
                                    <td class="descr"><?php echo __('Natural Stone Slabs Area', 'unilock') . ' | ' . $combined['persent'] . '% ' . ' ' . __('of', 'unilock') . ' ' . $data_prod['total_sq'] . ' ' . __('Sq.Ft', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </td>
                </tr>
            <?php $table_tr = ob_get_clean();
            }
        } else if ($data_prod['type'] == 'walls') {

            if (!isset($data_prod['combined'])) {
                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                $data_prod['total_avg'] = round($data_prod['total_avg'], 2);
                $data_prod['outside_corners'] = round($data_prod['outside_corners'], 2);
                ob_start(); ?>
                <tr class="nested-tr" data-id="<?php echo $data_prod['id'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="<?php echo $data_prod['type']; ?>" data-coping="<?php echo (isset($data_prod['coping']) && !empty($data_prod['coping'])); ?>" data-add-prod='<?php echo json_encode($data_prod); ?>' data-combined="false">
                    <td colspan="8">
                        <table class="table nested-table">
                            <tr>
                                <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/wall-icon.svg" alt="border-icon"><span><?php echo __('Retaining Walls & Base Units', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                                <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                                <td class="descr"><?php echo $data_prod['total_sq'] . ' ' . __('LFT', 'unilock') . ' | ' . $data_prod['total_avg'] . ' ' . __('in', 'unilock') . ' | ' . $data_prod['outside_corners'] . ' ' . __('Outside Corners', 'unilock'); ?></td>
                                <td class="control-btn">
                                    <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                    <div class="control-panel">
                                        <ul>
                                            <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                            <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                        </ul>
                                    </div>
                                    <div class="layer-close"></div>
                                </td>
                            </tr>
                            <?php if (isset($data_prod['base_unit'])) :
                                $data_prod['base_unit']['total_sq'] = round($data_prod['base_unit']['total_sq'], 2); ?>
                                <tr>
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/base-unit-icon.svg" alt="base-unit-icon"><span><?php echo __('Base Unit', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                                    <td class="name"><?php echo stripcslashes($data_prod['base_unit']['title']); ?></td>
                                    <td class="descr"><?php echo __('Base Unit', 'unilock') . ' - ' . ucfirst($data_prod['base_unit']['orientation']) . ' | ' . $data_prod['base_unit']['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($data_prod['coping'])) :
                                if (isset($data_prod['coping']['total_sq'])) $data_prod['coping']['total_sq'] = round($data_prod['coping']['total_sq'], 2); ?>
                                <tr data-num-type="coping" data-num="<?php echo $data_prod['coping']['num']; ?>" data-coping-wall="true">
                                    <td class="category"><img src="<?php echo esc_url($this->THEME_URL_CALCULATOR_ASSETS . '/img/coping-icon.svg'); ?>" alt="prod-icon_<?php $data_prod['coping']['id']; ?>"><span><?php echo __('Wall, Step & Pool Coping', 'unilock') . ' <i>' . $data_prod['coping']['num'] . '</i>'; ?></span>
                                    </td>
                                    <td class="name"><?php echo stripcslashes($data_prod['coping']['title']); ?></td>
                                    <td class="descr"><?php echo __('Wall, Step & Pool Coping', 'unilock') . ' - ' . ucfirst($data_prod['coping']['orientation']) . ' | ' . $data_prod['coping']['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>

                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            <?php
                $table_tr = ob_get_clean();
            } else if (isset($data_prod['combined']) && count($data_prod['combined']) > 0) {
                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                $data_prod['total_avg'] = round($data_prod['total_avg'], 2);
                $data_prod['outside_corners'] = round($data_prod['outside_corners'], 2);
                ob_start(); ?>
                <tr class="nested-tr" data-id="<?php echo $data_prod['id'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="<?php echo $data_prod['type']; ?>" data-add-prod='<?php echo json_encode($data_prod); ?>' data-combined="true">
                    <td colspan="8">
                        <table class="table nested-table">
                            <?php foreach ($data_prod['combined'] as $combined) { ?>
                                <tr class="active">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/wall-icon.svg" alt="border-icon"><span><?php echo __('Retaining Walls & Base Units', 'unilock') . ' <i>' . $data_prod['num'] . '</i> (' . __('Combined', 'unilock') . ')' ?></span>
                                    </td>
                                    <td class="name"><?php echo stripcslashes($combined['title']); ?></td>
                                    <td class="descr"><?php echo $combined['persent'] . '% ' . __('of', 'unilock') . ' ' . $data_prod['total_sq'] . ' ' . __('LFT', 'unilock') . ' | ' . $data_prod['total_avg'] . ' ' . __('in', 'unilock') . ' | ' . $data_prod['outside_corners'] . ' ' . __('Outside Corners', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (isset($data_prod['base_unit'])) :
                                $data_prod['base_unit']['total_sq'] = round($data_prod['base_unit']['total_sq'], 2); ?>
                                <tr class="active">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/base-unit-icon.svg" alt="base-unit-icon"><span><?php echo __('Base', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                                    <td class="name"><?php echo stripcslashes($data_prod['base_unit']['title']); ?></td>
                                    <td class="descr"><?php echo __('Base', 'unilock') . ' - ' . ucfirst($data_prod['base_unit']['orientation']) . ' | ' . $data_prod['base_unit']['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($data_prod['coping'])) :
                                if (isset($data_prod['coping']['total_sq'])) $data_prod['coping']['total_sq'] = round($data_prod['coping']['total_sq'], 2); ?>
                                <tr class="active" data-num-type="coping" data-num="<?php echo $data_prod['coping']['num']; ?>" data-coping-wall="true">
                                    <td class="category"><img src="<?php echo esc_url($this->THEME_URL_CALCULATOR_ASSETS . '/img/coping-icon.svg'); ?>" alt="prod-icon_<?php $data_prod['coping']['id']; ?>"><span><?php echo __('Wall, Step & Pool Coping', 'unilock') . ' <i>' . $data_prod['coping']['num'] . '</i>'; ?></span>
                                    </td>
                                    <td class="name"><?php echo stripcslashes($data_prod['coping']['title']); ?></td>
                                    <td class="descr"><?php echo __('Wall, Step & Pool Coping', 'unilock') . ' - ' . ucfirst($data_prod['coping']['orientation']) . ' | ' . $data_prod['coping']['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>

                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            <?php $table_tr = ob_get_clean();
            }
        } else if ($data_prod['type'] == 'wall-panel') {
            if (!isset($data_prod['combined'])) {
                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                $data_prod['total_avg'] = round($data_prod['total_avg'], 2);
                $data_prod['outside_corners'] = round($data_prod['outside_corners'], 2);
                ob_start(); ?>
                <tr class="nested-tr" data-id="<?php echo $data_prod['id'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="<?php echo $data_prod['type']; ?>" data-add-prod='<?php echo json_encode($data_prod); ?>' data-combined="false">
                    <td colspan="8">
                        <table class="table nested-table">
                            <tr data-id="<?php echo $data_prod['id'] ?>">
                                <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/wall-panel-icon.svg" alt="wall-panel-icon"><span><?php echo __('Wall Fascia Panel', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                                <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                                <td class="descr"><?php echo $data_prod['total_sq'] . ' ' . __('LFT', 'unilock') . ' | ' . $data_prod['total_avg'] . ' ' . __('in', 'unilock') . ' | ' . $data_prod['outside_corners'] . ' ' . __('Outside Corners', 'unilock'); ?></td>
                                <td class="control-btn">
                                    <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                    <div class="control-panel">
                                        <ul>
                                            <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                            <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                        </ul>
                                    </div>
                                    <div class="layer-close"></div>
                                </td>
                            </tr>
                            <?php if (isset($data_prod['base_unit'])) :
                                $data_prod['base_unit']['total_sq'] = round($data_prod['base_unit']['total_sq'], 2); ?>
                                <tr data-id="<?php echo $data_prod['base_unit']['id']; ?>">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/base-unit-icon.svg" alt="base-unit-icon"><span><?php echo __('Base', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                                    <td class="name"><?php echo stripcslashes($data_prod['base_unit']['title']); ?></td>
                                    <td class="descr"><?php echo __('Base', 'unilock') . ' - ' . ucfirst($data_prod['base_unit']['orientation']) . ' | ' . $data_prod['base_unit']['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            <?php
                $table_tr = ob_get_clean();
            } else if (isset($data_prod['combined']) && count($data_prod['combined']) > 0) {
                $data_prod['total_sq'] = round($data_prod['total_sq'], 2);
                $data_prod['total_avg'] = round($data_prod['total_avg'], 2);
                $data_prod['outside_corners'] = round($data_prod['outside_corners'], 2);
                ob_start(); ?>
                <tr class="nested-tr" data-id="<?php echo $data_prod['id'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="<?php echo $data_prod['type']; ?>" data-add-prod='<?php echo json_encode($data_prod); ?>' data-combined="true">
                    <td colspan="8">
                        <table class="table nested-table">
                            <?php foreach ($data_prod['combined'] as $combined) { ?>
                                <tr data-id="<?php echo $combined['id'] ?>" class="active">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/wall-panel-icon.svg" alt="wall-panel-icon"><span><?php echo __('Wall Fascia Panel', 'unilock') . ' <i>' . $data_prod['num'] . '</i> (' . __('Combined', 'unilock') . ')' ?></span>
                                    </td>
                                    <td class="name"><?php echo stripcslashes($combined['title']); ?></td>
                                    <td class="descr"><?php echo $combined['persent'] . '% ' . __('of', 'unilock') . ' ' . $data_prod['total_sq'] . ' ' . __('LFT', 'unilock') . ' | ' . $data_prod['total_avg'] . ' ' . __('in', 'unilock') . ' | ' . $data_prod['outside_corners'] . ' ' . __('Outside Corners', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (isset($data_prod['base_unit'])) :
                                $data_prod['base_unit']['total_sq'] = round($data_prod['base_unit']['total_sq'], 2); ?>
                                <tr data-id="<?php echo $data_prod['base_unit']['id']; ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="base_unit" class="active">
                                    <td class="category"><img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/base-unit-icon.svg" alt="base-unit-icon"><span><?php echo __('Base', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span></td>
                                    <td class="name"><?php echo stripcslashes($data_prod['base_unit']['title']); ?></td>
                                    <td class="descr"><?php echo __('Base', 'unilock') . ' - ' . ucfirst($data_prod['base_unit']['orientation']) . ' | ' . $data_prod['base_unit']['total_sq'] . ' ' . __('LFT', 'unilock'); ?></td>
                                    <td class="control-btn">
                                        <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                                        <div class="control-panel">
                                            <ul>
                                                <li class="js_edit_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                                                <li class="js_remove_combined_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="layer-close"></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
            <?php $table_tr = ob_get_clean();
            }
        } else if ($data_prod['type'] == 'pillar') {
            $icon = $this->THEME_URL_CALCULATOR_ASSETS . '/img/pillar-icon.svg';
            $data_prod['total_inch'] = round($data_prod['total_inch'], 2);
            ob_start(); ?>
            <tr data-id="<?php echo $data_prod['id'] ?>" data-num-type="<?php echo $data_prod['type'] ?>" data-num="<?php echo $data_prod['num'] ?>" data-type="<?php echo $data_prod['type'] ?>" data-add-prod='<?php echo json_encode($data_prod); ?>'>
                <td class="category"><img src="<?php echo esc_url($icon); ?>" alt="prod-icon_<?php $data_prod['id']; ?>"><span><?php echo __('Pillar', 'unilock') . ' <i>' . $data_prod['num'] . '</i>'; ?></span>
                </td>
                <td class="name"><?php echo stripcslashes($data_prod['title']); ?></td>
                <?php if (!empty($data_prod['pillar_cap'])) $pillar_cap_info = ' | ' . str_replace('"', "in", stripcslashes($data_prod['pillar_cap']));
                else $pillar_cap_info = ''; ?>
                <td class="descr"><?php echo $data_prod['total_num'] . ' ' . __('Pillars', 'unilock') . ' | ' . $data_prod['total_inch'] . ' ' . __('Inches', 'unilock') . $pillar_cap_info; ?></td>
                <td class="control-btn">
                    <img src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/control-btn.svg" alt="control-btn">
                    <div class="control-panel">
                        <ul>
                            <li class="js_edit_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/edit-icon.svg" alt="edit-icon"><?php _e('Edit Product', 'unilock'); ?></li>
                            <li class="js_remove_prod"><img class="icon" src="<?php echo $this->THEME_URL_CALCULATOR_ASSETS ?>/img/delete-icon.svg" alt="delete-icon"><?php _e('Remove Product', 'unilock'); ?>
                            </li>
                        </ul>
                    </div>
                    <div class="layer-close"></div>
                </td>
            </tr>
        <?php
            $table_tr = ob_get_clean();
        }
        restore_current_blog();
        return $table_tr;
    }

    /**
     * Transient Resave
     */
    public function uni_pro_transient_resave()
    {
        $all_data_prod = (isset($_POST['data_prod']) ? $_POST['data_prod'] : '');
        $transient_name = (isset($_COOKIE['project_trans']) ? $_COOKIE['project_trans'] : '');
        if (!empty($transient_name)) {
            if ($all_data_prod == '') set_transient($transient_name, '', 2 * HOUR_IN_SECONDS);
            else set_transient($transient_name, json_encode($all_data_prod), 2 * HOUR_IN_SECONDS);
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        die();
    }

    /**
     * Add pillar-cap
     */
    public function uni_calc_add_pillar_cap()
    {
        switch_to_blog(1);
        //$state = (isset($_COOKIE['state']) ? $_COOKIE['state'] : 'New York');
        $state = $this->GET_REGION;

        $term_cat = get_term_by('slug', 'pillar-cap', 'product_cat');
        $product_cat_id = $term_cat->term_id;
        $combinedArgs = array(
            'post_type' => 'variation',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'Region_Name',
                    'value' => $state,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'Categories',
                    'value' => $product_cat_id,
                    'compare' => 'LIKE'
                ),
            )
        );


        $combinedProds = get_posts($combinedArgs);
        $pillar_select = '';
        if ($combinedProds) {
            $product_height = $product_family_code = '';
            $select_single = $select_comb = [];
            foreach ($combinedProds as $key => $combined_id) {
                $product_height = get_post_meta($combined_id, 'Height');
                $product_family_code = get_post_meta($combined_id, 'Product_Family_Code');
                $product_family = get_post_meta($combined_id, 'Product_Family');
                $swatchs = get_post_meta($combined_id, 'Swatchs');
                $random_bandle = get_post_meta($combined_id, 'Random_Configuration');


                if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture'])) {
                    foreach ($swatchs[0][0]['Component_Picture'] as $component) {

                        if ($random_bandle[0] == 'true')

                            $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');
                        else
                            $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];

                        $this_title = str_replace('"', "in", stripcslashes($this_title));

                        // UNIVERSAL - BASE product
                        if (!array_key_exists($this_title, $select_comb) && $product_family[0] != 'UNIVERSAL') {
                            $select_comb[$this_title] = "<li data-id='" . $combined_id . "'>" . $this_title . "</li>";
                        }
                    }
                }
            }

            if (!empty($select_comb)) {
                //$select_comb = array_unique($select_comb, SORT_STRING);
                foreach ($select_comb as $select_comb_item) {
                    $pillar_select .= $select_comb_item;
                }
            }
        }
        restore_current_blog();
        return $pillar_select;
    }

    /**
     * Get Base units for Walls
     */
    public function uni_pro_get_base_units()
    {
        switch_to_blog(1);
        //$state = (isset($_COOKIE['state']) ? $_COOKIE['state'] : 'New York');
        $state = $this->GET_REGION;
        $term_cat = get_term_by('slug', 'walls', 'product_cat');
        $product_cat_id = $term_cat->term_id;
        if (empty($state) || empty($product_cat_id)) die(json_encode(array('success' => false)));

        $variations_args = array(
            'post_type' => 'variation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'Region_Name',
                    'value' => $state,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'Categories',
                    'value' => $product_cat_id,
                    'compare' => 'LIKE'
                ),
            )
        );
        $variations = get_posts($variations_args);
        if (empty($variations)) die(json_encode(array('success' => false)));

        $product_height = $product_family_code = $base_select = '';
        $select_single = [];
        foreach ($variations as $key => $variation_id) {
            $product_height = get_post_meta($variation_id, 'Height');
            $product_family = get_post_meta($variation_id, 'Product_Family');
            $product_family_code = get_post_meta($variation_id, 'Product_Family_Code');
            $product_cat = get_post_meta($variation_id, 'Categories');
            $random_bandle = get_post_meta($variation_id, 'Random_Configuration');
            $swatchs = get_post_meta($variation_id, 'Swatchs');
            $packaging_data = get_post_meta($variation_id, 'Packaging_Data');
            if (!empty($packaging_data)) {
                $sailor_layer = $packaging_data[0]['Sailor_Lnft_per_Layer'];
                $soldier_layer = $packaging_data[0]['Soldier_Lnft_per_Layer'];
            }
            //$product_max_height = get_post_meta($variation_id, 'Max_Height');
            //$Component_Configuration = get_post_meta($variation_id, 'Component_Configuration');
            //$corner_height_increment = get_post_meta($variation_id, 'Corner_Height_Increment');


            if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture'])) {
                foreach ($swatchs[0][0]['Component_Picture'] as $component) {

                    if ($random_bandle[0] == 'true') {
                        $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');
                    } else {
                        //$this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];
                        $this_title = $component['Component_Description'];
                    }

                    $this_title = str_replace('"', "in", $this_title);

                    $jsonProdArr = array(
                        'id' => $variation_id,
                        'title' => $this_title,
                        'cat' => $product_cat[0][0],
                        'height' => $product_height[0],
                        'family' => $product_family_code[0],
                    );

                    $checkOrientation = true;
                    if (empty($sailor_layer) || $sailor_layer == 0) $jsonProdArr['sailor_or'] = false;
                    else $jsonProdArr['sailor_or'] = true;
                    if (empty($soldier_layer) || $soldier_layer == 0) $jsonProdArr['soldier_or'] = false;
                    $jsonProdArr['soldier_or'] = true;
                    if (empty($sailor_layer) && empty($soldier_layer)) $checkOrientation = false;

                    $jsonProd = json_encode($jsonProdArr);
                    //UNIVERSAL - BASE product
                    if (!array_key_exists($this_title, $select_single) && $product_family[0] == 'UNIVERSAL' && $checkOrientation)
                        $select_single[$this_title] = "<li data-id='" . $variation_id . "' data-product='" . $jsonProd . "'>" . $this_title . "</li>";
                }
            }
        }
        if (!empty($select_single)) {
            foreach ($select_single as $select_item) {
                $base_select .= $select_item;
            }
        }
        restore_current_blog();
        return $base_select;
    }

    /**
     * Get Coping for Walls
     */
    public function uni_pro_get_coping()
    {
        switch_to_blog(1);
        //$state = (isset($_COOKIE['state']) ? $_COOKIE['state'] : 'New York');
        $state = $this->GET_REGION;
        $coping_cat_id = $stone_cat_id = '';
        $term_cat_coping = get_term_by('slug', 'coping', 'product_cat');
        $term_cat_stone = get_term_by('slug', 'natural-stone', 'product_cat');
        if (isset($term_cat_coping)) $coping_cat_id = $term_cat_coping->term_id;
        if (isset($term_cat_stone)) $stone_cat_id = $term_cat_stone->term_id;
        if (empty($state)) die(json_encode(array('success' => false)));
        $variations_args = array(
            'post_type' => 'variation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'Region_Name',
                    'value' => $state,
                    'compare' => 'LIKE'
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'Categories',
                        'value' => $stone_cat_id,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'Categories',
                        'value' => $coping_cat_id,
                        'compare' => 'LIKE'
                    ),
                ),
            )
        );

        $variations = get_posts($variations_args);
        //file_put_contents(__DIR__ . '/A_res_variations.txt', print_r($variations, true));
        $product_height = $product_family_code = $select = $use_calculator = $max_height = '';
        $select_single = $select_comb = $walls_random_bundle_array = [];
        foreach ($variations as $key => $variation_id) {
            $sailor_crate = $soldier_crate = $sailor_layer = $soldier_layer = '';

            $use_calculator_data = get_post_meta($variation_id, 'Use_Calculator');
            if (!empty($use_calculator_data)) $use_calculator = $use_calculator_data[0];
            $product_height = get_post_meta($variation_id, 'Height');
            $product_max_height = get_post_meta($variation_id, 'Max_Height');
            $product_family = get_post_meta($variation_id, 'Product_Family');
            $product_family_code = get_post_meta($variation_id, 'Product_Family_Code');
            $product_cat = get_post_meta($variation_id, 'Categories');
            $random_bandle = get_post_meta($variation_id, 'Random_Configuration');
            $swatchs = get_post_meta($variation_id, 'Swatchs');
            $packaging_data = get_post_meta($variation_id, 'Packaging_Data');
            if (!empty($packaging_data)) {
                if (isset($packaging_data[0]['Sailor_Lnft_per_Unit'])) $sailor_crate = $packaging_data[0]['Sailor_Lnft_per_Unit'];
                if (isset($packaging_data[0]['Sailor_Lnft_per_Layer'])) $sailor_layer = $packaging_data[0]['Sailor_Lnft_per_Layer'];
                if (isset($packaging_data[0]['Soldier_Lnft_per_Layer'])) $soldier_layer = $packaging_data[0]['Soldier_Lnft_per_Layer'];
            }

            if (!empty($swatchs[0][0]) && isset($swatchs[0][0]['Component_Picture']) && ($use_calculator != 'false')) {

                foreach ($swatchs[0][0]['Component_Picture'] as $component) {
                    if ($random_bandle[0] == 'true')
                        $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . __('Random Bundle', 'unilock');
                    else
                        $this_title = $product_family[0] . ' (' . $product_height[0] . ') ' . $component['Component_Description'];


                    $this_title = str_replace('"', "in", $this_title);

                    $jsonProdArr = array(
                        'id' => $variation_id,
                        'title' => $this_title,
                        'cat' => $product_cat[0][0],
                        'height' => $product_height[0],
                        'family' => $product_family_code[0],
                        'family_name' => $product_family[0],
                        'use_calc' => $use_calculator,
                    );

                    $checkOrientation = $addToCoping = true;

                    if (empty($sailor_crate) || $sailor_crate == 0) $jsonProdArr['sailor_or'] = false;
                    else $jsonProdArr['sailor_or'] = true;
                    if (empty($soldier_layer) || $soldier_layer == 0) $jsonProdArr['soldier_or'] = false;
                    else $jsonProdArr['soldier_or'] = true;
                    if ($jsonProdArr['sailor_or'] == false && $jsonProdArr['soldier_or'] == false) $checkOrientation = false;

                    //-- miss stone prods if height !=50 (22-stones / 50-coping / 150 - steps)
                    //if ($product_cat[0][0] == $stone_cat_id && $product_height[0] != '50MM' &&  $stone_cat_id != '') $addToCoping = false;

                    $jsonProd = json_encode($jsonProdArr);
                    if (!array_key_exists($this_title, $select_single) && $addToCoping && $checkOrientation && !empty($this_title)) {
                        $select_single[$this_title] = "<li data-id='" . $variation_id . "' data-product='" . $jsonProd . "'>" . $this_title . "</li>";
                    }
                }
            }
        }

        if (!empty($select_single)) {
            ksort($select_single);
            foreach ($select_single as $select_item) {
                $select .= $select_item;
            }
        }
        restore_current_blog();
        return $select;
    }

    /**
     * Loading Script Style
     */
    public function uni_pro_contractor_scripts()
    {
        if (is_page(PRODUCTS_IN_PROJECT_PAGE) || is_page(CREATE_PROJECT_PAGE) || is_page(EDIT_PROJECT_PAGE) || is_page(RESULT_PROJECT_PAGE)) {
            wp_enqueue_style('uni-pro-calculator-style-main', $this->THEME_URL_CALCULATOR_ASSETS . '/css/calculator-main.css', '', $this->SCRIPT_VER);
            wp_enqueue_script('uni-pro-inputmask', $this->THEME_URL_CALCULATOR_ASSETS . '/js/jquery.inputmask.min.js', array('jquery'), $this->SCRIPT_VER, true);
            wp_enqueue_script('uni-pro-calculator-global', $this->THEME_URL_CALCULATOR_ASSETS . '/js/calculator-global.js', array('jquery'), $this->SCRIPT_VER, true);
            wp_enqueue_script('uni-pro-calculator-js', $this->THEME_URL_CALCULATOR_ASSETS . '/js/pro-calculator.js', array('jquery'), $this->SCRIPT_VER, true);

            $unilock = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'child_theme' => $this->THEME_URL_CALCULATOR_ASSETS,
                'print_url' => get_permalink(PRINT_PROJECT_PAGE),
                'result_url' => get_permalink(RESULT_PROJECT_PAGE),
                'error_server' => __('Server error. Try again.', 'unilock'),
                'error_location' => __('Location not found, please try again.', 'unilock')
            );
            wp_localize_script('uni-pro-calculator-js', 'ajax', $unilock);
        }
    }

    /**
     * Loading Script Style
     */
    public function uni_pro_contractor_scripts_footer()
    {
        if (is_page(PRODUCTS_IN_PROJECT_PAGE) || is_page(CREATE_PROJECT_PAGE) || is_page(EDIT_PROJECT_PAGE) || is_page(RESULT_PROJECT_PAGE)) {
            wp_enqueue_style('uni-pro-calculator-style', $this->THEME_URL_CALCULATOR_ASSETS . '/css/calculator-style.css', '', $this->SCRIPT_VER);
            wp_enqueue_style('uni-pro-calculator-style-pro', $this->THEME_URL_CALCULATOR_ASSETS . '/css/pro-calculator.css', '', $this->SCRIPT_VER);
        }
    }

    /**
     * Loading Breadcrumbs
     */
    public function uni_pro_breadcrumbs($parent_id)
    {
        ob_start(); ?>
        <ul class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo home_url('/'); ?>"><span itemprop="name"><?php _e('Home', 'unilock'); ?></span> &gt;</a>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo get_permalink(INSTALLATION_PAGE); ?>"><span itemprop="name"><?php echo get_the_title(INSTALLATION_PAGE); ?></span> &gt;</a>
            </li>
            <li class="active">
                <span itemprop="name"><?php echo get_the_title($parent_id); ?></span>
            </li>
        </ul>
<?php
        $breadcrumbs = ob_get_clean();
        return $breadcrumbs;
    }

    /**
     * Rename project
     */
    public function uni_pro_rename_project()
    {
        $all_data_prod = [];
        $project_new_name = (isset($_POST['project_new_name']) ? $_POST['project_new_name'] : '');
        if (empty($project_new_name))
            $project_new_name = __('Project', 'unilock') . ' ' . strtotime("now"); // project name optional

        $old_transient_name = (isset($_COOKIE['project_trans']) ? $_COOKIE['project_trans'] : '');
        $new_transient_name = $project_new_name . '_data_' . strtotime("now");

        if (empty($old_transient_name))
            die(json_encode(array('success' => false)));

        //--save new project name
        setcookie('project_name', $project_new_name, time() + 2 * HOUR_IN_SECONDS, "/");
        setcookie('project_trans', $new_transient_name, time() + 2 * HOUR_IN_SECONDS, "/");

        $all_data_prod = get_transient($old_transient_name);
        //--resave data transient
        set_transient($new_transient_name, $all_data_prod, 2 * HOUR_IN_SECONDS);
        delete_transient($old_transient_name);

        die(json_encode(array('success' => true, 'new_name' => $project_new_name)));
    }

    /**
     * Change region transient
     *
     */
    public function uni_pro_region_transient_delete()
    {
        $transient_name = (isset($_COOKIE['project_trans']) ? $_COOKIE['project_trans'] : '');
        if (!empty($transient_name)) {
            delete_transient($transient_name);
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        die();
    }
}

new UnilockProCalculator();
