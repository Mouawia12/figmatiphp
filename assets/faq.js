document.addEventListener("DOMContentLoaded", function () {
  const acc = document.querySelector(".faq-acc");
  if (!acc) return;

  // السماح بفتح عنصر واحد فقط في نفس الوقت
  acc.addEventListener(
    "toggle",
    (e) => {
      const t = e.target;
      if (t.tagName.toLowerCase() !== "details" || !t.open) return;
      acc.querySelectorAll("details[open]").forEach((d) => {
        if (d !== t) d.open = false;
      });
    },
    true
  );
});
