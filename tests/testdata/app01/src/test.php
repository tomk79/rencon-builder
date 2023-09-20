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
}
