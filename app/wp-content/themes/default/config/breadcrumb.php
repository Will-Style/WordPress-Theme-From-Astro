<?php 

function breadcrumb($echo=true,$args = array()){
	global $post;
	$str ='';
	$defaults = array(
		'class' => "",
		'home' => "Home",
		'search' => "で検索した結果",
		'tag' => "Tag",
		'author' => "投稿者",
		'notfound' => "404 Not found",
		'separator' => ''
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	if(!is_home()&&!is_admin()) { 
	//!is_admin は管理ページ以外という条件分岐
		$str.= '<div class="c-breadcrumb">';
		$str.= '<ol class="c-breadcrumb">';
		$str.= '<li class="c-breadcrumb-item"><a href="'. home_url() .'/"><span>'. $home .'</span></a></li>';
		$my_taxonomy = get_query_var('taxonomy');  //[taxonomy] の値（タクソノミーのスラッグ）
		$cpt = get_query_var('post_type');   //[post_type] の値（投稿タイプ名）
		if($my_taxonomy &&  is_tax($my_taxonomy)){
			//カスタム分類のページ
			$my_tax = get_queried_object();  //print_r($my_tax);
			$post_types = get_taxonomy( $my_taxonomy )->object_type;
			$cpt = $post_types[0];  //カスタム分類名からカスタム投稿名を取得。
			$str.='<li class="c-breadcrumb-item"><a href="' .get_post_type_archive_link($cpt).'"><span>'. get_post_type_object($cpt)->label.'</span></a></li>';  //カスタム投稿のアーカイブへのリンクを出力
		if($my_tax -> parent != 0) {  
			//親があればそれらを取得して表示
			$ancestors = array_reverse(get_ancestors( $my_tax -> term_id, $my_tax->taxonomy ));
			foreach($ancestors as $ancestor){
				$str.='<li class="c-breadcrumb-item"><a href="'. get_term_link($ancestor, $my_tax->taxonomy) .'"><span>'. get_term($ancestor, $my_tax->taxonomy)->name .'</span></a></li>';
			}
		}
	$str.='<li class="c-breadcrumb-item"><span>'. $my_tax -> name . '</span></li>'; 

	}elseif(is_category()) {  
	//カテゴリーのアーカイブページ
		$cat = get_queried_object();
		if($cat -> parent != 0){
			$ancestors = array_reverse(get_ancestors( $cat -> cat_ID, 'category' ));
			foreach($ancestors as $ancestor){
				$str.='<li class="c-breadcrumb-item"><a href="'. get_category_link($ancestor) .'"><span>'. get_cat_name($ancestor) .'</span></a></li>'; 
			}
		}
		$str.='<li class="c-breadcrumb-item"><span>'. $cat -> name . '</span></li>'; 
	}elseif(is_post_type_archive()) {  //カスタム投稿のアーカイブページ
		$cpt = get_query_var('post_type');
		$str.='<li class="c-breadcrumb-item"><span>'. get_post_type_object($cpt)->label . '</span></li>'; 
	}elseif($cpt && is_singular($cpt)){  //カスタム投稿の個別記事ページ
		$taxes = get_object_taxonomies( $cpt  );
		$mytax = $taxes[0];
        if(get_post_type_object($cpt)){
		    $str.='<li class="c-breadcrumb-item"><a href="' .get_post_type_archive_link($cpt).'"><span>'. get_post_type_object($cpt)->label.'</span></a></li>';  //カスタム投稿のアーカイブへのリンクを出力
        }
		$taxes = get_the_terms($post->ID, $mytax); 
		if($taxes){
			$tax = $taxes[0];  //print_r($tax);
			if($tax -> parent != 0){
				$ancestors = array_reverse(get_ancestors( $tax -> term_id, $mytax ));
				foreach($ancestors as $ancestor){
					$str.='<li class="c-breadcrumb-item"><a href="'. get_term_link($ancestor, $mytax).'"><span>'. get_term($ancestor, $mytax)->name . '</span></a></li>';            
				}
			}
			$str.='<li class="c-breadcrumb-item"><a href="'. get_term_link($tax, $mytax).'"><span>'. $tax -> name . '</span></a></li>';  	
		}
		$str.= '<li class="c-breadcrumb-item"><span>'. $post -> post_title .'</span></li>';
	}elseif(is_single()){  //個別記事ページ
		$categories = get_the_category($post->ID);
		$cat = get_youngest_cat($categories);
		if($cat -> parent != 0){
			$ancestors = array_reverse(get_ancestors( $cat -> cat_ID, 'category' ));
			foreach($ancestors as $ancestor){
				$str.='<li class="c-breadcrumb-item"><a href="'. get_category_link($ancestor).'"><span>'. get_cat_name($ancestor). '</span></a></li>';
			}
		}
		$str.='<li class="c-breadcrumb-item"><a href="'. get_category_link($cat -> term_id). '"><span>'. $cat-> cat_name . '</span></a></li>';
		$str.= '<li class="c-breadcrumb-item"><span>'. $post -> post_title .'</span></li>';
	} elseif(is_page()){  //固定ページ
		if($post -> post_parent != 0 ){
			$ancestors = array_reverse(get_post_ancestors( $post->ID ));
			foreach($ancestors as $ancestor){
				$str.='<li class="c-breadcrumb-item"><a href="'. get_permalink($ancestor).'"><span>'. get_the_title($ancestor) .'</span></a></li>';
			}
		}
		$str.= '<li class="c-breadcrumb-item"><span>'. $post -> post_title .'</span></li>';
	} elseif(is_date()){  //日付ベースのアーカイブページ
		if(get_query_var('day') != 0){  //年別アーカイブ
			$str.='<li class="c-breadcrumb-item"><a href="'. get_year_link(get_query_var('year')). '"><span>' . get_query_var('year'). '年</span></a></li>';
			$str.='<li class="c-breadcrumb-item"><a href="'. get_month_link(get_query_var('year'), get_query_var('monthnum')). '"><span>'. get_query_var('monthnum') .'月</span></a></li>';
			$str.='<li class="c-breadcrumb-item"><span>'. get_query_var('day'). '日</span></li>';
		} elseif(get_query_var('monthnum') != 0){  //月別アーカイブ
			$str.='<li class="c-breadcrumb-item"><a href="'. get_year_link(get_query_var('year')) .'"><span>'. get_query_var('year') .'年</span></a></li>';
			$str.='<li class="c-breadcrumb-item"><span>'. get_query_var('monthnum'). '月</span></li>';
		} else {  //年別アーカイブ
			$str.='<li class="c-breadcrumb-item"><span>'. get_query_var('year') .'年</span></li>';
		}
	} elseif(is_search()) {  //検索結果表示ページ
		$str.='<li class="c-breadcrumb-item"><span>「'. get_search_query() .'」'. $search .'</span></li>';
	} elseif(is_author()){  //投稿者のアーカイブページ
		$str .='<li class="c-breadcrumb-item"><span>'. $author .' : '. get_the_author_meta('display_name', get_query_var('author')).'</span></li>';
	} elseif(is_tag()){  //タグのアーカイブページ
		$str.='<li class="c-breadcrumb-item"><span>'. $tag .' : '. single_tag_title( '' , false ). '</span></li>';
	} elseif(is_attachment()){  //添付ファイルページ
		$str.= '<li class="c-breadcrumb-item"><span>'. $post -> post_title .'</span></li>';
	} elseif(is_404()){  //404 Not Found ページ
		$str.='<li class="c-breadcrumb-item"><span>'.$notfound.'</span></li>';
	} else{  //その他
		$str.='<li class="c-breadcrumb-item"><span>'. wp_title('', true) .'</span></li>';
	}
		$str.='</ol>';
		$str.='</div>';
	}
	if($echo){
		echo $str;
	}else{
		return $str;
	}
}

add_shortcode("breadcrumb","breadcrumb");



function get_youngest_cat($categories){
  global $post;
  if(count($categories) == 1 ){
    $youngest = $categories[0];
  }
  else{
    $count = 0;
     foreach($categories as $category){  //それぞれのカテゴリーについて調査
        $children = get_term_children( $category -> term_id, 'category' );  //子カテゴリーの ID を取得
      if($children){  //子カテゴリー（の ID ）が存在すれば
        if ( $count < count($children) ){  //子カテゴリーの数が多いほど、そのカテゴリーは階層が下なのでそれを元に調査するかを判定
          $count = count($children);  //$count に子カテゴリーの数を代入
          $lot_children = $children;
          foreach($lot_children as $child){  //それぞれの「子カテゴリー」について調査 $childは子カテゴリーのID
            if( in_category( $child, $post -> ID ) ){  //現在の投稿が「子カテゴリー」のカテゴリーに属するか
              $youngest = get_category($child);  //属していればその「子カテゴリー」が一番若い（一番下の階層）
              }
            }
          }
        }
      else{  //子カテゴリーが存在しなければ
        $youngest = $category;  //そのカテゴリーが一番若い（一番下の階層）
      }
    }
  }
  return $youngest;
}

//一番下の階層のタクソノミーを返す関数
function get_youngest_tax($taxes, $mytaxonomy){
	global $post;
	if(count($taxes) == 1 ){
  		$youngest = $taxes[key($taxes)];
	}
  	else{
     $count = 0;
    foreach($taxes as $tax){  //それぞれのタクソノミーについて調査
      $children = get_term_children( $tax -> term_id, $mytaxonomy );  //子タクソノミーの ID を取得
      if($children){  //子カテゴリー（の ID ）が存在すれば
        if ( $count < count($children) ){  //子タクソノミーの数が多いほど、そのタクソノミーは階層が下なのでそれを元に調査するかを判定
          $count = count($children);  //$count に子タクソノミーの数を代入
          $lot_children = $children;
          foreach($lot_children as $child){  //それぞれの「子タクソノミー」について調査 $childは子タクソノミーのID
            if( is_object_in_term( $post -> ID, $mytaxonomy ) ){  //現在の投稿が「子タクソノミー」のタクソノミーに属するか
              $youngest = get_term($child, $mytaxonomy);  //属していればその「子タクソノミー」が一番若い（一番下の階層）
            }
          }
        }
      }
      else{  //子タクソノミーが存在しなければ
        $youngest = $tax;  //そのタクソノミーが一番若い（一番下の階層）
      }
    }
  }
  return $youngest;
}
