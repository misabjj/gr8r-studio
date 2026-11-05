const STORE_KEY = 'gr8r.v2';

export function savedTheme() {
  try {
    const stored = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
    return ['light', 'dark'].includes(stored?.prefs?.theme) ? stored.prefs.theme : 'system';
  } catch {
    return 'system';
  }
}

export function effectiveDark(theme = savedTheme()) {
  if (theme === 'dark') return true;
  if (theme === 'light') return false;

  return window.matchMedia?.('(prefers-color-scheme: dark)').matches || false;
}

export function applyTheme(theme) {
  if (theme === 'system') document.documentElement.removeAttribute('data-theme');
  else document.documentElement.setAttribute('data-theme', theme);
}

export function toggleTheme() {
  const theme = effectiveDark() ? 'light' : 'dark';

  try {
    const stored = JSON.parse(localStorage.getItem(STORE_KEY) || '{}');
    localStorage.setItem(STORE_KEY, JSON.stringify({ ...stored, prefs: { ...stored.prefs, theme } }));
  } catch {
    // Keep the current page usable when browser storage is unavailable.
  }

  applyTheme(theme);
  return theme;
}
