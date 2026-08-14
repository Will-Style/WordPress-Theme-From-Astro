<?php $members = $attributes["talkmembers"]; ?>
<div class="blog__talkmember">
    <h2 class="blog__talkmember__title">(Talk member)</h2>
    <div class="blog__talkmember__wrapper">
        <ul class="blog__talkmember__ul">
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): ?>
                    <li class="blog__talkmember__li">
                        <figure class="blog__talkmember__figure">
                            <?php if (!empty($member["talkmembers-icon"]["url"])): ?>
                                <img src="<?php echo $member["talkmembers-icon"]["url"]; ?>" class="blog__talkmember__image" alt="" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </figure>
                        <div class="blog__talkmember__content">
                            <?php if (!empty($member['talkmembers-position'])): ?>
                                <div class="blog__talkmember__position"><?php echo nl2br(esc_html($member['talkmembers-position'])); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($member['talkmembers-name'])): ?>
                                <div class="blog__talkmember__name"><?php echo esc_html($member['talkmembers-name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>