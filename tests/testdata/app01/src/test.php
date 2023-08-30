<?php

namespace app01;

class test {
    static public function start( $rencon ){
        ?>
        <p>test::start()</p>
        <form action="?a=test.post" method="post">
            <input type="hidden" name="CSRF_TOKEN" value="<?= htmlspecialchars($rencon->auth()->get_csrf_token()) ?>" />
            <button type="submit">test.post</button>
        </form>
        <?php
        return;
    }
    static public function post( $rencon ){
        echo "test::post()"."\n";
        return;
    }
    static public function api_preview($rencon){
        // TODO: PHPビルトインサーバーでは受け取れない。AJAXでpostの通信もプレビューするように書き換える。
        ?>
        <p>開発中</p>
        <p><a href="/app01.php?api=api.test.test001" target="_blank">http://localhost:8088/app01.php?api=api.test.test001</a></p>
        <p><a href="/app01.php?api=api.test.aaaaaa" target="_blank">http://localhost:8088/app01.php?api=api.test.aaaaaa</a></p>
        <?php
        return;
    }
}
