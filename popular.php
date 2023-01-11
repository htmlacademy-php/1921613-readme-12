<?php
require_once 'helpers.php';
require_once 'utils.php';
require_once 'db.php';

$is_auth = rand(0, 1);
$page_title = 'популярное';
$user_name = 'the-nepodarok'; // укажите здесь ваше имя

// получение типов контента
$query = 'SELECT * FROM content_type';
$content_types = get_data_from_db($db_connection, $query);

$type_id = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_NUMBER_INT); // Параметр запроса фильтрации по типу контента
$sort_param = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_SPECIAL_CHARS); // Параметр запроса сортировки

// составление запроса списка постов с типом, именем пользователя и кол-вом лайков, отсортированного по типу и/или заданным критериям
$query = prepare_posts($type_id, $sort_param);
$posts = get_data_from_db($db_connection, $query);

array_walk_recursive($posts, 'secure'); // защита от XXS

//foreach ($posts as $key => $post) { // добавляем постам в массиве рандомные даты - the-nepodarok
//    $posts[$key]['date'] = generate_random_date($key);
//}

$main_content = include_template('main.php', [
    'content_types' => $content_types,
    'type_id' => $type_id ?? '',
    'sort_by' => $sort_param ?? 'view_count',
    'posts' => $posts,
]);

$layout_template = include_template('layout.php', [
    'page_title' => $page_title,
    'is_auth' => $is_auth,
    'user_name' => $user_name,
    'main_content' => $main_content,
]);

print($layout_template);
?>
