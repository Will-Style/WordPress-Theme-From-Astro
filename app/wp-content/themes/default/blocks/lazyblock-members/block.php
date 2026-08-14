<?php $members = $attributes["members"];?>
<div class="cases__member">
    <h4 class="cases__member__title">(Talk member)</h4>
    <div class="cases__member__wrapper">
        <?php if(!empty($members)):?>
        <?php foreach ($members as $member):?>
        <div class="cases__member__card">
            <div class="cases__member__card__content">
                <?php if(!empty($member['position'])):?>
                    <div class="cases__member__card__position"><?php echo nl2br(esc_html($member['position']));?></div>
                <?php endif;?>
                <?php if(!empty($member['name'])):?>
                <h3 class="cases__member__card__name"><?php echo esc_html($member['name']);?></h3>
                <?php endif;?>
            </div>
            <?php if(!empty($member["icon"]["url"])):?><figure class="cases__member__card__figure"><img src="<?php echo $member["icon"]["url"]; ?>" alt="" class="c-card__image"></figure><?php endif;?>
        </div>
        <?php endforeach;?>
        <?php endif;?>
    </div>
</div>