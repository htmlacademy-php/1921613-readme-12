<?php
require_once 'helpers.php';
require_once 'utils.php';
require_once 'db.php';

// массив с данными страницы и пользователя
$params = array(
    'is_auth' => $is_auth = rand(0, 1),
    'page_title' => $page_title = 'популярное',
    'user_name' => $user_name = 'the-nepodarok', // укажите здесь ваше имя
);

// получение типов контента
$query = 'SELECT * FROM content_type';
$content_types = get_data_from_db($db_connection, $query);

// Параметр запроса фильтрации по типу контента; по умолчанию равен 0
$type_id = filter_input(INPUT_GET, 'type_id', FILTER_SANITIZE_NUMBER_INT);
$type_options = array_column($content_types,'id');
if (!in_array($type_id, $type_options)) {
    $type_id = 0; // default value
}

// Параметр запроса сортировки; по умолчанию задаётся сортировка по кол-ву просмотров
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_SPECIAL_CHARS);
$sort_options = array('view_count', 'like_count', 'create_dt');
if (!in_array($sort_by, $sort_options)) {
    $sort_by = $sort_options[0];
}

$sort_by_likes = $sort_by === 'like_count';

// Формирование запроса для вывода постов с фильтром, сортировкой и ограничение на кол-во постов на странице
$query = 'SELECT p.*,
             u.user_avatar,
             u.user_name,
             ct.type_val,
             ct.type_name,
             (SELECT COUNT(id) FROM fav_list WHERE post_id = p.id) AS like_count,
             (SELECT COUNT(id) FROM comment WHERE comment.post_id = p.id) AS comment_count
          FROM post AS p
             JOIN user AS u
                ON p.user_id = u.id
             JOIN content_type AS ct
                ON p.content_type_id = ct.id';

if ($type_id) {
    $query .= " WHERE p.content_type_id = $type_id";
} // фильтрация по типу

// добавление префикса таблицы
$query .= ' ORDER BY ' . ($sort_by_likes ? '' : 'p.') . $sort_by . ' DESC';
// запрос сформирован

// список всех постов
$all_posts = get_data_from_db($db_connection, $query);

// приведение ссылок к формату
foreach ($all_posts as &$post) {
    if ($post['link_text_content']) {
        $post['link_text_content'] = trim_link($post['link_text_content']);
    }
}

// кол-во всех постов
$all_posts_count = count($all_posts);

// лимит на кол-во постов на странице
$show_limit = 6;

// кол-во страниц с постами
$page_count = ceil($all_posts_count / $show_limit);

// условие для отображения/скрытия кнопок пагинации
$show_pagination = $all_posts_count > $show_limit;

// текущая страница
$current_page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);

// отсылаем к первой странице, если для page передано значение меньше допусутимого, и к последней, если задано значение больше
if ($current_page < 1) {
    $current_page = 1;
} elseif ($current_page > $page_count) {
    $current_page = $page_count;
}

// список публикаций к отображению на странице
$posts = array_slice($all_posts, (($current_page - 1) * $show_limit), $show_limit, true);

// параметры текущего адреса
$url_param = array(
    'type_id' => $type_id,
    'sort_by' => $sort_by,
);

// формирование адреса для предыдущей страницы
$url_less = $url_param + ['page' => ($current_page - (int)($current_page > 1))];
$prev_page = http_build_query($url_less , '', '&');

//// формирование адреса для следующей страницы
$url_more = $url_param + ['page' => ($current_page + (int)($current_page < $page_count))];
$next_page = http_build_query($url_more, '', '&');

// массив с данными для пагинации
$pagination = array(
    'page_count' => $page_count,
    'current_page' => $current_page,
    'prev_page' => $prev_page,
    'next_page' => $next_page,
);

// защита от XSS
array_walk_recursive($posts, 'secure');

// формирование параметра запроса для фильтрации по типу
$type_filter_url = $type_id ? "&type_id=$type_id" : '';

//foreach ($posts as $key => $post) { // добавляем постам в массиве рандомные даты - the-nepodarok
//    $posts[$key]['date'] = generate_random_date($key);
//}

$main_content = include_template('main.php', [
    'sort_by' => $sort_by,
    'type_id' => $type_id,
    'type_filter_url' => $type_filter_url,
    'content_types' => $content_types,
    'posts' => $posts,
    'pagination' => $pagination,
    'show_pagination' => $show_pagination,
]);

print build_page('layout.php', $params, $main_content);
