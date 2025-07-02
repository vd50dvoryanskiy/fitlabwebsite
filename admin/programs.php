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

// Обработка добавления программы
if ($_POST && isset($_POST['add_program'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $difficulty_level = $_POST['difficulty_level'];
    $duration_minutes = $_POST['duration_minutes'];
    $max_participants = $_POST['max_participants'];
    $price = $_POST['price'];
    
    if (empty($name) || empty($difficulty_level)) {
        $message = 'Название и уровень сложности обязательны';
        $message_type = 'error';
    } else {
        try {
            $query = "INSERT INTO Programs (name, description, difficulty_level, duration_minutes, max_participants, price) 
                     VALUES (:name, :description, :difficulty_level, :duration_minutes, :max_participants, :price)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':difficulty_level', $difficulty_level);
            $stmt->bindParam(':duration_minutes', $duration_minutes);
            $stmt->bindParam(':max_participants', $max_participants);
            $stmt->bindParam(':price', $price);
            
            if ($stmt->execute()) {
                $message = 'Программа успешно добавлена';
                $message_type = 'success';
                $_POST = [];
            }
        } catch(PDOException $e) {
            $message = 'Ошибка добавления программы: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка редактирования программы
if ($_POST && isset($_POST['edit_program'])) {
    $program_id = $_POST['program_id'];
    $name = trim($_POST['edit_name']);
    $description = trim($_POST['edit_description']);
    $difficulty_level = $_POST['edit_difficulty_level'];
    $duration_minutes = $_POST['edit_duration_minutes'];
    $max_participants = $_POST['edit_max_participants'];
    $price = $_POST['edit_price'];
    
    if (empty($name) || empty($difficulty_level)) {
        $message = 'Название и уровень сложности обязательны';
        $message_type = 'error';
    } else {
        try {
            $query = "UPDATE Programs SET name = :name, description = :description, difficulty_level = :difficulty_level, 
                     duration_minutes = :duration_minutes, max_participants = :max_participants, price = :price 
                     WHERE program_id = :program_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':difficulty_level', $difficulty_level);
            $stmt->bindParam(':duration_minutes', $duration_minutes);
            $stmt->bindParam(':max_participants', $max_participants);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':program_id', $program_id);
            
            if ($stmt->execute()) {
                $message = 'Программа успешно обновлена';
                $message_type = 'success';
            }
        } catch(PDOException $e) {
            $message = 'Ошибка обновления программы: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка удаления программы
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $query = "DELETE FROM Programs WHERE program_id = :program_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':program_id', $_GET['delete']);
        
        if ($stmt->execute()) {
            $message = 'Программа успешно удалена';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Ошибка удаления программы. Возможно, программа используется в расписании.';
        $message_type = 'error';
    }
}

// Получаем список программ
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM Schedules s 
                      JOIN WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id 
                      WHERE wt.program_id = p.program_id) as schedule_count
              FROM Programs p 
              ORDER BY p.difficulty_level, p.name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $programs = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление программами - FitLab Admin</title>
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
                    <li><a href="reports.php">Отчеты</a></li>
                    <li><a href="../index.php">На сайт</a></li>
                    <li><a href="../logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <section class="section">
            <div class="container">
                <h1>Управление программами тренировок</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Форма добавления программы -->
                <div class="card">
                    <h3>Добавить программу</h3>
                    <form method="POST" action="">
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label for="name">Название *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="difficulty_level">Уровень сложности *</label>
                                <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                                    <option value="">Выберите уровень</option>
                                    <option value="beginner" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] == 'beginner') ? 'selected' : ''; ?>>Начинающий</option>
                                    <option value="intermediate" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] == 'intermediate') ? 'selected' : ''; ?>>Средний</option>
                                    <option value="advanced" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] == 'advanced') ? 'selected' : ''; ?>>Продвинутый</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="duration_minutes">Длительность (мин)</label>
                                <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" 
                                       value="<?php echo isset($_POST['duration_minutes']) ? htmlspecialchars($_POST['duration_minutes']) : ''; ?>" min="15" max="180">
                            </div>
                            
                            <div class="form-group">
                                <label for="max_participants">Макс. участников</label>
                                <input type="number" id="max_participants" name="max_participants" class="form-control" 
                                       value="<?php echo isset($_POST['max_participants']) ? htmlspecialchars($_POST['max_participants']) : ''; ?>" min="1" max="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Цена (₽)</label>
                                <input type="number" id="price" name="price" class="form-control" 
                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Описание</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="add_program" class="btn btn-primary">Добавить программу</button>
                    </form>
                </div>

                <!-- Список программ -->
                <div class="card">
                    <h3>Список программ (<?php echo count($programs); ?>)</h3>
                    
                    <?php if (empty($programs)): ?>
                        <p style="text-align: center; color: var(--medium-gray); font-style: italic;">
                            Программы не найдены
                        </p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Уровень</th>
                                        <th>Длительность</th>
                                        <th>Макс. участников</th>
                                        <th>Цена</th>
                                        <th>В расписании</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programs as $program): ?>
                                        <tr>
                                            <td><?php echo $program['program_id']; ?></td>
                                            <td><?php echo htmlspecialchars($program['name']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $program['difficulty_level']; ?>">
                                                    <?php 
                                                    $levels = ['beginner' => 'Начинающий', 'intermediate' => 'Средний', 'advanced' => 'Продвинутый'];
                                                    echo $levels[$program['difficulty_level']] ?? $program['difficulty_level']; 
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo $program['duration_minutes'] ? $program['duration_minutes'] . ' мин' : '-'; ?></td>
                                            <td><?php echo $program['max_participants'] ?: '-'; ?></td>
                                            <td><?php echo $program['price'] ? number_format($program['price'], 0, ',', ' ') . ' ₽' : '-'; ?></td>
                                            <td><?php echo $program['schedule_count']; ?> занятий</td>
                                            <td>
                                                <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem; margin-right: 0.5rem;" 
                                                        onclick="editProgram(<?php echo htmlspecialchars(json_encode($program)); ?>)">
                                                    Изменить
                                                </button>
                                                <a href="?delete=<?php echo $program['program_id']; ?>" 
                                                   class="btn btn-secondary" 
                                                   style="font-size: 0.8rem; padding: 0.5rem 1rem;"
                                                   onclick="return confirm('Вы уверены, что хотите удалить эту программу?')">
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

    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Редактировать программу</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_program_id" name="program_id">
                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="edit_name">Название *</label>
                        <input type="text" id="edit_name" name="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_difficulty_level">Уровень сложности *</label>
                        <select id="edit_difficulty_level" name="edit_difficulty_level" class="form-control" required>
                            <option value="beginner">Начинающий</option>
                            <option value="intermediate">Средний</option>
                            <option value="advanced">Продвинутый</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_duration_minutes">Длительность (мин)</label>
                        <input type="number" id="edit_duration_minutes" name="edit_duration_minutes" class="form-control" min="15" max="180">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_max_participants">Макс. участников</label>
                        <input type="number" id="edit_max_participants" name="edit_max_participants" class="form-control" min="1" max="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_price">Цена (₽)</label>
                        <input type="number" id="edit_price" name="edit_price" class="form-control" min="0" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Описание</label>
                    <textarea id="edit_description" name="edit_description" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" name="edit_program" class="btn btn-primary">Сохранить изменения</button>
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
        function editProgram(program) {
            document.getElementById('edit_program_id').value = program.program_id;
            document.getElementById('edit_name').value = program.name;
            document.getElementById('edit_description').value = program.description || '';
            document.getElementById('edit_difficulty_level').value = program.difficulty_level;
            document.getElementById('edit_duration_minutes').value = program.duration_minutes || '';
            document.getElementById('edit_max_participants').value = program.max_participants || '';
            document.getElementById('edit_price').value = program.price || '';
            
            document.getElementById('editModal').style.display = 'block';
        }

        // Закрытие модального окна
        document.querySelector('.close').onclick = function() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>
