<?php $lang = $attributes["code-lang"]; ?>
<div class="blog__highlight" data-highlight>
    <?php if (!empty($attributes['code-title'])): ?>
        <h4 class="blog__highlight__title"><?php echo esc_html($attributes['code-title']); ?></h4>
    <?php endif; ?>
    <pre><code class="language-<?php echo $lang; ?>" data-scrollbars><?php echo $attributes["code-body"]; ?></code></pre>
    <?php if (!empty($attributes['code-notice'])): ?>
        <p class="blog__highlight__notice"><?php echo esc_html($attributes['code-notice']); ?></p>
    <?php endif; ?>
</div>