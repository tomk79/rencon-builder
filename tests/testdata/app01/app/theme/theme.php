<?php
$app_info = $this->app_info();
$current_page_info = $this->get_current_page_info();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><?= htmlspecialchars( $app_info->name ) ?> | <?= htmlspecialchars( $current_page_info->title ) ?></title>
<link rel="stylesheet" href="?res=theme.css" />
</head>
<body>

<p><a href="?a="><?= htmlspecialchars( $app_info->name ) ?></a></p>

<ul><?php
foreach( $app_info->pages as $pid=>$page_info ){
    echo '<li><a href="?a='.htmlspecialchars($pid).'">'.htmlspecialchars($page_info->title).'</a></li>'."\n";
}

?></ul>

<hr />
<div class="theme-middle">
<h1><?= nl2br( htmlspecialchars( $current_page_info->title ) ) ?></h1>
<div class="contents">
<?= $content ?>
</div>
</div>

<hr />

<?php if( $this->main->conf()->is_login_required() && $login->check() ) { ?>
<p>
    <a href="?a=logout">Logout</a>
</p>
<?php } ?>

<script src="?res=theme.js"></script>
</body>
</html>
