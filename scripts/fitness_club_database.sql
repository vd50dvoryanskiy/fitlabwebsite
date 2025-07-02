-- Создание базы данных фитнес-клуба
CREATE DATABASE IF NOT EXISTS fitness_club_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitness_club_db;

-- Таблица для клиентов
CREATE TABLE IF NOT EXISTS Clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(255),
    registration_date DATE DEFAULT (CURRENT_DATE),
    gender ENUM('Мужской', 'Женский') NOT NULL,
    birth_date DATE
);

-- Таблица для тренеров
CREATE TABLE IF NOT EXISTS Trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(255),
    specialization VARCHAR(100),
    hire_date DATE DEFAULT (CURRENT_DATE),
    photo VARCHAR(255)
);

-- Таблица для типов тренировок
CREATE TABLE IF NOT EXISTS WorkoutTypes (
    workout_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- Таблица для залов
CREATE TABLE IF NOT EXISTS Rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT
);

-- Таблица для расписания тренировок
CREATE TABLE IF NOT EXISTS Schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    workout_type_id INT NOT NULL,
    trainer_id INT NOT NULL,
    room_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_participants INT,
    FOREIGN KEY (workout_type_id) REFERENCES WorkoutTypes(workout_type_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES Trainers(trainer_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE
);

-- Таблица для абонементов
CREATE TABLE IF NOT EXISTS Memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    membership_type VARCHAR(100) NOT NULL,
    purchase_date DATE DEFAULT (CURRENT_DATE),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (client_id) REFERENCES Clients(client_id) ON DELETE CASCADE
);

-- Таблица для посещений тренировок
CREATE TABLE IF NOT EXISTS WorkoutAttendances (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    schedule_id INT NOT NULL,
    attendance_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Clients(client_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES Schedules(schedule_id) ON DELETE CASCADE
);

-- Таблица пользователей системы
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    full_name VARCHAR(255),
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- Добавление администратора по умолчанию
INSERT INTO Users (username, password_hash, email, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@fitlab.ru', 'Администратор', 'admin')
ON DUPLICATE KEY UPDATE username = username;
