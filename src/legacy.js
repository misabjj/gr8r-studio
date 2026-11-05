import './core/utils.js';
import './core/icons.js';
import './core/constants.js';
import './data/seed.js';
import './core/store.js';
import './ui/helpers.js';
import './core/theme.js';
import './ui/toast.js';
import './ui/select.js';
import './shell/render.js';
import './shell/layout.js';
import './shell/skeletons.js';
import './shell/view-engine.js';
import './components/task-list.js';
import './pages/home.js';
import './pages/overview.js';
import './pages/my-tasks.js';
import './pages/tasks.js';
import './pages/inbox.js';
import './pages/notifications.js';
import './pages/favorites.js';
import './pages/projects.js';
import './pages/members.js';
import './pages/activity.js';
import './pages/search.js';
import './pages/errors.js';
import './pages/project.js';
import './views/project-overview.js';
import './views/board.js';
import './views/table.js';
import './views/calendar.js';
import './views/timeline.js';
import './views/files.js';
import './overlays/drawer.js';
import './overlays/modals.js';
import './overlays/popovers.js';
import './overlays/context-menu.js';
import './overlays/palette.js';
import './pages/settings.js';
import './pages/auth.js';
import './pages/design-system.js';
import './actions/actions.js';
import './actions/events.js';
import './actions/keyboard.js';
import './actions/drag-drop.js';
import './features/teams.js';
import './features/subtasks.js';
import './features/archive.js';
import './actions/teams.js';
import './actions/subtasks.js';
import './actions/archive.js';

import { S, D } from './core/store.js';
import { A } from './actions/actions.js';
import { ROUTE_NAMES, render, go } from './shell/render.js';

const authRoutes = ['login', 'signup', 'forgot', 'reset', 'verify', 'onboarding'];

export function mountLegacyPage({ route, props = {} }) {
  document.documentElement.lang = props.locale || 'en';

  if (!document.getElementById('sr-live')) {
    const live = document.createElement('div');
    live.id = 'sr-live';
    live.className = 'sr';
    live.setAttribute('aria-live', 'polite');
    document.body.appendChild(live);
  }

  if (route === 'Auth/Login') {
    S.ui.auth = 'login';
  } else if (route === 'Projects/Index') {
    S.ui.auth = null;
    S.ui.route = 'projects';
  } else if (route === 'Projects/Show') {
    S.ui.auth = null;
    S.ui.route = 'project';
    const project = D().projects.find(item => item.key === props.project?.key || item.name === props.project?.name);
    S.ui.params = { id: project?.id || String(props.project?.id || ''), tab: 'board' };
  } else {
    S.ui.auth = null;
    S.ui.route = S.prefs.home || 'home';
  }

  render();

  if (import.meta.env.DEV) window.__gr8r = { S, D, A, go, render };
}

export function unmountLegacyPage() {
  document.getElementById('legacy-app')?.replaceChildren();
}

export { ROUTE_NAMES };
