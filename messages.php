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

// параметр запроса ID пользователя для переписки
$user_id = (int)filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);

// проверка существования пользователя
$user_exists = check_user($db_connection, $user_id);

// запрос для получения списка переписок
$query = 'SELECT u.id,
                 u.user_name,
                 u.user_avatar
          FROM user AS u
              JOIN message AS ms
                  ON u.id IN (message_sender_id, message_receiver_id)
          WHERE ' . $_SESSION['user']['id'] . ' IN (message_sender_id, message_receiver_id)
              AND u.id <> ' . $_SESSION['user']['id'] . '
          GROUP BY u.id';
$dialogues = get_data_from_db($db_connection, $query);

// реиндексация по id пользователей
$dialogues = array_column($dialogues, NULL, 'id');

// получение последнего сообщения и время его получения для вывода в списке переписок
if ($dialogues) {
    foreach ($dialogues as &$dialogue) {
        $query = 'SELECT message_create_dt,
                         message_content,
                         (SELECT COUNT(id) FROM message
                         WHERE is_read = 0
                             AND message_sender_id = ' . $dialogue['id'] . '
                         ) AS unread_counter
                  FROM message
                  WHERE ' . $dialogue['id'] . ' IN (message_sender_id, message_receiver_id)
                  ORDER BY message_create_dt DESC
                  LIMIT 1';
        $last_message = get_data_from_db($db_connection, $query, 'row');

        // форматирование даты последнего сообщения в списке диалогов
        $format_date = strtotime($last_message['message_create_dt']);

        if (date('Y-m-d', $format_date) === date('Y-m-d')) {
            $format_date = date('H:i', $format_date);
        } else {
            setlocale(LC_TIME, 'ru-RU.UTF-8');
            $format_date = strftime("%e %b", $format_date);
        }

        $dialogue['format_date'] = $format_date;
        $dialogue += $last_message;
    }
    unset($dialogue); // разорвать ссылку на последний элемент
}

// сортировка диалогов по дате последнего сообщения
usort($dialogues, function ($item, $next_item) {
    return $next_item['message_create_dt'] <=> $item['message_create_dt'];
});

// повторная реиндексация по ID после сортировки
$dialogues = array_column($dialogues, NULL, 'id');

$messages = []; // массив сообщений в диалоге
$current_dialogue = 0; // номер текущего открытого диалога
$new_dialogue = []; // массив для нового диалога с пользователем не из списка

if ($user_exists) {
    // проверка на существование переписки с пользователем
    if (array_key_exists($user_id, $dialogues)) {
        $current_dialogue = $dialogues[$user_id];

        // получение всех сообщений из переписки с пользователем
        $query = 'SELECT * FROM message
                  WHERE ' . $user_id . '
                      IN (message_sender_id, message_receiver_id)
                  ORDER BY message_create_dt DESC';
        $messages = get_data_from_db($db_connection, $query);

        // обнуление счётчика непрочитанных сообщений
        $unread_counter = $current_dialogue['unread_counter'];
        if ($unread_counter) {
            $unread_counter = 0;
            $query = 'UPDATE message
                      SET is_read = 1
                      WHERE message_sender_id = ' . $user_id;
            mysqli_query($db_connection, $query);
        }
    } else { // при открытии переписки с новым пользователем
        // получение данных пользователя
        $query = 'SELECT id,
                     user_name,
                     user_avatar
                  FROM user
                  WHERE id = ' . $user_id;
        $new_dialogue = get_data_from_db($db_connection, $query, 'row');
    }
} else {
    $user_id = 0;
}

// отображение нового пользователя вверху списка диалогов
if ($new_dialogue) {
    array_unshift($dialogues, $new_dialogue);
}

$message_input = ''; // ввод текста сообщения
$errors = []; // массив для ошибок

// отправка нового сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

    // замена символа переноса строки на пробел для корректного отображения
    $message_text = str_replace('&#13;&#10;', ' ', $message_data['message-text']);
    $message_text = trim($message_text); // обрезка лишних пробелов

    if ($message_text) {
        // запись нового сообщения в таблицу
        $query = 'INSERT into message
                    (message_content, message_sender_id, message_receiver_id)
                  VALUES (?, ?, ?)';
        $stmt = mysqli_prepare($db_connection, $query);

        // данные для подстановки
        $query_vars = array(
            $message_text,
            $_SESSION['user']['id'],
            $user_id
        );

        // выполнение подготовленного выражения
        mysqli_stmt_bind_param($stmt, 'sii', ...$query_vars);
        mysqli_stmt_execute($stmt);

        // обновление страницы диалога
        header('Location: messages.php?user_id=' . $user_id);
        exit;
    } else {
        fill_errors($errors,
            'message-text',
            'Поле не заполнено',
            'Пустое сообщение',
            'Напишите сообщение');
    }

    // показ текста комментария при ошибке
    if ($errors) {
        $message_input = filter_input(INPUT_POST, 'message-text', FILTER_SANITIZE_STRING);
    }
}

// класс для отображения ошибки рядом с полем
$alert_class = 'form__input-section--error';

// массив с данными страницы
$params = array(
    'page_title' => 'Личные сообщения',
    'active_page' => 'messages',
    'db_connection' => $db_connection,
);

$main_content = include_template('messages_template.php', [
    'dialogues' => $dialogues,
    'user_id' => $user_id,
    'user_exists' => $user_exists,
    'messages' => $messages,
    'current_dialogue' => $current_dialogue,
    'errors' => $errors,
    'alert_class' => $alert_class,
    'message_input' => $message_input,
]);

print build_page('layout.php', $params, $main_content);
