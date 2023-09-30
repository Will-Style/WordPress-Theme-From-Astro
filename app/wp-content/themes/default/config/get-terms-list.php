<?php 


function order_the_terms( $id , $taxonomy = 'category',$class = ""){
	$html = "";
	$args = array('orderby' => 'term_order', 'order' => 'ASC');
	$terms = wp_get_object_terms( $id, $taxonomy, $args);
    $class = ' class="' . $class . '"';
	if(!empty($terms)){
  		if(!is_wp_error( $terms )){
			foreach($terms as $term){
				
				$html .= '<a href="' . get_category_link($term->term_id) . '"'.$class.'>' . esc_html( $term->name )  . '</a>';
				
			}
		}
	}
	return $html;
}