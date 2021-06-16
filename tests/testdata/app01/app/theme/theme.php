<?php
$current_page_info = $this->get_current_page_info();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><?= htmlspecialchars( $current_page_info->title ) ?></title>
<link rel="stylesheet" href="?res=theme.css" />
</head>
<body>


<hr />
<div class="theme-middle">
<h1><?= nl2br( htmlspecialchars( $current_page_info->title ) ) ?></h1>
<div class="contents">
<?= $content ?>
</div>
</div>

<script src="?res=theme.js"></script>
</body>
</html>
