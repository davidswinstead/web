const ICONS = {
  spark: "\u2726",
  mail: "\u2709\ufe0f",
  podcast: "\ud83c\udfa7",
  mic: "\ud83c\udfa4",
  video: "\ud83c\udfac",
  instagram: { type: "img", src: "instagram.png", alt: "Instagram" },
  linkedin: { type: "img", src: "linkedin.png", alt: "LinkedIn" },
  camera: "\ud83d\udcf8",
  time: "\ud83d\udd5e\ufe0f"
};

const linksList = document.getElementById("links");

function parseCSV(text) {
  const rows = [];
  let row = [];
  let cell = "";
  let inQuotes = false;

  for (let i = 0; i < text.length; i++) {
    const char = text[i];
    const next = text[i + 1];

    if (char === '"' && inQuotes && next === '"') {
      cell += '"';
      i++;
      continue;
    }

    if (char === '"') {
      inQuotes = !inQuotes;
      continue;
    }

    if (char === "," && !inQuotes) {
      row.push(cell);
      cell = "";
      continue;
    }

    if ((char === "\n" || char === "\r") && !inQuotes) {
      if (char === "\r" && next === "\n") i++;
      row.push(cell);
      if (row.some((val) => val.trim() !== "")) rows.push(row);
      row = [];
      cell = "";
      continue;
    }

    cell += char;
  }

  if (cell.length || row.length) {
    row.push(cell);
    if (row.some((val) => val.trim() !== "")) rows.push(row);
  }

  return rows;
}

function iconFromValue(value) {
  if (!value) return "";
  const trimmed = value.trim();
  if (ICONS[trimmed]) return ICONS[trimmed];
  return trimmed;
}

function isUpcoming(dateString) {
  if (!dateString) return true;
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, "0");
  const dd = String(today.getDate()).padStart(2, "0");
  const todayString = `${yyyy}${mm}${dd}`;
  const normalized = dateString.replace(/\D/g, "");
  return normalized >= todayString;
}

function renderLinks(linksConfig) {
  linksList.innerHTML = "";

  linksConfig.forEach((item, index) => {
    const listItem = document.createElement("li");
    listItem.className = "link-item";
    listItem.style.animationDelay = `${0.12 * index}s`;

    const anchor = document.createElement("a");
    anchor.className = "link-button";
    anchor.href = item.link;
    anchor.target = "_blank";
    anchor.rel = "noopener noreferrer";

    const icon = document.createElement("span");
    icon.className = "link-icon";
    if (typeof item.icon === "string") {
      icon.textContent = item.icon;
    } else if (item.icon && item.icon.type === "img") {
      const iconImage = document.createElement("img");
      iconImage.src = item.icon.src;
      iconImage.alt = item.icon.alt;
      iconImage.loading = "lazy";
      icon.appendChild(iconImage);
    }

    const text = document.createElement("span");
    text.className = "link-text";

    const title = document.createElement("span");
    title.className = "link-title";
    title.textContent = item.title;

    const subtitle = document.createElement("span");
    subtitle.className = "link-subtitle";
    subtitle.textContent = item.subtitle;

    text.appendChild(title);
    text.appendChild(subtitle);

    anchor.appendChild(icon);
    anchor.appendChild(text);
    listItem.appendChild(anchor);
    linksList.appendChild(listItem);
  });
}

async function loadLinksFromCSV() {
  const response = await fetch("data/links.csv", { cache: "no-store" });
  if (!response.ok) {
    throw new Error(`Failed to load CSV: ${response.status}`);
  }

  const text = await response.text();
  const rows = parseCSV(text);

  if (!rows.length) return [];

  const [header, ...dataRows] = rows;
  const colIndex = header.reduce((acc, col, i) => {
    acc[col.trim().toLowerCase()] = i;
    return acc;
  }, {});

  return dataRows
    .map((cols) => ({
      date: cols[colIndex.date] || "",
      title: cols[colIndex.title] || "",
      subtitle: cols[colIndex.subtitle] || "",
      link: cols[colIndex.link] || "",
      icon: iconFromValue(cols[colIndex.icon] || "")
    }))
    .filter((item) => isUpcoming(item.date));
}

loadLinksFromCSV()
  .then(renderLinks)
  .catch((error) => {
    console.error(error);
  });
