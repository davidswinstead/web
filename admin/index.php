<?php
session_start();

$rootDir = dirname(__DIR__);
$dbPath = $rootDir . "/data/links.sqlite";

function isLoggedIn()
{
    return isset($_SESSION["admin_user"]);
}

$login_error = "";

if (!isLoggedIn() && $_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    try {
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([":username" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["admin_user"] = $user["username"];
            $_SESSION["admin_id"] = $user["id"];
            header("Location: index.php");
            exit;
        } else {
            $login_error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $login_error = "Database error: " . $e->getMessage() . " - Did you visit /admin/users.php first?";
    } catch (Throwable $e) {
        $login_error = "Login error: " . $e->getMessage();
    }
}

if (isset($_GET["logout"])) {
    session_destroy();
    header("Refresh: 0");
    exit;
}

$loggedIn = isLoggedIn();

$GLOBALS["dbPath"] = $dbPath;

function h($value)
{
  return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function connectDatabase($dbPath)
{
  $pdo = new PDO("sqlite:" . $dbPath);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS links (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      date TEXT,
      title TEXT,
      subtitle TEXT,
      link TEXT,
      icon TEXT,
      position INTEGER
    )"
  );
  return $pdo;
}

function seedDatabase(PDO $pdo, $items)
{
  $pdo->beginTransaction();
  $stmt = $pdo->prepare(
    "INSERT INTO links (date, title, subtitle, link, icon, position)
     VALUES (:date, :title, :subtitle, :link, :icon, :position)"
  );
  foreach ($items as $index => $item) {
    $stmt->execute([
      ":date" => $item["date"] ?? "",
      ":title" => $item["title"] ?? "",
      ":subtitle" => $item["subtitle"] ?? "",
      ":link" => $item["link"] ?? "",
      ":icon" => $item["icon"] ?? "",
      ":position" => $index
    ]);
  }
  $pdo->commit();
}

function fetchLinks(PDO $pdo)
{
  $stmt = $pdo->query(
    "SELECT id, date, title, subtitle, link, icon
     FROM links
     ORDER BY
      CASE WHEN date IS NULL OR date = '' THEN 1 ELSE 0 END ASC,
      REPLACE(date, '/', '-') ASC,
      position ASC,
      id ASC"
  );
  return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function isUpcomingDate($dateString)
{
  if (!$dateString) {
    return true;
  }
  $normalized = preg_replace("/\D/", "", $dateString);
  if (strlen($normalized) < 8) {
    return true;
  }
  $today = date("Ymd");
  return $normalized >= $today;
}

function saveLinks(PDO $pdo, $items)
{
  $pdo->beginTransaction();
  $pdo->exec("DELETE FROM links");
  $stmt = $pdo->prepare(
    "INSERT INTO links (date, title, subtitle, link, icon, position)
     VALUES (:date, :title, :subtitle, :link, :icon, :position)"
  );
  foreach ($items as $index => $item) {
    $stmt->execute([
      ":date" => $item["date"],
      ":title" => $item["title"],
      ":subtitle" => $item["subtitle"],
      ":link" => $item["link"],
      ":icon" => $item["icon"],
      ":position" => $index
    ]);
  }
  $pdo->commit();
}

$notice = "";
$error = "";

try {
  $dbExists = file_exists($dbPath);
  $pdo = connectDatabase($dbPath);

  if (!$dbExists) {
    seedDatabase($pdo, []);
  }

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dates = $_POST["date"] ?? [];
    $titles = $_POST["title"] ?? [];
    $subtitles = $_POST["subtitle"] ?? [];
    $links = $_POST["link"] ?? [];
    $icons = $_POST["icon"] ?? [];
    $delete = array_flip($_POST["delete"] ?? []);

    $items = [];
    $count = max(count($titles), count($subtitles), count($links), count($icons), count($dates));

    for ($i = 0; $i < $count; $i++) {
      if (isset($delete[$i])) {
        continue;
      }

      $date = trim($dates[$i] ?? "");
      $title = trim($titles[$i] ?? "");
      $subtitle = trim($subtitles[$i] ?? "");
      $link = trim($links[$i] ?? "");
      $icon = trim($icons[$i] ?? "");

      if ($title === "" && $subtitle === "" && $link === "" && $icon === "" && $date === "") {
        continue;
      }

      $items[] = [
        "date" => $date,
        "title" => $title,
        "subtitle" => $subtitle,
        "link" => $link,
        "icon" => $icon
      ];
    }

    saveLinks($pdo, $items);
    $notice = "Saved successfully.";
  }

  $data = fetchLinks($pdo);
} catch (Throwable $exception) {
  $error = $exception->getMessage();
  $data = [];
}

if ($loggedIn) {
  $upcoming = array_values(array_filter($data, function ($item) {
    return isUpcomingDate($item["date"] ?? "");
  }));
  $past = array_values(array_filter($data, function ($item) {
    return !isUpcomingDate($item["date"] ?? "");
  }));
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Link Hub Admin</title>
    <style>
      :root {
        color-scheme: light dark;
        --bg: #0b0d10;
        --card: #171c22;
        --text: #f7f5f0;
        --muted: #d3cbbf;
        --accent: #f6b857;
        --border: rgba(255, 255, 255, 0.18);
      }
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        background: radial-gradient(circle at top, rgba(246, 184, 87, 0.16), transparent 40%), var(--bg);
        color: var(--text);
        padding: 32px 20px 64px;
      }
      body > main {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        gap: 24px;
      }
      header {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }
      h1 {
        margin: 0;
        font-size: 2rem;
      }
      p {
        margin: 0;
        color: var(--muted);
      }
      .notice {
        padding: 12px 16px;
        border-radius: 12px;
        background: rgba(246, 184, 87, 0.15);
        border: 1px solid rgba(246, 184, 87, 0.4);
        color: var(--text);
      }
      form {
        background: var(--card);
        border-radius: 18px;
        border: 1px solid var(--border);
        padding: 18px;
        display: grid;
        gap: 16px;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th, td {
        text-align: left;
        padding: 10px 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        vertical-align: top;
      }
      th {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
      }
      input[type="text"], input[type="url"], input[type="date"] {
        width: 100%;
        background: #0d1116;
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: var(--text);
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 0.92rem;
      }
      select {
        width: 100%;
        background: #0d1116;
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: var(--text);
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 0.92rem;
      }
      .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: space-between;
        align-items: center;
      }
      .btn {
        border: none;
        background: var(--accent);
        color: #1a1a1a;
        padding: 10px 16px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
      }
      .btn.secondary {
        background: transparent;
        color: var(--text);
        border: 1px solid rgba(255, 255, 255, 0.3);
      }
      .delete-cell {
        text-align: center;
      }
      .help {
        font-size: 0.85rem;
        color: var(--muted);
      }
      .help.inline {
        margin-top: 6px;
      }
      .login-card {
        max-width: 360px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 24px;
        display: grid;
        gap: 16px;
        margin: 0 auto;
      }
      .form-group {
        display: grid;
        gap: 6px;
      }
      .form-group label {
        font-size: 0.9rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .form-group input[type="text"],
      .form-group input[type="password"] {
        background: #0d1116;
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: var(--text);
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 0.95rem;
      }
      .form-group input:focus {
        outline: none;
        border-color: rgba(246, 184, 87, 0.6);
      }
      .login-error {
        background: rgba(255, 96, 96, 0.15);
        border: 1px solid rgba(255, 96, 96, 0.35);
        color: var(--text);
        padding: 12px 14px;
        border-radius: 12px;
        font-size: 0.9rem;
      }
      @media (max-width: 900px) {
        body {
          padding: 20px 14px 40px;
        }
        body > main {
          gap: 16px;
        }
        table, thead, tbody, th, td, tr {
          display: block;
        }
        thead {
          display: none;
        }
        form {
          padding: 0;
          border: none;
          background: transparent;
        }
        tr {
          border: 1px solid rgba(255, 255, 255, 0.14);
          border-radius: 12px;
          padding: 12px;
          margin-bottom: 12px;
          background: rgba(0, 0, 0, 0.16);
        }
        td {
          border-bottom: none;
          padding: 6px 0 10px;
        }
        td label {
          display: block;
          font-size: 0.75rem;
          text-transform: uppercase;
          color: var(--muted);
          margin-bottom: 6px;
        }
        .delete-cell {
          text-align: left;
        }
        .actions {
          flex-direction: column;
          align-items: stretch;
        }
        .btn {
          width: 100%;
        }
      }
      @media (max-width: 520px) {
        body {
          padding: 24px 16px 56px;
        }
        tr {
          padding: 10px;
        }
      }
    </style>
  </head>
  <body>
    <main>
      <?php if (!$loggedIn) : ?>
      <div class="login-card">
        <h1>Admin Login</h1>
        <?php if ($login_error) : ?>
        <div class="login-error"><?php echo h($login_error); ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required />
          </div>
          <button type="submit" class="btn">Login</button>
        </form>
      </div>
      <?php else : ?>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div></div>
        <a href="?logout=1" style="color: var(--muted); text-decoration: none; font-size: 0.9rem;">Logout</a>
      </div>
      <header>
        <h1>Link Hub Admin</h1>
      </header>

      <?php if ($notice) : ?>
      <div class="notice"><?php echo h($notice); ?></div>
      <?php endif; ?>

      <?php if ($error) : ?>
      <div class="notice"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <?php $rowIndex = 0; ?>
        <div class="actions">
          <button type="button" class="btn secondary js-add-row">Add row</button>
          <button type="submit" class="btn">Save changes</button>
        </div>
        <h2>Upcoming</h2>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Title</th>
              <th>Subtitle</th>
              <th>Link</th>
              <th>Icon</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody id="rows">
            <?php if (!$upcoming) : ?>
            <tr>
              <td colspan="6" class="help">No upcoming entries.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($upcoming as $item) : ?>
            <tr>
              <td>
                <label>Date</label>
                <input type="date" name="date[]" value="<?php echo h($item['date'] ?? ''); ?>" />
              </td>
              <td>
                <label>Title</label>
                <input type="text" name="title[]" value="<?php echo h($item['title'] ?? ''); ?>" />
              </td>
              <td>
                <label>Subtitle</label>
                <input
                  type="text"
                  name="subtitle[]"
                  placeholder="defaults to show the date"
                  value="<?php echo h($item['subtitle'] ?? ''); ?>"
                />
                <div class="help inline">Optional.</div>
              </td>
              <td>
                <label>Link</label>
                <input type="url" name="link[]" value="<?php echo h($item['link'] ?? ''); ?>" />
              </td>
              <td>
                <label>Icon</label>
                <select name="icon[]">
                  <?php $iconValue = $item['icon'] ?? ''; ?>
                  <option value="" <?php echo $iconValue === '' ? 'selected' : ''; ?>>None</option>
                  <option value="mic" <?php echo $iconValue === 'mic' ? 'selected' : ''; ?>>Mic</option>
                  <option value="instagram" <?php echo $iconValue === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                  <option value="linkedin" <?php echo $iconValue === 'linkedin' ? 'selected' : ''; ?>>LinkedIn</option>
                  <option value="spark" <?php echo $iconValue === 'spark' ? 'selected' : ''; ?>>Spark</option>
                  <option value="mail" <?php echo $iconValue === 'mail' ? 'selected' : ''; ?>>Mail</option>
                  <option value="podcast" <?php echo $iconValue === 'podcast' ? 'selected' : ''; ?>>Podcast</option>
                  <option value="video" <?php echo $iconValue === 'video' ? 'selected' : ''; ?>>Video</option>
                  <option value="camera" <?php echo $iconValue === 'camera' ? 'selected' : ''; ?>>Camera</option>
                  <option value="time" <?php echo $iconValue === 'time' ? 'selected' : ''; ?>>Time</option>
                </select>
              </td>
              <td class="delete-cell">
                <label>Delete</label>
                <input type="checkbox" name="delete[]" value="<?php echo $rowIndex; ?>" />
              </td>
            </tr>
            <?php $rowIndex++; ?>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="actions">
          <button type="button" class="btn secondary js-add-row">Add row</button>
          <button type="submit" class="btn">Save changes</button>
        </div>

        <h2>Past</h2>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Title</th>
              <th>Subtitle</th>
              <th>Link</th>
              <th>Icon</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody id="past-rows">
            <?php if (!$past) : ?>
            <tr>
              <td colspan="6" class="help">No past entries.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($past as $item) : ?>
            <tr class="past-row">
              <td>
                <label>Date</label>
                <input type="date" name="date[]" value="<?php echo h($item['date'] ?? ''); ?>" />
              </td>
              <td>
                <label>Title</label>
                <input type="text" name="title[]" value="<?php echo h($item['title'] ?? ''); ?>" />
              </td>
              <td>
                <label>Subtitle</label>
                <input
                  type="text"
                  name="subtitle[]"
                  placeholder="defaults to show the date"
                  value="<?php echo h($item['subtitle'] ?? ''); ?>"
                />
                <div class="help inline">Optional.</div>
              </td>
              <td>
                <label>Link</label>
                <input type="url" name="link[]" value="<?php echo h($item['link'] ?? ''); ?>" />
              </td>
              <td>
                <label>Icon</label>
                <select name="icon[]">
                  <?php $iconValue = $item['icon'] ?? ''; ?>
                  <option value="" <?php echo $iconValue === '' ? 'selected' : ''; ?>>None</option>
                  <option value="mic" <?php echo $iconValue === 'mic' ? 'selected' : ''; ?>>Mic</option>
                  <option value="instagram" <?php echo $iconValue === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                  <option value="linkedin" <?php echo $iconValue === 'linkedin' ? 'selected' : ''; ?>>LinkedIn</option>
                  <option value="spark" <?php echo $iconValue === 'spark' ? 'selected' : ''; ?>>Spark</option>
                  <option value="mail" <?php echo $iconValue === 'mail' ? 'selected' : ''; ?>>Mail</option>
                  <option value="podcast" <?php echo $iconValue === 'podcast' ? 'selected' : ''; ?>>Podcast</option>
                  <option value="video" <?php echo $iconValue === 'video' ? 'selected' : ''; ?>>Video</option>
                  <option value="camera" <?php echo $iconValue === 'camera' ? 'selected' : ''; ?>>Camera</option>
                  <option value="time" <?php echo $iconValue === 'time' ? 'selected' : ''; ?>>Time</option>
                </select>
              </td>
              <td class="delete-cell">
                <label>Delete</label>
                <input type="checkbox" name="delete[]" value="<?php echo $rowIndex; ?>" />
              </td>
            </tr>
            <?php $rowIndex++; ?>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="actions">
          <button type="button" class="btn secondary js-add-row">Add row</button>
          <button type="submit" class="btn">Save changes</button>
        </div>
        <p class="help">Icons are limited to the dropdown choices.</p>
      </form>
    </main>

    <template id="row-template">
      <tr>
        <td>
          <label>Date</label>
          <input type="date" name="date[]" />
        </td>
        <td>
          <label>Title</label>
          <input type="text" name="title[]" />
        </td>
        <td>
          <label>Subtitle</label>
          <input type="text" name="subtitle[]" placeholder="defaults to show the date" />
          <div class="help">Optional.</div>
        </td>
        <td>
          <label>Link</label>
          <input type="url" name="link[]" />
        </td>
        <td>
          <label>Icon</label>
          <select name="icon[]">
            <option value="" selected>None</option>
            <option value="mic">Mic</option>
            <option value="instagram">Instagram</option>
            <option value="linkedin">LinkedIn</option>
            <option value="spark">Spark</option>
            <option value="mail">Mail</option>
            <option value="podcast">Podcast</option>
            <option value="video">Video</option>
            <option value="camera">Camera</option>
            <option value="time">Time</option>
          </select>
        </td>
        <td class="delete-cell">
          <label>Delete</label>
          <button type="button" class="btn secondary js-remove-row">Remove</button>
        </td>
      </tr>
    </template>

    <script>
      const addRowButtons = document.querySelectorAll(".js-add-row");
      const rows = document.getElementById("rows");
      const template = document.getElementById("row-template");

      addRowButtons.forEach((button) => {
        button.addEventListener("click", () => {
          const clone = template.content.cloneNode(true);
          const row = clone.querySelector("tr");
          const dateInput = row.querySelector("input[type='date']");
          if (dateInput) {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, "0");
            const dd = String(today.getDate()).padStart(2, "0");
            dateInput.value = `${yyyy}-${mm}-${dd}`;
          }
          rows.appendChild(clone);
        });
      });

      rows.addEventListener("click", (event) => {
        const target = event.target;
        if (target instanceof HTMLElement && target.classList.contains("js-remove-row")) {
          const row = target.closest("tr");
          if (row) {
            row.remove();
          }
        }
      });
    </script>
    <?php endif; ?>
  </body>
</html>
