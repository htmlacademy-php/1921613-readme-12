<?php
$ds = DIRECTORY_SEPARATOR;
$store_folder = 'uploads';
if (!empty($_FILES)) {
    $temp_file = $_FILES['file']['tmp_name'];
    $target_path = dirname(__FILE__) . $ds . $store_folder . $ds;
    $target_file = $target_path . $_FILES['file']['name'];
    move_uploaded_file($temp_file, $target_file);
}
