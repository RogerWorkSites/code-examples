<?php

/**
 * Products Class
 */

//if (!defined('ABSPATH')) exit;


class Products extends UniPublicClass
{
    private $is_user_login;
    private $current_category;
    private $main_product_id;

    private $CASH_DIR_FILES = ABSPATH . 'wp-content/uploads/product_cash_files';

    public function __construct($product_arr = array())
    {
        $this->current_category = get_queried_object();
        $this->is_user_login = is_user_logged_in();
        $this->main_product_arr = $product_arr;


        if (!file_exists($this->CASH_DIR_FILES)) mkdir($this->CASH_DIR_FILES, 0755);
    }

    /*
    * Product Category Functions
    * */
    public function product_ajax()
    {
        $term_slug = $filters = $products = '';

        if (isset($_REQUEST['term_slug']) && !empty($_REQUEST['term_slug'])) {
            $term_slug = $_REQUEST['term_slug'];

            $filters = $this->getFilters($term_slug);
            $products = $this->getCategoryProducts($term_slug);
        } else {
            $filters = $this->getFilters();
            $products = $this->getProducts();
        }

        echo json_encode(array('success' => true, 'filters' => $filters, 'products' => $products['html'], 'count' => $products['count']));
        die();
    }

    public function getFilters($term_slug = '')
    {
        $get_params = array();
        if ('GET' == $_SERVER['REQUEST_METHOD'] && !empty($_GET)) {
            $get_params = $_GET;
        } else if (isset($_REQUEST['filters_arr'])) {
            $get_params = $_REQUEST['filters_arr'];
        }

        $data = $this->getProductFilter($term_slug);
        $product_filter = $data['filters'];
        ob_start(); ?>
        <div class="sidebar-header">
            <div class="sidebar-toggle"><?php _e('Hide Filter', 'unilock') ?></div>
            <div class="sidebar-icon">
                <svg width="41" height="16" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs>
                        <path id="a" fill="#fff" d="M32 1l-1.41 1.41L36.17 8H0v2h36.17l-5.58 5.59L32 17l8-8z"></path>
                    </defs>
                    <use xlink:href="#a" transform="matrix(-1 0 0 1 40.804 -1)" fill-rule="evenodd"></use>
                </svg>
            </div>
        </div>
        <div class="sidebar-content">
            <div class="sidebar-info" <?php if ($data['count'] != 0) echo 'style="display: block;"' ?>>
                <div class="input-hint"><span class="d-none d-md-block"><?php _e('Filter', 'unilock') ?></span> <span class="d-md-none"><?php _e('Filter (Scroll down after applying filters)', 'unilock') ?></span></div>
                <p><?php echo $data['count']; ?> Selected</p>
                <button class="clear-btn js_clear_product_filter"><?php _e('Clear Filter', 'unilock') ?></button>
            </div>
            <?php // Category
            if (!empty($product_filter['product_cat'])) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_cat']) || !empty($term_slug)) echo 'active'; ?>" data-type="product_cat" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title"><?php _e('Category', 'unilock') ?></div>
                    <div class="sf-list" <?php if (!empty($get_params['product_cat']) || !empty($term_slug)) echo 'style="display: block;"'; ?>>
                        <?php
                        if (!empty($product_filter['product_cat'])) {
                            foreach ($product_filter['product_cat'] as $key => $product_cat) {
                                $get_product_cat = '';
                                if (!empty($get_params['product_cat']) || !empty($term_slug)) {
                                    $get_product_cat = !empty($term_slug) ? $term_slug : explode(',', $get_params['product_cat']);
                                }

                                if (empty($term_slug)) { ?>
                                    <label class="sf-checkbox <?php if (!empty($get_product_cat) && ((is_array($get_product_cat) && in_array($product_cat['slug'], $get_product_cat)) || $product_cat['slug'] == $get_product_cat)) echo "checked"; ?>">
                                        <input class="js_product_filter_input" type="radio" name="product_cat" value="<?php echo $product_cat['slug']; ?>" <?php echo (!empty($get_product_cat) && in_array($product_cat['slug'], $get_product_cat) ? " checked" : "") ?> hidden>
                                        <span class="check-text"><?php echo ucwords(strtolower(trim($product_cat['name']))) . ' (' . $product_cat['count'] . ')'; ?></span>
                                    </label>
                                <?php } else {
                                    $term_link = get_term_link($product_cat['slug'], 'product_cat'); ?>
                                    <label class="sf-checkbox sf-checkbox-cat <?php if (!empty($get_product_cat) && $product_cat['slug'] === $get_product_cat) echo "checked"; ?>">
                                        <a href="<?php echo $term_link; ?>" class="check-text"><?php echo ucwords(strtolower(trim($product_cat['name']))) . ' (' . $product_cat['count'] . ')'; ?></a>
                                    </label>
                                <?php } ?>
                        <?php
                            }
                        } ?>
                    </div>
                </div>
            <?php }
            // Style
            if (!empty($product_filter['product_style'])) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_style'])) echo 'active'; ?>" data-type="product_cat" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title">Style</div>
                    <div class="sf-list" <?php if (!empty($get_params['product_style'])) echo 'style="display: block;"'; ?>>
                        <?php
                        foreach ($product_filter['product_style'] as $product_style) {
                            $get_product_style = '';
                            if (!empty($get_params['product_style'])) {
                                $get_product_style = explode(',', $get_params['product_style']);
                            }
                        ?>
                            <label class="sf-checkbox <?php echo (!empty($get_product_style) && in_array($product_style['slug'], $get_product_style) ? "checked" : "") ?>">
                                <input class="js_product_filter_input" type="radio" name="product_style" value="<?php echo $product_style['slug'] ?>" <?php echo (!empty($get_product_style) && in_array($product_style['slug'], $get_product_style) ? " checked" : "") ?> hidden>
                                <span class="check-text"><?php echo $product_style['name'] . ' (' . $product_style['count'] . ')'; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
            <?php }

            // Style
            if (!empty($product_filter['product_color'])) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_color'])) echo 'active'; ?>" data-type="product_color" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title">Color</div>
                    <div class="sf-list-color sf-list" <?php if (!empty($get_params['product_color'])) echo 'style="display: block;"'; ?>>
                        <?php
                        foreach ($product_filter['product_color'] as $product_color) {
                            $get_product_color = '';
                            if (!empty($get_params['product_color'])) {
                                $get_product_color = explode(',', $get_params['product_color']);
                            }
                            $color_swatch = '';
                            switch ($product_color['slug']) {
                                case 'beige':
                                    $color_swatch = 'background: #C6B997';
                                    break;
                                case 'black':
                                    $color_swatch = 'background: #2D2A2A';
                                    break;
                                case 'brown':
                                    $color_swatch = 'background: #997628';
                                    break;
                                case 'grey':
                                    $color_swatch = 'background: #B6B6B9';
                                    break;
                                case 'red':
                                    $color_swatch = 'background: #AF0303';
                                    break;
                                case 'white':
                                    $color_swatch = 'border: 1px solid #C8C8C8; background: #E4E4E4';
                                    break;
                            } ?>
                            <label class="sf-checkbox <?php echo (!empty($get_product_color) && in_array($product_color['slug'], $get_product_color) ? "checked" : "") ?>">
                                <input class="js_product_filter_input" type="radio" name="product_color" value="<?php echo $product_color['slug'] ?>" <?php echo (!empty($get_product_color) && in_array($product_color['slug'], $get_product_color) ? " checked" : "") ?> hidden>
                                <span class="check-text"><?php echo $product_color['name'] . ' (' . $product_color['count'] . ')' ?></span>
                                <span class="icon bg-color-4" style="<?php echo $color_swatch ?>"></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
            <?php }
            // Style
            if (!empty($product_filter['product_price'])) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_price'])) echo 'active'; ?>" data-type="product_price" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title">Price Range</div>
                    <div class="sf-list" <?php if (!empty($get_params['product_price'])) echo 'style="display: block;"'; ?>>
                        <div class="input-group">
                            <?php
                            ksort($product_filter['product_price']);

