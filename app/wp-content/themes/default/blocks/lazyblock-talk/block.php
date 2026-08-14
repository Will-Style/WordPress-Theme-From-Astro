<div class="blog__talk">
    <?php if (!empty($attributes["talk-question"])): ?>
        <h2 class="blog__talk__title"><?php echo esc_html($attributes["talk-question"]); ?></h2>
    <?php endif; ?>
    <?php $answers = $attributes['talk-answers']; ?>
    <?php if (!empty($answers)): ?>
        <?php foreach ($answers as $answer): ?>
            <div class="blog__talk__content">
                <div class="blog__talk__avatar">
                    <figure class="blog__talk__avatar-figure">
                        <?php if (!empty($answer["talk-icon"]["url"])): ?>
                            <img src="<?php echo $answer["talk-icon"]["url"]; ?>" alt="" class="blog__talk__avatar-image">
                        <?php endif; ?>
                    </figure>
                </div>
                <div class="blog__talk__body">
                    <div class="blog__talk__avatar-name"><?php echo esc_html($answer['talk-name']); ?></div>
                    <?php echo $answer['talk-body']; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>