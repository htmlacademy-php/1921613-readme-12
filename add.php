<?php
require_once 'helpers.php';
require_once 'utils.php';
require_once 'db.php';

// массив с данными страницы и пользователя
$params = array(
    'is_auth' => $is_auth = rand(0, 1),
    'page_title' => $page_title = 'публикация',
    'user_name' => $user_name = 'the-nepodarok', // укажите здесь ваше имя
);

// получение типов контента
$query = 'SELECT * FROM content_type';
$content_types = get_data_from_db($db_connection, $query);

// параметр запроса типа добавляемого поста
$form_tab = filter_input(INPUT_GET, 'post_type', FILTER_SANITIZE_STRING);
$form_tab_options = array_column($content_types, 'type_val');

// тип публикации, для добавления которой форма открывается по умолчанию
if (!in_array($form_tab, $form_tab_options)) {
    $form_tab = 'text';
}

$errors = [];
$values = [];

// массив с допустимыми для загрузки в форме типами файлов
define('ALLOWED_TYPES', array(
    'image/jpeg',
    'image/png',
    'image/gif',
));

// выборка обязательных для заполнения полей
$required_fields = [];
switch ($form_tab) {
    case 'photo':
        $required_fields = [
            'photo-heading' => 'Заголовок',
        ];
        break;
    case 'text':
        $required_fields = [
            'text-heading' => 'Заголовок',
            'post-text' => 'Текст поста',
        ];
        break;
    case 'quote':
        $required_fields = [
            'quote-heading' => 'Заголовок',
            'cite-text' => 'Текст цитаты',
            'quote-author' => 'Автор',
        ];
        break;
    case 'video':
        $required_fields = [
            'video-heading' => 'Заголовок',
            'video-url' => 'Ссылка YouTube',
        ];
        break;
    case 'link':
        $required_fields = [
            'link-heading' => 'Заголовок',
            'post-link' => 'Ссылка',
        ];
}

// временная переменная с данными пользователя - автора публикации
$user = array(
    'id' => 1,
);

