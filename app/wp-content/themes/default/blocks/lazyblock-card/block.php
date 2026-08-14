<div class="blog__card<?php if($attributes["border"]): echo " :border"; endif;?>">
    <?php if(!empty($attributes["title"])):?><h2 class="content-card__title"><?php echo esc_html($attributes["title"]);?></h2><?php endif;?>
    <div class="content-card__body">
        <InnerBlocks />
    </div>
</div>