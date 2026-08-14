<div class="blog__slider" data-blog-slider>
    <div class="splide" data-main-slider>
        <div class="splide__track">
            <div class="splide__list">
                <?php $slides = $attributes['slide']; ?>
                <?php if (!empty($slides)): ?>
                    <?php foreach ($slides as $slide): ?>
                        <div class="splide__slide">
                            <?php if (!empty($slide["slide-img"]["url"])): ?>
                                <img src="<?php echo $slide["slide-img"]["url"]; ?>" alt="">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="blog__slider-progress">
            <div class="blog__slider-progress-bar"></div>
        </div>
    </div>
</div>