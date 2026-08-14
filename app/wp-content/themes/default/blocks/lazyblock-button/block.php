<div class="blog__cases__qa mt-30 xl:mt-60">
    <?php if(!empty($attributes["question"])):?>
    <h3 class="cases__qa__title" data-scroll-trigger="top"><?php echo esc_html($attributes["question"]);?></h3>
    <?php endif;?>
    <?php $answers = $attributes['answers'];?>
    <?php if(!empty($answers)):?>
    <?php foreach ($answers as $answer):?>
    <dl class="cases__qa__dl">
        <div class="cases__qa__row">
            <dt class="cases__qa__q" data-scroll-trigger="top">
                <div class="cases__qa__avatar">
                    <div class="cases__qa__avatar__img">
                        <?php if(!empty($answer["icon"]["url"])):?>
                        <img src="<?php echo $answer["icon"]["url"]; ?>" alt="" class="c-card__image">
                        <?php endif;?>
                    </div>
                    <div class="cases__qa__avatar__name"><?php echo esc_html($answer['name']);?></div>
                </div>
            </dt>
            <dd class="cases__qa__a" data-scroll-trigger="top">
                <?php echo $answer['body'];?>
            </dd>
        </div>
    </dl>
    <?php endforeach;?>
    <?php endif;?>
</div> 