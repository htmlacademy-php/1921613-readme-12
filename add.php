<?php

session_start();

// Перенаправление анонимного пользователя
if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

require_once 'helpers.php';
require_once 'utils.php';
require_once 'db_config.php';

$content_types = $_SESSION['ct_types']; // типы контента
$post_type_options = array_column($content_types, 'type_val'); // перечень допустимых параметров

// функция для проверки на допустимое значение параметра post_type
$filter_post_type = function ($type, $options) {
    if (!in_array($type, $options)) {
        $type = 'text';
    }
    return $type;
};

$post_data = []; // массив для заполнения данными из формы
$errors = []; // массив для заполнения ошибками полей формы

// обработка данных формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // фильтрация данных формы
    $post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
    $post_type = $post_data['post_type']; // тип поста
    $post_type = $filter_post_type($post_type, $post_type_options); // проверка параметра

    // выборка обязательных для заполнения полей
    switch ($post_type) {
        case 'text':
            $required_fields = [
                'post-text' => 'Текст поста',
            ];
            break;
        case 'quote':
            $required_fields = [
                'post-text' => 'Текст цитаты',
                'quote-author' => 'Автор',
            ];
            break;
        case 'video':
            $required_fields = [
                'video-url' => 'Ссылка YouTube',
            ];
            break;
        case 'link':
            $required_fields = [
                'post-link' => 'Ссылка',
            ];
            break;
        default:
            $required_fields = [];
    }

    // заголовок - общее обязательное поле для всех
    $required_fields = ['post-heading' => 'Заголовок'] + $required_fields;

    // обработка пустых обязательных полей
    check_if_empty($errors, $required_fields, $post_data);

    // валидация ссылок и загрузка файлов
    switch ($post_type) {
        case 'video':
            $field_name = 'video-url';
            // прервать дальнейшую проверку, если поле со ссылкой на видео пустое
            if (isset($errors[$field_name])) {
                break;
            }
            $video_url = $post_data[$field_name];
            if (validate_url($errors, $video_url, $field_name, PHP_URL_PATH)) {
                $yt_check = check_youtube_url($video_url);
                if ($yt_check !== true) {
                    $err_type = 'Не найдено видео по ссылке';
                    $err_heading = 'Ссылка YouTube';
                    $err_text = $yt_check;
                }
            }
            break;

        case 'photo':
            $field_name = 'file-photo';
            $file_attached = (bool)$_FILES[UPLOAD_IMG_NAME]['name']; // был ли приложен файл
            if ($file_attached) {
                $file = upload_image($errors, UPLOAD_IMG_NAME);
            } elseif ($post_data['photo-url']) { // если не было загрузки файла и заполнено поле со ссылкой
                $field_name = 'photo-url';
                // приведение ссылки к протоколу https
                $file_url = $post_data[$field_name];
                // проверка ссылки на path
                if (validate_url($errors, $file_url, $field_name, PHP_URL_PATH)) {
                    // валидация по типу и размеру файла по ссылке
                    $file = download_file_from_url($errors, $file_url);
                }
            } else { // если файл не был загружен ни из файловой системы, ни по ссылке
                $err_type = 'Файл не был загружен';
                $err_heading = 'Нет файла';
                $err_text = 'Воспользуйтесь полем для загрузки файла или вставьте ссылку на изображение';
            }
            break;

        case 'link':
            $field_name = 'post-link';
            // прервать дальнейшую проверку, если поле для ссылки пустое
            if (isset($errors[$field_name])) {
                break;
            }

            // проверка валидности и доступности ссылки
            validate_url($errors, $post_data[$field_name], $field_name);
            break;
    }

    // заполнить массив с ошибками, если такие возникли
    if (isset($err_text)) {
        fill_errors($errors, $field_name, $err_type, $err_heading, $err_text);
    }

    // валидация хэштегов
    $tags_field = $post_data['tags'];

    if ($tags_field) {
        $tags_field = trim_extra_spaces($tags_field); // убираются лишние пробелы

        // проверка на соответствие формату
        $match = preg_match('/^(#[A-z_А-я\d]+\h)+$/ui', $tags_field . ' '); // пробел в конце для учёта особенности паттерна

        $err_heading = 'Теги';
        if ($match === 0) {
            $err_type = 'Недопустимые символы';
            $err_text = 'Теги должны начинаться с #, могут состоять из букв, цифр и символа подчёркивания и разделены пробелами';
            fill_errors($errors, 'tags', $err_type, $err_heading, $err_text);
        } elseif (!$match) { // если по какой-то причине preg_match вернул false
            $err_type = 'Ошибка алгоритма';
            $err_text = 'Что-то пошло не так, попробуйте ещё раз';
            fill_errors($errors, 'tags', $err_type, $err_heading, $err_text);
        }
    }

    // Обработка полученных данных в случае правильного заполнения всех полей и добавление новой публикации в БД
    if (empty($errors)) {
        // подключение конфиг. данных для e-mail рассылки
        require_once 'email_config.php';

        // получение id типа контента в зав-ти от формы
        $content_type_values = array_column($content_types, 'type_val', 'id');
        $content_type_id = array_search($post_type, $content_type_values);

        // подготовка выражения
        $query = "INSERT INTO post (
                            post_header,
                            text_content,
                            quote_origin,
                            photo_content,
                            video_content,
                            link_text_content,
                            user_id,
                            content_type_id
                         )
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // 8 полей
        $stmt = mysqli_prepare($db_connection, $query);

        // данные для подстановки
        $query_vars = array(
            $post_data['post-heading'],
            $post_data['post-text'] ?? NULL,
            $post_data['quote-author'] ?? NULL,
            $file ?? NULL,
            $post_data['video-url'] ?? NULL,
            $post_data['post-link'] ?? NULL,
            $_SESSION['user']['id'],
            $content_type_id
        );

        // выполнение подготовленного выражения
        mysqli_stmt_bind_param($stmt, 'ssssssii', ...$query_vars);
        mysqli_stmt_execute($stmt);

        // сохраняем id нового поста
        $new_post_id = mysqli_insert_id($db_connection);

        // добавление тегов
        if ($tags_field) {
            $tags = explode(' ', $tags_field); // разбивка на отдельные теги по пробелу

            foreach ($tags as $tag) {
                $tag = trim($tag, '#');

                // подготовка запроса для связки поста с тегами
                $sql = 'INSERT INTO post_hashtag_link
                                 (post_id, hashtag_id)
                               VALUES (?, ?)';
                $stmt = mysqli_prepare($db_connection, $sql);

                // проверка на уже существующие теги
                $query = "SELECT id FROM hashtag WHERE hashtag_name = '$tag'";
                $tag_id = get_data_from_db($db_connection, $query, 'one');

                // если такого тега ещё нет, добавить его и взять его id
                if (!$tag_id) {
                    mysqli_query($db_connection, "INSERT INTO hashtag SET hashtag_name = '$tag'");
                    $tag_id = mysqli_insert_id($db_connection);
                }

                $query_vars = array(
                    $new_post_id,
                    $tag_id
                );

                // выполнение подготовленного выражения
                mysqli_stmt_bind_param($stmt,'ii',...$query_vars);
                mysqli_stmt_execute($stmt);
            }
        }

        // e-mail оповещение о новой публикации

        // получение списка подписчиков пользователя для рассылки
        $followers_query = 'SELECT user_name,
                                   user_email
                            FROM user
                                INNER JOIN follower_list
                                    ON following_user_id = user.id
                            WHERE followed_user_id = ' . $_SESSION['user']['id'];
        $followers = get_data_from_db($db_connection, $followers_query);

        foreach ($followers as $follower) {
            // Формирование e-mail сообщения
            $message = new Symfony\Component\Mime\Email();
            $message->to($follower['user_email']);
            $message->from('readme_blog_noreply@list.ru');
            $message->subject('Новая публикация от пользователя ' . $_SESSION['user']['user_name']);
            $message->text('Здравствуйте, ' . $follower['user_name'] .
                                 '. Пользователь ' . $_SESSION['user']['user_name'] .
                                 ' только что опубликовал новую запись „' . $post_data['post-heading'] . '“.
                                 Посмотрите её на странице пользователя: readme/profile.php?user_id=' . $_SESSION['user']['id']);

            // Отправка сообщения
            $mailer = new Symfony\Component\Mailer\Mailer($transport);
            try {
                $mailer->send($message);
            } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                $_SESSION['errors'] = 'Cannot send message, ' . $e->getMessage();
            }
        }

        // переадресация на страницу с созданным постом
        header('Location: post.php?post_id=' . $new_post_id);
        exit;
    }
} else { // обработка данных GET
    // параметр типа добавляемой публикации
    $post_type = filter_input(INPUT_GET, 'post_type', FILTER_SANITIZE_STRING);

    // проверка параметра или задание значения по умолчанию
    $post_type = $filter_post_type($post_type, $post_type_options);
}

