<?php defined('ABSPATH') or die; ?>

<?php if ($parameterMissing): ?>

<p><?=esc_html__('Nonce and/or id parameter is missing.', 'another-simple-image-optimizer')?></p>

<?php else: ?>

    <?php if (!$meta || !$mightBeImage): ?>

<p><?=esc_html__('Couldn\'t optimize file.', 'another-simple-image-optimizer')?></p>
<p><?=esc_html__('ID:', 'another-simple-image-optimizer').' '.esc_html($id)?></p>

    <?php else: ?>

<p><?=esc_html__('Optimized file.', 'another-simple-image-optimizer')?></p>
<p><?=esc_html__('ID:', 'another-simple-image-optimizer')?> <?=esc_html($id)?></p>
<p><?=esc_html__('File size:', 'another-simple-image-optimizer')?> <?=esc_html($fileSizeStr)?></p>
<p><a href="<?=esc_url($url)?>"><?=esc_html__('Back to file', 'another-simple-image-optimizer')?></a></p>

    <?php endif ?>

<?php endif ?>

<p><a href="<?=esc_url(admin_url('upload.php'))?>"><?=esc_html__('Back to media library', 'another-simple-image-optimizer')?></a></p>
