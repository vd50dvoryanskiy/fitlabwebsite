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

$message = '';
$message_type = '';

// Обработка добавления типа тренировки
if ($_POST && isset($_POST['add_workout'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $program_id = $_POST['program_id'] ?: null;
    
    if (empty($name)) {
        $message = 'Название тренировки обязательно';
        $message_type = 'error';
    } else {
        try {
            $query = "INSERT INTO WorkoutTypes (name, description, program_id) VALUES (:name, :description, :program_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':program_id', $program_id);
            
            if ($stmt->execute()) {
                $message = 'Тип тренировки успешно добавлен';
                $message_type = 'success';
                $_POST = [];
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'Тренировка с таким названием уже существует';
            } else {
                $message = 'Ошибка добавления тренировки';
            }
            $message_type = 'error';
        }
    }
}

// Обработка редактирования типа тренировки
if ($_POST && isset($_POST['edit_workout'])) {
    $workout_type_id = $_POST['workout_type_id'];
    $name = trim($_POST['edit_name']);
    $description = trim($_POST['edit_description']);
    $program_id = $_POST['edit_program_id'] ?: null;
    
    if (empty($name)) {
        $message = 'Название тренировки обязательно';
        $message_type = 'error';
    } else {
        try {
            $query = "UPDATE WorkoutTypes SET name = :name, description = :description, program_id = :program_id WHERE workout_type_id = :workout_type_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':program_id', $program_id);
            $stmt->bindParam(':workout_type_id', $workout_type_id);
            
            if ($stmt->execute()) {
                $message = 'Тип тренировки успешно обновлен';
                $message_type = 'success';
            }
        } catch(PDOException $e) {
            $message = 'Ошибка обновления тренировки';
            $message_type = 'error';
        }
    }
}

// Обработка добавления зала
if ($_POST && isset($_POST['add_room'])) {
    $name = trim($_POST['room_name']);
    $capacity = $_POST['capacity'] ?: null;
    
    if (empty($name)) {
        $message = 'Название зала обязательно';
        $message_type = 'error';
    } else {
        try {
            $query = "INSERT INTO Rooms (name, capacity) VALUES (:name, :capacity)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':capacity', $capacity);
            
            if ($stmt->execute()) {
                $message = 'Зал успешно добавлен';
                $message_type = 'success';
                $_POST = [];
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'Зал с таким названием уже существует';
            } else {
                $message = 'Ошибка добавления зала';
            }
            $message_type = 'error';
        }
    }
}

// Обработка редактирования зала
if ($_POST && isset($_POST['edit_room'])) {
    $room_id = $_POST['room_id'];
    $name = trim($_POST['edit_room_name']);
    $capacity = $_POST['edit_capacity'] ?: null;
    
    if (empty($name)) {
        $message = 'Название зала обязательно';
        $message_type = 'error';
    } else {
        try {
            $query = "UPDATE Rooms SET name = :name, capacity = :capacity WHERE room_id = :room_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':capacity', $capacity);
            $stmt->bindParam(':room_id', $room_id);
            
            if ($stmt->execute()) {
                $message = 'Зал успешно обновлен';
                $message_type = 'success';
            }
        } catch(PDOException $e) {
            $message = 'Ошибка обновления зала';
            $message_type = 'error';
        }
    }
}

// Обработка удаления
if (isset($_GET['delete_workout']) && is_numeric($_GET['delete_workout'])) {
    try {
        $query = "DELETE FROM WorkoutTypes WHERE workout_type_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_GET['delete_workout']);
        
        if ($stmt->execute()) {
            $message = 'Тип тренировки успешно удален';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Ошибка удаления тренировки';
        $message_type = 'error';
    }
}

if (isset($_GET['delete_room']) && is_numeric($_GET['delete_room'])) {
    try {
        $query = "DELETE FROM Rooms WHERE room_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_GET['delete_room']);
        
        if ($stmt->execute()) {
            $message = 'Зал успешно удален';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Ошибка удаления зала';
        $message_type = 'error';
    }
}

// Получаем данные
try {
    $workoutQuery = "SELECT wt.*, p.name as program_name FROM WorkoutTypes wt LEFT JOIN Programs p ON wt.program_id = p.program_id ORDER BY wt.name";
    $stmt = $conn->prepare($workoutQuery);
    $stmt->execute();
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $roomQuery = "SELECT * FROM Rooms ORDER BY name";
    $stmt = $conn->prepare($roomQuery);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $programQuery = "SELECT * FROM Programs ORDER BY name";
    $stmt = $conn->prepare($programQuery);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $workouts = [];
    $rooms = [];
    $programs = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление тренировками - FitLab Admin</title>
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
                <h1>Управление тренировками и залами</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-2">
                    <!-- Типы тренировок -->
                    <div class="card">
                        <h3>Добавить тип тренировки</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="name">Название *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="program_id">Программа</label>
                                <select id="program_id" name="program_id" class="form-control">
                                    <option value="">Выберите программу</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?php echo $program['program_id']; ?>" 
                                                <?php echo (isset($_POST['program_id']) && $_POST['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Описание</label>
                                <textarea id="description" name="description" class="form-control" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <button type="submit" name="add_workout" class="btn btn-primary">Добавить тренировку</button>
                        </form>
                    </div>

                    <!-- Залы -->
                    <div class="card">
                        <h3>Добавить зал</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="room_name">Название зала *</label>
                                <input type="text" id="room_name" name="room_name" class="form-control" 
                                       value="<?php echo isset($_POST['room_name']) ? htmlspecialchars($_POST['room_name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="capacity">Вместимость</label>
                                <input type="number" id="capacity" name="capacity" class="form-control" 
                                       value="<?php echo isset($_POST['capacity']) ? htmlspecialchars($_POST['capacity']) : ''; ?>" min="1">
                            </div>
                            
                            <button type="submit" name="add_room" class="btn btn-primary">Добавить зал</button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-2">
                    <!-- Список тренировок -->
                    <div class="card">
                        <h3>Типы тренировок (<?php echo count($workouts); ?>)</h3>
                        
                        <?php if (empty($workouts)): ?>
                            <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                                Типы тренировок не найдены
                            </p>
                        <?php else: ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($workouts as $workout): ?>
                                    <div style="padding: 1rem; border-bottom: 1px solid #555; display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($workout['name']); ?></h4>
                                            <p style="margin: 0; color: var(--medium-gray); font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($workout['description'] ?: 'Описание не указано'); ?>
                                            </p>
                                            <?php if ($workout['program_name']): ?>
                                                <p style="margin: 0.5rem 0 0 0; color: var(--accent-blue); font-size: 0.8rem;">
                                                    Программа: <?php echo htmlspecialchars($workout['program_name']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;" 
                                                    onclick="editWorkout(<?php echo htmlspecialchars(json_encode($workout)); ?>)">
                                                Изменить
                                            </button>
                                            <a href="?delete_workout=<?php echo $workout['workout_type_id']; ?>" 
                                               class="btn btn-secondary" 
                                               style="font-size: 0.8rem; padding: 0.5rem 1rem;"
                                               onclick="return confirm('Вы уверены, что хотите удалить этот тип тренировки?')">
                                                Удалить
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Список залов -->
                    <div class="card">
                        <h3>Залы (<?php echo count($rooms); ?>)</h3>
                        
                        <?php if (empty($rooms)): ?>
                            <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                                Залы не найдены
                            </p>
                        <?php else: ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($rooms as $room): ?>
                                    <div style="padding: 1rem; border-bottom: 1px solid #555; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($room['name']); ?></h4>
                                            <p style="margin: 0; color: var(--medium-gray); font-size: 0.9rem;">
                                                Вместимость: <?php echo $room['capacity'] ?: 'Не указана'; ?> человек
                                            </p>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;" 
                                                    onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                                Изменить
                                            </button>
                                            <a href="?delete_room=<?php echo $room['room_id']; ?>" 
                                               class="btn btn-secondary" 
                                               style="font-size: 0.8rem; padding: 0.5rem 1rem;"
                                               onclick="return confirm('Вы уверены, что хотите удалить этот зал?')">
                                                Удалить
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Модальное окно редактирования тренировки -->
    <div id="editWorkoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editWorkoutModal')">&times;</span>
            <h3>Редактировать тип тренировки</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_workout_type_id" name="workout_type_id">
                <div class="form-group">
                    <label for="edit_name">Название *</label>
                    <input type="text" id="edit_name" name="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_program_id">Программа</label>
                    <select id="edit_program_id" name="edit_program_id" class="form-control">
                        <option value="">Выберите программу</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['program_id']; ?>">
                                <?php echo htmlspecialchars($program['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Описание</label>
                    <textarea id="edit_description" name="edit_description" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" name="edit_workout" class="btn btn-primary">Сохранить изменения</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования зала -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editRoomModal')">&times;</span>
            <h3>Редактировать зал</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_room_id" name="room_id">
                <div class="form-group">
                    <label for="edit_room_name">Название зала *</label>
                    <input type="text" id="edit_room_name" name="edit_room_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_capacity">Вместимость</label>
                    <input type="number" id="edit_capacity" name="edit_capacity" class="form-control" min="1">
                </div>
                
                <button type="submit" name="edit_room" class="btn btn-primary">Сохранить изменения</button>
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
        function editWorkout(workout) {
            document.getElementById('edit_workout_type_id').value = workout.workout_type_id;
            document.getElementById('edit_name').value = workout.name;
            document.getElementById('edit_description').value = workout.description || '';
            document.getElementById('edit_program_id').value = workout.program_id || '';
            
            document.getElementById('editWorkoutModal').style.display = 'block';
        }

        function editRoom(room) {
            document.getElementById('edit_room_id').value = room.room_id;
            document.getElementById('edit_room_name').value = room.name;
            document.getElementById('edit_capacity').value = room.capacity || '';
            
            document.getElementById('editRoomModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Закрытие модальных окон при клике вне их
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
