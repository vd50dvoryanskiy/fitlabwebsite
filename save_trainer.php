<?php
session_start();
require_once '../config/database.php';

$conn = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainer_id = $_POST['trainer_id'] ?? null;
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);

    // Проверка обязательных полей
    if (empty($full_name)) {
        die("Имя тренера обязательно");
    }

    try {
        // Получаем текущее изображение, если оно существует
        $currentImage = null;
        if ($trainer_id) {
            $stmt = $conn->prepare("SELECT profile_image FROM Trainers WHERE trainer_id = ?");
            $stmt->execute([$trainer_id]);
            $currentImage = $stmt->fetchColumn();
        }

        // Загрузка нового изображения
        $uploadDir = '../uploads/trainers/';
        $imageName = $currentImage;

        if (!empty($_FILES['profile_image']['name'])) {
            $image = $_FILES['profile_image'];
            $fileName = uniqid() . '_' . basename($image['name']);
            $targetPath = $uploadDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

            // Разрешенные типы файлов
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                    // Удаляем старое фото, если оно было
                    if ($currentImage && file_exists($uploadDir . $currentImage)) {
                        unlink($uploadDir . $currentImage);
                    }
                    $imageName = $fileName;
                } else {
                    die("Ошибка при загрузке файла");
                }
            } else {
                die("Недопустимый формат файла");
            }
        }

        // Если это новая запись
        if (!$trainer_id) {
            $query = "INSERT INTO Trainers (full_name, phone_number, email, specialization, profile_image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$full_name, $phone_number, $email, $specialization, $imageName]);
        } else {
            // Если это обновление существующей записи
            $query = "UPDATE Trainers SET full_name = ?, phone_number = ?, email = ?, specialization = ?, profile_image = ? WHERE trainer_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$full_name, $phone_number, $email, $specialization, $imageName, $trainer_id]);
        }

        header('Location: trainers_list.php');
        exit;

    } catch (PDOException $e) {
        die("Ошибка при сохранении данных: " . $e->getMessage());
    }
}
?>
