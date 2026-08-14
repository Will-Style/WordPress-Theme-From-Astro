<?php
// キャッシュクリア用
define("VERSION",'');
// RECAPTCHA シークレットキー
define("RECAPTCHA_SECRET",'');

include('config/settings.php');

include('config/sendmail/sendmail.php');
include('config/sendmail/sendmail-complete.php');

include('config/namespace.php');
include('config/blank-page.php');
include('config/pagenavi.php');
include('config/post-types.php');
include('config/image-sizes.php');
include('config/admin-columns.php');
include('config/archive-filter.php');
include('config/lazy-blocks.php');
include('config/aside.php');
include('config/breadcrumb.php');
include('config/get-terms-list.php');
include('config/feed-request.php');
