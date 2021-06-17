<?php
namespace app01\middleware;
class sample {
    public function middleware( $rencon ){
        if( !$rencon->req()->get_param('middleware') ){
            return;
        }


        ob_start();
?>

<p>パラメータ <code>middleware</code> に <code><?= htmlspecialchars($rencon->req()->get_param('middleware')) ?></code> がセットされました。</p>

<?php
        $src = ob_get_clean();
        echo $rencon->theme()->bind($src);
        exit;
    }
}
