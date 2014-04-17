<?php
if (!empty($_POST['error'])) {
    file_put_contents('test.log', "=======\n" .$_POST['data'] . "\n >>>>>\n" . $_POST['error'] . "\n\n", FILE_APPEND);
}