// обработка данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // удаление XSS
    array_walk($_POST, 'secure');

    // обработка пустых обязательных полей
    foreach ($required_fields as $key => $value) {
        if (empty($_POST[$key])) {
            $errors[$key] = $value . '. Это поле должно быть заполнено.';
        }
    }

    // валидация ссылок и загрузка файлов
    switch ($form_tab) {
        case 'video':
            $video_url = $_POST['video-url'];
            if (checkURL($video_url)) {
                if (gettype(check_youtube_url($video_url)) === 'string') {
                    $errors['video-url'] = check_youtube_url($video_url);
                }
            } else {
                $errors['video-url'] = 'Ссылка YouTube. URL должен быть корректным';
            }
            break;

        case 'photo':
            if (validateFile('userpic_file_photo', ALLOWED_TYPES)) {
                $file = uploadFile('userpic_file_photo');
            } elseif ($_POST['photo-url']) {
                $err_msg = 'Ссылка из интернета. Введите действующую ссылку на изображение в формате jpg, png или gif.';
                if (checkURL($_POST['photo-url'])) {
                    $file = uploadFileFromURL('photo-url');
                    $file_type = finfo_open(FILEINFO_MIME_TYPE);
                    $file_type = finfo_file($file_type, $file);

                    if (!in_array($file_type, ALLOWED_TYPES)) {
                        unlink($file);
                        $errors['photo-url'] = $err_msg;
                    }
                } else {
                    $errors['photo-url'] = $err_msg;
                }
            } else {
                $errors['photo'] = 'Загрузите картинку в формате jpg, png или gif. Максимальный размер файла: 200Кб.';
        }
            break;

        case 'link':
            $post_link = $_POST['post-link'];
            if (!filter_var($post_link, FILTER_VALIDATE_URL)) {
                $errors['post-link'] = 'Ссылка. URL должен быть корректным';
            }
            break;
    }

    // Обработка полученных данных в случае правильного заполнения всех полей
    if (empty($errors)) {
        // Добавление новых публикаций в базу данных
        switch ($form_tab) {
            case 'text':
                $query = "INSERT INTO post
                        (post_header, text_content, quote_origin, photo_content, video_content, link_text_content, user_id, content_type_id)
                      VALUES
                        ('{$_POST[$form_tab . '-heading']}', '{$_POST['post-text']}', NULL, NULL, NULL, NULL, {$user['id']}, 1)";
                $result = mysqli_query($db_connection, $query);
                break;
            case 'quote':
                $query = "INSERT INTO post
                        (post_header, text_content, quote_origin, photo_content, video_content, link_text_content, user_id, content_type_id)
                      VALUES
                        ('{$_POST[$form_tab . '-heading']}', '{$_POST['cite-text']}', '{$_POST['quote-author']}', NULL, NULL, NULL, {$user['id']}, 2)";
                $result = mysqli_query($db_connection, $query);
                break;
            case 'photo':
                $query = "INSERT INTO post
                        (post_header, text_content, quote_origin, photo_content, video_content, link_text_content, user_id, content_type_id)
                      VALUES
                        ('{$_POST[$form_tab . '-heading']}', NULL, NULL, '$file', NULL, NULL, {$user['id']}, 3)";
                $result = mysqli_query($db_connection, $query);
                break;
            case 'video':
                $query = "INSERT INTO post
                        (post_header, text_content, quote_origin, photo_content, video_content, link_text_content, user_id, content_type_id)
                      VALUES
                        ('{$_POST[$form_tab . '-heading']}', NULL, NULL, NULL, '{$_POST['video-url']}', NULL, {$user['id']}, 4)";
                $result = mysqli_query($db_connection, $query);
                break;
            case 'link':
                $query = "INSERT INTO post
                        (post_header, text_content, quote_origin, photo_content, video_content, link_text_content, user_id, content_type_id)
                      VALUES
                        ('{$_POST[$form_tab . '-heading']}', NULL, NULL, NULL, NULL, '{$_POST['post-link']}', {$user['id']}, 5)";
                $result = mysqli_query($db_connection, $query);
                break;
        }

        // сохраняем id нового поста
        $new_post_id = mysqli_insert_id($db_connection);

        // добавление тегов
        $tags_field = $_POST[$form_tab === 'quote' ? 'cite-tags' : $form_tab . '-tags'];

        if (!empty($tags_field)) {
            $tags = explode(' ', $tags_field);

            foreach ($tags as $tag) {
                $tag = strtolower(trim($tag, '#'));

                $tag_exists = get_data_from_db($db_connection, "SELECT id FROM hashtag WHERE hashtag_name = '$tag'", 'one');
                // проверка тегов на уже существующие в базе данных
                if ($tag_exists) {
                    mysqli_query($db_connection, "INSERT INTO post_hashtag_link
                                                            (post_id, hashtag_id)
                                                          VALUES
                                                            ('$new_post_id', $tag_exists)");
                } else {
                    mysqli_query($db_connection, "INSERT INTO hashtag SET hashtag_name = '$tag'");
                    $new_tag_id = mysqli_insert_id($db_connection);
                    mysqli_query($db_connection, "INSERT INTO post_hashtag_link
                                                            (post_id, hashtag_id)
                                                          VALUES
                                                            ('$new_post_id', $new_tag_id)");
                }
            }
        }

        // переадресация на страницу с новосозданным постом
        header('Location: post.php?post_id=' . $new_post_id);
    } else {
        // заполняется массив с введёнными пользователем данными
        foreach ($_POST as $key => $value) {
            $values[$key] = htmlspecialchars($value);
        }
    }
}

$alert_class = 'form__input-section--error';

// подключение шаблона для отображения блоков с ошибками заполнения полей формы
$error_list = include_template('add-post_error-list.php', [
    'errors' => $errors,
]);

// подключение шаблона для отображения поля ввода заголовка
$show_error_class = (array_key_exists($form_tab . '-heading', $errors)) ? 'form__input-section--error' : '';
$header_field = include_template('add-post_header_template.php', [
    'type' => $form_tab,
    'show_error_class' => $show_error_class,
    'values' => $values,
    'errors' => $errors,
]);

// подключение шаблона для отображения поля ввода тегов
$tag_post_type = ($form_tab === 'quote') ? 'cite' : $form_tab;
$tag_field = include_template('add-post_tags_template.php', [
    'type' => $tag_post_type,
    'values' => $values,
]);

// отображение страницы
$main_content = include_template('add-post_template.php', [
    'content_types' => $content_types,
    'form_tab' => $form_tab,
    'header_field' => $header_field,
    'errors' => $errors,
    'alert_class' => $alert_class,
    'values' => $values,
    'error_list' => $error_list,
    'tag_field' => $tag_field,
]);

print build_page('layout.php', $params, $main_content);
