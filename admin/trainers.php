<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';
$message_type = '';

// Добавление тренера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $hire_date = trim($_POST['hire_date']);

    if (empty($full_name)) {
        $message = 'Поле "Полное имя" обязательно для заполнения.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Trainers (full_name, phone_number, email, specialization, hire_date) 
                                    VALUES (:full_name, :phone_number, :email, :specialization, :hire_date)");
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone_number' => $phone_number,
                ':email' => $email,
                ':specialization' => $specialization,
                ':hire_date' => $hire_date
            ]);
            $message = 'Тренер успешно добавлен.';
            $message_type = 'success';
            $_POST = [];
        } catch (PDOException $e) {
            $message = 'Ошибка при добавлении тренера: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Редактирование тренера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_trainer'])) {
    $trainer_id = (int)$_POST['trainer_id'];
    $full_name = trim($_POST['edit_full_name']);
    $phone_number = trim($_POST['edit_phone_number']);
    $email = trim($_POST['edit_email']);
    $specialization = trim($_POST['edit_specialization']);
    $hire_date = trim($_POST['edit_hire_date']);

    if (empty($full_name)) {
        $message = 'Поле "Полное имя" обязательно для заполнения.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE Trainers SET full_name = :full_name, phone_number = :phone_number, 
                                        email = :email, specialization = :specialization, hire_date = :hire_date 
                                    WHERE trainer_id = :trainer_id");
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone_number' => $phone_number,
                ':email' => $email,
                ':specialization' => $specialization,
                ':hire_date' => $hire_date,
                ':trainer_id' => $trainer_id
            ]);
            $message = 'Данные тренера обновлены.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Ошибка при обновлении: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Удаление тренера
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM Trainers WHERE trainer_id = :id");
        $stmt->execute([':id' => $_GET['delete']]);
        $message = 'Тренер удален.';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Ошибка при удалении: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Получение списка тренеров
try {
    $stmt = $conn->query("SELECT * FROM Trainers ORDER BY hire_date DESC");
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trainers = [];
    $message = 'Ошибка загрузки данных: ' . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тренеры - FitLab Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="../index.php" class="logo">FitLab Admin</a>
            <nav class="nav">
                <ul>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="users.php">Пользователи</a></li>
                    <li><a href="trainers.php">Тренеры</a></li>
                    <li><a href="programs.php">Программы</a></li>
                    <li><a href="workouts.php">Тренировки</a></li>
                    <li><a href="schedules.php">Расписание</a></li>
                    <li><a href="memberships.php">Абонементы</a></li>
                    <li><a href="clients.php">Отчеты</a></li>
                    <li><a href="../views.php">Представления</a></li>
                    <li><a href="../index.php">На сайт</a></li>
                    <li><a href="../logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <h1>Тренеры</h1>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Добавление -->
                <div class="card">
                    <h3>Добавить тренера</h3>
                    <form method="POST">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="full_name">Полное имя *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Телефон</label>
                                <input type="tel" id="phone_number" name="phone_number" class="form-control"
                                       value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="specialization">Специализация</label>
                                <input type="text" id="specialization" name="specialization" class="form-control"
                                       value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="hire_date">Дата найма</label>
                                <input type="date" id="hire_date" name="hire_date" class="form-control"
                                       value="<?php echo isset($_POST['hire_date']) ? htmlspecialchars($_POST['hire_date']) : ''; ?>">
                            </div>
                        </div>
                        <button type="submit" name="add_trainer" class="btn btn-primary">Добавить</button>
                    </form>
                </div>

                <!-- Список -->
                <div class="card">
                    <h3>Список тренеров (<?php echo count($trainers); ?>)</h3>
                    <?php if (!$trainers): ?>
                        <p>Нет данных</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Полное имя</th>
                                        <th>Телефон</th>
                                        <th>Email</th>
                                        <th>Специализация</th>
                                        <th>Дата найма</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <tr>
                                            <td><?php echo $trainer['trainer_id']; ?></td>
                                            <td><?php echo htmlspecialchars($trainer['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['phone_number'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['email'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['specialization'] ?: '-'); ?></td>
                                            <td><?php echo $trainer['hire_date'] ? date('d.m.Y', strtotime($trainer['hire_date'])) : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-right: 0.5rem;"
                                                        onclick="editTrainer(<?php echo htmlspecialchars(json_encode($trainer), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    Редактировать
                                                </button>
                                                <a href="?delete=<?php echo $trainer['trainer_id']; ?>" 
                                                   onclick="return confirm('Удалить?')" 
                                                   class="btn btn-secondary" 
                                                   style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                                                    Удалить
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Модальное окно -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
            <h3>Редактировать тренера</h3>
            <form method="POST">
                <input type="hidden" id="edit_trainer_id" name="trainer_id">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="edit_full_name">Полное имя *</label>
                        <input type="text" id="edit_full_name" name="edit_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone_number">Телефон</label>
                        <input type="tel" id="edit_phone_number" name="edit_phone_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_specialization">Специализация</label>
                        <input type="text" id="edit_specialization" name="edit_specialization" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_hire_date">Дата найма</label>
                        <input type="date" id="edit_hire_date" name="edit_hire_date" class="form-control">
                    </div>
                </div>
                <button type="submit" name="edit_trainer" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 FitLab Admin Panel. Все права защищены.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        function editTrainer(trainer) {
            document.getElementById('edit_trainer_id').value = trainer.trainer_id;
            document.getElementById('edit_full_name').value = trainer.full_name;
            document.getElementById('edit_phone_number').value = trainer.phone_number || '';
            document.getElementById('edit_email').value = trainer.email || '';
            document.getElementById('edit_specialization').value = trainer.specialization || '';
            document.getElementById('edit_hire_date').value = trainer.hire_date || '';
            document.getElementById('editModal').style.display = 'block';
        }

        // Закрытие модального окна
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>