// класс для отображения ошибки рядом с полем
$alert_class = 'form__input-section--error';

// подключение шаблона для отображения блоков с ошибками заполнения справа от формы
$error_list = '';

if ($errors) {
    $error_list = include_template('form_error-list.php', [
        'errors' => $errors,
    ]);
}

// сохранение адреса страницы для перенаправления на странице поиска
$_SESSION['prev_page'] = 'add.php';

// массив с данными страницы
$params = array(
    'page_title' => 'новая публикация',
    'db_connection' => $db_connection,
);

// подключение шаблона для отображения поля ввода заголовка
$show_error_class = (array_key_exists('post-heading', $errors)) ? $alert_class : '';
$header_field = include_template('add-post_header_template.php', [
    'show_error_class' => $show_error_class,
    'post_heading' => $post_data['post-heading'] ?? '',
    'err_msg' => show_error_msg($errors, 'post-heading'),
]);

// подключение шаблона для отображения поля ввода тегов
$show_error_class = (array_key_exists('tags', $errors)) ? $alert_class : '';
$tag_field = include_template('add-post_tags_template.php', [
    'show_error_class' => $show_error_class,
    'tag_values' => $post_data['tags'] ?? '',
    'err_msg' => show_error_msg($errors, 'tags') ?? '',
]);

// отображение страницы
$main_content = include_template('add-post_template.php', [
    'post_type' => $post_type,
    'header_field' => $header_field,
    'errors' => $errors,
    'alert_class' => $alert_class,
    'post_data' => $post_data,
    'error_list' => $error_list,
    'tag_field' => $tag_field,
]);

print build_page('layout.php', $params, $main_content);