                            foreach ($product_filter['product_price'] as $product_price) {
                                $get_product_price = '';
                                if (!empty($get_params['product_price'])) {
                                    $get_product_price = explode(',', $get_params['product_price']);
                                }
                                $name_count = strlen($product_price['name']); ?>

                                <label class="checkbox-entry price-checkbox <?php echo (!empty($get_product_price) && in_array($product_price['slug'], $get_product_price) ? "checked" : "") ?>">
                                    <input class="js_product_filter_input" type="radio" name="product_price" value="<?php echo $product_price['slug'] ?>" <?php echo (!empty($get_product_price) && in_array($product_price['slug'], $get_product_price) ? " checked" : "") ?>>
                                    <span><?php echo '<em>' . $product_price['name'] . '</em> '; ?></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php }
            // Style
            if (!empty($product_filter['product_tech'])) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_tech'])) echo 'active'; ?>" data-type="product_tech" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title">Technologies</div>
                    <div class="sf-list" <?php if (!empty($get_params['product_tech'])) echo 'style="display: block;"'; ?>>
                        <?php
                        if (!empty($product_filter['product_tech'])) {
                            foreach ($product_filter['product_tech'] as $product_tech) {
                                $get_product_tech = '';
                                if (!empty($get_params['product_tech'])) {
                                    $get_product_tech = explode(',', $get_params['product_tech']);
                                }
                        ?>
                                <label class="sf-checkbox <?php echo (!empty($get_product_tech) && in_array($product_tech['slug'], $get_product_tech) ? "checked" : "") ?>">
                                    <input class="js_product_filter_input" type="radio" name="product_tech" value="<?php echo $product_tech['slug'] ?>" <?php echo (!empty($get_product_tech) && in_array($product_tech['slug'], $get_product_tech) ? " checked" : "") ?> hidden>
                                    <span class="check-text"><?php echo $product_tech['name'] . ' (' . $product_tech['count'] . ')'; ?></span>
                                </label>
                        <?php
                            }
                        } ?>
                    </div>
                </div>
            <?php }
            // Style
            if (!empty($product_filter['product_appl'])) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_appl'])) echo 'active'; ?>" data-type="product_appl" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title">Applications</div>
                    <div class="sf-list" <?php if (!empty($get_params['product_appl'])) echo 'style="display: block;"'; ?>>
                        <?php
                        foreach ($product_filter['product_appl'] as $product_appl) {
                            $get_product_appl = '';
                            if (!empty($get_params['product_appl'])) {
                                $get_product_appl = explode(',', $get_params['product_appl']);
                            }
                            if ($product_appl['slug'] != 'structural_wall_product') { ?>
                                <label class="sf-checkbox <?php echo (!empty($get_product_appl) && in_array($product_appl['slug'], $get_product_appl) ? "checked" : "") ?>">
                                    <input class="js_product_filter_input" type="radio" name="product_appl" value="<?php echo $product_appl['slug'] ?>" <?php echo (!empty($get_product_appl) && in_array($product_appl['slug'], $get_product_appl) ? " checked" : "") ?> hidden>
                                    <span class="check-text"><?php echo $product_appl['name'] . ' (' . $product_appl['count'] . ')'; ?></span>
                                </label>
                        <?php }
                        } ?>
                    </div>
                </div>
            <?php }
            // Style
            if (!empty($product_filter['product_paver_thickness']) && ((isset($get_params['product_cat']) && strpos($get_params['product_cat'], 'paver') !== false) || (strpos($term_slug, 'paver') !== false))) { ?>
                <div class="side-filter <?php if (!empty($get_params['product_paver_thickness'])) echo 'active'; ?>" data-type="product_paver_thickness" data-cat-name="<?php echo $term_slug; ?>">
                    <div class="sf-title">Paver Thickness</div>
                    <div class="sf-list" <?php if (!empty($get_params['product_paver_thickness'])) echo 'style="display: block;"'; ?>>
                        <?php
                        $array_paver_thickness = $this->array_sort($product_filter['product_paver_thickness'], 'name', SORT_ASC);
                        foreach ($array_paver_thickness as $product_paver_thickness) {
                            $get_product_paver_thickness = '';
                            if (!empty($get_params['product_paver_thickness'])) {
                                $get_product_paver_thickness = explode(',', $get_params['product_paver_thickness']);
                            } ?>
                            <label class="sf-checkbox <?php echo (!empty($get_product_paver_thickness) && in_array($product_paver_thickness['slug'], $get_product_paver_thickness) ? "checked" : "") ?>">
                                <input class="js_product_filter_input" type="radio" name="product_paver_thickness" value="<?php echo $product_paver_thickness['slug'] ?>" <?php echo (!empty($get_product_paver_thickness) && in_array($product_paver_thickness['slug'], $get_product_paver_thickness) ? " checked" : "") ?> hidden>
                                <span class="check-text"><?php echo $product_paver_thickness['name'] . ' (' . $product_paver_thickness['count'] . ')'; ?></span>
                            </label>
                        <?php
                        } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php return ob_get_clean();
    }

    public function getProductFilter($term_slug = ''): array
    {
        $regions_product = cont_check_region_user();
        $count = 0;

        $get_params = array();

        if ('GET' == $_SERVER['REQUEST_METHOD'] && !empty($_GET)) {
            $get_params = $_GET;
        } else if (isset($_REQUEST['filters_arr'])) {
            $get_params = $_REQUEST['filters_arr'];
        }

        $filterArgs = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_region',
                    'field' => 'slug',
                    'terms' => $regions_product
                )
            )
        );

        if ((isset($get_params['product_cat']) && !empty($get_params['product_cat'])) || !empty($term_slug)) {
            $get_products = !empty($term_slug) ? [$term_slug] : explode(',', $get_params['product_cat']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $get_products
            ];
            $count += count($get_products);
        }
        if (isset($get_params['product_style']) && !empty($get_params['product_style'])) {
            $get_style = explode(',', $get_params['product_style']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_style',
                'field' => 'slug',
                'terms' => $get_style
            ];
            $count += count($get_style);
        }
        if (isset($get_params['product_color']) && !empty($get_params['product_color'])) {
            $get_color = explode(',', $get_params['product_color']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_color',
                'field' => 'slug',
                'terms' => $get_color
            ];
            $count += count($get_color);
        }
        if (isset($get_params['product_price']) && !empty($get_params['product_price'])) {
            $get_price = explode(',', $get_params['product_price']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_price',
                'field' => 'slug',
                'terms' => $get_price
            ];
            $count += count($get_price);
        }
        if (isset($get_params['product_tech']) && !empty($get_params['product_tech'])) {
            $get_tech = explode(',', $get_params['product_tech']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_tech',
                'field' => 'slug',
                'terms' => $get_tech
            ];
            $count += count($get_tech);
        }
        if (isset($get_params['product_appl']) && !empty($get_params['product_appl'])) {
            $get_appl = explode(',', $get_params['product_appl']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_appl',
                'field' => 'slug',
                'terms' => $get_appl
            ];
            $count += count($get_appl);
        }
        if (isset($get_params['product_paver_thickness']) && !empty($get_params['product_paver_thickness'])) {
            $get_appl = explode(',', $get_params['product_paver_thickness']);
            $filterArgs['tax_query'][] = [
                'taxonomy' => 'product_paver_thickness',
                'field' => 'slug',
                'terms' => $get_appl
            ];
            $count += count($get_appl);
        }

        $filterArgsNoProducts = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_region',
                    'field' => 'slug',
                    'terms' => $regions_product
                )
            )
        );
        if (isset($get_params['product_style']) && !empty($get_params['product_style'])) {
            $get_style = explode(',', $get_params['product_style']);
            $filterArgsNoProducts['tax_query'][] = [
                'taxonomy' => 'product_style',
                'field' => 'slug',
                'terms' => $get_style
            ];
        }
        if (isset($get_params['product_color']) && !empty($get_params['product_color'])) {
            $get_color = explode(',', $get_params['product_color']);
            $filterArgsNoProducts['tax_query'][] = [
                'taxonomy' => 'product_color',
                'field' => 'slug',
                'terms' => $get_color
            ];
        }
        if (isset($get_params['product_price']) && !empty($get_params['product_price'])) {
            $get_price = explode(',', $get_params['product_price']);
            $filterArgsNoProducts['tax_query'][] = [
                'taxonomy' => 'product_price',
                'field' => 'slug',
                'terms' => $get_price
            ];
        }
        if (isset($get_params['product_tech']) && !empty($get_params['product_tech'])) {
            $get_tech = explode(',', $get_params['product_tech']);
            $filterArgsNoProducts['tax_query'][] = [
                'taxonomy' => 'product_tech',
                'field' => 'slug',
                'terms' => $get_tech
            ];
        }
        if (isset($get_params['product_appl']) && !empty($get_params['product_appl'])) {
            $get_appl = explode(',', $get_params['product_appl']);
            $filterArgsNoProducts['tax_query'][] = [
                'taxonomy' => 'product_appl',
                'field' => 'slug',
                'terms' => $get_appl
            ];
        }
        if (isset($get_params['product_paver_thickness']) && !empty($get_params['product_paver_thickness'])) {
            $get_appl = explode(',', $get_params['product_paver_thickness']);
            $filterArgsNoProducts['tax_query'][] = [
                'taxonomy' => 'product_paver_thickness',
                'field' => 'slug',
                'terms' => $get_appl
            ];
        }

        $filter = new WP_Query($filterArgs);
        $filterNoProducts = new WP_Query($filterArgsNoProducts);

        $array_filter = [];
        foreach ($filterNoProducts->posts as $post) {
            //Categories
            $category_terms = get_the_terms($post, 'product_cat');
            if (is_array($category_terms)) {
                foreach ($category_terms as $cur_term) {
                    if (isset($array_filter['product_cat'][$cur_term->term_id])) {
                        $array_filter['product_cat'][$cur_term->term_id]['count']++;
                    } else {
                        $number_position = get_field('number_position', $cur_term->taxonomy . '_' . $cur_term->term_id);
                        $category_new_name = get_field('category_new_name', $cur_term->taxonomy . '_' . $cur_term->term_id);

                        $array_filter['product_cat'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_cat'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_cat'][$cur_term->term_id]['name'] = !empty($category_new_name) ? esc_html($category_new_name) : $cur_term->name;
                        $array_filter['product_cat'][$cur_term->term_id]['number_position'] = $number_position;
                    }
                }
            }
        }

        foreach ($filter->posts as $post) {
            //Categories
            $color_terms = get_the_terms($post, 'product_style');
            if (is_array($color_terms)) {
                foreach ($color_terms as $cur_term) {
                    if (isset($array_filter['product_style'][$cur_term->term_id])) {
                        $array_filter['product_style'][$cur_term->term_id]['count']++;
                    } else {
                        $array_filter['product_style'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_style'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_style'][$cur_term->term_id]['name'] = $cur_term->name;
                    }
                }
            }
        }

        foreach ($filter->posts as $post) {
            //Categories
            $color_terms = get_the_terms($post, 'product_color');

            if (is_array($color_terms)) {
                foreach ($color_terms as $cur_term) {
                    if (isset($array_filter['product_color'][$cur_term->term_id])) {
                        $array_filter['product_color'][$cur_term->term_id]['count']++;
                    } else {
                        $array_filter['product_color'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_color'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_color'][$cur_term->term_id]['name'] = $cur_term->name;
                    }
                }
            }
        }

        foreach ($filter->posts as $post) {
            //Categories
            $color_terms = get_the_terms($post, 'product_price');
            if (is_array($color_terms)) {
                foreach ($color_terms as $cur_term) {
                    if (isset($array_filter['product_price'][$cur_term->term_id])) {
                        $array_filter['product_price'][$cur_term->term_id]['count']++;
                    } else {
                        $array_filter['product_price'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_price'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_price'][$cur_term->term_id]['name'] = $cur_term->name;
                    }
                }
            }
        }

        foreach ($filter->posts as $post) {
            //Categories
            $color_terms = get_the_terms($post, 'product_tech');
            if (is_array($color_terms)) {
                foreach ($color_terms as $cur_term) {
                    if (isset($array_filter['product_tech'][$cur_term->term_id])) {
                        $array_filter['product_tech'][$cur_term->term_id]['count']++;
                    } else {
                        $number_position = get_field('number_position', $cur_term->taxonomy . '_' . $cur_term->term_id);
                        $array_filter['product_tech'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_tech'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_tech'][$cur_term->term_id]['name'] = $cur_term->name;
                        $array_filter['product_tech'][$cur_term->term_id]['number_position'] = $number_position;
                    }
                }
            }
        }

        foreach ($filter->posts as $post) {
            //Categories
            $color_terms = get_the_terms($post, 'product_appl');
            if (is_array($color_terms)) {
                foreach ($color_terms as $cur_term) {
                    if (isset($array_filter['product_appl'][$cur_term->term_id])) {
                        $array_filter['product_appl'][$cur_term->term_id]['count']++;
                    } else {
                        $array_filter['product_appl'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_appl'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_appl'][$cur_term->term_id]['name'] = $cur_term->name;
                    }
                }
            }
        }

        foreach ($filter->posts as $post) {
            //Categories
            $color_terms = get_the_terms($post, 'product_paver_thickness');
            if (is_array($color_terms)) {
                foreach ($color_terms as $cur_term) {
                    if (isset($array_filter['product_paver_thickness'][$cur_term->term_id])) {
                        $array_filter['product_paver_thickness'][$cur_term->term_id]['count']++;
                    } else {
                        $array_filter['product_paver_thickness'][$cur_term->term_id]['count'] = 1;
                        $array_filter['product_paver_thickness'][$cur_term->term_id]['slug'] = $cur_term->slug;
                        $array_filter['product_paver_thickness'][$cur_term->term_id]['name'] = $cur_term->name;
                    }
                }
            }
        }

        $category_filter = [];
        if (!empty($array_filter['product_cat'])) {
            foreach ($array_filter['product_cat'] as $key => $category) {
                if (!empty($category['number_position'])) {
                    $category_filter[$key] = $category['number_position'];
                }
            }
            array_multisort($category_filter, $array_filter['product_cat']);
        }

        wp_reset_postdata();
        return array('filters' => $array_filter, 'count' => $count);
    }

    private function array_sort($array, $on, $order = SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array, SORT_NATURAL | SORT_FLAG_CASE);
                    break;
                case SORT_DESC:
                    arsort($sortable_array, SORT_NATURAL | SORT_FLAG_CASE);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    public function getCategoryProducts($term_slug = '')
    {
        $html = $prodsCount = '';
        $allProductsArr = $this->getAllProducts($term_slug);
        if (!empty($allProductsArr['prods_arr'])) {
            $prodsCount = $allProductsArr['count'];
            ob_start(); ?>
            <div class="products-row">
                <?php $html_products = $this->getProduct($allProductsArr['prods_arr']);
                if (!empty($html_products)) echo $html_products; ?>
            </div>
            <?php
            $html = ob_get_clean();
        }
        return array('html' => $html, 'count' => $prodsCount);
    }

    private function getAllProducts($term_slug = '', $application = '')
    {
        $regions_product = cont_check_region_user();
        $get_params = array();

        if ('GET' == $_SERVER['REQUEST_METHOD'] && !empty($_GET)) {
            $get_params = $_GET;
        } else if (isset($_REQUEST['filters_arr'])) {
            $get_params = $_REQUEST['filters_arr'];
        } else if (isset($_REQUEST['product_cat']) && !empty($_REQUEST['product_cat'])) {
            $get_params = $_REQUEST['product_cat'];
        } else if (isset($_REQUEST['product_appl']) && !empty($_REQUEST['product_appl'])) {
            $get_params = $_REQUEST['product_appl'];
        }

        $prodArgs = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'fields' => 'ids',
            'order' => 'ASC',
            'orderby' => 'title',
            'posts_per_page' => -1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_region',
                    'field' => 'slug',
                    'terms' => $regions_product
                )
            )
        );
        if ((isset($get_params['product_cat']) && !empty($get_params['product_cat'])) || !empty($term_slug)) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => !empty($term_slug) ? $term_slug : explode(',', $get_params['product_cat'])
            ];
        }
        if (isset($get_params['product_style']) && !empty($get_params['product_style'])) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_style',
                'field' => 'slug',
                'terms' => explode(',', $get_params['product_style'])
            ];
        }
        if (isset($get_params['product_color']) && !empty($get_params['product_color'])) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_color',
                'field' => 'slug',
                'terms' => explode(',', $get_params['product_color'])
            ];
        }
        if (isset($get_params['product_price']) && !empty($get_params['product_price'])) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_price',
                'field' => 'slug',
                'terms' => explode(',', $get_params['product_price'])
            ];
        }
        if (isset($get_params['product_tech']) && !empty($get_params['product_tech'])) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_tech',
                'field' => 'slug',
                'terms' => explode(',', $get_params['product_tech'])
            ];
        }

        if (isset($get_params['product_appl']) && !empty($get_params['product_appl'])) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_appl',
                'field' => 'slug',
                'terms' => explode(',', $get_params['product_appl'])
            ];
        }

        if (isset($get_params['product_paver_thickness']) && !empty($get_params['product_paver_thickness'])) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_paver_thickness',
                'field' => 'slug',
                'terms' => explode(',', $get_params['product_paver_thickness'])
            ];
        }

        $allProds = get_posts($prodArgs);

        if (!empty($term_slug)) {
            return array('prods_arr' => $allProds, 'count' => count($allProds));
        }

        $res = $prod_cat = '';
        $prod_cats_arr = $category_filter = [];
        foreach ($allProds as $prod_id) {
            if (empty($application)) $prod_cat_terms = get_the_terms($prod_id, 'product_cat');
            else $prod_cat_terms = get_the_terms($prod_id, 'product_appl');

            if (!empty($prod_cat_terms)) {
                $prod_cats_str = '';
                foreach ($prod_cat_terms as $term) {
                    $prod_ids_arr = [];

                    $cat_name = $term->name;
                    $cat_term_id = $term->term_id;
                    $cat_term_slug = $term->slug;
                    $number_position = get_field('number_position', $term->taxonomy . '_' . $term->term_id);

                    $category_filter[$cat_term_id] = $number_position;

                    if (array_key_exists($cat_term_id, $prod_cats_arr)) {
                        $prod_ids_arr = $prod_cats_arr[$cat_term_id]['prods'];
                        $prod_ids_arr[] = $prod_id;
                        $prod_cats_arr[$cat_term_id]['prods'] = $prod_ids_arr;
                    } else {
                        $prod_ids_arr[] = $prod_id;
                        $prod_cats_arr[$cat_term_id] = array('cat_id' => $cat_term_id, 'cat_slug' => $cat_term_slug, 'prods' => $prod_ids_arr);
                    }
                }
            }
        }


        if (!empty($category_filter)) array_multisort($category_filter, $prod_cats_arr);

        return array('prods_arr' => $prod_cats_arr, 'count' => count($allProds));
    }

    private function getProduct($prod_ids_arr, $cat_name = '', $show_all_products = false)
    {
        $prod_html = '';
        if (!empty($prod_ids_arr)) {
            ob_start();
            foreach ($prod_ids_arr as $key => $prod_id) {
                $current_term_slug = $colors = '';
                $prod_link = get_the_permalink($prod_id);
                $prod_title = get_the_title($prod_id);
                $thumbnail_url = get_field('thumbnail', $prod_id);
                $img_url = get_field('preview_image', $prod_id);
                $_prod_hex_code = get_field('_prod_hex_code', $prod_id);

                if (isset($_prod_hex_code) && !empty($_prod_hex_code)) $colors = $_prod_hex_code;

                if (empty($thumbnail_url)) {
                    $thumbnail_url = get_field('pd_image', $prod_id);
                    $thumbnail_url = $thumbnail_url['url'] ?? null;
                }
                if (empty($img_url)) {
                    $img_url = get_field('banner_background', $prod_id);
                    $img_url = $img_url['url'] ?? null;
                }

                if (isset($this->current_category->slug) && !empty($this->current_category->slug)) $current_term_slug = '?cat=' . $this->current_category->slug;
                else if (isset($_GET['product_cat']) && !empty($_GET['product_cat'])) $current_term_slug = '?cat=' . trim($_GET['product_cat']);
                else if (isset($_REQUEST['filters_arr']['product_cat']) && !empty($_REQUEST['filters_arr']['product_cat'])) $current_term_slug = '?cat=' . trim($_REQUEST['filters_arr']['product_cat']);

                $prod_color_terms = get_the_terms($prod_id, 'product_color'); ?>
                <div class="product-item">
                    <a href="<?php echo esc_url($prod_link . $current_term_slug); ?>" class="hover" style="background-image:url(<?php echo esc_url($img_url); ?>)">
                        <span class="btn btn-1 color3"><?php _e('Learn More', 'unilock') ?></span>
                    </a>
                    <div class="image" style="background-image:url(<?php echo (!empty($thumbnail_url) && $thumbnail_url != '1') ? esc_url($thumbnail_url) : esc_url($img_url); ?>)"></div>
                    <div class="product-item-title">
                        <p><?php echo esc_html($prod_title); ?></p>
                    </div>
                    <?php if (!empty($colors)) : ?>
                        <div class="product-item-colors">
                            <?php foreach ($colors as $color) : ?>
                                <div class="circle bg-color-1" style="background: #<?php echo $color ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($prod_color_terms) : ?>
                        <div class="product-item-colors">
                            <?php foreach ($prod_color_terms as $color_terms) :
                                $color_swatch = '';
                                switch ($color_terms->slug) {
                                    case 'beige':
                                        $color_swatch = 'background: #C6B997';
                                        break;
                                    case 'black':
                                        $color_swatch = 'background: #2D2A2A';
                                        break;
                                    case 'brown':
                                        $color_swatch = 'background: #997628';
                                        break;
                                    case 'grey':
                                        $color_swatch = 'background: #B6B6B9';
                                        break;
                                    case 'red':
                                        $color_swatch = 'background: #AF0303';
                                        break;
                                    case 'white':
                                        $color_swatch = 'border: 1px solid #C8C8C8; background: #E4E4E4';
                                        break;
                                } ?>
                                <div class="circle bg-color-1" style="<?php echo $color_swatch ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            }
            if (count($prod_ids_arr) > 5 && $cat_name && !$show_all_products) { ?>
                <a href="#more" class="product-item more">
                    <span class="more-icon">
                        <img src="<?php echo CHILD_THEME_URI; ?>/img/load-more.svg" alt="Load More Product" width="100px" height="100px">
                    </span>
                    <span class="more-title"><?php _e('Load More', 'unilock') ?><?php echo $cat_name; ?></span>
                </a>
            <?php
            }

            $prod_html = ob_get_clean();
        }
        return $prod_html;
    }

    public function getProducts()
    {
        $is_application = $html = $prodsCount = $get_params = $category_new_name = '';
        $show_all_product = false;

        if (isset($_REQUEST['product_cat']) && !empty($_REQUEST['product_cat'])) $get_params = $_REQUEST['product_cat'];
        else if (isset($_REQUEST['filters_arr']['product_cat']) && !empty($_REQUEST['filters_arr']['product_cat'])) $get_params = $_REQUEST['filters_arr']['product_cat'];

        /* FIX APPLICATION */
        /*else if (isset($_REQUEST['product_appl']) && !empty($_REQUEST['product_appl'])) {
            $get_params = $_REQUEST['product_appl'];
            $is_application = true;
        } else if (isset($_REQUEST['filters_arr']['product_appl']) && !empty($_REQUEST['filters_arr']['product_appl'])) {
            $get_params = $_REQUEST['filters_arr']['product_appl'];
            $is_application = true;
        }*/

        $allProductsArr = $this->getAllProducts('', $is_application);

        if (!empty($allProductsArr['prods_arr'])) {
            $prodsCount = $allProductsArr['count'];
            $cat_name = '';
            ob_start();
            foreach ($allProductsArr['prods_arr'] as $prod_ids_arr) {
                $cat_id = $prod_ids_arr['cat_id'];
                //$cat_slug = $prod_ids_arr['cat_slug'];

                if (!$is_application) $cat_terms = get_term_by('id', $cat_id, 'product_cat');
                else $cat_terms = get_term_by('id', $cat_id, 'product_appl');

                if (!empty($get_params) && $cat_terms->slug !== $get_params) continue;
                else if (!empty($get_params)) $show_all_product = true;
                if (!$is_application) $category_new_name = get_field('category_new_name', 'product_cat_' . $cat_id);

                if ($category_new_name) $cat_name = $category_new_name;
                else if ($cat_terms) $cat_name = $cat_terms->name;
                $html_products = $this->getProduct($prod_ids_arr['prods'], $cat_name, $show_all_product); ?>
                <?php if (!empty($html_products)) : ?>
                    <div class="products-block">
                        <div class="products-block-header">
                            <div class="title">
                                <div class="h5"><?php echo strtoupper($cat_name); ?></div>
                            </div>
                            <?php if (!$is_application) { ?><a href="<?php echo get_term_link($cat_id); ?>" class="link"><?php _e('See all', 'unilock') ?> <?php echo strtoupper($cat_name); ?></a> <?php } ?>
                        </div>
                        <div class="products-row <?php if (!empty($get_params)) echo 'show_all_products' ?>"><?php echo $html_products; ?></div>
                    </div>
                <?php endif; ?>
            <?php
            }
            $html = ob_get_clean();
        }
        return array('html' => $html, 'count' => $prodsCount);
    }

    /*
    * Single Product Functions
    * */

    public function create_files_json($type = 'LAYING PATTERN'): string
    {
        $regions = array('Ontario', 'Chicago', 'Michigan', 'Ohio', 'Boston', 'New York', 'Buffalo', 'Other');
        $path = [];

        foreach ($regions as $region) {
            $products = [];
            $product_files_path = CHILD_THEME_URL . '/products_files';
            $region_path = $product_files_path . '/' . $region;
            $type_path = $region_path . '/' . sanitize_title(trim($type));

            if (!file_exists($product_files_path)) mkdir($product_files_path, 0755);
            if (!file_exists($region_path)) mkdir($region_path, 0755);
            if (!file_exists($type_path)) mkdir($type_path, 0755);

            if ($region == 'Other') $region = 'Ontario';
            else if ($region == 'Buffalo') $region = 'Ohio';

            $product_ids = $this->get_all_region_product($region);

            foreach ($product_ids as $prod_id) {
                $files = [];
                $product_id = get_post_meta($prod_id, '_ho_prod_id')[0];
                $prod_files = $this->get_product_files($product_id, $type);
                $prod_title = get_the_title($prod_id);

                if (isset($prod_files['files']) && !empty($prod_files['files'])) {
                    $products[] = $prod_title;

                    $files['title'] = $prod_title;
                    $files['files'] = $prod_files['files'];
                    $files['type'] = $prod_files['type'];
                    $files['count'] = $prod_files['count'];

                    $file_name = $type_path . '/' . sanitize_title(trim($prod_title)) . '.json';

                    if (file_exists($file_name)) unlink($file_name);

                    file_put_contents($file_name, json_encode($files));
                    $path[] = $file_name;
                } else {
                    $file_name = $type_path . '/' . sanitize_title(trim($prod_title)) . '.json';
                    if (file_exists($file_name)) unlink($file_name);
                }
            }

            if (!empty($products)) {
                sort($products);
                file_put_contents($type_path . '/alfa_patterns_products.json', json_encode($products));
            } else {
                if (file_exists($type_path . '/alfa_patterns_products.json')) unlink($type_path . '/alfa_patterns_products.json');
            }
        }

        return 'SUCCESS CREATED';
    }

    /*
     * Create JSON Data Files
     * */

    public function get_all_region_product($regions_product = 'Ontario', $product_cat = '', $show_applications = false)
    {
        $prodArgs = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_region',
                    'field' => 'slug',
                    'terms' => $regions_product
                )
            )
        );

        if (isset($product_cat) && !empty($product_cat)) {
            $prodArgs['tax_query']['relation'] = 'AND';
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $product_cat
            ];
        }

        if (!$show_applications) {
            $prodArgs['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => array('accessories'),
                'operator' => 'NOT IN',
            ];
        }

        return get_posts($prodArgs);
    }

    public function get_product_files($product_id = 0, $type = 'LAYING PATTERN'): array
    {
        switch_to_blog(1);
        $variations_args = array(
            'post_type' => 'variation',
            'posts_per_page' => -1,
            'post_parent' => $product_id,
            'fields' => 'ids',
        );
        $variations = get_posts($variations_args);
        $variations_counter = 1;
        $files = [];

        foreach ($variations as $variation_id) {
            // Get PDFs
            $product_images = get_post_meta($variation_id, 'Other_Images', true);
            if ($product_images) {
                foreach ($product_images as $image) {
                    if (isset($image['Contractor']) && $image['Contractor'] == 'true') {
                        if (!empty($image['Other_Picture'])) {
                            //if ($variations_counter == 1) {
                            if ($image['Image_Type'] == $type && !empty($image['Description'])) {
                                $image['Other_Picture'] = wp_get_attachment_url($image['Other_Picture']);
                                if (isset($image['Description'])) {
                                    $image['Description'] = str_replace("_", " ", $image['Description']);
                                    $image['Description'] = str_replace(array(".pdf", ".jpg"), "", $image['Description']);
                                }
                                if (!empty($image['File_Name'])) {
                                    $files[$image['File_Name']] = $image;
                                } else {
                                    $files[$image['Description']] = $image;
                                }
                            }
                            //}
                        }
                    }
                }
                foreach ($product_images as $image) {
                    if (isset($image['Contractor']) && $image['Contractor'] == 'true') {
                        if (!empty($image['Other_Picture'])) {
                            //if ($variations_counter == 1) {
                            if ($image['Image_Type'] == 'LP - THUMBNAIL' && !empty($image['Description'])) {
                                if (!empty($image['File_Name'])) {
                                    $image['Other_Picture'] = wp_get_attachment_url($image['Other_Picture']);
                                    $files[$image['File_Name']]['Thumbnail_Pattern'] = $image;
                                }
                            }
                            //}
                        }
                    }
                }
            }
            $variations_counter++;
        }
        restore_current_blog();

        $files = $this->check_if_array_exist(array_values($files));

        return array('type' => $type, 'files' => $files, 'count' => count($files));
    }

    public function check_if_array_exist($array = [])
    {
        $new_array = [];
        if (isset($array) && !empty($array)) {
            foreach ($array as $file) {
                if (isset($file['Thumbnail_Pattern']) && !empty($file['Thumbnail_Pattern']) && !empty($file['Other_Picture'])) $new_array[] = $file;
            }
        }
        return $new_array;
    }

    public function get_product_technologies($product_technologies = [])
    {
        switch_to_blog(1);
        $product_technologies_new = [];
        $links = $contents = '';
        if (!empty($product_technologies)) {
            foreach ($product_technologies as $techs) {
                foreach ($techs as $value) {
                    foreach ($value as $v) {
                        $product_technologies_new[] = $v;
                    }
                }
            }
        }
        $product_technologies_new = array_unique($product_technologies_new, SORT_REGULAR);
        if ($product_technologies_new) {
            $tech_output = $tech_tab_output = '';
            foreach ($product_technologies_new as $key => $tech_id) {
                $tech_title_image = $tech_title = $tech_image_ho = '';

                $tech_title = get_field('product_technology_contractor_title_text', 'product_tech_' . $tech_id);
                if (empty($tech_title)) $tech_title = get_field('product_technology_title_text', 'product_tech_' . $tech_id);

                $tech_descr = get_field('product_technology_contractor_description', 'product_tech_' . $tech_id);
                if (empty($tech_descr)) $tech_descr = get_field('product_technology_description', 'product_tech_' . $tech_id);

                $tech_image_contractor = get_field('product_technology_contractor_image', 'product_tech_' . $tech_id);
                $tech_image = get_field('product_technology_image', 'product_tech_' . $tech_id);

                $tech_video = get_field('product_technology_contractor_video', 'product_tech_' . $tech_id);
                $video = get_bynder_file($tech_video);
                if (!isset($tech_title) || !empty($tech_title)) {
                    $tech_term = get_term($tech_id, 'product_tech');
                    $tech_title = $tech_term->name;
                }

                if ($tech_title && $tech_descr) {
                    $tech_tab_output .= '<li data-tech-id="' . $tech_id . '"><a href="#tech_' . $tech_id . '"> ' . $tech_title . ' </a></li>';
                }

                ob_start(); ?>
                <div id="tech_<?php echo $tech_id; ?>" class="row row-184 ml-0 mr-0 testimonial type1">
                    <div class="col-md-6 <?php if (($key + 1) % 2 == 0) echo 'order-2' ?> align-self-center">
                        <?php if ($tech_title) { ?>
                            <div class="title-4 color-gray upper mb-12"><?php echo esc_html($tech_title); ?></div>
                        <?php } ?>
                        <?php if ($tech_descr) { ?>
                            <div class="text-2"><?php echo wp_kses_post($tech_descr); ?></div>
                        <?php } ?>
                        <?php if (!empty($video['url'])) { ?>
                            <div class="spacer-20"></div>
                            <div class="watch-video open-video" data-rel="video-popup" data-video="<?php echo $video['url']; ?>">
                                <div class="icon">
                                    <svg viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M10,15L15.19,12L10,9V15M21.56,7.17C21.69,7.64 21.78,8.27 21.84,9.07C21.91,9.87 21.94,10.56 21.94,11.16L22,12C22,14.19 21.84,15.8 21.56,16.83C21.31,17.73 20.73,18.31 19.83,18.56C19.36,18.69 18.5,18.78 17.18,18.84C15.88,18.91 14.69,18.94 13.59,18.94L12,19C7.81,19 5.2,18.84 4.17,18.56C3.27,18.31 2.69,17.73 2.44,16.83C2.31,16.36 2.22,15.73 2.16,14.93C2.09,14.13 2.06,13.44 2.06,12.84L2,12C2,9.81 2.16,8.2 2.44,7.17C2.69,6.27 3.27,5.69 4.17,5.44C4.64,5.31 5.5,5.22 6.82,5.16C8.12,5.09 9.31,5.06 10.41,5.06L12,5C16.19,5 18.8,5.16 19.83,5.44C20.73,5.69 21.31,6.27 21.56,7.17Z"></path>
                                    </svg>
                                </div>
                                <?php _e('Watch video', 'unilock'); ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="col-md-6 <?php if (($key + 1) % 2 == 0) echo 'order-1 mb-sm-20' ?>">
                        <?php if (!empty($tech_image_contractor['url'])) : ?>
                            <div class="testimonial-img type2" style="background-image: url(<?php echo esc_url($tech_image_contractor['url']); ?>);"></div>
                        <?php elseif (!empty($tech_image)) : ?>
                            <div class="testimonial-img type2" style="background-image: url(<?php echo esc_url($tech_image); ?>);"></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                $tech_output .= ob_get_clean();
            }
        }
        if ((isset($tech_tab_output) && $tech_tab_output)) {
            ob_start(); ?>
            <div class="pd-tech pd-main-item">
                <div class="title h6"><?php _e('Technologies', 'unilock') ?></div>
                <ul>
                    <?php echo $tech_tab_output ?>
                </ul>
            </div>
        <?php
            $links = ob_get_clean();
        }
        if ((isset($tech_output) && $tech_output)) {
            ob_start(); ?>
            <div id="tech" class="anchor-sect inner-padd detail-section pb-0 animate-item bg-10">
                <div class="spacer-xl"></div>
                <div class="h4 semi-bold title-6 upper color-gray mb--30 mt-40 mb-sm-40"><?php _e('<b>Unique</b> Technologies', 'unilock') ?></div>
                <?php echo $tech_output ?>
                <div class="spacer-90"></div>
            </div>
        <?php
            $contents = ob_get_clean();
        }
        restore_current_blog();

        return array('links' => $links, 'contents' => $contents);
    }


    /*
    * Technologies Block
    * */

    public function get_slider_description($product_id)
    {
        $slider_description = get_field('slider_description', $product_id);
        if (is_array($slider_description) && !empty($slider_description)) {
            ob_start(); ?>
            <!-- MAIN SLIDER -->
            <div class="animate-item">
                <div class="spacer-md"></div>
                <div class="main-slider">
                    <div class="swiper-entry swiper-buttons">
                        <div class="swiper-container" data-options='{"slidesPerView":1,"progressbar":true, "customFraction":true}'>
                            <div class="swiper-wrapper">
                                <?php foreach ($slider_description as $slide) { ?>
                                    <?php if (!empty($slide['background']) || !empty($slide['title']) || !empty($slide['description']) || !empty($slide['link'])) { ?>
                                        <div class="swiper-slide">
                                            <div class="slide-container">
                                                <div class="slide-content">
                                                    <div class="slide-row row d-flex">
                                                        <?php if (!empty($slide['title']) || !empty($slide['description']) || !empty($slide['link'])) { ?>
                                                            <div class="col-lg-5 order-lg-1 order-2">
                                                                <div class="slide-left">
                                                                    <?php if (!empty($slide['title'])) { ?>
                                                                        <div class="h3 extra-bold color-dark title-1 mb-20"><?php echo wp_kses_post($slide['title']); ?></div>
                                                                    <?php } ?>
                                                                    <?php if (!empty($slide['description'])) { ?>
                                                                        <div class="text-2 color-text mb-40"><?php echo wp_kses_post($slide['description']); ?></div>
                                                                    <?php } ?>
                                                                    <?php if (!empty($slide['link'])) {
                                                                        $link_url = $slide['link']['url'];
                                                                        $link_title = $slide['link']['title'];
                                                                        $link_target = $slide['link']['target'] ? $slide['link']['target'] : '_self'; ?>
                                                                        <a class="btn btn-1" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                        <?php if (!empty($slide['background'])) { ?>
                                                            <div class="col-lg-7 pl-0 order-1">
                                                                <div class="slide-right">
                                                                    <img src="<?php echo $slide['background']['url']; ?>" alt="" class="slide-img" <?php echo uni_width_height_image($slide['background']); ?>>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <div class="custom-fraction-wrap d-none d-lg-block">
                                <div class="custom-current"></div>
                                <div class="swiper-pagination"></div>
                                <div class="custom-total"></div>
                            </div>
                            <div class="swiper-button-prev"><img src="<?php echo CHILD_THEME_URI; ?>/img/arrow-left1.svg" alt="arrow-left1.svg" width="16px" height="16px"></div>
                            <div class="swiper-button-next"><img src="<?php echo CHILD_THEME_URI; ?>/img/arrow-left1.svg" alt="arrow-left1.svg" width="16px" height="16px"></div>
                        </div>
                    </div>
                </div>
                <div class="spacer-md"></div>
            </div>
        <?php return ob_get_clean();
        }
        return false;
    }

    /*
     * Information/Packaging Block
     * */

    public function get_videos($product_id)
    {
        $background_text_video = get_field('background_text', $product_id);
        $title_video = get_field('title', $product_id);
        $videos = get_field('videos', $product_id);
        if (!empty($videos) && is_array($videos) && (!empty($background_text_video) || !empty($title_video))) {
            ob_start(); ?>
            <div id="videos" class="anchor-sect inner-padd detail-section animate-item">
                <div class="videos-product-detail">
                    <?php if ($title_video) {
                        $title_video = uni_text_strong($title_video); ?>
                        <div class="h4 title extra-bold red-gray"><?php echo wp_kses_post($title_video); ?></div>
                    <?php } ?>
                    <div class="row row-68 videos relative">
                        <?php if ($background_text_video) { ?>
                            <div class="decor-text-2 type2 color-light"><?php echo esc_html($background_text_video); ?></div>
                        <?php } ?>
                        <?php foreach ($videos as $video) { ?>
                            <?php if (!empty($video['video'])) {
                                $video_file = get_bynder_file($video['video']);
                            ?>
                                <div class="col-lg-4">
                                    <div class="video">
                                        <div class="video-img open-video" data-rel="video-popup" data-video="<?php echo esc_url($video_file['url']); ?>" <?php if (!empty($video_file['preview'])) echo 'style="background:url(' . $video_file['preview'] . ')"'; ?>></div>
                                        <?php if (!empty($video['video']) || !empty($video['title']) || !empty($video['description'])) { ?>
                                            <div class="video-caption">
                                                <?php if (!empty($video['title'])) { ?>
                                                    <div class="video-title"><?php echo esc_html($video['title']); ?>
                                                        <div class="icon-wrapper">
                                                            <div class="btn-icon">
                                                                <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon.svg" alt="Share" data-tooltip="Share" width="48px" height="48px">
                                                                <?php echo uni_share_drop($video_file['url'], $video['title'], 'video', false); ?>
                                                            </div>
                                                            <div class="btn-icon js_share_single" data-tooltip="Mail" data-share="mail" data-url="<?php echo uni_share_video_url($video_file['url']); ?>" data-title="<?php echo esc_attr($video['title']); ?>"><img src=" <?php echo CHILD_THEME_URI; ?>/img/email.svg" alt="Email" width="48px" height="48px"></div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <?php if (!empty($video['description'])) { ?>
                                                    <div class="text-2 regular"><?php echo wp_kses_post($video['description']); ?></div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php return ob_get_clean();
        }
        return false;
    }

    public function get_gallery($product_gallery)
    {
        if (!empty($product_gallery)) {
            ob_start(); ?>
            <div id="gallery" class="prod-gallery anchor-sect inner-padd detail-section">
                <div class="spacer-90"></div>
                <div class="h4 upper title-6 semi-bold color-gray type1 mb-15 animate-item animated"><b>Product</b> Gallery</div>
                <div class="swiper-entry">
                    <div class="swiper-container" data-options='{"slidesPerView":1,"spaceBetween":20,"progressbar":true, "customFraction":true}'>
                        <div class="button-wrap">
                            <div class="swiper-button-prev"><img src="<?php echo CHILD_THEME_URI; ?>/img/arrow-left1.svg" alt="arrow-left1" width="16px" height="16px"></div>
                            <div class="swiper-button-next"><img src="<?php echo CHILD_THEME_URI; ?>/img/arrow-left1.svg" alt="arrow-left1" width="16px" height="16px"></div>
                        </div>
                        <div class="swiper-wrapper">
                            <?php foreach ($product_gallery as $slide) {
                                $link = $slide['url'];
                                $name = $slide['title'];
                            ?>
                                <div class="swiper-slide">
                                    <div class="project-thumb">
                                        <img src="<?php echo aq_resize($link, 1300, 715, true, true, true); ?>" alt="<?php echo esc_attr($name); ?>" <?php echo uni_width_height_image($link); ?>>
                                        <div class="project-thumb-overflow">
                                            <div class="project-thumb-btns d-flex align-items-center justify-content-end invert-icons">
                                                <?php $save_attr = save_to_my_project_attr(null, array('name' => esc_attr($name), 'url' => esc_url($link)), $this->main_product_arr); ?>
                                                <a href="<?php echo !$this->is_user_login ? $save_attr['sing_in_url'] : '#' ?>" class="btn-group-item p-10 <?php if ($this->is_user_login) echo $save_attr['class']; ?>" <?php if ($this->is_user_login) echo $save_attr['attr']; ?> data-tooltip="Save to My Projects">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="20" viewBox="0 0 28 24">
                                                        <g fill="none" fill-rule="evenodd" transform="translate(.427 .724)">
                                                            <polygon fill="#1085A2" points="14.903 2.769 27.376 2.769 27.376 .363 13.184 .363" />
                                                            <polygon fill="#064C5E" points="10.067 0 0 0 0 22.564 27.376 22.564 27.376 5.614 14.077 5.614" />
                                                            <polygon fill="#fff" points="9.563 18.619 9.563 13.287 14.895 13.287 14.895 11.538 9.563 11.538 9.563 6.206 7.814 6.206 7.814 11.538 2.482 11.538 2.482 13.287 7.814 13.287 7.814 18.619" />
                                                        </g>
                                                    </svg>
                                                </a>
                                                <a href="<?php echo esc_url($link); ?>" download="" target="_blank" class="btn-group-item p-10" data-tooltip="Download">
                                                    <svg width="32px" height="32px" viewBox="-6 -4 28 28" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                        <polygon fill="#1085A2" points="0 17 16 17 16 19 0 19"></polygon>
                                                        <polygon fill="#064C5E" transform="translate(8.000000, 8.000000) scale(-1, -1) rotate(-270.000000) translate(-8.000000, -8.000000) " points="8 16 9.41317365 14.5868263 3.83233533 8.98203593 16 8.98203593 16 7.01796407 3.83233533 7.01796407 9.41317365 1.41317365 8 0 9.09494702e-13 8"></polygon>
                                                    </svg>
                                                </a>
                                                <button class="btn-group-item p-15" data-tooltip="Share">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 18 18">
                                                        <path fill="#064C5E" fill-rule="evenodd" d="M16.5714447,20.4285731 C18.5468935,20.4285731 20.1428776,18.8325891 20.1428776,16.8571403 C20.1428776,14.8816915 18.5468935,13.2857075 16.5714447,13.2857075 C15.6339436,13.2857075 14.7745676,13.6540115 14.1384061,14.24553 L10.1205442,12.2365991 C10.1317049,12.1138311 10.1428657,11.9799023 10.1428657,11.8571343 C10.1428657,11.7343663 10.1317049,11.6004376 10.1205442,11.4776696 L14.1384061,9.46873864 C14.7745676,10.0602572 15.6339436,10.4285612 16.5714447,10.4285612 C18.5468935,10.4285612 20.1428776,8.83257717 20.1428776,6.85712838 C20.1428776,4.8816796 18.5468935,3.28569555 16.5714447,3.28569555 C14.595996,3.28569555 13.0000119,4.8816796 13.0000119,6.85712838 C13.0000119,6.97989639 13.0111726,7.11382512 13.0223334,7.23659312 L9.00447144,9.24552409 C8.36830997,8.65400552 7.50893395,8.28569555 6.57143283,8.28569555 C4.59598405,8.28569555 3,9.88168556 3,11.8571343 C3,13.8325831 4.59598405,15.4285672 6.57143283,15.4285672 C7.50893395,15.4285672 8.36830997,15.0602632 9.00447144,14.4687446 L13.0223334,16.4776756 C13.0111726,16.6004436 13.0000119,16.7343723 13.0000119,16.8571403 C13.0000119,18.8325891 14.595996,20.4285731 16.5714447,20.4285731 Z" transform="translate(-3 -3)" />
                                                    </svg>
                                                    <?php echo uni_share_drop($link, $name); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="custom-fraction-wrap d-flex d-lg-block">
                            <div class="custom-current"></div>
                            <div class="d-lg-none">&nbsp;&nbsp;/&nbsp;&nbsp;</div>
                            <div class="swiper-pagination d-none d-lg-block"></div>
                            <div class="custom-total"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php return ob_get_clean();
        }
        return false;
    }

    public function get_where_to_buy()
    {
        $title = get_field('wtb_product_title', 'option');
        $bg = get_field('wtb_product_bg', 'option');

        $title_2 = get_field('wtb_product_title_2', 'option');
        $descr = get_field('wtb_product_description', 'option');
        $btn = get_field('wtb_product_button', 'option');

        if ($title || $bg || $title_2 || $descr) {
            ob_start(); ?>
            <div class="animate-item">
                <div class="container-3 find-dealer">
                    <div class="row">
                        <?php if ($title || $bg) : ?>
                            <div class="col-lg-9 col-xl-8 inner-left">
                                <?php if ($title) :
                                    $title = uni_text_strong($title); ?>
                                    <div class="h4 extra-bold box3-title mt-120"><?php echo wp_kses_post($title); ?></div>
                                <?php endif; ?>
                                <?php if ($bg) : ?>
                                    <div class="bg-wrapper">
                                        <img src="<?php echo esc_url($bg['url']); ?>" alt="<?php echo esc_attr($bg['alt']); ?>" <?php echo uni_width_height_image($bg); ?>>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($title_2 || $descr) : ?>
                            <div class="col-lg-3 col-xl-4 d-flex flex-column justify-content-center">
                                <?php if ($title_2) : ?>
                                    <div class="h4 semi-bold title-3 color-gray upper"><?php echo wp_kses_post($title_2); ?></div>
                                <?php endif; ?>
                                <?php if ($descr) : ?>
                                    <div class="text-1"><?php echo wp_kses_post($descr); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($btn)) :
                                    $link_url = $btn['url'];
                                    $link_title = $btn['title'];
                                    $link_target = $btn['target'] ? $btn['target'] : '_self'; ?>
                                    <a href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>" class="btn btn-1 pl-80 pr-80"><?php echo esc_html($link_title); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="spacer-xxl d-none d-md-block"></div>
                <div class="spacer-lg d-md-none"></div>
            </div>
        <?php return ob_get_clean();
        }
        return false;
    }

    public function get_related_literature($term_id)
    {
        $title = get_field('related_title', 'product_cat_' . $term_id);
        $related_literature = get_field('related_literature', 'product_cat_' . $term_id);
        $button_link = get_field('related_button_link', 'product_cat_' . $term_id);

        if (!empty($related_literature)) {
            ob_start(); ?>
            <div id="articles" class="anchor-sect inner-padd detail-section animate-item animated">
                <?php if (!empty($title)) : ?>
                    <div class="h4 title-6 semi-bold letter-04 upper color-dark mb-45"><?php echo wp_kses_post($title); ?></div>
                <?php endif; ?>
                <?php if (!empty($related_literature)) : ?>
                    <div class="row articles">
                        <?php foreach ($related_literature as $key => $related_id) :
                            $related_title = get_the_title($related_id);
                            $related_url = get_the_permalink($related_id);
                            $related_img = get_field('article_img', $related_id);
                            $related_cats = wp_get_post_terms($related_id, 'doc_cat', array('fields' => 'names'));
                            $related_excerpt = get_the_excerpt($related_id); ?>
                            <div class="col-lg-6">
                                <div class="article type-2 d-sm-flex">
                                    <a href="<?php echo esc_url($related_url); ?>" class="article-img"><span <?php if (!empty($related_img)) echo 'style="background:url(' . esc_url($related_img['url']) . ');"' ?>></span></a>
                                    <div class="article-caption d-flex flex-column justify-content-center">
                                        <div class="article-title title-4 tt-none line-clamp line-clamp1"><a href="<?php echo esc_url($related_url); ?>"><?php echo esc_html($related_title); ?></a></div>
                                        <?php if (!empty($related_cats)) : ?>
                                            <div class="article-category"><?php echo esc_html($related_cats[0]); ?></div>
                                        <?php endif; ?>
                                        <div class="article-desc text-2 line-clamp line-clamp2"><?php echo wp_kses_post($related_excerpt); ?></div>
                                        <div class="text-right"><a href="<?php echo esc_url($related_url); ?>" class="link-2"><span><?php _e('Read Article', 'unilock'); ?></span>
                                                <svg class="t1" width="41" height="16" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                    <path id="a" d="M32 1l-1.41 1.41L36.17 8H0v2h36.17l-5.58 5.59L32 17l8-8z"></path>
                                                </svg>
                                            </a></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($button_link)) :
                    $link_url = $button_link['url'];
                    $link_title = $button_link['title'];
                    $link_target = $button_link['target'] ? $button_link['target'] : '_self'; ?>
                    <a class="btn btn-1 mt-30" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                <?php endif; ?>
                <div class="spacer-lg"></div>
            </div>
        <?php return ob_get_clean();
        }
        return false;
    }

    public function get_testimonials($term_id)
    {
        $testimonials_title = get_field('testimonials_title', 'product_cat_' . $term_id);
        $testimonials_link_button = get_field('testimonials_link_button', 'product_cat_' . $term_id);
        $testimonials = get_field('testimonials', 'product_cat_' . $term_id);

        if (!empty($testimonials)) {
            ob_start(); ?>
            <?php /* <div id="testimonials" class="inner-padd unique-tech"> */ ?>
            <div id="testimonials" class="anchor-sect inner-padd detail-section animate-item">
                <div class="spacer-sm"></div>
                <?php if (!empty($testimonials_title)) { ?>
                    <div class="h4 semi-bold title-6 upper color-gray mb--30 mb-sm-40"><?php echo wp_kses_post($testimonials_title); ?></div>
                <?php } ?>
                <?php if (!empty($testimonials)) {
                    foreach ($testimonials as $key => $testimonial_id) {
                        $t_title = get_the_title($testimonial_id);
                        $t_text = get_field('testimonial_text', $testimonial_id);
                        $t_img = get_field('testimonial_image', $testimonial_id);
                        $t_video = get_field('testimonial_video', $testimonial_id);
                        $t_name = get_field('testimonial_name_and_location', $testimonial_id); ?>
                        <div class="row testimonial type1">
                            <div class="col-md-5 align-self-center <?php if (($key + 1) % 2 == 0) echo 'order-2'; ?>">
                                <div class="pr-xxl-140">
                                    <?php if (!empty($t_title)) { ?>
                                        <div class="title-7 tt-none mb-12"><?php echo esc_html($t_title); ?></div>
                                    <?php } ?>
                                    <?php if (!empty($t_text)) { ?>
                                        <p class="text-2 semi-bold"><?php echo wp_kses_post($t_text); ?></p>
                                    <?php } ?>
                                    <?php if (!empty($t_name)) { ?>
                                        <small class="mb-sm-20"><?php echo wp_kses_post($t_name); ?></small>
                                    <?php } ?>
                                </div>
                            </div>

                            <?php $class2 = '';
                            if (($key + 1) % 2 == 0) $class2 .= 'order-1';
                            if ($key != 0) $class2 .= ' mb-sm-20'; ?>

                            <div class="col-md-7 <?php echo $class2; ?>">
                                <div class="testimonial-img <?php if (!empty($t_video)) echo 'open-video'; ?>" <?php if (!empty($t_img)) echo 'style="background-image: url(' . esc_url($t_img['url']) . ');"'; ?> data-rel="video-popup" <?php if (!empty($t_video)) echo 'data-video="' . esc_url($t_video) . '"' ?>>
                                    <?php if (!empty($t_video)) { ?>
                                        <button><i></i><?php /*_e('Play Video', 'unilock'); */ ?></button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="spacer-50"></div>
                    <?php if (!empty($testimonials_link_button)) {
                        $link_url = $testimonials_link_button['url'];
                        $link_title = $testimonials_link_button['title'];
                        $link_target = $testimonials_link_button['target'] ? $testimonials_link_button['target'] : '_self'; ?>
                        <a href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>" class="btn btn-1 type-3"><?php echo esc_html($link_title); ?></a>
                    <?php } ?>
                    <div class="spacer-90"></div>
                <?php } ?>
                <div class="spacer-sm"></div>
            </div>
            <?php return ob_get_clean();
        }
        return false;
    }

    public function get_product_info_packaging_downloads($product_information = '', $laying_pattern = array(), $product_packaging = array(), $product_categories = '', $pattern_files = array(), $cross_section = array())
    {
        switch_to_blog(1);

        $product_term = get_term($product_categories[0][0], 'product_cat');
        $product_term = $product_term->name;

        $site_option_packaging = get_field('packaging_repeater', 'option');

        $new_array = [];
        $site_option_counter = 0;

        foreach ($product_packaging as $key => $value_array) {
            foreach ($value_array as $key_packaging => $value_packaging) {
                foreach ($site_option_packaging[0] as $key_option => $value_option) {
                    if (array_key_exists($key_option, $value_packaging) && !empty($value_packaging[$key_option])) {
                        if (is_numeric($value_packaging[$key_option]) && $value_packaging[$key_option] < 0.0001) {
                            continue;
                        }
                        $new_array['packaging'][$value_option][$site_option_counter] = $value_packaging[$key_option];
                        if (isset($value_packaging['height_1'])) {
                            $new_array['packaging']['height_1'][$site_option_counter] = $value_packaging['height_1'];
                        }
                    }
                }
            }
            $site_option_counter++;
        }

        //var_dump($pattern_files);
        $line_pattern_tab = '';
        $show_patterns_tab = FALSE;
        if (!isset($_GET['cat']) || (isset($_GET['cat']) && $_GET['cat'] !== 'coping')) {
            ob_start();
            foreach ($pattern_files as $pattern) {
                $link = isset($pattern["Other_Picture"]) ? wp_get_attachment_url($pattern["Other_Picture"]) : '';
                $thumbnail = isset($pattern['Thumbnail_Pattern']["Other_Picture"]) ? wp_get_attachment_url($pattern['Thumbnail_Pattern']["Other_Picture"]) : '';
                $pattern_descr = '';
                if (isset($pattern['Description'])) {
                    $pattern_descr = str_replace("_", " ", $pattern['Description']);
                    $pattern_descr = str_replace(array(".pdf", ".jpg"), "", $pattern_descr);
                } ?>
                <?php if (isset($pattern['Contractor']) && ($pattern['Contractor'] == 'true') && !empty($link) && !empty($thumbnail)) {
                    $show_patterns_tab = TRUE; ?>
                    <div class="col-lg-3 col-sm-6">
                        <div class="item-pattern">
                            <div class="item-img img-wrapper mb-20">
                                <img src="<?php echo $thumbnail; ?>" alt="<?php echo $pattern_descr; ?>" width="16px" height="16px">
                            </div>
                            <div class="caption">
                                <div class="title title-4 color-gray letter-08 mb-20"><?php echo $pattern_descr; ?></div>
                                <ul>
                                    <li>
                                        <?php $save_attr = save_to_my_project_attr(null, array('name' => esc_attr($pattern_descr), 'url' => esc_url($link), 'type' => 'doc'), $this->main_product_arr); ?>
                                        <a href="<?php echo !$this->is_user_login ? $save_attr['sing_in_url'] : '#' ?>" class="<?php if ($this->is_user_login) echo $save_attr['class']; ?>" <?php if ($this->is_user_login) echo $save_attr['attr']; ?>><img src="<?php echo CHILD_THEME_URI; ?>/img/folder-icon1.svg" alt="folder-icon1" width="48px" height="48px"><?php _e('Save To My Project', 'unilock'); ?></a>
                                    </li>
                                    <li>
                                        <a href="<?php echo $link; ?>" target="_blank">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/download1.svg" alt="download1" width="48px" height="48px"><?php _e('Download PDF', 'unilock'); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="js_share_single" data-share="mail" data-url="<?php echo esc_url($link); ?>" data-title="<?php echo esc_attr($pattern_descr); ?>"><img src="<?php echo CHILD_THEME_URI; ?>/img/email.svg" alt="mail" width="48px" height="48px"><?php _e('Email a copy', 'unilock'); ?></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } ?>
        <?php }
            $line_pattern_tab = ob_get_clean();
        }

        $show_active_tab = true;
        $show_active_tab_name = '';
        $sell_sheets_key = array_search("SELL SHEET", array_column($laying_pattern, 'Image_Type'));
        $sell_sheets_url = wp_get_attachment_url($laying_pattern[$sell_sheets_key]['Other_Picture']);

        $laying_pattern = '';
        ob_start(); ?>

        <div id="techInformation" class="tech-info anchor-sect inner-padd detail-section bg-block">
            <div class="spacer-90"></div>
            <div class="h4 upper title-6 type1 semi-bold color-gray mb-45 mb-mobile-0 animate-item animated"><?php _e('<b>Technical </b> information', 'unilock') ?></div>
            <div class="tabs-wrapper">
                <div class="scroll-wrapper scroll-wrapper_type1">
                    <ul class="cross-tabs tabs type-5 mb-25 d-none d-xl-block">
                        <?php if ($show_patterns_tab) { ?>
                            <li <?php if ($show_active_tab) {
                                    echo 'class="active"';
                                    $show_active_tab = false;
                                    $show_active_tab_name = 'pattern_files';
                                } ?>><?php _e('Laying Patterns', 'unilock') ?></li>
                        <?php } ?>
                        <?php if (!empty($new_array) && isset($new_array['packaging']) && count($new_array['packaging']) > 1) { ?>
                            <li <?php if ($show_active_tab) {
                                    echo 'class="active"';
                                    $show_active_tab = false;
                                    $show_active_tab_name = 'new_array';
                                } ?>><?php _e('Packaging', 'unilock') ?></li>
                        <?php } ?>
                        <?php if (!empty($product_information)) { ?>
                            <li <?php if ($show_active_tab) {
                                    echo 'class="active"';
                                    $show_active_tab = false;
                                    $show_active_tab_name = 'product_information';
                                } ?>><?php _e('Things to know', 'unilock') ?></li>
                        <?php } ?>
                        <?php /* <li>Cross Sections</li> */ ?>
                        <?php if (!empty($cross_section)) { ?>
                            <li <?php if ($show_active_tab) {
                                    echo 'class="active"';
                                    $show_active_tab = false;
                                    $show_active_tab_name = 'cross_section';
                                } ?>><?php _e('Cross Sections', 'unilock') ?></li>
                        <?php } ?>
                    </ul>

                    <div class="с-select-wrapp cats-select mb-25 js_mobile_select d-xl-none value">
                        <div class="input-label mb-0"></div>
                        <div class="input-placeholder"><?php _e('Select category', 'unilock'); ?></div>
                        <div class="с-select">
                            <button class="с-current-btn"></button>
                            <div class="с-current-val">
                                <?php
                                if (!empty($show_patterns_tab)) {
                                    _e('Laying Patterns', 'unilock');
                                } else if (!empty($new_array) && isset($new_array['packaging']) && count($new_array['packaging']) > 1) {
                                    _e('Packaging', 'unilock');
                                } else if (!empty($product_information)) {
                                    _e('Things to know', 'unilock');
                                } else if (!empty($cross_section)) {
                                    _e('Cross Sections', 'unilock');
                                }
                                ?>
                            </div>
                            <ul>
                                <?php if ($show_patterns_tab) { ?>
                                    <li <?php if ($show_active_tab) {
                                            echo 'class="active"';
                                            $show_active_tab = false;
                                            $show_active_tab_name = 'pattern_files';
                                        } ?>><?php _e('Laying Patterns', 'unilock') ?></li>
                                <?php } ?>
                                <?php if (!empty($new_array) && isset($new_array['packaging']) && count($new_array['packaging']) > 1) { ?>
                                    <li <?php if ($show_active_tab) {
                                            echo 'class="active"';
                                            $show_active_tab = false;
                                            $show_active_tab_name = 'new_array';
                                        } ?>><?php _e('Packaging', 'unilock') ?></li>
                                <?php } ?>
                                <?php if (!empty($product_information)) { ?>
                                    <li <?php if ($show_active_tab) {
                                            echo 'class="active"';
                                            $show_active_tab = false;
                                            $show_active_tab_name = 'product_information';
                                        } ?>><?php _e('Things to know', 'unilock') ?></li>
                                <?php } ?>
                                <?php /* <li>Cross Sections</li> */ ?>
                                <?php if (!empty($cross_section)) { ?>
                                    <li <?php if ($show_active_tab) {
                                            echo 'class="active"';
                                            $show_active_tab = false;
                                            $show_active_tab_name = 'cross_section';
                                        } ?>><?php _e('Cross Sections', 'unilock') ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>

                </div>
                <div class="inner-wrapper">
                    <?php if ($show_patterns_tab) { ?>
                        <div class="tab-item <?php if ($show_active_tab_name == 'pattern_files') echo 'active'; ?>">
                            <div class="row row-90">
                                <?php echo $line_pattern_tab; ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($new_array) && isset($new_array['packaging']) && count($new_array['packaging']) > 1) { ?>
                        <div class="tab-item <?php if ($show_active_tab_name == 'new_array') echo 'active'; ?>">
                            <div class="table-wrapper">
                                <?php $products_packaging_pdf_title = get_field('products_packaging_pdf_title');
                                $products_packaging_pdf_file_title = get_field('products_packaging_pdf_file_title');
                                $products_packaging_pdf_file = get_field('products_packaging_pdf_file');

                                if ($products_packaging_pdf_title && $products_packaging_pdf_file_title && $products_packaging_pdf_file) {
                                    echo '<p class="packaging_pdf_title">' . $products_packaging_pdf_title . ' ' . '<a class="packaging_pdf_file" href="' . $products_packaging_pdf_file . '" target="_blank">' . $products_packaging_pdf_file_title . '</a></p>';
                                } else {
                                    $select_layout = get_field('select_layout');
                                    echo $this->layout_paver($new_array);
                                } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($product_information)) { ?>
                        <div class="tab-item <?php if ($show_active_tab_name == 'product_information') echo 'active'; ?>">
                            <div class="thing_to_know_title">THINGS TO KNOW</div>
                            <div class="text-2 semi-bold mb-45 columns-2">
                                <?php echo str_replace('<p>&nbsp;</p>', '', $product_information) ?>
                                <?php if (!empty($sell_sheets_url)) { ?>
                                    <a href="<?php echo $sell_sheets_url; ?>" download class="btn type2 pl-80 pr-80 mt-40"><?php _e('Download Technical Sheet', 'unilock'); ?></a>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php /* <div class="tab-item <?php if ($show_active_tab_name == 'product_information') echo 'active'; ?>">
                        <div class="project-docs-wrapper">
                            <div class="project-docs double-row row">
                                <div class="col-md-6">
                                    <a href=""><span class="icon-doc"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf.svg" alt=""></span><span
                                                class="doc-title">Verticals and Features Cross Sections Name</span></a>
                                    <div class="doc-options">
                                        <a href="" class="btn-group-item open-popup" data-tooltip="Save to My Projects"
                                           data-rel="save-to-my-projects">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/save.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Download">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/download.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Share">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon1.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <a href=""><span class="icon-doc"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf.svg" alt=""></span><span
                                                class="doc-title">Verticals and Features Cross Sections Name</span></a>
                                    <div class="doc-options">
                                        <a href="" class="btn-group-item open-popup" data-tooltip="Save to My Projects"
                                           data-rel="save-to-my-projects">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/save.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Download">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/download.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Share">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon1.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <a href=""><span class="icon-doc"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf.svg" alt=""></span><span
                                                class="doc-title">Verticals and Features Cross Sections Name</span></a>
                                    <div class="doc-options">
                                        <a href="" class="btn-group-item open-popup" data-tooltip="Save to My Projects"
                                           data-rel="save-to-my-projects">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/save.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Download">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/download.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Share">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon1.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <a href=""><span class="icon-doc"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf.svg" alt=""></span><span
                                                class="doc-title">Verticals and Features Cross Sections Name</span></a>
                                    <div class="doc-options">
                                        <a href="" class="btn-group-item open-popup" data-tooltip="Save to My Projects"
                                           data-rel="save-to-my-projects">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/save.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Download">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/download.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Share">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon1.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <a href=""><span class="icon-doc"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf.svg" alt=""></span><span
                                                class="doc-title">Verticals and Features Cross Sections Name</span></a>
                                    <div class="doc-options">
                                        <a href="" class="btn-group-item open-popup" data-tooltip="Save to My Projects"
                                           data-rel="save-to-my-projects">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/save.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Download">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/download.svg" alt="">
                                        </a>

                                        <a href="" class="btn-group-item" data-tooltip="Share">
                                            <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon1.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> */ ?>
                    <?php if (is_array($cross_section) && !empty($cross_section)) {
                        $cross_section = $this->unique_multidim_array($cross_section, 'Other_Picture'); ?>
                        <div class="tab-item <?php if ($show_active_tab_name == 'cross_section') echo 'active'; ?>">
                            <div class="project-docs-wrapper">
                                <div class="project-docs double-row row">
                                    <?php
                                    $sorted_cross_section = $this->array_sort($cross_section, 'Description', SORT_ASC);
                                    foreach ($sorted_cross_section as $pattern) {
                                        $pattern_descr = '';
                                        $cs_com = false;
                                        if (isset($pattern['Description'])) {
                                            $cs_com = strpos($pattern['Description'], 'CS_COM');
                                            $pattern_descr = str_replace(array('CS_RES_', 'CS-RES '), '', $pattern['Description']);
                                            $pattern_descr = str_replace('_', ' ', $pattern_descr);
                                            $pattern_descr = str_replace(array('.pdf', '.jpg'), '', $pattern_descr);
                                        }
                                        if ($cs_com === false) { ?>
                                            <div class="col-md-6">
                                                <a href="<?php echo wp_get_attachment_url($pattern["Other_Picture"]); ?>">
                                                    <span class="icon-doc"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf.svg" alt="pdf" width="48px" height="48px"></span><span class="doc-title"><?php echo $pattern_descr; ?></span>
                                                </a>
                                                <div class="doc-options">
                                                    <?php $save_attr = save_to_my_project_attr(null, array('name' => esc_attr($pattern_descr), 'url' => esc_url(wp_get_attachment_url($pattern["Other_Picture"])), 'type' => 'doc'), $this->main_product_arr); ?>
                                                    <a href="<?php echo !$this->is_user_login ? $save_attr['sing_in_url'] : '#' ?>" class="btn-group-item <?php if ($this->is_user_login) echo $save_attr['class']; ?>" <?php if ($this->is_user_login) echo $save_attr['attr']; ?> data-tooltip="Save To My Project"><img src="<?php echo CHILD_THEME_URI; ?>/img/save.svg" alt="folder-icon1" width="48px" height="48px"></a>
                                                    <a href="<?php echo wp_get_attachment_url($pattern["Other_Picture"]); ?>" class="btn-group-item" target="_blank" data-tooltip="Download">
                                                        <img src="<?php echo CHILD_THEME_URI; ?>/img/download.svg" alt="download" width="48px" height="48px">
                                                    </a>
                                                    <a href="#" class="btn-group-item" data-tooltip="Share">
                                                        <img src="<?php echo CHILD_THEME_URI; ?>/img/share-icon1.svg" alt="share-icon1" width="48px" height="48px">
                                                        <?php echo uni_share_drop(wp_get_attachment_url($pattern["Other_Picture"]), $pattern_descr); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="spacer-50"></div>
        </div>

    <?php restore_current_blog();

        return ob_get_clean();
    }

    private function layout_paver($product_packaging)
    { ?>
        <table class="table type-2">
            <?php
            // get max count row in packaging
            if (!empty($product_packaging['packaging'])) {
                $max_count = 0;
                foreach ($product_packaging['packaging'] as $key => $value_product) {
                    $count_product = count($value_product);
                    if ($count_product > $max_count) {
                        $max_count = $count_product;
                    }
                }
            }

            if (!empty($product_packaging['packaging'])) {
                $table_output = $thead_output = '';
                foreach ($product_packaging['packaging'] as $key => $value_product) {
                    $key_ = $key;
                    if ($key == 'height_1') {
                        // file_put_contents(__DIR__.'/height_1.txt', print_r($key, true));
                        $table_output .= '<tr style="display:none;" class="table_height_1"><td>' . $key . '</td>';
                        // $thead_output .= '<th style="display:none;"><span>' . $key . '</span></th>';
                    } else {
                        if (empty($key)) {
                            $thead_output .= '<tr><td>' . $key . '</td>';
                        } else {
                            $table_output .= '<tr><td>' . $key . '</td>';
                        }
                        //$thead_output .= '<th><span>' . $key . '</span></th>';
                    }
                    $counter_key = 0;
                    $prev_row = 0;

                    $packaging_all_keys = array_keys($value_product);
                    $packaging_last_key = end($packaging_all_keys);

                    foreach ($value_product as $key => $value) {
                        $height = isset($product_packaging['packaging']['height_1'][$key]) ? '(' . intval($product_packaging['packaging']['height_1'][$key]) . ')' : '';
                        if ($key !== $counter_key) {
                            $counter_th = $key - $prev_row;
                            if ($counter_key == 0 && $prev_row == 0) {
                                for ($i = -1; $i < $counter_th - 1; $i++) {
                                    $table_output .= '<td> - </td>';
                                }
                            } else if ($counter_th != 0) {
                                for ($i = 0; $i < $counter_th - 1; $i++) {
                                    $table_output .= '<td> - </td>';
                                }
                            }
                        }

                        $pos = strpos($value, '.');
                        if (!empty($pos)) {
                            settype($value, 'float');
                        }

                        //replace quotes  to special symbol
                        $value = str_replace('"', "&#34;", $value);
                        $value = str_replace('/', " / ", $value);
                        if (!is_numeric($value)) {
                            $thead_output .= '<td data-head-table="' . $value . '"> ' . $height . ' ' . $value . '</td>';
                        } else if (is_float($value)) {
                            $table_output .= '<td data-key="' . $key . '">' . round($value, 2) . '</td>';
                        } else {
                            $table_output .= '<td data-head-table="' . $value . '">' . round($value, 2) . '</td>';
                        }

                        $prev_row = $key;
                        $counter_key++;

                        // check last empty th
                        if ($packaging_last_key == $key && $key !== ($max_count - 1)) {
                            $counter_th = $max_count - $key;
                            for ($i = 0; $i < $counter_th - 1; $i++) {
                                $table_output .= '<td> - </td>';
                            }
                        }
                    }


                    $table_output .= '</tr>';
                }

                if ($thead_output) echo '<thead>' . $thead_output . '</thead>';
                echo $table_output;
            }
            ?>
        </table>
        <?php }

    private function unique_multidim_array($array, $key)
    {
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    public function laying_patterns_products_render($classes = '')
    {
        $first_product = '';

        $products = $this->laying_patterns_json('alfa_patterns_products');
        if (isset($products) && !empty($products)) {
            ob_start(); ?>
            <ul class="guides-tabs <?php echo $classes; ?> tabs d-none d-lg-block js_patterns_products">
                <?php foreach ($products as $key => $product) {
                    if ($key == 0) $first_product = $product; ?>
                    <li <?php if ($key == 0) echo 'class="active"'; ?> data-product="<?php echo $product; ?>"><?php echo $product; ?></li>
                <?php } ?>
            </ul>
            <div class="с-select-wrapp cats-select mb-25 d-lg-none">
                <div class="input-label mb-0"></div>
                <div class="input-placeholder"><?php _e('Select a product', 'unilock'); ?></div>
                <div class="с-select">
                    <button class="с-current-btn"></button>
                    <div class="с-current-val"></div>
                    <ul class="js_patterns_products">
                        <?php foreach ($products as $key => $product) {
                            if ($key == 0) $first_product = $product; ?>
                            <li <?php if ($key == 0) echo 'class="active"'; ?> data-product="<?php echo $product; ?>"><?php echo $product; ?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php
            $html = ob_get_clean();
            return array('first_product' => $first_product, 'html' => $html);
        }
        return false;
    }

    /*
     * LAYING PATTERNS
     * */

    public function laying_patterns_json($product_name = '', $type = 'LAYING PATTERN', $laying_pattern = array())
    {
        if (!empty($product_name)) {
            $regions_product = cont_check_region_user();
            $product_files_path = CHILD_THEME_URL . '/products_files';
            $region_path = $product_files_path . '/' . $regions_product;
            if ($product_name == 'alfa_patterns_products') {
                $product_file_path = $region_path . '/' . sanitize_title(trim($type)) . '/alfa_patterns_products.json';
            } else {
                $product_file_path = $region_path . '/' . sanitize_title(trim($type)) . '/' . sanitize_title(trim($product_name)) . '.json';
            }

            if (file_exists($product_file_path)) {
                $files = file_get_contents($product_file_path);
                return json_decode($files, ARRAY_A);
            } else {
                return false;
            }
        } else if (!empty($laying_pattern)) {
            return $laying_pattern;
        }
        return false;
    }

    public function laying_patterns_ajax()
    {
        check_ajax_referer('ajax_request_nonce');
        if (!empty($_REQUEST['product'])) {
            $patterns = $this->laying_patterns_render($_REQUEST['product'], $_REQUEST['page'] ?? 1, $_REQUEST['per_page'] ?? 6);
            echo json_encode(array('success' => true, 'content' => $patterns));
            die();
        }
        echo json_encode(array('success' => false, 'message' => __('Product not found!', 'unilock')));
        die();
    }

    public function laying_patterns_render($product_name, $page = 1, $per_page = 6)
    {
        $get_patterns = $this->laying_patterns_json($product_name);

        if ($page > 1 && $get_patterns['count'] > $per_page) $start = ($page - 1) * $per_page;
        else $start = 0;

        $pages = ceil($get_patterns['count'] / $per_page);
        ob_start();
        if (isset($get_patterns['files']) && !empty($get_patterns['files']) && isset($get_patterns['files'][0]['Description']) && isset($get_patterns['files'][0]['Other_Picture'])) {
        ?>
            <div class="guides-item tab-item active">
                <div class="title-7 color-gray bold mb-30"><?php echo $product_name; ?></div>
                <div class="row row-124">
                    <?php for ($i = $start; $i < $start + $per_page; $i++) { ?>
                        <?php if (isset($get_patterns['files'][$i]['Description']) && isset($get_patterns['files'][$i]['Other_Picture'])) {
                            $title = $get_patterns['files'][$i]['Description'];
                            $link = $get_patterns['files'][$i]['Other_Picture'];
                            $thumbnail = isset($get_patterns['files'][$i]['Thumbnail_Pattern']["Other_Picture"]) ? $get_patterns['files'][$i]['Thumbnail_Pattern']["Other_Picture"] : CHILD_THEME_URI . "/img/pattern1.jpg"; ?>
                            <div class="col-lg-4 col-sm-6">
                                <div class="item-pattern">
                                    <div class="item-img img-wrapper mb-20">
                                        <img src="<?php echo $thumbnail; ?>" alt="no-image" width="100px" height="100px">
                                    </div>
                                    <div class="caption">
                                        <div class="title title-4 tt-none mb-20"><?php echo $title; ?></div>
                                        <ul>
                                            <li><?php $save_attr = save_to_my_project_attr(null, array('name' => esc_attr($title), 'url' => esc_url($link), 'type' => 'doc'), $this->main_product_arr); ?>
                                                <a href="<?php echo !$this->is_user_login ? $save_attr['sing_in_url'] : '#' ?>" class="<?php if ($this->is_user_login) echo $save_attr['class']; ?>" <?php if ($this->is_user_login) echo $save_attr['attr']; ?>><img src="<?php echo CHILD_THEME_URI; ?>/img/folder-icon1.svg" alt=""><?php _e('Save To My Project', 'unilock'); ?></a>
                                            </li>
                                            <li>
                                                <a href="<?php echo $link; ?>" target="_blank"><img src="<?php echo CHILD_THEME_URI; ?>/img/download1.svg" alt="download1" width="48px" height="48px">Download PDF</a>
                                            </li>
                                            <li>
                                                <a href="#" class="js_share_single" data-share="mail" data-url="<?php echo esc_url($link); ?>" data-title="<?php echo esc_attr($title); ?>"><img src="<?php echo CHILD_THEME_URI; ?>/img/email.svg" alt="mail" width="48px" height="48px"><?php _e('Email a copy', 'unilock'); ?></a>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
            <?php echo $this->laying_patterns_pagination($page, $pages, $product_name); ?>
        <?php
        } else { ?>
            <div class="guides-item tab-item active">
                <div class="title-7 color-gray bold mb-30"><?php echo $product_name; ?></div>
                <div class="row row-124">
                    <div class="col-12 text-center">
                        <h6>No Laying Patterns</h6>
                    </div>
                </div>
            </div>
            <?php }
        return ob_get_clean();
    }

    public function laying_patterns_pagination($page = 1, $pages = 1, $product_name = '', $type = '', $range = 2): string
    {
        $pag = $next_class = $prev_class = '';
        $showitems = $range * 1;

        if (1 != $pages) {
            $pag .= '<div class="nav-wrap js_patterns_pagination" data-product="' . $product_name . '" data-type="' . $type . '">';
            $pag .= '<div class="pagination pagination-left-bottom">';

            if (2 < $page && $pages >= $showitems) $pag .= '<a href="#1">1</a>';
            if (3 < $page && $pages >= $showitems) $pag .= '<span>...</span>';
            for ($i = 1; $i <= $pages; $i++) {
                if ($pages != 1 && (!($i >= $page + $range || $i <= $page - $range) || $pages <= $showitems)) {
                    if ($page == $i) $pag .= '<a href="#' . $i . '" class="pagination-active">' . $i . '</a>';
                    else $pag .= '<a href="#' . $i . '">' . $i . '</a>';
                }
            }
            if ($page < ($pages - 2) && $pages >= $showitems) $pag .= '<span>...</span>';
            if ($page < ($pages - 1) && $pages >= $showitems) $pag .= '<a href="#' . $pages . '">' . $pages . '</a>';

            $pag .= '</div>'; //pagination

            if ($page == 1) $prev_class = 'disabled';
            if ($page == $pages) $next_class = 'disabled';
            $pag .= '<div class="pagination-nav">';
            $pag .= '<a class="prev arrow ' . $prev_class . '"><svg width="41" height="16" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><path id="a" d="M32 1l-1.41 1.41L36.17 8H0v2h36.17l-5.58 5.59L32 17l8-8z"/></defs><use fill="#FFF" xlink:href="#a" transform="matrix(-1 0 0 1 40.804 -1)" fill-rule="evenodd"/></svg></a>';
            $pag .= '<a href="#" class="next arrow ' . $next_class . '"><svg width="41" height="16" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><path id="a" d="M32 1l-1.41 1.41L36.17 8H0v2h36.17l-5.58 5.59L32 17l8-8z"/></defs><use fill="#FFF" xlink:href="#a" transform="matrix(-1 0 0 1 40.804 -1)" fill-rule="evenodd"/></svg></a>';
            $pag .= '</div>'; //pagination-nav
            $pag .= '</div>'; //nav-wrap
        }


        return $pag;
    }

    public function download_product_pdf_ajax()
    {
        $term_slug = $filters = $products = $pdf_url = '';
        check_ajax_referer('ajax_request_nonce');
        if (!isset($_POST['prod_id']) || empty($_POST['prod_id']) || !isset($_POST['page_id']) || empty($_POST['page_id'])) die(array('success' => false, 'mess' => __('Error ID', 'unilock')));

        $prod_id = $_POST['prod_id'];
        $page_id = $_POST['page_id'];
        $prod_type = isset($_POST['prod_type']) ? $_POST['prod_type'] : '';

        if (empty($prod_type) || $prod_type == 'standart') $ucara_product = false;
        else $ucara_product = true;

        $html = $this->product_pdf($prod_id, $page_id, $ucara_product);

        $ClassProdPDF = new PDFGeneration();
        $region = unilock_get_region();
        $product_name = get_the_title($page_id);

        $html_pdf = $ClassProdPDF->generate_prod_html_content($product_name, $html);
        //file_put_contents(__DIR__ . '/pdf_html_new.html', print_r($html_pdf, true));
        $pdf_url = $ClassProdPDF->render_pdf($product_name, $page_id, $region, $html_pdf, 'product');
        die(json_encode(array('success' => true, 'pdf_url' => $pdf_url)));
    }

    private function product_pdf($product_id, $page_id, $ucara_product)
    {
        $random_bundle = [];
        $product = $this->get_product($product_id, $ucara_product);
        $title = get_the_title($page_id);
        $banner_image = $product['banner_image'];
        $main_product_description = $product['main_product_description'];
        $use_u_cara_sorting = $product['use_u_cara_sorting'];
        $swatches = $product['swatches'];
        $swatches_random = $product['swatches_random'];
        $product_technologies = $product['product_technologies'];
        $laying_pattern = $product['laying_pattern'];
        $pattern_files = $product['pattern_files'];
        $product_information = $product['product_information'];
        $product_packaging = $product['product_packaging'];
        switch_to_blog(1);

        $pattern_files_tab = ' ';
        if (!empty($pattern_files)) {
            $count_pattern = count($pattern_files);
            $p_qty = 1;
            ob_start();
            foreach ($pattern_files as $pattern) {
                $link = isset($pattern["Other_Picture"]) ? wp_get_attachment_url($pattern["Other_Picture"]) : '';
                $thumbnail = isset($pattern['Thumbnail_Pattern']["Other_Picture"]) ? wp_get_attachment_url($pattern['Thumbnail_Pattern']["Other_Picture"]) : '';
                if (isset($pattern['Contractor']) && ($pattern['Contractor'] == 'true') && !empty($thumbnail) && !empty($link)) {
                    $pattern_descr = '';
                    if (isset($pattern['Description'])) {
                        $pattern_descr = str_replace("_", " ", $pattern['Description']);
                        $pattern_descr = str_replace(array(".pdf", ".jpg"), "", $pattern_descr);
                    }
                    if ($p_qty == 1) echo '<tr>'; ?>
                    <td class="col-4">
                        <div class="pattern">
                            <img src="<?php echo esc_url($thumbnail) ?>" alt="<?php echo $pattern_descr; ?>" width="200px" height="auto">
                            <span><?php echo $pattern_descr; ?></span>
                        </div>
                    </td>
                <?php if ($p_qty % 3 == 0 && $p_qty != $count_pattern) echo '</tr><tr>';
                    if ($p_qty == $count_pattern) echo '</tr>';
                    $p_qty++;
                } ?>
        <?php }

            $empty_patterns = '';
            if ($p_qty < 5) {
                for ($i = $p_qty; $i < 6; $i++) {
                    $empty_patterns .= '<td class="col-2">&nbsp;</td>';
                }
            }

            $pattern_files_tab = ob_get_clean();
            $pattern_files_tab = trim($pattern_files_tab);
        }


        ob_start(); ?>

        <div class="section pg-1">
            <div class="top-line"><i></i></div>
            <div class="h3"><?php echo esc_html($title); ?></div>
            <?php if (!$banner_image) $banner_image = get_field('products_banner_image', $product_id); ?>
            <div class="img-wrapp"><img src="<?php echo (!empty($banner_image) && $banner_image != '1') ? esc_url($banner_image) : esc_url($img_url); ?>" alt="banner_image" width="100%" height="450px"></div>

            <?php if ($main_product_description) : ?>
                <div class="text">
                    <?php echo wp_kses_post($main_product_description); ?>
                </div>
            <?php endif; ?>


            <?php //--Prod Colors Images
            if ($swatches_random || $swatches) :
                if (empty($swatches_random)) $swatches_random = $swatches; ?>
                <div class="table-wrapp">
                    <table class="row">
                        <?php
                        $swatch_data = $swatch_shape = $uniq_color = [];
                        $empty_shape = false;
                        $qty = 1;
                        foreach ($swatches_random as $skey => $swatch_inside) :
                            foreach ($swatch_inside as $key => $swatch) {

                                if (!empty($swatch['name_bundle'])) {
                                    $swatch_shape[$key] = $swatch['name_bundle'];
                                } else {
                                    $empty_shape = true;
                                }
                            }

                            foreach ($swatch_inside as $swatch_random) :

                                $shape_image = wp_get_attachment_url($swatch_random['picture']);
                                $shape_image_big = wp_get_attachment_url($swatch_random['swatch_picture']);


                                if (!$shape_image) $shape_image = NO_IMAGE;
                                if (!$shape_image_big) $shape_image_big = NO_IMAGE;

                                $shape_image_big = aq_resize($shape_image_big, 130, 130, true, true, true);
                                $shape_image_small = aq_resize($shape_image, 90, 90, true, true, true);

                                $swatch_name_bundle = str_replace('"', '', $swatch_random['name_bundle']);
                                if ($use_u_cara_sorting) {
                                    $data_color_name = sanitize_title($swatch_random['product_surface'] . '-' . $swatch_random['color-name']);
                                } else {
                                    $data_color_name = $swatch_random['color-name'];
                                }

                                $random_bundle[$data_color_name][] = ['data' => $swatch_random['data'], 'img' => $shape_image_small];

                                if (!in_array($data_color_name, $uniq_color)) :
                                    $uniq_color[] = $data_color_name;
                                    if ($qty == 1 || $qty % 4 == 0) echo '<tr>'; ?>

                                    <td class="col-3">
                                        <div class="img-box">
                                            <img src="<?php echo esc_url($shape_image_big); ?>" alt="" width="auto" height="130px">
                                            <span><?php echo $swatch_name_bundle . ' - ' . $data_color_name; ?></span>
                                        </div>
                                    </td>

                        <?php if ($qty % 3 == 0) echo '</tr>';
                                    $qty++;
                                endif;
                            endforeach;
                        endforeach; ?>

                    </table>
                </div>
            <?php endif; ?>

            <div class="text text-1">
                <p>This product is made with the following Technologies. To learn more about these technologies visit <a href="<?php echo CHILD_HOME_URL; ?>" target="_blank">unilock.com</a></p>
            </div>


            <?php //--Technologies
            $product_technologies_new = [];
            if ($product_technologies) :
                foreach ($product_technologies as $techs) {
                    foreach ($techs as $value) {
                        foreach ($value as $v) {
                            $product_technologies_new[] = $v;
                        }
                    }
                }

                $product_technologies_new = array_unique($product_technologies_new, SORT_REGULAR);
                if ($product_technologies_new) :
                    $count = count($product_technologies_new);
                    $html_thech = ''; ?>
                    <div class="table-wrapp" style="max-width:50%">
                        <table class="row">
                            <?php $qty = 1;
                            foreach ($product_technologies_new as $tkey => $tech_id) {

                                // $tech_title = get_field('product_technology_contractor_title_text', 'product_tech_' . $tech_id);
                                // $tech_image = get_field('product_technology_contractor_image', 'product_tech_' . $tech_id);
                                $tech_image = get_field('product_technology_title_image', 'product_tech_' . $tech_id);
                                $tech_title = get_field('product_technology_title_text', 'product_tech_' . $tech_id);
                                if ($qty === 1) $html_thech .= '<tr class="test11">';

                                if (!empty($tech_image)) {
                                    $html_thech .= '<td class="col-3 tech_image"><img src="' . esc_url($tech_image) . '" alt="" width="auto" height="20px" style="max-width:120px;height:auto;"></td>';
                                } else {
                                    $html_thech .= '<td class="col-4 tech_image"><p>' . esc_html($tech_title) . '</p></td>';
                                }
                                if ($qty % 3 === 0 && $qty != $count) $html_thech .= '</tr><tr>';
                                if ($qty == $count) $html_thech .= '</tr>';

                                $qty++;
                            }
                            echo $html_thech; ?>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="page-num"><img src="<?php echo CHILD_THEME_URI; ?>/img/bg-num.png" alt="" width="55px" height="83px">1</div>
            <div class="footer-logo"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo.png" alt="" width="82px" height="auto"></div>
        </div>
        <div class="page_break"></div>

        <?php //--RANDOM BUNDLES
        if (!empty($random_bundle)) : ?>
            <div class="section pg-2">
                <div class="top-line"><i></i></div>
                <div class="h4 upper mb-1">Shape and size</div>
                <div class="subtitle">Random Bundle</div>
                <div class="table-wrapp">
                    <table class="row">
                        <?php $fist_color_bundle = reset($random_bundle);
                        $count_bundle = count($fist_color_bundle);
                        foreach ($fist_color_bundle as $bkey => $item_bundle) :
                            $unit_info = '';
                            if (!empty($item_bundle['data'])) $unit_info = explode("<br/>", $item_bundle['data']);
                            if (($bkey + 1) == 1) echo '<tr>'; ?>

                            <td class="col-6">
                                <table class="row unit">
                                    <tr>
                                        <?php if (!empty($item_bundle['img'])) :
                                            $item_bundle_img = aq_resize($item_bundle['img'], 90, 90, true); ?>
                                            <td class="col-4">
                                                <div class="unit-img">
                                                    <img src="<?php echo esc_url($item_bundle_img); ?>" alt="item_bundle_image" width="90px" height="90px">
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                        <td class="col-8">
                                            <?php if (!empty($unit_info)) : ?>
                                                <div class="unit-title"><?php echo $unit_info[0]; ?></div>
                                                <div class="unit-desc">
                                                    <p><?php echo $unit_info[1]; ?></p>
                                                    <p><?php echo $unit_info[2]; ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>

                            <?php if (($bkey + 1) % 2 == 0 && ($bkey + 1) != $count_bundle) echo '</tr><tr>';
                            if (($bkey + 1) == $count_bundle) echo '</tr>' ?>

                        <?php endforeach; ?>
                    </table>
                </div>
                <div class="page-num"><img src="<?php echo CHILD_THEME_URI; ?>/img/bg-num.png" alt="" width="55px" height="83px">2</div>
                <div class="footer-logo"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo.png" alt="" width="82px" height="auto"></div>
            </div>
            <div class="page_break"></div>
        <?php endif; ?>


        <?php //--LAYING PATTERNS
        if (!empty($pattern_files_tab)) : ?>
            <div class="section pg-3">
                <div class="top-line"><i></i></div>
                <div class="h4 upper">laying patterns</div>
                <div class="table-wrapp">
                    <table class="row">
                        <?php echo $pattern_files_tab; ?>
                        <?php echo $empty_patterns; ?>
                    </table>
                </div>
                <div class="page-num"><img src="<?php echo CHILD_THEME_URI; ?>/img/bg-num.png" alt="" width="55px" height="83px">3</div>
                <div class="footer-logo"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo.png" alt="" width="82px" height="auto"></div>
            </div>
            <div class="page_break"></div>
        <?php endif; ?>

        <?php //--PACKAGING
        if (!empty($product_packaging)) :
            $site_option_packaging = get_field('packaging_repeater', 'option');

            $new_array = [];
            $site_option_counter = 0;

            foreach ($product_packaging as $key => $value_array) {
                foreach ($value_array as $key_packaging => $value_packaging) {
                    foreach ($site_option_packaging[0] as $key_option => $value_option) {
                        if (array_key_exists($key_option, $value_packaging) && !empty($value_packaging[$key_option])) {
                            if (is_numeric($value_packaging[$key_option]) && $value_packaging[$key_option] < 0.0001) {
                                continue;
                            }
                            $new_array['packaging'][$value_option][$site_option_counter] = $value_packaging[$key_option];
                            if (isset($value_packaging['height_1'])) {
                                $new_array['packaging']['height_1'][$site_option_counter] = $value_packaging['height_1'];
                            }
                        }
                    }
                }
                $site_option_counter++;
            } ?>
            <div class="section pg-4">
                <div class="top-line"><i></i></div>
                <div class="h4 upper">PACKAGING</div>
                <div class="packaging-table">
                    <?php if (!empty($new_array) && isset($new_array['packaging']) && count($new_array['packaging']) > 1) {
                        echo $this->layout_paver($new_array);
                    } ?>
                </div>
                <div class="page-num"><img src="<?php echo CHILD_THEME_URI; ?>/img/bg-num.png" alt="" width="55px" height="83px">3</div>
                <div class="footer-logo"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo.png" alt="" width="82px" height="auto"></div>
            </div>
            <div class="page_break"></div>
        <?php endif; ?>


        <?php //--INFO
        if (!empty($product_information)) : ?>

            <div class="section pg-5">
                <div class="top-line"><i></i></div>
                <div class="h4 upper">information</div>
                <div class="text text-2"><?php echo wp_kses_post($product_information[0]); ?></div>
                <div class="page-num"><img src="<?php echo CHILD_THEME_URI; ?>/img/bg-num.png" alt="" width="55px" height="83px">4</div>
                <div class="footer-logo"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo.png" alt="" width="82px" height="auto"></div>
            </div>
        <?php endif; ?>

        <div class="section">
            <div class="text">
                <p>The information in this document was printed <?php echo date('F j, Y'); ?> Products, colors and other information may change over time. Please refer to <a href="<?php echo CHILD_HOME_URL; ?>" target="_blank">unilock.com</a> for
                    the most current information. </p>
            </div>
        </div>

        <?php $pdf_html = ob_get_clean();
        restore_current_blog();
        return $pdf_html;
    }

    public function get_product($product_id, $ucara_product = false)
    {
        switch_to_blog(1);

        $variations_args = array(
            'post_type' => 'variation',
            'posts_per_page' => -1,
            'orderby' => 'publish_date',
            'order' => 'ASC',
            'post_parent' => $product_id,
            'fields' => 'ids',
        );

        if (isset($_GET['cat']) && !empty($_GET['cat'])) {
            $variations_args['meta_query'] = array(
                array(
                    'key' => '_uni_variation_category',
                    'value' => $_GET['cat'],
                    'compare' => 'LIKE'
                )
            );
        }

        $variations = get_posts($variations_args);

        if (count($variations) <= 0) {
            $variations = get_posts(array(
                'post_type' => 'variation',
                'orderby' => 'publish_date',
                'order' => 'ASC',
                'posts_per_page' => -1,
                'post_parent' => $product_id,
                'fields' => 'ids',
            ));
        }

        $variations_counter = 1;
        $product_swatches = $product_packaging = $product_technologies = $product_pac = $product_aplications = [];

        $banner_image = $main_product_description = $product_information = $product_categories = $mob_banner_image = '';
        $swatches_all = $swatches = $swatches_random = $random_bundle = $applications = $reddot = $laying_pattern = $cross_section = $pattern_files = $product_surfaces = $product_grouped_colors = $variation_tech_id = [];

        $count_variation = $count_aplication = 0;

        $random_bundle_isset = false;

        foreach ($variations as $variation_id) {

            $variation_tech_id = get_post_meta($variation_id, 'Technology');
            $product_technologies[] = get_post_meta($variation_id, 'Technology');
            $variation_height = get_post_meta($variation_id, 'Height', true);
            $variation_SKU = get_post_meta($variation_id, 'SKU', true);
            // $variation_Item_No          = get_post_meta( $variation_id, 'Item_No', true );
            $variation_random = get_post_meta($variation_id, 'Random_Configuration', true);
            $variation_suppress_j = get_post_meta($variation_id, 'Suppress_Joint', true);
            $variation_reddot = get_post_meta($variation_id, 'Reddot_Year', true);
            $variation_swatches = get_post_meta($variation_id, 'Swatchs', true);
            // file_put_contents(__DIR__.'/_variation_swatches_'.$variation_id.'.txt', print_r($variation_swatches, true));

            $variation_component = get_post_meta($variation_id, 'Component_Configuration', true);
            $variation_applications = get_post_meta($variation_id, 'Application', true);
            $variation_special_o = get_post_meta($variation_id, 'Special_Order', true);
            // if ( get_current_user_id() == 1 ){
            //     file_put_contents(__DIR__.'/R_variation_special_o_'.$title.'_'.$variation_id.'.txt', print_r($variation_special_o, true));
            // }

            $product_surface = get_post_meta($variation_id, 'Surface', true);
            $product_grouped_colors[$product_surface][] = $variation_swatches;

            $product_surfaces[] = get_post_meta($variation_id, 'Surface', true);

            // Get Banner Image and PDFs
            $product_images = get_post_meta($variation_id, 'Other_Images', true);

            /*$variation_data = [
                'Product' => get_the_title($product_id),
                'Region' => cont_check_region_user(),
                'Variation Title' => get_the_title($variation_id),
                'Variation SKU' => $variation_SKU,
                'Variation Swatches' => $variation_swatches,
                'PDFs' => $product_images,
            ];*/

            //file_put_contents(__DIR__.'/_product_content_' . $product_id . '.json', print_r(json_encode($variation_data), true), FILE_APPEND);

            if ($product_images) {
                foreach ($product_images as $image) {
                    if (isset($image["Contractor"]) && $image["Contractor"] == 'true') {

                        if (!empty($image['Other_Picture'])) {
                            if ($image['Image_Type'] == 'HERO PICTURE') {
                                if (!$banner_image) {
                                    $banner_image = wp_get_attachment_url($image['Other_Picture']);
                                }
                            }

                            if ($image['Image_Type'] == 'MOBILE HEADER') {
                                if (empty($mob_banner_image)) {
                                    $mob_banner_image = wp_get_attachment_url($image['Other_Picture']);
                                }
                            }
                            //if ($variations_counter == 1) {
                            if ($image['Image_Type'] == 'SELL SHEET' && !empty($image['Description'])) {
                                $laying_pattern[] = $image;
                            }
                            //}
                            if ($variations_counter == 1) {
                                if ($image['Image_Type'] == 'CROSS SECTION' && !empty($image['Description'])) {
                                    $cross_section[] = $image;
                                }
                            }
                        }
                    }
                }
                foreach ($product_images as $image) {
                    if (isset($image["Contractor"]) && $image["Contractor"] == 'true') {
                        if (!empty($image['Other_Picture'])) {
                            //                            if ($variations_counter == 1) {
                            //var_dump($image);
                            if ($image['Image_Type'] == 'LAYING PATTERN' && !empty($image['Description'])) {
                                $laying_pattern[] = $image;
                                if (!empty($image['File_Name'])) {
                                    $pattern_files[$image['File_Name']] = $image;
                                } else {
                                    $pattern_files[$image['Description']] = $image;
                                }
                            }
                            //                            }
                        }
                    }
                }
                foreach ($product_images as $image) {
                    if (isset($image['Contractor']) && $image['Contractor'] == 'true') {
                        if (!empty($image['Other_Picture'])) {
                            //                            if ($variations_counter == 1) {
                            if ($image['Image_Type'] == 'LP - THUMBNAIL' && !empty($image['File_Name'])) {
                                $pattern_files[$image['File_Name']]['Thumbnail_Pattern'] = $image;
                            }
                            //                            }
                        }
                    }
                }
            }

            // file_put_contents(__DIR__.'/r_laying_pat_'.$variation_id.'.txt', print_r($product_images, true));

            if ($variations_counter == 1) {
                $main_product_description = get_post_meta($variation_id, 'Contractor_Product_Desc', true);
                if (empty($main_product_description)) $main_product_description = get_post_meta($variation_id, 'Main_Product_Description', true);
                $product_information = get_post_meta($variation_id, 'Important_Information');
                $product_categories = get_post_meta($variation_id, 'Categories');
            }
            // if($variation_random === 'false'){
            $product_pac = get_post_meta($variation_id, 'Packaging_Data', true);
            // }

            // Reddot Year
            if ($variation_reddot) {
                $reddot[] = $variation_reddot;
            }

            // Applications
            if ($variation_applications) {
                $applications = array_merge($applications, $variation_applications);
            }

            // Component Configuration
            if ($variation_component) {
                foreach ($variation_component as $component) {
                    if ((!empty($component['Component_Description']))) {
                        $check_repeated_description = false;
                        $check_repeated_height = false;
                        if (!empty($product_packaging)) {
                            foreach ($product_packaging as $key => $packaging_array) {
                                if ($packaging_array[0]['packaging_1'] == $component['Component_Description']) {
                                    $check_repeated_description = true;
                                }
                                if (isset($packaging_array[0]['height_1']) && ($component['Height_mm'] == $packaging_array[0]['height_1']) && !$check_repeated_height && $variation_random == 'true') {
                                    $check_repeated_height = true;
                                }
                            }
                        }
                        if ($check_repeated_description == true || $check_repeated_height == true) {
                            continue;
                        }

                        $product_packaging[$count_variation][0] = $product_pac;
                        if ($variation_random == 'true') {
                            $product_packaging[$count_variation][0]['packaging_1'] = 'Random Bundle';
                            $product_packaging[$count_variation][0]['height_1'] = $component['Height_mm'];
                            $random_bundle_isset = true;
                        } else {
                            $product_packaging[$count_variation][0] = $product_pac;
                            $product_packaging[$count_variation][0]['packaging_1'] = $component['Component_Description'];
                        }
                        $count_variation++;
                    }
                }
            }

            // Swatches and Swatches Random
            if ($variation_swatches) {
                foreach ($variation_swatches as $variation_swatch) {
                    $swatch_picture = $variation_swatch['Swatch_Picture'];

                    foreach ($variation_swatch['Component_Picture'] as $picture) {
                        $mm = $inch = $length = $width = $height = '';
                        foreach ($variation_component as $configuration) {
                            if (!empty($configuration['Component']) && $configuration['Component'] == $picture['Component']) {
                                $mm = (is_array($configuration['Dimension_mm']) ? implode(' ', $configuration['Dimension_mm']) : $configuration['Dimension_mm']);
                                $inch = (is_array($configuration['Dimension_imperial']) ? implode(' ', $configuration['Dimension_imperial']) : $configuration['Dimension_imperial']);
                                $length = (is_array($configuration['Length_mm']) ? implode(' ', $configuration['Length_mm']) : $configuration['Length_mm']);
                                $width = (is_array($configuration['Width_mm']) ? implode(' ', $configuration['Width_mm']) : $configuration['Width_mm']);
                                // $height = round( $configuration['Height_mm']/10, 2);
                                $height = (!is_array($configuration['Height_mm']) ? round($configuration['Height_mm'] / 10, 2) : '');
                                break;
                            }
                        }

                        // array technologies ids for variations
                        $product_technologies_id = [];
                        foreach ($variation_tech_id as $key => $technologies_inside) {
                            foreach ($technologies_inside as $key => $tech_id) {
                                $product_technologies_id[] = $tech_id;
                            }
                        }
                        $product_technologies_id = array_unique($product_technologies_id);


                        $picture_component = '<span>' . $picture['Component_Description'] . '</span>';
                        if ($variation_random == 'true') {
                            $swatches_random[$product_surface][] = [
                                'data' => implode('<br/>', [$picture_component, $mm, $inch]),
                                'picture' => $picture['Picture'],
                                'length' => $length,
                                'width' => $width,
                                'height' => $height,
                                'color-name' => $picture['Colour_Description'],
                                'Component_Description' => $picture['Component_Description'],
                                'SKU' => $variation_SKU,
                                'name_bundle' => $picture['Component_Description'],
                                'variation_applications' => $variation_applications,
                                'product_surface' => $product_surface,
                                'product_technologies' => $product_technologies_id,
                                'special_order' => $variation_special_o,
                                'variation_id' => $variation_id,
                                'swatch_picture' => $swatch_picture
                            ];
                        } else {
                            $sort_shape = preg_replace("/[^0-9]/", "", $picture['Component_Description']);
                            if (strlen($sort_shape) < 1) {
                                $sort_shape = $picture['Component_Description'];
                            }

                            $swatches[$product_surface][] = [
                                'data' => implode('<br/>', [$picture_component, $mm, $inch]),
                                'picture' => $picture['Picture'],
                                'color-name' => $picture['Colour_Description'],
                                'SKU' => $variation_SKU,
                                'height' => $height,
                                'sort_shape' => $sort_shape,
                                'name_bundle' => $picture['Component_Description'],
                                'variation_applications' => $variation_applications,
                                'product_surface' => $product_surface,
                                'product_technologies' => $product_technologies_id,
                                'special_order' => $variation_special_o,
                                'variation_id' => $variation_id,
                                'swatch_picture' => $swatch_picture
                            ];
                        }
                    }
                    $swatches_all[] = $variation_swatch;
                }
            }
            $variations_counter++;
        }


        if ($swatches_all) {
            $swatches_all = unilock_super_unique($swatches_all, 'Colour_Hex_Code');
        }

        // If Pavers category and more that 2 surfaces use sorting functionality from U-cara
        $use_u_cara_sorting = $ucara_product;
        $result_surfaces = array_unique($product_surfaces);
        if (count($result_surfaces) > 1) {
            $product_cats = get_the_terms($product_id, 'product_cat');
            if ($product_cats) {
                foreach ($product_cats as $product_cat) {
                    if ($product_cat->name == 'PAVERS' || $product_cat->name == 'PERMEABLE PAVERS') {
                        $use_u_cara_sorting = true;
                    }
                }
            }
        }
        // product gallery
        $product_gallery = get_field('products_gallery', $product_id);

        // check if create item menu
        $all_technologies = [];
        foreach ($product_technologies as $key => $value) {
            if (!empty($value[0])) {
                $all_technologies[] = $value;
            }
        }

        restore_current_blog();

        //var_dump($product_packaging);
        return array(
            'product_swatches' => $product_swatches,
            'product_packaging' => $product_packaging,
            'product_technologies' => $product_technologies,
            'product_pac' => $product_pac,
            'product_aplications' => $product_aplications,
            'banner_image' => $banner_image,
            'mob_banner_image' => $mob_banner_image,
            'main_product_description' => $main_product_description,
            'swatches_all' => $swatches_all,
            'swatches' => $swatches,
            'swatches_random' => $swatches_random,
            'random_bundle' => $random_bundle,
            'applications' => $applications,
            'reddot' => $reddot,
            'pattern_files' => $pattern_files,
            'laying_pattern' => $laying_pattern,
            'cross_section' => $cross_section,
            'product_surfaces' => $product_surfaces,
            'product_grouped_colors' => $product_grouped_colors,
            'variation_tech_id' => $variation_tech_id,
            'use_u_cara_sorting' => $use_u_cara_sorting,
            'product_gallery' => $product_gallery,
            'product_information' => $product_information,
            'product_categories' => $product_categories,
            'CASH_DIR_FILES' => $this->CASH_DIR_FILES,
        );
    }

    public function tech_sheets_pdf($product_id, $ucara_product, $tech_sheets_pages_html = '')
    {

        //$prod_cat = '';
        $product = $this->get_product($product_id, $ucara_product);
        $random_bundle = $prod_application_res = [];
        $finish_detail = $edge_detail = $finish_detail_descr = $edge_detail_descr = $joint_spacing = $prod_tech_html = $prod_tech_logo = $astm_data = $leed_data = $other_tech_sheets_pages = $cat_slug = $empty_patterns = '';
        $product_technologies = $product['product_technologies'];
        $swatches = $product['swatches'];
        $swatches_random = $product['swatches_random'];
        $pattern_files = $product['pattern_files'];
        switch_to_blog(1);
        $prod_url = get_the_permalink($product_id);

        /*$cur_terms = get_the_terms($product_id, 'product_cat');
        if (!empty($cur_terms)) $prod_cat = $cur_terms[0]->slug;*/

        $prod_cat = [];
        $cur_terms = get_the_terms($product_id, 'product_cat');
        if (!empty($cur_terms)) {
            foreach ($cur_terms as $cat) {
                $prod_cat[] = $cat->slug;
            }
        }

        $prod_url = stristr($prod_url, 'product/');
        $prod_url = CHILD_HOME_URL . $prod_url;
        $prod_title = get_the_title($product_id);
        $cur_category = get_the_terms($product_id, 'product_cat');
        if (!empty($cur_category)) {
            $cat_slug = $cur_category[0]->slug;
            $other_tech_sheets_pages = $this->other_tech_sheets_pages($cat_slug);
        }

        $variations_args = array(
            'post_type' => 'variation',
            'posts_per_page' => -1,
            'post_parent' => $product_id,
            'fields' => 'ids',
        );

        $variations = get_posts($variations_args);
        //--Other meta data values
        if (!empty($variations)) {
            foreach ($variations as $variation_id) {
                $product_images = get_post_meta($variation_id, 'Other_Images');
                if (!empty($product_images) && empty($edge_detail) && empty($finish_detail)) {
                    $product_images = maybe_unserialize($product_images);
                    foreach ($product_images[0] as $image) {
                        if ($image['Image_Type'] == 'FINISH DETAIL' && empty($finish_detail)) {
                            if (!empty($image['Other_Picture'])) $finish_detail = wp_get_attachment_url($image['Other_Picture']);
                            if (empty($finish_detail_descr)) $finish_detail_descr = $image['Description'];
                        }
                        if ($image['Image_Type'] == 'EDGE DETAIL' && empty($edge_detail)) {
                            if (!empty($image['Other_Picture'])) $edge_detail = wp_get_attachment_url($image['Other_Picture']);
                            if (empty($edge_detail_descr)) $edge_detail_descr = $image['Description'];
                        }
                        if (!empty($edge_detail) && !empty($finish_detail)) break;
                    }
                }
                if (empty($joint_spacing)) $joint_spacing = get_post_meta($variation_id, 'Joint_Spacing');
                if (empty($astm_data)) $astm_data = get_post_meta($variation_id, 'ASTM_Data');
                if (empty($leed_data)) $leed_data = get_post_meta($variation_id, 'LEED_Data');
                $prod_application_res = $this->get_variations_opportunities($variation_id, $prod_application_res);
            }
        }

        //--Technologies
        $product_technologies_new = [];
        if ($product_technologies) {
            foreach ($product_technologies as $techs) {
                foreach ($techs as $value) {
                    foreach ($value as $v) {
                        $product_technologies_new[] = $v;
                    }
                }
            }

            $product_technologies_new = array_unique($product_technologies_new, SORT_REGULAR);
            //file_put_contents(__DIR__ . '/product_technologies_new.txt', print_r($product_technologies_new, true));
            if ($product_technologies_new) {
                foreach ($product_technologies_new as $tkey => $tech_id) {
                    // $tech_title = get_field('product_technology_title_text', 'product_tech_' . $tech_id);
                    // $tech_image = get_field('product_technology_title_image', 'product_tech_' . $tech_id);
                    // $tech_descr = get_field('product_technology_description', 'product_tech_' . $tech_id);
                    $tech_descr = get_field('product_technology_contractor_description_pdf', 'product_tech_' . $tech_id);
                    $tech_image = get_field('product_technology_title_image', 'product_tech_' . $tech_id);
                    $tech_title = get_field('product_technology_title_text', 'product_tech_' . $tech_id);
                    //file_put_contents(__DIR__ . '/product_technologies_new.txt', print_r($tech_descr, true), FILE_APPEND);
                    if (!empty($tech_image) && empty($prod_tech_logo)) $prod_tech_logo = $tech_image;
                    $prod_tech_html .= '<h3>' . esc_html($tech_title) . '</h3>';
                    if (!empty($tech_descr)) $prod_tech_html .= '<p>' . wp_kses_post($tech_descr) . '</p>';
                }
            }
        }

        $pdf_patterns_tab = '';
        if (!empty($pattern_files)) {
            $p_qty = 1;

            ob_start();
            foreach ($pattern_files as $pattern) {
                $link = isset($pattern["Other_Picture"]) ? wp_get_attachment_url($pattern["Other_Picture"]) : '';
                $thumbnail = isset($pattern['Thumbnail_Pattern']["Other_Picture"]) ? wp_get_attachment_url($pattern['Thumbnail_Pattern']["Other_Picture"]) : '';
                if (!empty($thumbnail) && !empty($link) && $p_qty < 6) {
                    $pattern_descr = '';
                    if (isset($pattern['Description'])) {
                        $pattern_descr = str_replace("_", " ", $pattern['Description']);
                        $pattern_descr = str_replace(array(".pdf", ".jpg"), "", $pattern_descr);
                    } ?>
                    <td class="col-2">
                        <div class="pattern">
                            <img src="<?php echo esc_url($thumbnail) ?>" alt="<?php echo $pattern_descr; ?>" width="210px" height="105px" style="margin-top:5px;height:auto">
                            <span><?php echo $pattern_descr; ?></span>
                        </div>
                    </td>
        <?php $p_qty++;
                }
            }

            if ($p_qty < 5) {
                for ($i = $p_qty; $i < 6; $i++) {
                    $empty_patterns .= '<td class="col-2">&nbsp;</td>';
                }
            }

            $pdf_patterns_tab = ob_get_clean();
            $pdf_patterns_tab = trim($pdf_patterns_tab);
        }

        ob_start(); ?>

        <div class="page page_1">
            <div class="header">
                <table class="row">
                    <tbody>
                        <tr>
                            <td class="col-6">
                                <div class="logo"><?php echo $prod_title; ?></div>
                            </td>
                            <?php if (!empty($prod_tech_logo)) : ?>
                                <td class="col-6 text-right logo2"><img src="<?php echo esc_url($prod_tech_logo); ?>" alt="prod_tech_logo"></td>
                            <?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <table class="row mb-2">
                <tbody>
                    <tr>
                        <td class="col-6">
                            <div class="pr-1">
                                <?php if (!empty($edge_detail) || !empty($finish_detail) || !empty($finish_detail_descr) || !empty($edge_detail_descr) || !empty($joint_spacing[0])) : ?>
                                    <div class="section">
                                        <div class="title">PRODUCT ATTRIBUTES</div>

                                        <?php //--EDGE & FINISH DETAIL
                                        if (!empty($edge_detail) || !empty($finish_detail) || !empty($finish_detail_descr) || !empty($edge_detail_descr)) : ?>
                                            <table class="row mb-1">
                                                <tbody>
                                                    <tr>
                                                        <?php if (!empty($finish_detail) || !empty($finish_detail_descr)) : ?>
                                                            <td class="col-6 text-center">
                                                                <div class="box box-inner">
                                                                    <div class="title type1">FINISH DETAIL</div>
                                                                    <?php if (!empty($finish_detail)) : ?>
                                                                        <div class="box-img"><img src="<?php echo esc_url($finish_detail); ?>" alt="fin_det_img"></div>
                                                                    <?php else : ?>
                                                                        <!-- test image -->
                                                                        <div class="box-img"><img src="<?php echo CHILD_THEME_URL; ?>/img/img-product1.jpg" alt=""></div>
                                                                        <!-- end test image -->
                                                                    <?php endif; ?>
                                                                    <div class="text1"><?php echo wp_kses_post($finish_detail_descr); ?></div>
                                                                </div>
                                                            </td>
                                                        <?php endif; ?>
                                                        <?php if (!empty($edge_detail) || !empty($edge_detail_descr)) : ?>
                                                            <td class="col-6 text-center">
                                                                <div class="box box-inner">
                                                                    <div class="title type1">EDGE DETAIL</div>
                                                                    <?php if (!empty($edge_detail)) : ?>
                                                                        <div class="box-img"><img src="<?php echo esc_url($edge_detail); ?>" alt="edge_det_img"></div>
                                                                    <?php else : ?>
                                                                        <!-- test image -->
                                                                        <div class="box-img"><img src="<?php echo CHILD_THEME_URL; ?>/img/img-product2.jpg" alt=""></div>
                                                                        <!-- end test image -->
                                                                    <?php endif; ?>
                                                                    <div class="text1"><?php echo wp_kses_post($edge_detail_descr); ?></div>
                                                                </div>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>

                                        <?php //--Joint Spacing
                                        if (!empty($joint_spacing[0]) || $joint_spacing[0] == 0 || $joint_spacing[0] == ' ') : ?>
                                            <div class="simple-page mb-1">
                                                <h3>Joint Spacing</h3>
                                                <p><?php echo $joint_spacing[0]; ?></p>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                <?php endif; ?>

                                <?php //--Technologies
                                if (!empty($prod_tech_html)) { ?>
                                    <div class="section">
                                        <div class="title">TECHNOLOGIES</div>
                                        <div class="box">
                                            <div class="box-inner2">
                                                <div class="simple-page type1">
                                                    <?php echo $prod_tech_html; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

                            </div>
                        </td>

                        <?php //--APPLICATIONS & OPPORTUNITIES
                        if (!empty($prod_application_res)) : ?>
                            <td class="col-6">
                                <div class="section">
                                    <table class="row">
                                        <tbody>
                                            <tr>
                                                <td class="col-5 border-right">
                                                    <div class="title">APPLICATIONS</div>
                                                </td>
                                                <td class="col-7 pl-1">
                                                    <div class="title">OPPORTUNITIES</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <table class="row">
                                        <tbody>
                                            <?php foreach ($prod_application_res as $application_name => $opportunity) : ?>
                                                <tr>
                                                    <td class="col-5 box">
                                                        <div class="box-inner1 text1">
                                                            <ul>
                                                                <li><?php echo esc_html($application_name); ?></li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                    <td class="col-7 box">
                                                        <div class="box-inner1 text1">
                                                            <ul><?php echo wp_kses_post($opportunity); ?></ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                </tbody>
            </table>


            <?php //--Prod Colors && RANDOM BUNDLES
            if ($swatches_random || $swatches) :
                if (empty($swatches_random)) $swatches_random = $swatches; ?>
                <div class="section">
                    <table class="row mb-2">
                        <tbody>
                            <tr>

                                <td class="col-6">
                                    <div class="pr-1">
                                        <div class="title">COLORS <small>Refer to regional product data guide for stock colors</small></div>
                                        <table class="table row">
                                            <tr>
                                                <td class="col-12">
                                                    <div class="box box-inner">
                                                        <div class="simple-page type1">
                                                            <h3>STOCK</h3>
                                                            <ul>
                                                                <?php $qty = 1;
                                                                $swatch_data = $swatch_shape = $uniq_color = [];
                                                                $empty_shape = false;
                                                                foreach ($swatches_random as $skey => $swatch_inside) {
                                                                    foreach ($swatch_inside as $key => $swatch) {
                                                                        if (!empty($swatch['name_bundle'])) $swatch_shape[$key] = $swatch['name_bundle'];
                                                                        else $empty_shape = true;
                                                                    }
                                                                    foreach ($swatch_inside as $swatch_random) {
                                                                        $data_color_name = $swatch_random['color-name'];
                                                                        if (!in_array($data_color_name, $uniq_color)) {
                                                                            $uniq_color[] = $data_color_name;
                                                                            echo '<li>' . $swatch_random['color-name'] . '</li>';
                                                                        }
                                                                        //$random_bundle[$data_color_name] = $swatch_random['data'];
                                                                        $random_bundle[] = $swatch_random['data'];
                                                                    }
                                                                    $random_bundle = array_unique($random_bundle);
                                                                } ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>

                                <?php //--RANDOM BUNDLES
                                if (!empty($random_bundle)) : ?>
                                    <td class="col-6">
                                        <div class="title">SIZES</div>
                                        <table class="table row">
                                            <tr>
                                                <td class="col-12">
                                                    <div class="box box-inner">
                                                        <div class="simple-page type1 list1">
                                                            <h3>STOCK</h3>
                                                            <ul>
                                                                <li>Random Bundle
                                                                    <ul>
                                                                        <?php foreach ($random_bundle as $item_bundle) {
                                                                            $unit_info = explode("<br/>", $item_bundle);
                                                                            echo '<li>' . $unit_info[1] . '<br/>' . $unit_info[2] . '</li>';
                                                                        } ?>
                                                                    </ul>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php //--APPLICATIONS & OPPORTUNITIES
            if (!empty($pdf_patterns_tab)) : ?>
                <div class="section">
                    <div class="title type3">LAYING PATTERNS <small> More patterns available at <a href="<?php echo esc_url($prod_url); ?>" target="_blank">Unilock.com</a></small></div>
                    <div class="box2">
                        <div class="table-wrapp">
                            <table class="row">
                                <tr>
                                    <?php echo $pdf_patterns_tab; ?>
                                    <?php echo $empty_patterns; ?>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="footer text-right">
                <img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo-unilock.jpg" alt="logo-unilock">
            </div>

        </div>

        <div class="page-break"></div>

        <div class="page page_2">

            <?php //--ASTM_Data
            if (!empty($astm_data[0])) : ?>
                <div class="section">
                    <div class="title">TECHNICAL INFORMATION</div>
                    <table class="row">
                        <tr>
                            <td class="col-12">
                                <div class="simple-page type2">
                                    <p><?php echo wp_kses_post($astm_data[0]) ?></p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div class="divider"></div>
                </div>
            <?php endif; ?>

            <?php //--LEED_Data
            if (!empty($leed_data[0])) : ?>
                <div class="section">
                    <div class="title">LEED V4.1 INFORMATION</div>
                    <div class="simple-page type2">
                        <p><?php echo wp_kses_post($leed_data[0]) ?></p>
                    </div>
                    <div class="divider"></div>
                </div>
            <?php endif; ?>

            <?php /* --hard code information -- */ ?>
            <div class="section">
                <div class="title">SRI INFORMATION</div>
                <div class="simple-page type2">
                    <p>Refer to Regional Product Data for SRI information.</p>
                </div>
                <div class="divider"></div>
            </div>

            <?php if ($cat_slug == 'pavers') : ?>
                <div class="section">
                    <div class="title">TYPICAL BASE RECOMMENDATIONS</div>
                    <div class="simple-page type2 mb-1">
                        <p>The depth of excavation depends on load requirements, drainage, existing soil conditions and
                            paver style
                            and thickness. To determine the depth of the excavation, use of the following guidelines:
                        </p>
                    </div>
                    <div class="img-wrapp mb-1"><img src="<?php echo CHILD_THEME_URI; ?>/img/img-recommendation.jpg" alt="img-recommendation"></div>
                </div>
            <?php endif; ?>

            <div class="footer">
                <table class="row">
                    <tr>
                        <td class="col-9">
                            <div class="footer-text">
                                <p>All measurements and conversions are nominal.</p>
                                <p>Note: Excavation depth guide is only a guide. Site engineering or experienced
                                    contractor guidance is always recommended.</p>
                            </div>
                        </td>
                        <td class="col-3 text-right"><img src="<?php echo CHILD_THEME_URI; ?>/img/pdf-logo-unilock.jpg" alt="pdf-logo-unilock"></td>
                    </tr>
                </table>
            </div>

        </div>

        <div class="page-break"></div>

        <?php if (!empty($other_tech_sheets_pages)) echo $other_tech_sheets_pages; ?>
        <?php /*if (!empty($tech_sheets_pages_html)) echo $tech_sheets_pages_html;
        else $other_tech_sheets_pages*/ ?>

<?php $pdf_html_pages = ob_get_clean();
        restore_current_blog();
        return array('title' => $prod_title, 'html' => $pdf_html_pages, 'cat' => $prod_cat);
    }

    private function other_tech_sheets_pages($slug)
    {
        if ($slug == 'coping') {
            $pdf_html_other_pages = '<div class="page page_9"> <div class="title type4 style1">Coping</div> <div class="title mb-2">APPLICATIONS</div> <div class="simple-page mb-3"> <p>A variety of coping styles are available at Unilock, in both concrete and natural stone. Remove any channels or alignment keys from the top of the wall blocks</p> </div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-coping1.png" alt="" width="237px" height="auto"></div> <div class="simple-page mb-4"> <p>Install coping using concrete adhesive. Natural Stone can be installed with concrete adhesive or mortar.</p> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-coping2.png" alt="" width="411px" height="auto"></div><div class="page-break"></div><div class="page page_10"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <h5>PPE</h5> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn.</p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for pavers or natural stone, dependent on the product, may be used for color restoration or general cleaning. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected.</p> </div></div><div class="page-break"></div>';
        } else if ($slug == 'natural-stone') {
            $pdf_html_other_pages = '<div class="page page_11"> <div class="title type4 style1">Natural Stone</div> <div class="title type4">Installation Options</div> <div class="title type7">CONCRETE OVERLAY</div> <div class="title type6 mb-3">FOR <b>GOOD</b> QUALITY CONCRETE PATIOS, PORCHES OR BALCONY SURFACES THAT ARE PROPERLY SLOPED</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-stone1.png" alt="" width="724px" height="auto"></div><div class="page-break"></div><div class="page page_12"> <div class="title type7">CONCRETE OVERLAY</div> <div class="title type6 mb-5">FOR <b>POOR</b> QUALITY OR UNEVEN CONCRETE PATIOS, PORCHES OR BALCONY SURFACES THAT ARE PROPERLY SLOPED</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-stone2.png" alt="" width="724px" height="auto"></div><div class="page-break"></div><div class="page page_13"> <div class="title type7">WOOD DECK VENEER</div> <div class="title type6 mb-5">STRUCTURAL UNDERLAYMENT FOR ELEVATED DECKS</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-stone4.png" alt="" width="724px" height="auto"></div><div class="page-break"></div><div class="page page_14"> <div class="title type7">WOOD DECK VENEER</div> <div class="title type6 mb-3">LOW PROFILE PEDESTAL</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-stone5.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_15"> <div class="title type7 mb-3">PEDESTAL ON CONCRETE APPLICATION</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-stone6.png" alt="" width="724px" height="auto"></div><div class="page-break"></div><div class="page page_16"> <div class="title type7 mb-3">POOL COPING AND POOL DECK</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-stone7.png" alt="" width="724px" height="auto"></div><div class="page-break"></div><div class="page page_17"> <div class="title type4">Base Options</div> <div class="title type7 mb-3">OPEN GRADED OR COMPACTED GRANULAR BASE</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-stone8.png" alt="" width="724px" height="auto"></div><div class="page-break"></div><div class="page page_18"> <div class="title type4">Base and Jointing Options</div> <div class="simple-page type1"> <h5>SPECIAL NOTE:</h5> <p>Refer to Natural Stone Installation guide for more information and best practices for the installation of Natural Stone.</p> <ol> <li>Concrete integrity – concrete must be in good condition, and not crumbling.</li> <li>Use a ramping mortar to correct where necessary.</li> <li>Drainage holes – In lowest areas ofthe concrete, drill1-2” holes in concrete (on 12” centers) and fill holes with ¼” chip (ASTM No. 8).</li> <li>Base drainage - the area below the concrete must not be subject to frost movement.</li> <li>Surface - surface must be totally smooth and flat equivalentto the desired finished surface.</li> <li>Waterproofing - may be required when installing Natural Stone over concrete where there is a basement or cold cellar below. A crack isolation/ waterproofing membrane is recommended.</li> <li>Jointing Sand is notrecommended between joints - Use an impervious exterior grade grout/caulk between joints.</li> </ol> </div></div><div class="page-break"></div><div class="page page_19"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <h5>PPE</h5> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn.</p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur. Because of its size and weight, always use a vacuum lifter device to place large units.</p> <h5>EDGE RESTRAINT</h5> <p>Always install an edge restraint around the perimeter of any paverinstallation notrestrained by building structures. Spike-in edge restraints come in plastic and metal and work wellfor most applications. A concrete curb or a sub-surface concrete wedge can also be installed to retain the edge.</p> <h5>COMPACTION</h5> <p>Do not compact the surface of Natural Stone paving with plate compactors.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for Natural Stone may be used for colorrestoration or general cleaning. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected. </p> <p><b>NOTE:</b> Efflorescence is a naturally occurring calcium saltthat can sometimes appear on the surface of concrete and natural stone hardscape products. This calcium saltis usually wicked up from the gravel and sand below. It does not affectthe structural integrity of the product and willrecede overtime. To speed the removal of efflorescence,the surface can be washed with a stone cleaner specifically designed to remove efflorescence.</p> <h5>SEALERS</h5> <ul> <li>Product may be sealed for aesthetic or cleanliness reasons butitis not required</li> <li>Use any sealer approved for Natural Stone</li> <li>Select type for desired aesthetics</li> <li>Product must be cleaned and dry before sealing</li> <li>Always read and follow sealer manufacturer’s application procedures</li> <li>Always test a small area to make sure the results are as expected</li> </ul> <h5>SNOW REMOVAL AND MAINTENANCE</h5> <p>Recommended Deicing Chemical: Sodium Chloride (NaCl)fortemperatures down to +20°F (-7°C). Sodium Chloride is commonly known as rock salt. Only when necessary, Calcium Chloride (CaCl2) can be used fortemperature ranges from below +20°F (-7°C)to -2°F (-19°C).</p> <p>We do not recommend using any other types of deicing chemicals. This includes: • Magnesium Chloride (MgCl2) • Calcium Magnesium Acetate (CMA) • Potassium Chloride (KCl) • Potassium Acetate (KA) • Fertilizers containing Ammonium Nitrate and Ammonium Sulfate.</p> <p>Only apply minimum amount of de-icing salts necessary to melt the snow and ice. Use sparingly to reduce damage to plants and vegetation. Remove excess salt after ice melts.</p> <p>After the winter season,thoroughly wash the paver surface to remove any excess deicing chemicalremaining.</p> <p>Regular cleaning routine should include sweeping or blowing loose debris from the pavement surface, and less frequently, deep cleaning with cleaning products and/or water</p> </div></div><div class="page-break"></div>';
        } else if ($slug == 'cap-stone') {
            $pdf_html_other_pages = '<div class="page page_20"> <div class="title type4 style1">Pillar Cap</div> <div class="title">APPLICATIONS</div> <div class="simple-page mb-3"> <p>A variety of Pillar Cap styles are available at Unilock, in both concrete and natural stone. Remove any channels or alignment keys from the top of wall blocks.</p> </div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-coping1.png" alt="" width="237px" height="auto"></div> <div class="title type4">Installation</div> <div class="simple-page mb-3"> <p>Install pillar cap using concrete adhesive. Natural Stone can be installed with concrete adhesive or mortar.</p> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-pillar.png" alt="" width="455px" height="auto"></div><div class="page-break"></div>';
        } else if ($slug == 'porcelain') {
            $pdf_html_other_pages = '<div class="page page_21"> <div class="title type4 style1">Porcelain</div> <div class="title type4">Installation Options</div> <div class="title type7 mb-1">NEW OR EXISTING CONCRETE PATIO, PORCH OR BALCONY OVERLAY</div> <div class="title type6 mb-3">MODIFIED MORTAR BEDDING</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain1.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_22"> <div class="title type7 mb-1">EXISTING CONCRETE PATIO, PORCH OR BALCONY OVERLAY</div> <div class="title type6 mb-3">CEMENTITIOUS BEDDING</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain2.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_23"> <div class="title type7 mb-1">PEDESTAL ON CONCRETE APPLICATION</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain3.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_24"> <div class="title type7 mb-1">INTERLOCKING POLYETHYLENE FOAM BOARD (IPFB)</div> <div class="title type6 mb-3">COMPACTED GRANULAR BASE</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain4.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_25"> <div class="title type7 mb-1">WOOD DECK VENEER</div> <div class="title type6 mb-3">STRUCTURAL UNDERLAYMENT FOR ELEVATED DECKS</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain5.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_26"> <div class="title type7 mb-1">WOOD DECK VENEER</div> <div class="title type6 mb-3">LOW PROFILE PEDESTAL</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain6.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_27"> <div class="title type7 mb-1">POOL COPING AND POOL DECK</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain7.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_28"> <div class="title type4">Base Options</div> <div class="title type7 mb-1">OPEN GRADED BASE - RIGID BEDDING (STABILIZED CHIP)</div> <div class="title type6 mb-3">FULLY PERMEABLE</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain8.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_29"> <div class="title type7 mb-1">OPEN GRADED BASE - FLEXIBLE BEDDING</div> <div class="title type6 mb-3">FULLY PERMEABLE</div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-porcelain9.png" alt="" width="724px" height="auto"></div></div><div class="page-break"></div><div class="page page_30"> <div class="title type4">Base Options</div> <div class="simple-page type1"> <h5>SPECIAL NOTE:</h5> <p>Refer to Porcelain Tile Installation guide for more information and best practices for the installation of PorcelainNatural Stone.</p> <ol> <li>Concrete integrity – concrete must be in good condition, and not crumbling.</li> <li>Drainage slope – concrete below must be sloped away from all buildings and structures.</li> <li>Drainage holes – In lowest areas of the concrete, drill 1-2” holes in concrete (on 12” centers) and fill holes with ¼” chip (ASTM No. 8).</li> <li>Base drainage - the area below the concrete must not be subject to frost movement.</li> <li>Surface - surface must be totally smooth and flat equivalent to the desired finished surface.</li> <li>Waterproofing - may be required when installing porcelain over concrete where there is a basement or cold cellar below. Install an impervious rubber membrane over the surface prior to installing any porcelain over the surface.</li> <li>Jointing Sand - Use an impervious polymeric sand when installing over concrete.</li> </ol> </div> <div class="divider"></div> <div class="title type4">Jointing Material and Joint Stabilization</div> <div class="text mb-2">All sands must meet ASTM C144 or C33 Specifications. For best appearance and optimal performance, keep jointing materials approximately 1/8” below the chamfer (bevel edge) of the porcelain.</div> <table class="row"> <tr> <td class="col-6 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">GOOD OPTION</div> <div class="text">Polymeric sand or Easy Pro jointing compound.</div> </div> </td> <td class="col-6 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">BEST OPTION</div> <div class="text">Polymeric sand or Easy Pro jointing compound.</div> </div> </td> <td class="col-2">&nbsp;</td> </tr> </table></div><div class="page-break"></div><div class="page page_31"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <h5>PPE</h5> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn.</p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur.</p> <h5>EDGE RESTRAINT</h5> <p>Always install an edge restraint around the perimeter of any porcelain installation not restrained by building structures. Spike-in edge restraints come in plastic and metal and work well for most applications. A concrete curb or a sub-surface concrete wedge can also be installed to retain the edge.</p> <h5>COMPACTION</h5> <p>Do not compact the surface of porcelain tile with plate compactors.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for porcelain tile may be used for color restoration or general cleaning. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected.</p> <h5>SNOW REMOVAL AND MAINTENANCE</h5> <p>Follow manufacturer recommendation for de-icing products.</p> <p>Only apply minimum amount of de-icing product necessary to melt the snow and ice. Use sparingly to reduce damage to plants and vegetation. Remove excess salt after ice melts.</p> <p>Only apply minimum amount of de-icing product necessary to melt the snow and ice. Use sparingly to reduce damage to plants and vegetation. Remove excess salt after ice melts.</p> <p>Regular cleaning routine should include sweeping or blowing loose debris from the pavement surface, and less frequently, deep cleaning with cleaning products and/or water.</p> </div></div><div class="page-break"></div>';
        } else if ($slug == 'walls') {
            $pdf_html_other_pages = '<div class="page page_32"> <div class="title type4 style1">Walls</div> <div class="simple-page type1"> <h5>APPLICATIONS</h5> <p>&nbsp;</p> <h5>NON-STRUCTURAL WALLS</h5> <p>Garden walls, planters, seat walls and features such as grill islands and water features can be created using segmental retaining wall blocks. Be sure to check with local building codes to confirm the maximum height allowed.</p> <h5>STRUCTURAL WALLS</h5> <p>Segmental Retaining Walls over 30-36” in height (depending on local building codes) are required to be engineered. Typical sections are available from Unilock however the final design must be created by an engineer taking into consideration site conditions.</p> </div></div><div class="page-break"></div><div class="page page_33"> <div class="title type4">Installation Options</div> <div class="title type4">Typical Details – Non-Structural Walls</div> <div class="title type7 mb-2">GARDENS AND PLANTER WALLS</div> <div class="custom-order-list mb-3"> <ol> <li>Install Universal Base Units on a 6” gravel base ensuring they are level. Use DriveGrid™ under gravel for added stability.</li> <li>Use a permeable filter fabric to separate the soil from the back of the wall.</li> <li>If stacked vertically blocks should be glued.</li> <li>Always glue coping to the top of your blocks after removing the alignment key.</li> </ol> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-walls1.png" width="724px" height="auto" alt=""></div><div class="page-break"></div><div class="page page_34"> <div class="title type7 mb-2">RAISED PATIO</div> <div class="custom-order-list mb-3"> <ol> <li>Install Universal Base Units on a 6” gravel base, ensuring they are level.</li> <li>Adhere the first row of blocks onto the Universal Base Units. (Optional) Use the grooves impressed onto the surface of the base unit to maintain block alignment.</li> <li>Continue to install subsequent rows of block making sure you horizontally offset each row by one half block. You may need to cut one unit in half every other row. </li> <li>Back fill as you go (max two layers of wall block) with 3/4” clear stone (ASTM No. 57). A layer of filter fabric is recommended directly behind the block to prevent any aggregate from migrating through any openings. Note: Geogrid (optional) may also be used to reinforce walls.</li> <li>When you reach patio-level height and your plan calls for a seat wall around the perimeter of the patio, you can transition to vertical stacking of the units (depending on the product type).</li> <li>In order to attach coping to the top of the seat wall, removal of top keys may be necessary.</li> </ol> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-walls2.png" width="724px" height="auto" alt=""></div><div class="page-break"></div><div class="page page_35"> <div class="title type7 mb-2">GRILL ISLAND</div> <div class="custom-order-list mb-3"> <ol> <li>Grill Islands and other larger features should be constructed on a concrete pad supported by 10” diameter concrete piers seated below the frost line. Space piers 6’ apart and place a wire mesh or rebar across the piers prior to pouring the 6” thick pad.</li> <li>Ensure there is also 6” of 3/4” open-graded stone gravel under the pad.</li> <li>Dry fit the first row with the appropriate corner and wall units before gluing to the pad. Leave openings as required for appliances and doors. </li> <li>Complete all consecutive rows of block offsetting all vertical joints. Each row will require concrete adhesive.</li> <li>Install precast or granite counter top.</li> </ol> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-walls3.png" width="724px" height="auto" alt=""></div><div class="page-break"></div><div class="page page_36"> <div class="title type7 mb-2">FIREPIT</div> <div class="custom-order-list mb-3"> <ol> <li>Install 8 Universal Base Units on a minimum 6” gravel base as shown, ensuring they are level.</li> <li>Using corner and wall units construct the perimeter of the fire pit to the required dimensions. </li> <li>Only use construction adhesive rated for high-heat applications (A 1200°F minimum rating is recommended).</li> <li>Repeat for consecutive rows until the desired height has been reached. Install coping to finish the top course.</li> </ol> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-walls4.png" width="auto" height="655px" alt=""></div><div class="page-break"></div><div class="page page_37"> <div class="title type4">Typical Details – Structural Walls</div> <div class="title type7 mb-2">GRAVITY WALL</div> <div class="custom-order-list mb-3"> <ol> <li>Perforated Drainage Pipe</li> <li>Filter Fabric</li> <li>3/4” Clear Stone (ASTM No. 57) or Road Base (6” thick)</li> <li>Universal Base Unit</li> <li>3/4” Clear Stone (ASTM No. 57) Backfill min. 12” wide</li> <li>Wall Units</li> <li>Decorative Panel (if applicable)</li> <li>Coping</li> <li>Subsoil</li> <li>Topsoil</li> <li>Turf</li> </ol> </div> <div class="negative-mt-1"><img src="' . CHILD_THEME_URI . '/img/pdf-img-walls5.png" width="auto" height="655px" alt=""></div></div><div class="page-break"></div><div class="page page_38"> <div class="title type7 mb-2">GEOGRID WALL</div> <div class="custom-order-list mb-3"> <ol> <li>Perforated Drainage Pipe</li> <li>Compacted Granular Fill as specified by engineer</li> <li>3/4” Clear Stone (ASTM No. 57) or Road Base (6” thick)</li> <li>Universal Base Unit</li> <li>Approved Geogrid</li> <li>Wall Block</li> <li>Decorative Fascia Panel (if applicable)</li> <li>Filter Fabric</li> <li>Coping</li> <li>Subsoil</li> <li>Topsoil</li> <li>Turf</li> </ol> </div> <div class="negative-mt-2"><img src="' . CHILD_THEME_URI . '/img/pdf-img-walls6.png" width="auto" height="760px" alt=""></div></div><div class="page page_39"> <div class="section"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <h5>PPE</h5> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn.</p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur. Because of its size and weight, always use appropriate equipment to place extra large units.</p> <h5>base</h5> <p>A wall base of gravel must be well compacted prior to the construction of the wall. If site requires, refer to engineered specifications.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for pavers may be used for cleaning the face of walls. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected.</p> <p><b>NOTE:</b> Efflorescence is a naturally occurring calcium salt that can sometimes appear on the surface of concrete and clay products. It does not affect the structural integrity of concrete. Efflorescence typically disappears within several months after a season of rainfall. To speed the removal of efflorescence, wash the surface of the wall with a cleaner specifically is designed to remove efflorescence. </p> <h5>SEALERS</h5> <ul> <li>Product may be sealed for aesthetic or cleanliness reasons but it is not required</li> <li>Use any sealer approved for concrete pavers</li> <li>Select type for desired aesthetics</li> <li>Product must be cleaned and dry before sealing</li> <li>Always read and follow sealer manufacturer’s application procedures</li> <li>Always test a small area to make sure the results are as expected</li> </ul> <h5>SNOW REMOVAL AND MAINTENANCE</h5> <p>Recommended Deicing Chemical: Sodium Chloride (NaCl) for temperatures down to +20°F (-7°C). Sodium Chloride is commonly known as rock salt. Only when necessary, Calcium Chloride (CaCl2) can be used for temperature ranges from below +20°F (-7°C) to -2°F (-19°C).</p> <p>We do not recommend using any other types of deicing chemicals. This includes: • Magnesium Chloride (MgCl2) • Calcium Magnesium Acetate (CMA) • Potassium Chloride (KCl) • Potassium Acetate (KA) • Fertilizers containing Ammonium Nitrate and Ammonium Sulfate.</p> <p>Only apply minimum amount of de-icing salts necessary to melt the snow and ice. Use sparingly to reduce damage to plants and vegetation. Remove excess salt after ice melts.</p> <p>After the winter season, thoroughly wash the paver surface to remove any excess deicing chemical remaining. </p> <p>Regular cleaning routine should include sweeping or blowing loose debris from the pavement surface, and less frequently, deep cleaning with cleaning products and/or water</p> </div> </div> <div class="footer text-right"> <img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""> </div></div><div class="page-break"></div>';
        } else if ($slug == 'permeable-pavers') {
            $pdf_html_other_pages = '<div class="page page_40"> <div class="title type4 style1 title-mb">Permeable</div> <div class="title mb-3">TYPICAL BASE THICKNESS</div> <div class="title color1 mb-2">BASE & AGGREGATE CHARTS</div> <div class="simple-page mb-6"> <p>Careful selection of base material, as described below, ensures that an installation can handle almost any amount of rainfall. Testing results of all the aggregates listed below show a void ratio of approximately 40 percent. Choosing the correct void filter is critical as well. The aggregate infiltration rates below illustrate the performance of the system.</p> </div> <table class="row mb-6"> <tr> <td class="col-6"> <div class="title style2">AGGREGATE INFILTRATION RATES</div> <table class="table1 row"> <thead> <tr> <th>Approximate Particle Size</th> <th>Permeability (k) in./hr (m/s)</th> </tr> </thead> <tbody> <tr> <td>ASTM No. 8 (2 – 10 mm)*</td> <td>1,400 - 4,000 (3 x 10- 1 to 1 x 10- 2)</td> </tr> <tr> <td>ASTM No. 9 (2 – 5 mm)</td> <td>140 - 1,400 (1 x 10- 2 to 1 x 10- 3)</td> </tr> <tr> <td>ASTM No. 10 (1 – 3 mm)</td> <td>14 - 140 (1 x 10- 3 to 1 x 10- 4)</td> </tr> <tr> <td>ASTM No. 57 (12.5 – 25 mm)*</td> <td>500 - 2,000</td> </tr> <tr> <td>ASTM No. 2 (50 – 63 mm)*</td> <td>>1,000</td> </tr> </tbody> </table> <div class="footer-text sm"> <p>Permeability ranges of joint fill aggregates for permeable pavers. <br> * Unilock recommendations</p> </div> </td> <td class="col-1">&nbsp;</td> <td class="col-6"> <div class="title style2">SETTING BED AGGREGATE</div> <table class="table1 row"> <thead> <tr> <th>Sieve Size</th> <th>Percent Passing</th> </tr> </thead> <tbody> <tr> <td>0.5" (12 mm)</td> <td>100</td> </tr> <tr> <td>0.375" (9.5 mm)</td> <td>85 – 100</td> </tr> <tr> <td>0.375" (9.5 mm)</td> <td>85 – 100</td> </tr> <tr> <td>(2.36 mm) (No. 8)</td> <td>0 – 10</td> </tr> <tr> <td>(1.16 mm) (No. 16)</td> <td>0 – 5</td> </tr> </tbody> </table> <div class="footer-text sm"> <p>Grading requirements for ASTM No. 8 bedding and joint / opening filler. <br> Setting bed aggregate can be used as joint aggregate for Eco-Optiloc™.</p> </div> </td> </tr> </table> <table class="row"> <tr> <td class="col-6"> <div class="title style2">BASE AGGREGATE</div> <table class="table1 row"> <thead> <tr> <th>SIEVE Size</th> <th>Percent Passing</th> </tr> </thead> <tbody> <tr> <td>1.5" (37.5 mm)</td> <td>100</td> </tr> <tr> <td>1" (25 mm)</td> <td>95 – 100</td> </tr> <tr> <td>0.5" (12 mm)</td> <td>25 – 60</td> </tr> <tr> <td>(4.75 mm) (No. 4)</td> <td>0 – 10</td> </tr> <tr> <td>(2.36 mm) (No. 8)</td> <td>0 – 10</td> </tr> </tbody> </table> <div class="footer-text"> <p>Grading requirements for ASTM No. 57 base.</p> </div> </td> <td class="col-1">&nbsp;</td> <td class="col-6"> <div class="title style2">SUBBASE AGGREGATE</div> <table class="table1 row"> <thead> <tr> <th>Sieve Size</th> <th>Percent Passing</th> </tr> </thead> <tbody> <tr> <td>3" (75 mm)</td> <td>100</td> </tr> <tr> <td>2.5" (63 mm)</td> <td>90 – 100</td> </tr> <tr> <td>2" (50 mm)</td> <td>35 – 70</td> </tr> <tr> <td>1.5" (37.5 mm)</td> <td>0 – 15</td> </tr> <tr> <td>1.5" (37.5 mm)</td> <td>0 – 5</td> </tr> </tbody> </table> <div class="footer-text sm"> <p>Grading requirements for ASTM No. 8 bedding and joint / opening filler. <br> Setting bed aggregate can be used as joint aggregate for Eco-Optiloc™.</p> </div> </td> </tr> </table></div><div class="page-break"></div><div class="page page_41"> <div class="title color1 mb-2">BASE THICKNESS</div> <div class="simple-page mb-6"> <p>Permeable paving is not a typical segmental pavement. Unilock recommends that a professional engineer design a site-specific plan based on available site information. Along with information provided in this brochure, Unilock offers comprehensive software solutions, and industry-experienced consultants to assist you in the design of your pavement.</p> </div> <table class="table1 row"> <thead> <tr> <th>Pavement Use</th> <th>Subbase ASTM No. 2</th> <th>Base ASTM No. 57</th> <th>Minimum Total</th> </tr> </thead> <tbody> <tr> <td>Heavy-duty industrial</td> <td>14" (355 mm)</td> <td>6" (152 mm)</td> <td>20" (559 mm)</td> </tr> <tr> <td>Municipal street</td> <td>12" (305 mm)</td> <td>6" (152 mm)</td> <td>18" (457 mm)</td> </tr> <tr> <td>Light-duty parking lot</td> <td>8" (203 mm)</td> <td>6" (152 mm)</td> <td>14" (356 mm)</td> </tr> <tr> <td>Residential driveway</td> <td>n/a</td> <td>12" (305 mm)</td> <td>12" (305 mm)</td> </tr> <tr> <td>Non-vehicular sidewalk</td> <td>n/a</td> <td>10" (254 mm)</td> <td>10" (254 mm)</td> </tr> </tbody> </table> <div class="footer-text sm"> <p>Notes: 1) All permeable pavers require a 1.5" (38 mm) setting bed of ASTM No. 8 for placement. 2) All thicknesses are after compaction. 3) Geotextiles between subgrade and ASTM No. 2 are optional and based on soil conditions. 4) Geotextiles are not required between the subbase, base or setting bed layers.</p> </div></div><div class="page-break"></div><div class="page page_42"> <div class="title type4">Base Options</div> <div class="title type7 title-mb1">PERMEABLE BASE</div> <div class="simple-page mw1 mb-4"> <h5>BASE AND BEDDING COURSE</h5> <p><b>NOTE:</b> Always use a compactor on open-graded stone to maximize consolidation.</p> </div> <div class="mb-5"><img src="' . CHILD_THEME_URI . '/img/pdf-img-permeable1.png" width="724px" height="auto" alt=""></div> <div class="footer-text"> <p>NOTE: The installation practices listed are based on residential applications. <br> Not all base designs may be suitable for this product. Contact your representative for more information.</p> </div></div><div class="page-break"></div><div class="page page_43"> <div class="title type7 title-mb1">PERMEABLE INSTALLATIONS</div> <div class="simple-page mb-3"> <p>Similar to the non-permeable paver systems structural component, permeable paver installations offer secondary purpose for capturing and detaining rainwater. Common uses can range from sidewalk and plaza areas, to heavy-duty parking lots and roadways and include various base depths as shown in the two details below.</p> </div> <div class="mb-3"><img src="' . CHILD_THEME_URI . '/img/pdf-img-permeable2.png" width="724px" height="auto" alt=""></div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-permeable3.png" width="724px" height="auto" alt=""></div><div class="page-break"></div><div class="page page_44"> <div class="title type4">Jointing Material and Joint Stabilization</div> <div class="simple-page mb-2"> <p>All joint material in permeable installations must be open graded and allow water to flow through the joints. Keep joints topped up with material approximately 1/8” below the paver edge for optimal system functionality.</p> </div> <table class="row"> <tr> <td class="col-6 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">GOOD OPTION</div> <div class="text">Open Graded, Crushed, Angular Stone; <br> ASTM No 8 or ASTM No 9</div> </div> </td> <td class="col-6 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">BEST OPTION</div> <div class="text">ASTM No 9 Granite Chip</div> </div> </td> <td class="col-2">&nbsp;</td> </tr> </table></div><div class="page-break"></div><div class="page page_45"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <h5>PPE</h5> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn.</p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur. Because of its size and weight, always use a vacuum lifter device to place extra large units.</p> <h5>EDGE RESTRAINT</h5> <p>Always install an edge restraint around the perimeter of any paver installation not restrained by building structures. Spike-in edge restraints come in plastic and metal and work well for most applications. A concrete curb or a sub-surface concrete wedge can also be installed to retain the edge.</p> <h5>PAVER COMPACTION</h5> <p>Always use a protective polymer pad on the bottom of your compactor when doing the final compaction of the pavers. An alternative is to use a rubber-roller compactor for the final compaction.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for pavers may be used for color restoration or general cleaning. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected.</p> <p><b>NOTE:</b> Efflorescence is a naturally occurring calcium salt that can sometimes appear on the surface of concrete and clay products. It does not affect the structural integrity of concrete. Efflorescence typically disappears within several months after a season of rainfall. To speed the removal of efflorescence, wash the surface with a paver cleaner specifically is designed to remove efflorescence.</p> <h5>SEALERS</h5> <ul> <li>Product may be sealed for aesthetic or cleanliness reasons butitis not required</li> <li>Use any sealer approved for concrete pavers</li> <li>Select type for desired aesthetics</li> <li>Product must be cleaned and dry before sealing</li> <li>Always read and follow sealer manufacturer’s application procedures</li> <li>Always test a small area to make sure the results are as expected</li> </ul> <h5>SNOW REMOVAL AND MAINTENANCE</h5> <p>Recommended Deicing Chemical: Sodium Chloride (NaCl)fortemperatures down to +20°F (-7°C). Sodium Chloride is commonly known as rock salt. Only when necessary, Calcium Chloride (CaCl2) can be used fortemperature ranges from below +20°F (-7°C)to -2°F (-19°C).</p> <p>We do not recommend using any other types of deicing chemicals. This includes: • Magnesium Chloride (MgCl2) • Calcium Magnesium Acetate (CMA) • Potassium Chloride (KCl) • Potassium Acetate (KA) • Fertilizers containing Ammonium Nitrate and Ammonium Sulfate.</p> <p>Only apply minimum amount of de-icing salts necessary to melt the snow and ice. Use sparingly to reduce damage to plants and vegetation. Remove excess salt after ice melts.</p> <p>After the winter season,thoroughly wash the paver surface to remove any excess deicing chemicalremaining.</p> <p>Regular cleaning routine should include sweeping or blowing loose debris from the pavement surface, and less frequently, deep cleaning with cleaning products and/or water</p> </div></div><div class="page-break"></div>';
        } elseif ($slug == 'steps') {
            $pdf_html_other_pages = '<div class="page page_46"> <div class="title type4 style1">Steps</div> <div class="title">APPLICATIONS</div> <div class="simple-page"> <p>A variety of steps are available from Unilock, ranging from large solid steps or segmental units that work together to create the step.</p> </div></div><div class="page-break"></div><div class="page page_47"> <div class="title type4">Installation Options</div> <div class="title type7 mb-1">SEGMENTAL UNITS CREATING STEPS</div> <div class="simple-page custom-order-list maw1"> <p>Install pillar cap using concrete adhesive. Natural Stone can be installed with concrete adhesive or mortar.</p> <p>Building steps is like building a set of miniature single-course walls, one behind the other.</p> <ol> <li>Universal Base Units should be used to construct steps quickly and securely.</li> <li>Steps are constructed using wall blocks with the top keys removed. Position and glue an entire row of blocks to the Universal Base Unit. The adhesive under the blocks will facilitate the sliding forward.</li> <li>Complete each step by gluing the coping to the top of the units. An overhang of 1.5” (3.8 cm)–2” (5 cm) is recommended.</li> <li>For each consecutive step, install anotherrow of Universal Base Units flush to the top of the blocks of the previous step.</li> <li>Exposed step ends should utilize corner units and closed end coping and attach a decorative panel if applicable.</li> </ol> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-steps1.png" width="724px" height="auto" alt=""></div><div class="page-break"></div><div class="page page_48"> <div class="title type7 title-mb2">LARGE SOLID STEP</div> <img src="' . CHILD_THEME_URI . '/img/pdf-img-steps2.png" width="724px" height="auto" alt=""></div><div class="page-break"></div><div class="page page_49"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <h5>PPE</h5> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn.</p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur. Because of its size and weight, always use a vacuum lifter device to place extra large units.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for pavers may be used for color restoration or general cleaning. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected.</p> <p><b>NOTE:</b> Efflorescence is a naturally occurring calcium salt that can sometimes appear on the surface of concrete and clay products. It does not affect the structural integrity of concrete. Efflorescence typically disappears within several months after a season of rainfall. To speed the removal of efflorescence, wash the surface with a paver cleaner specifically is designed to remove efflorescence.</p> <h5>SEALERS</h5> <ul> <li>Product may be sealed for aesthetic or cleanliness reasons butitis not required</li> <li>Use any sealer approved for concrete pavers</li> <li>Select type for desired aesthetics</li> <li>Product must be cleaned and dry before sealing</li> <li>Always read and follow sealer manufacturer’s application procedures</li> <li>Always test a small area to make sure the results are as expected</li> </ul> <h5>SNOW REMOVAL AND MAINTENANCE</h5> <p>Recommended Deicing Chemical: Sodium Chloride (NaCl)fortemperatures down to +20°F (-7°C). Sodium Chloride is commonly known as rock salt. Only when necessary, Calcium Chloride (CaCl2) can be used fortemperature ranges from below +20°F (-7°C)to -2°F (-19°C).</p> <p>We do not recommend using any other types of deicing chemicals. This includes: • Magnesium Chloride (MgCl2) • Calcium Magnesium Acetate (CMA) • Potassium Chloride (KCl) • Potassium Acetate (KA) • Fertilizers containing Ammonium Nitrate and Ammonium Sulfate.</p> <p>Only apply minimum amount of de-icing salts necessary to melt the snow and ice. Use sparingly to reduce damage to plants and vegetation. Remove excess salt after ice melts.</p> <p>After the winter season,thoroughly wash the paver surface to remove any excess deicing chemicalremaining.</p> <p>Regular cleaning routine should include sweeping or blowing loose debris from the pavement surface, and less frequently, deep cleaning with cleaning products and/or water</p> </div></div>';
        } else {
            $pdf_html_other_pages = '<div class="page page_3"> <div class="title type4">Base Options</div> <div class="row"> <div class="col-7"> <div class="title type5">STANDARD GRANULAR BASE</div> <div class="title">BASE AND BEDDING COURSE</div> <div class="simple-page type2 mw1"> <p>NOTE: Always use a compactor to maximize consolidation and to remove any undetected voids. Compact to 95% SPD (Standard Proctor Density)</p> </div> <div class="img-wrapp1 mb-2"><img src="' . CHILD_THEME_URI . '/img/granular-base.png" alt=""></div> </div> </div> <div class="footer"> <table class="row"> <tr> <td class="col-9"> <div class="footer-text"> <p>NOTE: The installation practices listed are based on residential applications. </p> <p>Not all base designs may be suitable for this product. Contact your representative for more information. </p> </div> </td> <td class="col-3 text-right"><img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""></td> </tr> </table> </div></div><div class="page-break"></div><div class="page page_4"> <div class="title type4">Base Options</div> <div class="row"> <div class="col-7"> <div class="title type5">PERMEABLE BASE</div> <div class="title">BASE AND BEDDING COURSE</div> <div class="simple-page type2 mw1"> <p>NOTE: Always use a compactor on open-graded stone to maximize consolidation and to remove any undetected voids.</p> </div> <div class="img-wrapp1 mb-2"><img src="' . CHILD_THEME_URI . '/img/permeable-base.png" alt=""></div> </div> </div> <div class="footer"> <table class="row"> <tr> <td class="col-9"> <div class="footer-text"> <p>NOTE: The installation practices listed are based on residential applications. </p> <p>Not all base designs may be suitable for this product. Contact your representative for more information. </p> </div> </td> <td class="col-3 text-right"><img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""></td> </tr> </table> </div></div><div class="page-break"></div><div class="page page_5"> <div class="title type4">Base Options</div> <div class="row"> <div class="col-7"> <div class="title type5">RIGID FOAM BASE</div> <div class="title">RESIDENTIAL PATIO APPLICATION ONLY</div> <div class="simple-page type2 mw1"> <p>NOTE: Always use a compactor to maximize native soil consolidation and to remove any undetected voids. </p> </div> <div class="img-wrapp1 mb-2"><img src="' . CHILD_THEME_URI . '/img/rigid-base.png" alt=""></div> </div> </div> <div class="footer"> <table class="row"> <tr> <td class="col-9"> <div class="footer-text"> <p>NOTE: The installation practices listed are based on residential applications. </p> <p>Not all base designs may be suitable for this product. Contact your representative for more information. </p> </div> </td> <td class="col-3 text-right"><img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""></td> </tr> </table> </div></div><div class="page-break"></div><div class="page page_6"> <div class="section"> <div class="row"> <div class="col-10"> <div class="title type4">Typical Commercial Base Designs</div> <div class="simple-page type2 mb-2"> <p>Commercial base details must be specific to site conditions and load capacity. The details below are examples. Contact your Unilock Representative for more information. </p> </div> <div class="title">PAVERS ON GRANULAR BASE</div> <div class="img-wrapp2 mb-3"><img src="' . CHILD_THEME_URI . '/img/pavers-granular-base.png" alt=""></div> <div class="title">PAVER ON SAND OVER CONCRETE</div> <div class="img-wrapp2 mb-3"><img src="' . CHILD_THEME_URI . '/img/pavers-sand-over-base.png" alt=""></div> <div class="title">PAVERS ON BITUMEN</div> <div class="img-wrapp2"><img src="' . CHILD_THEME_URI . '/img/pavers-bitumen-base.png" alt=""></div> </div> </div> </div> <div class="footer"> <table class="row"> <tr> <td class="col-9">&nbsp;</td> <td class="col-3"> <div class="footer-text text-right"> <div class="mb-2"> <p>NOTE: Not all base designs may be suitable for this product. Contact your representative for more information. </p> </div> <img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""> </div> </td> </tr> </table> </div></div><div class="page-break"></div><div class="page page_7"> <div class="section"> <div class="title type4">Base Options <b>Continued</b></div> <div class="simple-page"> <h5>SPECIAL NOTE: CONCRETE DIRECT OVERLAY</h5> <p>In some areas of the country and in some applications pavers are very successfully placed directly over concrete. Concrete as a base is in itself quite strong, but it can affect the structural integrity of the paver particularly in vehicular applications, where the concrete below is sub-par. The following considerations must be taken into account to insure that the concrete below the surface is ideal:</p> <ol> <li>Concrete integrity – concrete must be in good condition, and not crumbling.</li> <li>Drainage slope – concrete below must be sloped away from all buildings and structures. </li> <li>Drainage holes – In lowest areas of the concrete, drill 1-2” holes in concrete (on 12” centers) and fill holes with ¼” chip (ASTM No. 8).</li> <li>Base drainage - the area below the concrete must not be subject to frost movement.</li> <li>Surface - surface must be totally smooth and flat equivalent to the desired finished surface.</li> <li>Waterproofing - may be required when installing pavers over concrete where there is a basement or cold cellar below. Install an impervious rubber membrane over the surface prior to installing any pavers over the surface.</li> <li>Jointing Sand - Use an impervious polymeric sand when installing over concrete.</li> </ol> </div> </div> <div class="divider"></div> <div class="section mb-2"> <div class="mb-2"> <div class="title type4">Jointing Material and Joint Stabilization</div> <div class="simple-page mb-1"> <p>All sands must meet ASTM C144 or C33 Specifications. For best appearance and optimal performance, keep jointing materials approximately 1/8” below the chamfer (bevel edge) of the paver.</p> </div> <table class="row"> <tr> <td class="col-6 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">GOOD OPTION</div> <div class="text">Ordinary sharp jointing sand in accordance with ASTM C144 or C33. (Common name: Concrete Sand)</div> </div> </td> <td class="col-6 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">BEST OPTION</div> <div class="text">Any polymeric sand or ordinary concrete sand stabilized by a water-based or solvent-based joint sand stabilizer sealer. Always follow manufacturer’s application specifications and requirements.</div> </div> </td> <td class="col-2">&nbsp;</td> </tr> </table> </div> <div class="title">COLOR OPTIONS</div> <table class="row"> <tr> <td class="col-5"> <div class="box"> <div class="box-inner2"> <table class="row"> <tr> <td class="col-4"> <div class="color-box"> <img src="' . CHILD_THEME_URI . '/img/img-color1.jpg" alt=""> <div class="text1">Black</div> </div> </td> <td class="col-4"> <div class="color-box"> <img src="' . CHILD_THEME_URI . '/img/img-color2.jpg" alt=""> <div class="text1">Tan</div> </div> </td> <td class="col-4"> <div class="color-box"> <img src="' . CHILD_THEME_URI . '/img/img-color3.jpg" alt=""> <div class="text1">Grey</div> </div> </td> </tr> </table> </div> </div> </td> <td class="col-7">&nbsp;</td> </tr> </table> </div> <div class="divider"></div> <div class="section"> <div class="title type4">Complimentary Products</div> <table class="row"> <tr> <td class="col-5 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">BORDER/ACCENT PAVER PAIRINGS</div> <table class="row"> <tr> <td class="col-6"> <div class="product-box"> <img src="' . CHILD_THEME_URI . '/img/img-product1.jpg" alt=""> <div class="text1">Copthorne®</div> </div> </td> <td class="col-6"> <div class="product-box"> <img src="' . CHILD_THEME_URI . '/img/img-product2.jpg" alt=""> <div class="text1">Copthorne®</div> </div> </td> </tr> </table> </div> </td> <td class="col-5 box border-top-none"> <div class="border-top-half"></div> <div class="box-inner2"> <div class="title">WALL PAIRINGS</div> <table class="row"> <tr> <td class="col-6"> <div class="product-box"> <img src="' . CHILD_THEME_URI . '/img/img-product3.jpg" alt=""> <div class="text1">Rivercrest® Wall</div> </div> </td> <td class="col-6"> <div class="product-box"> <img src="' . CHILD_THEME_URI . '/img/img-product4.jpg" alt=""> <div class="text1">Lineo® Dimensional Stone</div> </div> </td> </tr> </table> </div> </td> <td class="col-2">&nbsp;</td> </tr> </table> </div> <div class="footer text-right"> <img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""> </div></div><div class="page-break"></div><div class="page page_8"> <div class="section"> <div class="title type4">Safe Handling and Maintenance</div> <div class="simple-page type1"> <p>Proper personal protective equipment must be worn when handing hardscape materials. Safety shoes, hearing protection and breathing protection. When cutting the product approved NIOSH N95 respirator mask should be worn. </p> <h5>HANDLING</h5> <p>Do not bang edges together as chipping may occur. Because if its size and weight, always use a vacuum lifter device to place Richcliff XL units.</p> <h5>EDGE RESTRAINT</h5> <p>Always install an edge restraint around the perimeter of any paver installation not restrained by building structures. Spike-in edge restraints come in plastic and metal and work well for most applications. A concrete curb or a sub-surface concrete wedge can also be installed to retain the edge.</p> <h5>PAVER COMPACTION</h5> <p>Always use a protective polymer pad on the bottom of your compactor when doing the final compaction of the pavers. An alternative is to use a rubber-roller compactor for the final compaction.</p> <h5>CLEANERS</h5> <p>Any cleaner specifically designed for pavers may be used for color restoration or general cleaning. Follow manufacturer’s dilution rates and application procedures. Always test a small area to make sure the results are as expected. </p> <h5>SEALERS</h5> <ul> <li>Product may be sealed for aesthetic or cleanliness reasons but it is not required</li> <li>Use any sealer approved for concrete pavers</li> <li>Select type for desired aesthetics</li> <li>Product must be cleaned before sealing</li> <li>Always read and follow manufacturer’s application procedures</li> <li>Always test a small area to make sure the results are as expected</li> </ul> <h5>PAVER MAINTENANCE TIPS</h5> <ul> <li>Equip plow scrapers and blades with shoes or high-density plastic blades to reduce the risk of damaging paver joints and the surface</li> <li>Only apply min. amount of de-icing salts necessary to melt the snow and ice. Remove excess salt after ice melts</li> <li>Regular cleaning routine should include sweeping or blowing loose debris from the pavement surface, and less frequently, deep cleaning with cleaning products and/or water</li> <li>Unilock pavers do not require sealing, however, some people choose to do so for aesthetic purposes</li> <li>Efflorescence is a naturally occurring calcium salt that can sometimes appear on the surface of concrete and clay products. Efflorescence does not affect the structural integrity of concrete; it is a purely aesthetic issue that typically disappears with no further intervention after a season of rainfall. If desired, the process can be accelerated by washing the surface with an Efflorescence Remover.</li> </ul> </div> </div> <div class="footer text-right"> <img src="' . CHILD_THEME_URI . '/img/pdf-logo-unilock.jpg" alt=""> </div></div>';
        }
        return $pdf_html_other_pages;
    }

    public function get_variations_opportunities($variation_id, $array_uniq = [])
    {
        $prod_application_keys = [
            'Appl_ResidentCommercialPed' => ['Proj_Walkway', 'Proj_Patio', 'Proj_Terrace', 'Proj_PoolDeck', 'Proj_Entryway', 'Proj_Courtyard', 'Proj_RoofDeck', 'Proj_Plaza', 'Proj_Sidewalk', 'Proj_Park'],

            'Appl_LightDutyVehicular' => ['Proj_ResidentialDriveway', 'Proj_CommercialPedestrian', 'Proj_Roadway', 'Proj_Streetscape', 'Proj_VehicularEntranceway', 'Proj_ParkingLot'],

            'Appl_CommercialVehicular' => ['Proj_Streetscape', 'Proj_VehicularEntranceway', 'Proj_ParkingLot'],

            'Appl_HeavyDutyVechicular' => ['Proj_TruckTraffikcedRoadwy', 'Proj_IndustrialParkingLot', 'Proj_IndustrialLoadingArea', 'Proj_SustainableDesign', 'Proj_Stormwaterretention', 'Proj_LowImpactDesign', 'Proj_RainwaterHarvesting', 'Proj_IncreasedLotRatio', 'App_NonstructuralWall', 'Proj_RetainingWallunder3', 'Proj_SeatWall'],

            'Appl_Permeable' => ['Proj_SustainableDesign', 'Proj_Stormwaterretention', 'Proj_LowImpactDesign', 'Proj_RainwaterHarvesting', 'Proj_IncreasedLotRatio'],

            'Appl_NonstructuralWall' => ['Proj_RetainingWallunder3', 'Proj_GardenWall', 'Proj_WaterFeature', 'Proj_OutdoorKitchen', 'Proj_FireFeature'],

            'Appl_PedestalorOverlay' => ['Proj_DeckOverlay'],

            'Appl_Steps' => [],

            'Appl_Coping' => ['Proj_PoolCoping', 'Proj_WallCoping', 'Proj_StepTread', 'Proj_Step', 'Proj_RetainingWallsover3', 'Proj_CommunityEntranceWall', 'Proj_Amphitheatre'],

            'Appl_StructuralWall' => ['Proj_RetainingWallsover3', 'Proj_CommunityEntranceWall', 'Proj_Amphitheatre']

        ];

        foreach ($prod_application_keys as $appl_name => $opportunities_arr) {
            $appl_name_true = get_post_meta($variation_id, $appl_name);


            if (isset($appl_name_true[0]) && $appl_name_true[0] === 'true') {
                $appl_name_filter = explode('_', $appl_name);
                $appl_name_title = camelCaseToWords($appl_name_filter[1]);


                if (!empty($opportunities_arr)) {
                    $opport_res = '';
                    foreach ($opportunities_arr as $opportunity) {
                        $opportunity_true = get_post_meta($variation_id, $opportunity);

                        if ($opportunity_true[0] === 'true') {
                            $opportunity_title = explode('_', $opportunity);
                            $opport_res .= '<li>' . camelCaseToWords($opportunity_title[1]) . '</li>';
                        }
                    }
                    if (!empty($opport_res)) $array_uniq[$appl_name_title] = $opport_res;
                }
            }
        }
        return $array_uniq;
    }

    public function create_cash_of_product_main_block()
    {
        check_ajax_referer('ajax_request_nonce');
        if (!empty($_POST['product_block_content'])) {
            $url = $_SERVER['HTTP_REFERER'];
            $post_id = url_to_postid($url);
            if (!empty($post_id)) {
                $post_url = $this->CASH_DIR_FILES . '/' . $post_id . '.php';
                if (!file_exists($post_url)) {
                    file_put_contents($post_url, base64_decode($_POST['product_block_content']));
                    echo json_encode(array('success' => true, 'message' => 'File Created'));
                } else {
                    echo json_encode(array('success' => false, 'message' => 'File Exist'));
                }
                die();
            }
        }
        echo json_encode(array('success' => false, 'message' => __('Product not found!', 'unilock')));
        die();
    }

    public function remove_cash_product_folder($src = '')
    {
        if (empty($src)) $src = $this->CASH_DIR_FILES;
        array_map('unlink', glob($src . '/*.php'));
    }

    public function load()
    {
        add_action('wp_ajax_create_cash_of_product_main_block', array($this, 'create_cash_of_product_main_block'));
        add_action('wp_ajax_nopriv_create_cash_of_product_main_block', array($this, 'create_cash_of_product_main_block'));

        add_action('wp_ajax_prod_filters', array($this, 'product_ajax'));
        add_action('wp_ajax_nopriv_prod_filters', array($this, 'product_ajax'));
        /* DOWNLOADS PROD PDF */
        add_action('wp_ajax_download_prod_pdf', array($this, 'download_product_pdf_ajax'));
        add_action('wp_ajax_nopriv_download_prod_pdf', array($this, 'download_product_pdf_ajax'));

        /* LAYING PATTERNS */
        add_action('wp_ajax_laying_patterns_render', array($this, 'laying_patterns_ajax'));
        add_action('wp_ajax_nopriv_laying_patterns_render', array($this, 'laying_patterns_ajax'));
    }
}

$ClassProduct = new Products();
$ClassProduct->load();
