<div class="blog__box">
    <?php if(!empty($attributes['title'])):?>
    <h2 class="blog__box__title"><?php echo nl2br(esc_html($attributes['title'])); ?></h2>
    <?php endif;?>
    <div class="blog__box__body"><InnerBlocks /></div>
</div>