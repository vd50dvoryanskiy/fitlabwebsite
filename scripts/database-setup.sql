-- Создание базы данных FitLab
CREATE DATABASE IF NOT EXISTS fitlab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitlab;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('visitor', 'user', 'admin') DEFAULT 'user',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Таблица тренеров
CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(200),
    description TEXT,
    experience_years INT,
    photo VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE
);

-- Таблица абонементов
CREATE TABLE memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    features TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Таблица тренировок/программ
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    trainer_id INT,
    duration_minutes INT,
    max_participants INT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced'),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);

-- Таблица расписания
CREATE TABLE schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT,
    trainer_id INT,
    date_time DATETIME NOT NULL,
    available_spots INT,
    booked_spots INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);

-- Таблица записей на тренировки
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    schedule_id INT,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedule(id)
);

-- Таблица заявок/обратной связи
CREATE TABLE contact_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new'
);

-- Таблица отзывов
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_approved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Добавление администратора
INSERT INTO users (username, email, password, first_name, last_name, role) 
VALUES ('admin', 'admin@fitlab.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', 'Системы', 'admin');

-- Добавление тренеров
INSERT INTO trainers (name, specialization, description, experience_years, email, phone) VALUES
('Анна Петрова', 'Йога, Пилатес', 'Сертифицированный инструктор по йоге с 8-летним опытом. Специализируется на хатха-йоге и пилатесе для начинающих и продвинутых.', 8, 'anna@fitlab.ru', '+7 (495) 123-45-01'),
('Михаил Сидоров', 'CrossFit, Функциональный тренинг', 'Мастер спорта по тяжелой атлетике. Тренер высшей категории по CrossFit с международной сертификацией.', 12, 'mikhail@fitlab.ru', '+7 (495) 123-45-02'),
('Елена Козлова', 'Фитнес, Аэробика', 'Персональный тренер с опытом работы более 10 лет. Специалист по коррекции фигуры и жиросжиганию.', 10, 'elena@fitlab.ru', '+7 (495) 123-45-03'),
('Дмитрий Волков', 'Силовые тренировки, Бодибилдинг', 'Чемпион России по бодибилдингу. Эксперт в области набора мышечной массы и силовых показателей.', 15, 'dmitry@fitlab.ru', '+7 (495) 123-45-04'),
('Ольга Морозова', 'Танцы, Зумба', 'Хореограф и фитнес-инструктор. Создает позитивную атмосферу на тренировках, помогает раскрепоститься и получить удовольствие от движения.', 6, 'olga@fitlab.ru', '+7 (495) 123-45-05');

-- Добавление абонементов
INSERT INTO memberships (name, description, price, duration_days, features) VALUES
('Базовый', 'Доступ в тренажерный зал в рабочие дни с 9:00 до 17:00', 3500.00, 30, 'Тренажерный зал|Раздевалки|Душевые'),
('Стандартный', 'Полный доступ в тренажерный зал + 4 групповых занятия в месяц', 5500.00, 30, 'Тренажерный зал|Групповые занятия (4 в месяц)|Раздевалки|Душевые|Сауна'),
('Премиум', 'Безлимитный доступ ко всем услугам клуба', 8500.00, 30, 'Тренажерный зал|Безлимитные групповые занятия|Персональная тренировка|Раздевалки|Душевые|Сауна|Массаж'),
('Годовой VIP', 'Годовой абонемент с максимальными привилегиями', 85000.00, 365, 'Все услуги клуба|Персональный тренер|Индивидуальная программа питания|Приоритетная запись|Гостевые визиты');

-- Добавление программ
INSERT INTO programs (name, description, trainer_id, duration_minutes, max_participants, difficulty_level) VALUES
('Хатха-йога для начинающих', 'Мягкая практика йоги, направленная на развитие гибкости и расслабление', 1, 60, 15, 'beginner'),
('CrossFit WOD', 'Высокоинтенсивная функциональная тренировка дня', 2, 45, 12, 'advanced'),
('Фитнес-аэробика', 'Кардио-тренировка под музыку для сжигания калорий', 3, 50, 20, 'intermediate'),
('Силовая тренировка', 'Работа с весами для развития силы и мышечной массы', 4, 60, 8, 'intermediate'),
('Зумба', 'Танцевальная фитнес-программа в латиноамериканском стиле', 5, 45, 25, 'beginner'),
('Пилатес', 'Система упражнений для укрепления мышц кора', 1, 55, 12, 'intermediate'),
('Функциональный тренинг', 'Тренировка с использованием собственного веса и функциональных движений', 2, 50, 15, 'intermediate');

-- Добавление расписания на неделю
INSERT INTO schedule (program_id, trainer_id, date_time, available_spots) VALUES
-- Понедельник
(1, 1, '2024-12-16 09:00:00', 15),
(3, 3, '2024-12-16 10:00:00', 20),
(2, 2, '2024-12-16 18:00:00', 12),
(4, 4, '2024-12-16 19:00:00', 8),
-- Вторник
(5, 5, '2024-12-17 10:00:00', 25),
(6, 1, '2024-12-17 11:00:00', 12),
(7, 2, '2024-12-17 18:30:00', 15),
-- Среда
(1, 1, '2024-12-18 09:00:00', 15),
(3, 3, '2024-12-18 10:00:00', 20),
(2, 2, '2024-12-18 18:00:00', 12),
-- Четверг
(4, 4, '2024-12-19 19:00:00', 8),
(5, 5, '2024-12-19 10:00:00', 25),
(6, 1, '2024-12-19 11:00:00', 12),
-- Пятница
(7, 2, '2024-12-20 18:30:00', 15),
(1, 1, '2024-12-20 09:00:00', 15),
(3, 3, '2024-12-20 10:00:00', 20);
