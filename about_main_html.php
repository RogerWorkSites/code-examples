<?php if (!defined('ABSPATH')) exit;
$args = wp_parse_args(
  $args,
  array(
    'title'    => '',
    'subtitle' => '',
    'content'  => '',
    'image'    => '',
  )
); ?>

<section class="about-main" style="background: url(<?php echo esc_html($args['image']); ?>) bottom right no-repeat; background-size: 100% auto;">
  <div class="spacer-sm"></div>
  <div class="container-fluid">
    <div class="row justify-content-center">

      <div class="col-12 col-xl-10 col-ml-8">
        <?php if ($args['subtitle']) : ?>
          <div class="page-title text-center"><?php echo esc_html($args['subtitle']); ?></div>
        <?php endif; ?>
        <?php if ($args['title']) : ?>
          <h2 class="h2 text-center color-light-blue"><?php echo esc_html($args['title']); ?></h2>
        <?php endif; ?>
        <?php if ($args['content']) : ?>
          <div class="adout-text"><?php echo $args['content'] ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="spacer-ml"></div>
</section>