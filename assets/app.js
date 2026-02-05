(() => {
  const storageKey = "barcraft-theme";
  const root = document.documentElement;
  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)");

  const resolveTheme = (choice) => {
    if (choice === "dark") {
      return "dark";
    }
    if (choice === "light") {
      return "light";
    }
    return prefersDark.matches ? "dark" : "light";
  };

  const applyTheme = (choice) => {
    const resolved = resolveTheme(choice);
    root.dataset.theme = resolved;
    root.dataset.themeChoice = choice;

    document.querySelectorAll("[data-theme-toggle]").forEach((btn) => {
      btn.setAttribute("aria-pressed", resolved === "dark");
      btn.dataset.theme = resolved;
    });

    document.querySelectorAll("[data-theme-choice]").forEach((btn) => {
      btn.classList.toggle("is-selected", btn.dataset.themeChoice === choice);
    });
  };

  const stored = localStorage.getItem(storageKey) || "system";
  applyTheme(stored);

  document.addEventListener("click", (event) => {
    const choiceBtn = event.target.closest("[data-theme-choice]");
    if (choiceBtn) {
      const choice = choiceBtn.dataset.themeChoice || "system";
      localStorage.setItem(storageKey, choice);
      applyTheme(choice);
      return;
    }

    const toggleBtn = event.target.closest("[data-theme-toggle]");
    if (toggleBtn) {
      const next = root.dataset.theme === "dark" ? "light" : "dark";
      localStorage.setItem(storageKey, next);
      applyTheme(next);
    }
  });

  prefersDark.addEventListener("change", () => {
    const choice = root.dataset.themeChoice || "system";
    if (choice === "system") {
      applyTheme(choice);
    }
  });
})();
