<?php defined('ABSPATH') or die; ?>

<h2><?=esc_html__('Available image optimizers', 'another-simple-image-optimizer')?></h2>
<p><?=esc_html__('If no optimizer in the list below is checked, optimizing images won\'t work.', 'another-simple-image-optimizer')?>
<br />
<?=esc_html__('At least jpegoptim and optipng should be installed.', 'another-simple-image-optimizer')?></p>
<ul>
<?php foreach ($availableCommands as $cmd => $active): ?>
    <li><input type="checkbox" disabled<?=$active ? ' checked' : ''?>/> <?=esc_html($cmd)?></li>
<?php endforeach ?>
</ul>
