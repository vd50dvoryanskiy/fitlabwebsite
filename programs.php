<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

// Получаем все программы из базы данных
try {
    $query = "SELECT 
                  p.program_id,
                  p.name AS program_name,
                  p.description,
                  p.duration_minutes,
                  p.price,
                  p.difficulty_level,
                  COUNT(s.schedule_id) AS schedule_count
              FROM Programs p
              LEFT JOIN WorkoutTypes wt ON p.program_id = wt.program_id
              LEFT JOIN Schedules s ON wt.workout_type_id = s.workout_type_id
              GROUP BY p.program_id
              ORDER BY p.difficulty_level, p.name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Ошибка при получении программ: " . $e->getMessage());
    $programs = [];
}

// Группируем программы по уровню сложности
$programsByLevel = [
    'beginner' => [],
    'intermediate' => [],
    'advanced' => []
];

foreach ($programs as $program) {
    if (isset($program['difficulty_level']) && array_key_exists($program['difficulty_level'], $programsByLevel)) {
        $programsByLevel[$program['difficulty_level']][] = $program;
    } else {
        $programsByLevel['beginner'][] = $program;
    }
}

$levelNames = [
    'beginner' => 'Для начинающих',
    'intermediate' => 'Средний уровень',
    'advanced' => 'Продвинутый уровень'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Программы тренировок - FitLab</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <section class="hero programs-hero">
            <div class="container">
                <h1>Программы тренировок</h1>
                <p>Разнообразные программы для достижения ваших фитнес-целей под руководством профессиональных тренеров</p>
            </div>
        </section>

        <?php foreach ($programsByLevel as $level => $levelPrograms): ?>
            <?php if (!empty($levelPrograms)): ?>
                <section class="section" id="<?php echo $level; ?>">
                    <div class="container">
                        <h2 class="section-title"><?php echo $levelNames[$level]; ?></h2>
                        <div class="grid grid-2">
                            <?php foreach ($levelPrograms as $program): ?>
                                <div class="card program-card">
                                    <div class="program-header">
                                        <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                        <span class="badge badge-<?php echo $program['difficulty_level']; ?>">
                                            <?php echo $levelNames[$program['difficulty_level']]; ?>
                                        </span>
                                    </div>
                                    
                                    <p class="program-description"><?php echo htmlspecialchars($program['description']); ?></p>
                                    
                                    <div class="program-details">
                                        <div class="program-info-row">
                                            <span><strong>Длительность:</strong> <?php echo htmlspecialchars($program['duration_minutes']); ?> мин</span>
                                            <span><strong>Макс. участников:</strong> <?php echo htmlspecialchars($program['max_participants']); ?></span>
                                        </div>
                                        <div class="program-price">
                                            <strong>Цена:</strong> <?php echo number_format($program['price'], 0, ',', ' '); ?> ₽
                                        </div>
                                        <div class="program-schedule-count">
                                            <small>
                                                Доступно расписаний: <?php echo $program['schedule_count']; ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="program-actions">
                                        <a href="schedule.php?program_id=<?php echo $program['program_id']; ?>" class="btn btn-primary">Расписание</a>
                                        <?php if ($user): ?>
                                            <button class="btn btn-secondary" onclick="showProgramModal(<?php echo $program['program_id']; ?>)">Записаться</button>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-secondary">Войти для записи</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($programs)): ?>
            <section class="section">
                <div class="container">
                    <div class="card">
                        <p class="no-data-message">
                            Программы тренировок пока не добавлены. Скоро здесь появятся интересные предложения!
                        </p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
<!-- Модальное окно -->
<div id="programModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeProgramModal()">&times;</span>
        <h2>Подтвердите запись</h2>
        <p>Вы действительно хотите записаться на эту программу?</p>
        <form method="POST" action="enroll_program.php">
            <input type="hidden" id="program_id_input" name="program_id">
            <button type="submit" class="btn btn-primary">Да, записаться</button>
            <button type="button" class="btn btn-secondary" onclick="closeProgramModal()">Отмена</button>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
}
.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}
</style>

<!-- ✅ Скрипт должен быть вне <style> -->
<script>
function showProgramModal(programId) {
    document.getElementById('program_id_input').value = programId;
    document.getElementById('programModal').style.display = 'block';
}

function closeProgramModal() {
    document.getElementById('programModal').style.display = 'none';
}

// Закрытие по клику вне окна
window.onclick = function(event) {
    const modal = document.getElementById('programModal');
    if (event.target === modal) {
        closeProgramModal();
    }
}
</script>
</body>
</html>
