<?php
$app_info = $this->app_info();
$current_page_info = $this->get_current_page_info();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title><?= htmlspecialchars( $app_info->name ) ?> | <?= htmlspecialchars( $current_page_info->title ) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="robots" content="nofollow, noindex, noarchive" />
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

<?php if( $rencon->auth()->is_login_required() && $rencon->user()->is_login() ) { ?>
<p>
    <a href="?a=logout">Logout</a>
</p>
<?php } ?>

        <script src="?res=theme.js"></script>
    </body>
</html>
