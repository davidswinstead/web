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

const linksConfig = [
  {
    text: "Feb 18th - Open Mic Comedy @ Cafe De Buurvrouw",
    link: "https://www.eventbrite.nl/e/english-comedy-open-mic-at-cafe-de-buurvrouw-pay-what-you-can-tickets-1980185943462",
    icon: ICONS.mic
  },
  {
    text: "Feb 19th - That Comedy Thing @ Oosterbar",
    link: "https://www.eventbrite.nl/e/that-comedy-thing-tickets-1982455332265",
    icon: ICONS.mic
  },
  {
    text: "Feb 26th - That Comedy Thing @ Oosterbar",
    link: "https://www.eventbrite.nl/e/that-comedy-thing-tickets-1982461979146",
    icon: ICONS.mic
  },
  {
    text: "March 4th - Open Mic Comedy @ Cafe De Buurvrouw",
    link: "https://www.eventbrite.nl/e/english-comedy-open-mic-at-cafe-de-buurvrouw-pay-what-you-can-tickets-1980185943462",
    icon: ICONS.mic
  },
  {
    text: "@spinstead.gif on instagram",
            link: "https://www.instagram.com/spinstead.gif/",
    icon: ICONS.instagram
  },
  {
    text: "David Swinstead on LinkedIn (not comedy related - mainly CRO and AI content) ",
    link: "https://www.linkedin.com/in/david-swinstead-09267030/",
    icon: ICONS.linkedin
  }
];

const linksList = document.getElementById("links");

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
  text.textContent = item.text;

  anchor.appendChild(icon);
  anchor.appendChild(text);
  listItem.appendChild(anchor);
  linksList.appendChild(listItem);
});
