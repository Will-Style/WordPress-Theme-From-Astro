
<div class="sidebar">
    <nav class="sidebar__nav">
        <div class="grid">
            <div class="col-12 xs:col-6 lg:col-12">
                <h3 class="sidebar__title"><a href="/blog/" class="sidebar__title__link">Blog</a></h3>
                <ul class="sidebar__ul">
                <?php $cats = get_terms(['taxonomy' => 'blog-cat','orderby' => 'term_order','order' => 'ASC']);?>                            
                    <?php foreach ($cats as $key => $term) :?>
                        <li class="sidebar__list"><a href="<?php echo get_term_link($term,'category');?>" class="sidebar__link"><?php echo $term->name;?></a></li>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
    </nav>
    <h3 class="sidebar__title ">Archive</h3>
    <select class="sidebar__archive" data-archive-select>
        <option value="">アーカイブを選択</option>
        <?php wp_get_archives( 'type=monthly&format=option&post_type=' . get_post_type() ); ?>
    </select>
</div>