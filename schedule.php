<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

// Получаем фильтры
$program_filter = $_GET['program_id'] ?? '';
$trainer_filter = $_GET['trainer_id'] ?? '';

// Получаем расписание из базы данных
try {
    $query = "SELECT 
                s.schedule_id AS id, 
                s.schedule_date, 
                s.start_time,
                s.end_time,
                s.available_spots,
                s.booked_spots,a
                s.max_participants,
                wt.name AS workout_name, 
                wt.difficulty_level, 
                p.name AS program_name,
                p.duration_minutes,
                p.price,
                t.full_name AS trainer_name,
                r.name AS room_name,
                (s.available_spots - s.booked_spots) AS free_spots
              FROM 
                Schedules s
              JOIN 
                WorkoutTypes wt ON s.workout_type_id = wt.workout_type_id 
              JOIN 
                Programs p ON wt.program_id = p.program_id
              JOIN 
                Trainers t ON s.trainer_id = t.trainer_id 
              JOIN
                Rooms r ON s.room_id = r.room_id
              WHERE 
                s.is_active = 1 
                AND CONCAT(s.schedule_date, ' ', s.start_time) >= NOW()";
    
    // Добавляем фильтры
    if ($program_filter) {
        $query .= " AND p.program_id = :program_id";
    }
    if ($trainer_filter) {
        $query .= " AND t.trainer_id = :trainer_id";
    }
    
    $query .= " ORDER BY s.schedule_date, s.start_time";
    
    $stmt = $conn->prepare($query);
    
    if ($program_filter) {
        $stmt->bindParam(':program_id', $program_filter);
    }
    if ($trainer_filter) {
        $stmt->bindParam(':trainer_id', $trainer_filter);
    }
    
    $stmt->execute();
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Ошибка при получении расписания: " . $e->getMessage());
    $schedule = [];
}

// Группируем по дням
$scheduleByDay = [];
foreach ($schedule as $session) {
    $date = $session['schedule_date'];
    $dayName = date('l', strtotime($date));
    $dayNames = [
        'Monday' => 'Понедельник',
        'Tuesday' => 'Вторник', 
        'Wednesday' => 'Среда',
        'Thursday' => 'Четверг',
        'Friday' => 'Пятница',
        'Saturday' => 'Суббота',
        'Sunday' => 'Воскресенье'
    ];
    
    if (!isset($scheduleByDay[$date])) {
        $scheduleByDay[$date] = [
            'day_name' => $dayNames[$dayName],
            'date' => $date,
            'sessions' => []
        ];
    }
    $scheduleByDay[$date]['sessions'][] = $session;
}
ksort($scheduleByDay);

// Получаем данные для фильтров
try {
    $programsQuery = "SELECT * FROM Programs ORDER BY name";
    $stmt = $conn->prepare($programsQuery);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $trainersQuery = "SELECT * FROM Trainers ORDER BY full_name";
    $stmt = $conn->prepare($trainersQuery);
    $stmt->execute();
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $programs = [];
    $trainers = [];
}

