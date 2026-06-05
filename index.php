<?php
$dbPath = __DIR__ . "/data/links.sqlite";
$items = [];

if (file_exists($dbPath)) {
    try {
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $today = date("Ymd");
        $stmt = $pdo->query(
            "SELECT date, title, subtitle, link, icon
             FROM links
             WHERE REPLACE(REPLACE(date, '/', ''), '-', '') >= '$today'
             ORDER BY
          REPLACE(date, '/', '-') ASC,
          position ASC,
          id ASC"
        );
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $items = [];
    }
}

function h($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function formatDateLabel($dateString)
{
    if (!$dateString) {
        return "";
    }
    $normalized = str_replace("/", "-", $dateString);
    $date = DateTime::createFromFormat("Y-m-d", $normalized);
    if (!$date) {
        return $dateString;
    }

    $day = (int) $date->format("j");
    $suffix = "th";
    if ($day % 10 === 1 && $day % 100 !== 11) {
        $suffix = "st";
    } elseif ($day % 10 === 2 && $day % 100 !== 12) {
        $suffix = "nd";
    } elseif ($day % 10 === 3 && $day % 100 !== 13) {
        $suffix = "rd";
    }

    return $date->format("M ") . $day . $suffix;
}

$iconMap = [
    "spark" => "✦",
    "mail" => "✉️",
    "podcast" => "🎧",
    "mic" => "🎤",
    "video" => "🎬",
    "camera" => "📸",
    "time" => "🕞"
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Link Hub</title>
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <main class="page">
      <section class="content">
        <div class="profile">
          <div class="identity">
            <h1 class="name">David Swinstead</h1>
            <p class="bio">New comic, UK born, Amsterdam based.</p>
          </div>
        </div>
        <ul class="links" id="links">
          <?php foreach ($items as $item) : ?>
          <?php
            $subtitleValue = trim($item["subtitle"] ?? "");
            if ($subtitleValue === "") {
                $subtitleValue = formatDateLabel($item["date"] ?? "");
            }
            $iconKey = trim($item["icon"] ?? "");
            $hasLink = !empty($item["link"]);
          ?>
          <li class="link-item">
            <?php if ($hasLink) : ?>
            <a class="link-button" href="<?php echo h($item["link"]); ?>" target="_blank" rel="noopener noreferrer">
            <?php else : ?>
            <div class="link-button">
            <?php endif; ?>
              <span class="link-icon">
                <?php if ($iconKey === "instagram") : ?>
                  <img src="instagram.png" alt="Instagram" loading="lazy" />
                <?php elseif ($iconKey === "linkedin") : ?>
                  <img src="linkedin.png" alt="LinkedIn" loading="lazy" />
                <?php elseif ($iconKey !== "" && isset($iconMap[$iconKey])) : ?>
                  <?php echo h($iconMap[$iconKey]); ?>
                <?php else : ?>
                  <?php echo h($iconKey); ?>
                <?php endif; ?>
              </span>
              <span class="link-text">
                <span class="link-title"><?php echo h($item["title"] ?? ""); ?></span>
                <span class="link-subtitle"><?php echo h($subtitleValue); ?></span>
              </span>
            <?php if ($hasLink) : ?>
            </a>
            <?php else : ?>
            </div>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <ul class="links" id="static-links">
          <li class="link-item">
            <a
              class="link-button"
              href="https://www.instagram.com/swinstead.lol/"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="link-icon">
                <img src="instagram.png" alt="Instagram" loading="lazy" />
              </span>
              <span class="link-text">
                <span class="link-title">@swinstead.lol</span>
                <span class="link-subtitle">Instagram</span>
              </span>
            </a>
          </li>
          <li class="link-item">
            <a
              class="link-button"
              href="https://www.linkedin.com/in/david-swinstead-09267030/"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="link-icon">
                <img src="linkedin.png" alt="LinkedIn" loading="lazy" />
              </span>
              <span class="link-text">
                <span class="link-title">David Swinstead</span>
                <span class="link-subtitle">LinkedIn (CRO and AI content)</span>
              </span>
            </a>
          </li>
        </ul>
      </section>
    </main>
  </body>
</html>
