-- заполняем таблицу типов всеми возможными типами будущих записей
INSERT INTO user
    (user_email, user_name, user_password, user_avatar, user_reg_dt)
  VALUES
    ('vladik@gmail.com', 'Владик', 'oiuy45', 'userpic.jpg', '2020-11-04 17:58:52'),
    ('larisa@yandex.com', 'Лариса', 'shj4-sk', 'userpic-larisa-small.jpg', '2016-02-09 09:18:22'),
    ('viktor@yahoo.com', 'Виктор', 'fb2656', 'userpic-mark.jpg', '2022-10-11 11:18:22'),
    ('a.glu@mail.ru', 'Антон Глуханько', 't7znf5', 'userpic-medium.jpg', '2019-01-21 13:18:45');

-- заносим в таблицу постов пять записей разного типа, взятых из массива $posts
INSERT INTO content_type
    (type_name, type_val, type_icon_width, type_icon_height)
  VALUES
    ('Текст', 'text', 20, 21),
    ('Цитата', 'quote', 21, 20),
    ('Картинка', 'photo', 22, 18),
    ('Видео', 'video', 24, 16),
    ('Ссылка',  'link', 21, 18);

-- "пишем" по комментарию к двум разным записям
INSERT INTO post
    (post_header, create_dt, view_count, user_id, content_type_id, text_content, quote_origin, photo_content, link_text_content)
  VALUES
    ('Цитата', '2022-12-04 07:58:52', 23, 2, 2, 'Мы в жизни любим только раз, а после ищем лишь похожих', 'Неизвестный автор', NULL, NULL),
    ('Игра Престолов', '2022-12-01 11:16:42', 32, 1, 1, 'Не могу дождаться начала финального сезона своего любимого сериала!', NULL, NULL, NULL),
    ('Наконец, обработал фотки!', '2022-11-29 23:33:17', 35, 3, 3, NULL, NULL, 'img/rock-medium.jpg', NULL),
    ('Моя мечта', '2022-10-02 12:20:35', 12, 1, 3, NULL, NULL, 'img/coast-medium.jpg', NULL),
    ('Лучшие курсы', '2022-07-14 16:23:09', 24, 2, 5, NULL, NULL, NULL, 'https://www.htmlacademy.ru/'),
    ('PHP?', '2022-07-14 16:23:09', 24, 3, 5, NULL, NULL, NULL, 'www.php.net'),
    ('Получил свой первый сертификат', '2022-07-14 16:23:09', 24, 1, 5, NULL, NULL, NULL, 'https://htmlacademy.ru/profile/id1921613');

-- заносим в таблицу хештеги
INSERT INTO hashtag
  (hashtag_name)
VALUES
  ('landscape'),
  ('photooftheday'),
  ('сериалы'),
  ('цитатывеликихлюдей'),
  ('онлайнкурсы');

-- добавляем хештеги для постов
INSERT INTO post_hashtag_link
  (post_id, hashtag_id)
VALUES
  (1, 4),
  (2, 3),
  (3, 2),
  (3, 1),
  (4, 1),
  (5, 5);

-- создаём трёх пользователей
INSERT INTO comment
(comment_content, user_id, post_id, comment_create_dt)
VALUES
  ('Зря ждали, расходимся. Весь сериал запороли, как только могли', 1, 2, DEFAULT),
  ('Если я правильно помню, автор цитаты - Дэвид Бекхэм', 1, 1, '2022-12-14 16:20:09'),
  ('Красота!!!1!', 2, 3, '2022-07-14 16:23:09'),
  ('Озеро Байкал – огромное древнее озеро в горах Сибири к северу от монгольской границы. Байкал считается самым глубоким озером в мире. Он окружен сетью пешеходных маршрутов, называемых Большой байкальской тропой. Деревня Листвянка, расположенная на западном берегу озера, – популярная отправная точка для летних экскурсий. Зимой здесь можно кататься на коньках и собачьих упряжках.', 2, 3, '2022-07-14 23:23:09'),
  ('Я сам немного фотограф', 1, 3, DEFAULT);

-- добавляем один репост записи с рекламой академии
INSERT INTO post
  (post_header, create_dt, view_count, user_id, content_type_id, text_content, quote_origin, photo_content, link_text_content, is_repost, origin_post_id)
VALUES
  ('Лучшие курсы', '2022-12-13 22:43:01', 24, 2, 5, NULL, NULL, NULL, 'http://www.htmlacademy.ru/', 1, 5);

-- Получаем список постов, отсортированных по популярности, с именами пользователей и типом поста
SELECT p.*,
       user_name,
       type_name,
       type_val
FROM post AS p
       JOIN user AS u
            ON p.user_id = u.id
       JOIN content_type AS c
            ON p.content_type_id = c.id
ORDER BY p.view_count DESC;

-- Получаем список постов конкретного пользователя
SELECT * FROM post WHERE user_id = 1;

-- Получаем список комментариев к конкретному посту с отображением имени пользователя
SELECT c.id,
       comment_content,
       user_name
FROM comment AS c
        JOIN user AS u
              ON c.user_id = u.id
WHERE c.post_id = 1;

-- Добавлем один лайк от пользователя 2 к посту под номером 3
INSERT INTO fav_list
    SET user_id = 2,
        post_id = 3;

-- Добавляем пользователю 2 в подписчики пользователя 1
INSERT INTO follower_list
    SET following_user_id = 1,
        followed_user_id = 2;

-- Получаем таблицу с пользователями, у которых есть хотя бы один подписчик, отсортированную по убыванию
SELECT user_name,
       COUNT(following_user_id) AS f_count
FROM follower_list
       JOIN user u
              ON followed_user_id = u.id
GROUP BY user_name
ORDER BY f_count DESC;