$levelNames = [
    'beginner' => 'Начинающий',
    'intermediate' => 'Средний',
    'advanced' => 'Продвинутый'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <section class="hero schedule-hero">
            <div class="container">
                <h1>Расписание тренировок</h1>
                <p>Выберите удобное время для тренировок и записывайтесь онлайн</p>
            </div>
        </section>

        <section class="section filters-section">
            <div class="container">
                <div class="card">
                    <h3>Фильтры</h3>
                    <form method="GET" action="">
                        <div class="filters-container">
                            <div>
                                <label for="program_id">Программа:</label>
                                <select id="program_id" name="program_id" class="form-control filter-select">
                                    <option value="">Все программы</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?php echo $program['program_id']; ?>" 
                                                <?php echo $program_filter == $program['program_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="trainer_id">Тренер:</label>
                                <select id="trainer_id" name="trainer_id" class="form-control filter-select">
                                    <option value="">Все тренеры</option>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <option value="<?php echo $trainer['trainer_id']; ?>" 
                                                <?php echo $trainer_filter == $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($trainer['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Применить</button>
                            <a href="schedule.php" class="btn btn-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <?php if (empty($scheduleByDay)): ?>
                    <div class="card">
                        <p class="no-data-message">
                            <?php if ($program_filter || $trainer_filter): ?>
                                По выбранным фильтрам тренировки не найдены.
                            <?php else: ?>
                                На ближайшее время тренировки не запланированы.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scheduleByDay as $dayData): ?>
                        <div class="day-schedule">
                            <h2 class="day-title">
                                <?php echo $dayData['day_name']; ?>, <?php echo date('d.m.Y', strtotime($dayData['date'])); ?>
                            </h2>
                            
                            <div class="grid grid-2 schedule-grid">
                                <?php foreach ($dayData['sessions'] as $session): ?>
                                    <div class="card session-card">
                                        <div class="session-header">
                                            <div>
                                                <h3 class="session-title"><?php echo htmlspecialchars($session['program_name']); ?></h3>
                                                <p class="session-time">
                                                    <?php echo date('H:i', strtotime($session['start_time'])); ?> - 
                                                    <?php echo date('H:i', strtotime($session['end_time'])); ?>
                                                    (<?php echo htmlspecialchars($session['duration_minutes']); ?> мин)
                                                </p>
                                            </div>
                                            <span class="badge badge-<?php echo htmlspecialchars($session['difficulty_level']); ?>">
                                                <?php echo htmlspecialchars($levelNames[$session['difficulty_level']] ?? $session['difficulty_level']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="session-details">
                                            <p><strong>Тренер:</strong> <?php echo htmlspecialchars($session['trainer_name']); ?></p>
                                            <p><strong>Зал:</strong> <?php echo htmlspecialchars($session['room_name']); ?></p>
                                            <p><strong>Тип тренировки:</strong> <?php echo htmlspecialchars($session['workout_name']); ?></p>
                                            <?php if ($session['price']): ?>
                                                <p><strong>Цена:</strong> <?php echo number_format($session['price'], 0, ',', ' '); ?> ₽</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="session-availability">
                                            <div>
                                                <span class="availability-text <?php echo $session['free_spots'] > 0 ? 'available' : 'unavailable'; ?>">
                                                    <?php if ($session['free_spots'] > 0): ?>
                                                        Свободно мест: <?php echo $session['free_spots']; ?>
                                                    <?php else: ?>
                                                        Мест нет
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <small class="max-participants">
                                                Макс: <?php echo htmlspecialchars($session['max_participants']); ?> чел.
                                            </small>
                                        </div>
                                        
                                        <div class="session-actions">
                                            <?php if ($user): ?>
                                                <?php if ($session['free_spots'] > 0): ?>
                                                    <button class="btn btn-primary" onclick="bookSession(<?php echo $session['id']; ?>)">
                                                        Записаться
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-disabled" disabled>
                                                        Мест нет
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-primary">Войти для записи</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="section rules-section">
            <div class="container">
                <h2 class="section-title">Правила записи</h2>
                <div class="grid grid-2">
                    <div class="card">
                        <h3>Как записаться</h3>
                        <ul class="rules-list">
                            <li>Зарегистрируйтесь на сайте или войдите в личный кабинет</li>
                            <li>Выберите подходящую тренировку в расписании</li>
                            <li>Нажмите кнопку "Записаться"</li>
                            <li>Подтвердите запись</li>
                        </ul>
                    </div>
                    <div class="card">
                        <h3>Важная информация</h3>
                        <ul class="rules-list">
                            <li>Отменить запись можно за 2 часа до начала</li>
                            <li>Приходите за 10-15 минут до начала тренировки</li>
                            <li>При опоздании более чем на 10 минут вход на тренировку не разрешается</li>
                            <li>Обязательно имейте при себе полотенце и воду</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        // Запись на тренировку
        function bookSession(sessionId) {
            if (!confirm('Вы уверены, что хотите записаться на эту тренировку?')) {
                return;
            }
            
            fetch('ajax/book-session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Успех: ' + data.message);
                    setTimeout(() => {
                        location.reload(); 
                    }, 1000);
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                alert('Произошла ошибка при записи');
                console.error('Ошибка при бронировании:', error);
            });
        }
    </script>
</body>
</html>
