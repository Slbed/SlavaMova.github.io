<?php
// Настройки подключения к базе данных
$db_host = 'localhost';
$db_name = 'house_plants';
$db_user = 'root';
$db_pass = '';

// Подключение к базе
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем существование таблицы
    $tableExists = $pdo->query("SHOW TABLES LIKE 'testimonials'")->rowCount() > 0;
    if (!$tableExists) {
        $pdo->exec("CREATE TABLE testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            rating INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Обработка формы
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    // Валидация
    if (empty($name)) $errors[] = 'Укажите ваше имя';
    if (empty($message)) $errors[] = 'Напишите текст отзыва';
    if ($rating < 1 || $rating > 5) $errors[] = 'Выберите оценку от 1 до 5';
    
    // Если ошибок нет - сохраняем в БД
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO testimonials (name, rating, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $rating, $message]);
            $success = 'Спасибо! Ваш отзыв успешно отправлен.';
            
            // Перенаправление чтобы избежать повторной отправки формы
            header("Location: work.php?success=1");
            exit();
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при сохранении: ' . $e->getMessage();
        }
    }
}

// Получение отзывов из БД
try {
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 10");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonials = [];
    $errors[] = 'Ошибка при загрузке отзывов: ' . $e->getMessage();
}

// Проверяем успешное сохранение
if (isset($_GET['success'])) {
    $success = 'Спасибо! Ваш отзыв успешно отправлен.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#2e8b57">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Отзывы | House Plants</title>
  <link rel="stylesheet" href="css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <header class="header">
    <div class="container">
      <a href="index.html" class="logo">House Plants</a>
      <nav class="navigation">
        <a href="about.html">ABOUT US</a>
        <a href="work.php" class="active">REVIEWS</a>
        <a href="index.html#contacts">CONTACT</a>
      </nav>
    </div>
  </header>

  <section class="work-hero">
    <div class="container">
      <h1>Ваши отзывы</h1>
      <p>Мы ценим каждое ваше мнение</p>
    </div>
  </section>

  <main class="testimonial-section">
    <div class="testimonial-container">
      <form action="work.php" method="POST" class="testimonial-form">
        <h2>Оставьте свой отзыв</h2>
        
        <?php if (!empty($errors)): ?>
          <div class="error">
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
          <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="form-group">
          <label for="name">Ваше имя:</label>
          <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
          <label for="rating">Ваша оценка:</label>
          <select id="rating" name="rating" required>
            <option value="" disabled selected>Выберите оценку</option>
            <option value="5" <?= ($_POST['rating'] ?? 0) == 5 ? 'selected' : '' ?>>Отлично (5 звезд)</option>
            <option value="4" <?= ($_POST['rating'] ?? 0) == 4 ? 'selected' : '' ?>>Хорошо (4 звезды)</option>
            <option value="3" <?= ($_POST['rating'] ?? 0) == 3 ? 'selected' : '' ?>>Удовлетворительно (3 звезды)</option>
            <option value="2" <?= ($_POST['rating'] ?? 0) == 2 ? 'selected' : '' ?>>Плохо (2 звезды)</option>
            <option value="1" <?= ($_POST['rating'] ?? 0) == 1 ? 'selected' : '' ?>>Ужасно (1 звезда)</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="message">Текст отзыва:</label>
          <textarea id="message" name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Отправить отзыв</button>
      </form>

      <h2 style="text-align: center; color: var(--primary-color); font-family: 'Playfair Display', serif;">Последние отзывы</h2>
      <div class="testimonials-grid">
        <?php if (!empty($testimonials)): ?>
          <?php foreach ($testimonials as $item): ?>
            <div class="testimonial-card">
              <div class="testimonial-meta">
                <span class="testimonial-author"><?= htmlspecialchars($item['name']) ?></span>
                <span class="testimonial-rating">
                  <?php for ($i = 0; $i < 5; $i++): ?>
                    <i class="fas fa-star<?= $i < $item['rating'] ? '' : '-o' ?>"></i>
                  <?php endfor; ?>
                </span>
              </div>
              <div class="testimonial-text"><?= nl2br(htmlspecialchars($item['message'])) ?></div>
              <?php if (!empty($item['created_at'])): ?>
                <div class="testimonial-date">
                  <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="text-align: center;">Пока нет отзывов. Будьте первым!</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script src="js/app.js"></script>
</body>
</html>