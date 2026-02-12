<?php
$dbPath = dirname(__DIR__) . "/data/links.sqlite";

function h($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

$notice = "";
$error = "";

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        )"
    );

    $username = "david";
    $password = "4l3xisthebest!";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([":username" => $username]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            "INSERT INTO users (username, password_hash, created_at) VALUES (:username, :hash, :created_at)"
        );
        $insert->execute([
            ":username" => $username,
            ":hash" => $hash,
            ":created_at" => date("c")
        ]);
        $notice = "User created.";
    } else {
        $notice = "User already exists.";
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Users Setup</title>
    <style>
      body {
        font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        background: #0b0d10;
        color: #f7f5f0;
        margin: 0;
        padding: 32px 20px;
      }
      .card {
        max-width: 640px;
        margin: 0 auto;
        background: #171c22;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 16px;
        padding: 20px;
        display: grid;
        gap: 12px;
      }
      .notice {
        padding: 12px 14px;
        border-radius: 12px;
        background: rgba(246, 184, 87, 0.18);
        border: 1px solid rgba(246, 184, 87, 0.4);
      }
      .error {
        padding: 12px 14px;
        border-radius: 12px;
        background: rgba(255, 96, 96, 0.15);
        border: 1px solid rgba(255, 96, 96, 0.35);
      }
      code {
        background: #0d1116;
        padding: 2px 6px;
        border-radius: 6px;
      }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Admin User Setup</h1>
      <?php if ($notice) : ?>
      <div class="notice"><?php echo h($notice); ?></div>
      <?php endif; ?>
      <?php if ($error) : ?>
      <div class="error"><?php echo h($error); ?></div>
      <?php endif; ?>
      <p>Database: <code><?php echo h($dbPath); ?></code></p>
    </div>
  </body>
</html>
