-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: 127.127.126.26:3306
-- Время создания: Июн 02 2025 г., 14:09
-- Версия сервера: 8.0.35
-- Версия PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `audi`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cars`
--

CREATE TABLE `cars` (
  `id` int NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `category` enum('kuzov-l','kuzov-v','kuzov-s') NOT NULL,
  `main_image_path` varchar(255) NOT NULL,
  `detail_page_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `cars`
--

INSERT INTO `cars` (`id`, `model_name`, `category`, `main_image_path`, `detail_page_path`) VALUES
(6, 'RS6 C8', 'kuzov-l', 'авто/легковые/rs 6/audi 1.jpg', 'авто/легковые/rs 6/rs6.html'),
(7, 'e-tron GT', 'kuzov-l', 'авто/легковые/e-tron/1.webp', 'авто/легковые/e-tron/e-tron.html'),
(8, 'A3 8Y', 'kuzov-l', 'авто/легковые/a3/1.jpg', 'авто/легковые/a3/a3.html'),
(9, 'A6 allroad quattro C8', 'kuzov-l', 'авто/легковые/A6/1.jpg', 'авто/легковые/A6/A6.html'),
(10, 'Q3 F3', 'kuzov-v', 'авто/внедорожники/q3/1.webp', 'авто/внедорожники/q3/q3.html'),
(11, 'Q5 FY', 'kuzov-v', 'авто/внедорожники/q5/1.webp', 'авто/внедорожники/q5/q5.html'),
(12, 'Q7 4M', 'kuzov-v', 'авто/внедорожники/q7/1.webp', 'авто/внедорожники/q7/q7.html'),
(13, 'Q8 4M', 'kuzov-v', 'авто/внедорожники/q8/1.webp', 'авто/внедорожники/q8/q8.html'),
(14, 'S3 8Y', 'kuzov-s', 'авто/S-класс/S3/1.webp', 'авто/S-класс/s3/s3.html'),
(15, 'S4 B9', 'kuzov-s', 'авто/S-класс/s4/1.webp', 'авто/S-класс/s4/s4.html'),
(16, 'S6 C8', 'kuzov-s', 'авто/S-класс/s6/1.webp', 'авто/S-класс/s6/s6.html'),
(17, 'S8 D5', 'kuzov-s', 'авто/S-класс/s8/1.webp', 'авто/S-класс/s8/s8.html');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `car_id` int NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text,
  `payment_status` enum('не оплачен','оплачен') DEFAULT 'не оплачен',
  `order_status` enum('оформляется','отправлен','доставлен') DEFAULT 'оформляется',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `car_id`, `phone`, `message`, `payment_status`, `order_status`, `order_date`) VALUES
(1, 1, 7, '+79244573868', '123', 'оплачен', 'отправлен', '2025-05-31 14:03:34');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `birth_date` date NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `birth_date`, `avatar_path`, `is_admin`, `created_at`) VALUES
(1, 'Pumalk', '$2y$10$BQ02TCEHMNx5HusDUX6cFe3S2tbbWSLwPASR1Tmi6rv6QHSL0HTPu', 'example@local.ru', 'Иван', 'Иванов', '2025-05-01', 'uploads/avatars/кот.jpg', 1, '2025-05-31 10:32:23');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `model_name` (`model_name`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `orders_ibfk_2` (`car_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
