<script>
  import { onMount, tick } from 'svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { home, logout, overview } from '../routes';
  import { index as projectsIndex, show as projectShow } from '../routes/projects';
  import { index as tasksIndex } from '../routes/tasks';
  import { index as myTasksIndex } from '../routes/my-tasks';
  import { index as activityIndex } from '../routes/activity';
  import { index as archiveIndex } from '../routes/archive';
  import { index as favoritesIndex } from '../routes/favorites';
  import { index as inboxIndex } from '../routes/inbox';
  import { index as membersIndex } from '../routes/members';
  import { show as memberShow } from '../routes/members';
  import { index as notificationsIndex } from '../routes/notifications';
  import { show as teamShow } from '../routes/teams';
  import { index as searchIndex } from '../routes/search';
  import { index as calendarIndex } from '../routes/calendar';
  import { index as timelineIndex } from '../routes/timeline';
  import settingsRoutes from '../routes/settings';
  import systemRoutes from '../routes/system';
  import onboardingRoute from '../routes/onboarding';
  import WorkspaceTaskForm from '../Components/WorkspaceTaskForm.svelte';
  import { ic, wsLogo } from '@/src/core/icons.js';
  import { D, S, save } from '@/src/core/store.js';
  import { applyPrefs } from '@/src/core/theme.js';
  import { PCOLORS, PSTAT, TEAMS_SEED } from '@/src/core/constants.js';
  import { setNavigationIntent } from '../lib/navigation-intents.js';

  const page = usePage();
  let { children } = $props();
  let collapsed = $state(false);
  let previousPagePath = page.url.split('?')[0];
  let routeAnimationTimeout;
  let helpOpen = $state(false);
  let helpPosition = $state({ left: 12, top: 12 });
  let userOpen = $state(false);
  let userPosition = $state({ left: 12, top: 12 });
  let createOpen = $state(false);
  let createPosition = $state({ left: 12, top: 12 });
  let taskFormOpen = $state(false);
  let workspaceOpen = $state(false);
  let workspacePosition = $state({ left: 12, top: 12 });
  let workspaceRevision = $state(0);
  let expandedProjects = $state({ ...S.ui.expanded });
  let commandOpen = $state(false);
  let commandQuery = $state('');
  let commandIndex = $state(0);
  let offline = $state(false);
  let databaseProjects = $derived(page.props.projects || []);
  let snapshotProjects = $derived(page.props.workspaceData?.projects || []);
  let projects = $derived.by(() => {
    const sourceProjects = snapshotProjects.length ? snapshotProjects : D().projOrder.map(id => D().projects.find(project => project.id === id)).filter(Boolean);
    return sourceProjects
      .filter(project => !project.archived && project.status !== 'complete')
      .map(project => {
        const databaseProject = databaseProjects.find(item => item.source_key === project.id || item.id === project.database_id);
        return { ...project, database_id: project.database_id || databaseProject?.id || project.id };
      });
  });
  let teams = $derived(page.props.teams || D().teams || TEAMS_SEED);
  let unreadInbox = $derived((page.props.workspaceData?.notifs || D().notifs || []).filter(notification => !notification.read && notification.type !== 'update').length);
  let unreadNotifications = $derived((page.props.workspaceData?.notifs || D().notifs || []).filter(notification => !notification.read).length);
  let overdueAssignedCount = $derived((page.props.workspaceData?.tasks || D().tasks || []).filter(task =>
    task.assignee === (page.props.workspaceData?.me || D().me) && task.status !== 'done' && task.due && new Date(task.due) <= new Date()
  ).length);
  let workspace = $derived.by(() => {
    workspaceRevision;
    return D().ws;
  });
  let workspaces = $derived.by(() => {
    workspaceRevision;
    return D().workspaces;
  });
  let currentPath = $derived(page.url.split('?')[0]);
  let projectCrumb = $derived(currentPath.startsWith('/projects/') ? page.props.project : null);
  let memberCrumb = $derived(currentPath.startsWith('/members/') ? page.props.workspaceData?.members?.find(member => member.id === page.props.memberId) : null);
  let teamCrumb = $derived(currentPath.startsWith('/teams/') ? page.props.team : null);
  let currentLabel = $derived(currentPath === '/overview' ? 'Overview' : currentPath === '/my-tasks' ? 'My Tasks' : currentPath === '/tasks' ? 'Tasks' : currentPath === '/calendar' ? 'Calendar' : currentPath === '/timeline' ? 'Timeline' : currentPath === '/activity' ? 'Activity' : currentPath === '/favorites' ? 'Favorites' : currentPath === '/inbox' ? 'Inbox' : currentPath.startsWith('/members') ? 'Members' : currentPath.startsWith('/teams') ? 'Teams' : currentPath === '/notifications' ? 'Notifications' : currentPath === '/search' ? 'Search' : currentPath.startsWith('/settings') ? 'Settings' : currentPath === '/design-system' ? 'Design system' : currentPath === '/system-states' ? 'System states' : currentPath.startsWith('/projects') ? 'Projects' : 'Home');
  let currentHref = $derived(currentPath === '/overview' ? overview().url : currentPath === '/my-tasks' ? myTasksIndex().url : currentPath === '/tasks' ? tasksIndex().url : currentPath === '/calendar' ? calendarIndex().url : currentPath === '/timeline' ? timelineIndex().url : currentPath === '/activity' ? activityIndex().url : currentPath === '/favorites' ? favoritesIndex().url : currentPath === '/inbox' ? inboxIndex().url : currentPath.startsWith('/members') ? membersIndex().url : currentPath.startsWith('/teams') ? membersIndex().url : currentPath === '/notifications' ? notificationsIndex().url : currentPath === '/search' ? searchIndex().url : currentPath.startsWith('/settings') ? settingsRoutes.index().url : currentPath === '/design-system' ? systemRoutes.design().url : currentPath === '/system-states' ? systemRoutes.states().url : currentPath.startsWith('/projects') ? projectsIndex().url : home().url);
  let initials = $derived((page.props.auth?.user?.name || '').split(' ').map(name => name[0]).slice(0, 2).join(''));

  $effect(() => {
    const currentPagePath = page.url.split('?')[0];
    if (currentPagePath === previousPagePath) return;

    previousPagePath = currentPagePath;
    const content = document.getElementById('main-content');
    if (!content) return;

    content.classList.remove('fx-route');
    void content.offsetWidth;
    content.classList.add('fx-route');
    window.clearTimeout(routeAnimationTimeout);
    routeAnimationTimeout = window.setTimeout(() => content.classList.remove('fx-route'), 500);
  });
  const helpCommands = [
    { label: 'Search workspace', icon: 'search', shortcut: '/', href: searchIndex().url },
    { label: 'Go to Home', icon: 'house', shortcut: 'G H', href: home().url },
    { label: 'Go to Inbox', icon: 'inbox', shortcut: 'G I', href: inboxIndex().url },
    { label: 'Go to My Tasks', icon: 'circle-check', shortcut: 'G T', href: myTasksIndex().url },
    { label: 'Go to Projects', icon: 'folder-kanban', shortcut: 'G P', href: projectsIndex().url },
    { label: 'Go to Calendar', icon: 'calendar', shortcut: 'G C', href: calendarIndex().url },
    { label: 'Go to Timeline', icon: 'chart-gantt', href: timelineIndex().url },
    { label: 'Open settings', icon: 'settings', shortcut: 'G S', href: settingsRoutes.index().url },
    { label: 'Appearance settings', icon: 'palette', href: settingsRoutes.index({ query: { sec: 'appearance' } }).url },
    { label: 'Keyboard shortcuts', icon: 'keyboard', shortcut: '?', href: settingsRoutes.index({ query: { sec: 'shortcuts' } }).url },
  ];
  let filteredCommands = $derived(helpCommands.filter(command => command.label.toLowerCase().includes(commandQuery.trim().toLowerCase())));

  onMount(() => {
    offline = localStorage.getItem('gr8r.offline') === 'true';
    const onPointerDown = event => {
      if (!event.target.closest('.help-control')) helpOpen = false;
      if (!event.target.closest('.workspace-control')) workspaceOpen = false;
      if (!event.target.closest('.user-control') && !event.target.closest('.account-menu')) userOpen = false;
      if (!event.target.closest('.create-control') && !event.target.closest('.create-menu')) createOpen = false;
      if (!event.target.closest('.command-menu') && !event.target.closest('.help-control')) commandOpen = false;
    };
    const onKeyDown = event => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        openCommandMenu();
      } else if (event.key === 'Escape') {
        helpOpen = false;
        workspaceOpen = false;
        userOpen = false;
        createOpen = false;
        commandOpen = false;
      }
    };
    document.addEventListener('pointerdown', onPointerDown, true);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      window.clearTimeout(routeAnimationTimeout);
      document.removeEventListener('pointerdown', onPointerDown, true);
      document.removeEventListener('keydown', onKeyDown);
    };
  });

  function signOut() {
    router.post(logout().url);
  }

  function toggleProject(projectId) {
    expandedProjects = { ...expandedProjects, [projectId]: !expandedProjects[projectId] };
    S.ui.expanded = expandedProjects;
    save();
  }

  function openProject(event, projectId) {
    if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
    if (event.type === 'keydown') event.preventDefault();
    const selectedProject = projects.find(project => project.id === projectId);
    if (selectedProject) router.visit(projectShow(selectedProject.database_id).url);
  }

  function openProjectView(project, tab) {
    router.visit(`${projectShow(project.database_id).url}?tab=${tab}`);
  }

  async function toggleUserMenu(event) {
    if (userOpen) {
      userOpen = false;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    userPosition = { left: bounds.left, top: bounds.bottom + 4 };
    userOpen = true;
    await tick();
    const menu = document.querySelector('#account-menu');
    if (!menu) return;
    const menuBounds = menu.getBoundingClientRect();
    userPosition = {
      left: Math.max(8, Math.min(bounds.right - menuBounds.width, innerWidth - menuBounds.width - 8)),
      top: menuBounds.bottom > innerHeight - 8 ? Math.max(8, bounds.top - menuBounds.height - 4) : bounds.bottom + 4,
    };
  }

  function chooseTheme(theme) {
    S.prefs.theme = theme;
    save();
    applyPrefs();
  }

  async function toggleCreateMenu(event) {
    if (createOpen) {
      createOpen = false;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    createPosition = { left: bounds.left, top: bounds.bottom + 4 };
    createOpen = true;
    await tick();
    const menu = document.querySelector('#create-menu');
    if (!menu) return;
    const menuBounds = menu.getBoundingClientRect();
    createPosition = {
      left: Math.max(8, Math.min(bounds.right - menuBounds.width, innerWidth - menuBounds.width - 8)),
      top: menuBounds.bottom > innerHeight - 8 ? Math.max(8, bounds.top - menuBounds.height - 4) : bounds.bottom + 4,
    };
  }

  function createTask() {
    createOpen = false;
    taskFormOpen = true;
  }

  function taskCreated() {
    router.reload({ preserveScroll: true, preserveState: true });
  }

  function createProject() {
    setNavigationIntent({ type: 'create-project' });
    createOpen = false;
    router.visit(projectsIndex().url);
  }

  function createTeam() {
    createOpen = false;
    if (page.url.split('?')[0] === '/members') {
      window.dispatchEvent(new CustomEvent('gr8r:create-team'));
      return;
    }
    setNavigationIntent({ type: 'create-team' });
    router.visit(membersIndex({ query: { tab: 'teams' } }).url);
  }

  function inviteMember() {
    createOpen = false;
    router.visit(membersIndex().url);
  }

  async function toggleWorkspace(event) {
    if (workspaceOpen) {
      workspaceOpen = false;
      return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    workspacePosition = { left: rect.left, top: rect.bottom + 4 };
    workspaceOpen = true;
    await tick();
    const menu = document.querySelector('#workspace-switcher-menu');
    if (!menu) return;
    const bounds = menu.getBoundingClientRect();
    const left = bounds.right > innerWidth - 8 ? Math.max(8, rect.right - bounds.width) : rect.left;
    const top = bounds.bottom > innerHeight - 8 ? Math.max(8, rect.top - 4 - bounds.height) : rect.bottom + 4;
    workspacePosition = { left, top };
  }

  function switchWorkspace(selectedWorkspace) {
    const selected = D().workspaces.find(item => item.id === selectedWorkspace.id);
    if (!selected) return;
    D().ws = { name: selected.name, url: selected.name.toLowerCase().replace(/[^a-z0-9]+/g, ''), c: selected.c, brand: !!selected.brand };
    save();
    workspaceRevision += 1;
    workspaceOpen = false;
    router.visit(home().url);
  }

  function createWorkspace() {
    workspaceOpen = false;
    router.visit(`${onboardingRoute.index().url}?step=1`);
  }

  async function toggleHelp(event) {
    if (helpOpen) {
      helpOpen = false;
      return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    helpPosition = { left: rect.left, top: rect.bottom + 4 };
    helpOpen = true;
    await tick();
    const menu = document.querySelector('#help-resources-menu');
    if (!menu) return;
    const bounds = menu.getBoundingClientRect();
    const left = bounds.right > innerWidth - 8 ? Math.max(8, rect.right - bounds.width) : rect.left;
    const top = bounds.bottom > innerHeight - 8 ? Math.max(8, rect.top - 4 - bounds.height) : rect.bottom + 4;
    helpPosition = { left, top };
  }

  async function openCommandMenu() {
    helpOpen = false;
    commandQuery = '';
    commandIndex = 0;
    commandOpen = true;
    await tick();
    document.querySelector('#command-menu-input')?.focus();
  }

  function chooseCommand(command) {
    commandOpen = false;
    router.visit(command.href);
  }

  function handleCommandKey(event) {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      commandIndex = Math.min(commandIndex + 1, filteredCommands.length - 1);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      commandIndex = Math.max(commandIndex - 1, 0);
    } else if (event.key === 'Enter') {
      event.preventDefault();
      if (filteredCommands[commandIndex]) chooseCommand(filteredCommands[commandIndex]);
    }
  }

  function toggleOffline() {
    offline = !offline;
    localStorage.setItem('gr8r.offline', String(offline));
    helpOpen = false;
    showToast(offline ? 'Offline mode on — edits will fail to save' : 'Back online', offline ? 'err' : 'ok');
  }

  function resetLocalDemo() {
    if (!window.confirm('All sample workspace data and settings stored in this browser will be reset. Continue?')) return;
    localStorage.removeItem('gr8r.v2');
    localStorage.removeItem('gr8r.offline');
    window.location.reload();
  }

  function showToast(message, kind = 'ok') {
    const host = document.querySelector('#toasts');
    if (!host) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    const icon = document.createElement('span');
    icon.innerHTML = ic(kind === 'err' ? 'circle-alert' : kind === 'info' ? 'info' : 'circle-check', 15);
    const text = document.createElement('span');
    text.className = 'grow';
    text.textContent = message;
    toast.append(icon, text);
    host.append(toast);
    window.setTimeout(() => toast.remove(), 3000);
  }
</script>

<div class:collapsed class="shell">
  <nav class="side" aria-label="Main">
    <div class="side-top">
      <div class="row" style="gap:2px">
        <div class="workspace-control grow">
          <button class="ws" type="button" aria-haspopup="menu" aria-expanded={workspaceOpen} aria-label="Switch workspace" onclick={toggleWorkspace}>
            {@html wsLogo(workspace, collapsed ? 28 : 22)}
            <span class="ws-name trunc">{workspace.name}</span>
            <span class="chev-d faint">{@html ic('chevrons-up-down', 13)}</span>
          </button>
          {#if workspaceOpen}
            <div class="pop floating enter" id="workspace-switcher-menu" role="menu" aria-label="Workspaces" style={`left:${workspacePosition.left}px;top:${workspacePosition.top}px;width:260px`}>
              <div class="mh">Workspaces</div>
              {#each workspaces as item (item.id)}
                <button class="mi" type="button" role="menuitemradio" aria-checked={item.name === workspace.name} onclick={() => switchWorkspace(item)}>
                  {@html wsLogo(item, 20)}<span class="grow">{item.name}</span><span class="faint" style="font-size:11px">{item.plan}</span>{#if item.name === workspace.name}<span class="ck">{@html ic('check', 14)}</span>{/if}
                </button>
              {/each}
              <div class="msep"></div>
              <button class="mi" type="button" role="menuitem" onclick={createWorkspace}>{@html ic('plus', 15)}Create workspace</button>
              <Link class="mi" role="menuitem" href={settingsRoutes.index({ query: { sec: 'workspace' } }).url} onclick={() => workspaceOpen = false}>{@html ic('settings', 15)}Workspace settings</Link>
              <button class="mi" type="button" role="menuitem" onclick={() => { workspaceOpen = false; router.visit(`${onboardingRoute.index().url}?step=4`); }}>{@html ic('user-plus', 15)}Invite members</button>
              <div class="msep"></div>
              <button class="mi" type="button" role="menuitem" onclick={signOut}>{@html ic('log-out', 15)}Sign out</button>
            </div>
          {/if}
        </div>
        {#if !collapsed}<button class="ibtn ibtn-sm hide-m" onclick={() => collapsed = true} aria-label="Collapse sidebar">{@html ic('panel-left', 15)}</button>{/if}
      </div>
    </div>
    <div class="side-scroll">
      {#if collapsed}<button class="sitem" type="button" onclick={() => collapsed = false} aria-label="Expand sidebar">{@html ic('panel-left', 16)}</button>{/if}
      <Link class={currentPath === '/' ? 'sitem on' : 'sitem'} href={home().url}>{@html ic('house', 16)}<span>Home</span></Link>
      <Link class={currentPath === '/inbox' ? 'sitem on' : 'sitem'} href={inboxIndex().url}>{@html ic('inbox', 16)}<span>Inbox</span>{#if unreadInbox}<span class="ct dotc">{unreadInbox}</span>{/if}</Link>
      <Link class={currentPath === '/my-tasks' ? 'sitem on' : 'sitem'} href={myTasksIndex().url}>{@html ic('circle-check', 16)}<span>My Tasks</span>{#if overdueAssignedCount}<span class="ct">{overdueAssignedCount}</span>{/if}</Link>
      <Link class={currentPath === '/favorites' ? 'sitem on' : 'sitem'} href={favoritesIndex().url}>{@html ic('star', 16)}<span>Favorites</span></Link>
      <Link class={currentPath === '/notifications' ? 'sitem on' : 'sitem'} href={notificationsIndex().url}>{@html ic('bell', 16)}<span>Notifications</span>{#if unreadNotifications}<span class="ct">{unreadNotifications}</span>{/if}</Link>
      <div class="sgroup">
        <div class="sgroup-h">Workspace</div>
        <Link class={currentPath === '/overview' ? 'sitem on' : 'sitem'} href={overview().url}>{@html ic('layout-dashboard', 16)}<span>Overview</span></Link>
        <Link class={currentPath === '/projects' ? 'sitem on' : 'sitem'} href={projectsIndex().url}>{@html ic('folder-kanban', 16)}<span>Projects</span></Link>
        <Link class={currentPath === '/tasks' ? 'sitem on' : 'sitem'} href={tasksIndex().url}>{@html ic('list-checks', 16)}<span>Tasks</span></Link>
        <Link class={currentPath === '/calendar' ? 'sitem on' : 'sitem'} href={calendarIndex().url}>{@html ic('calendar', 16)}<span>Calendar</span></Link>
        <Link class={currentPath === '/timeline' ? 'sitem on' : 'sitem'} href={timelineIndex().url}>{@html ic('chart-gantt', 16)}<span>Timeline</span></Link>
        <Link class={currentPath.startsWith('/members') ? 'sitem on' : 'sitem'} href={membersIndex().url}>{@html ic('users', 16)}<span>Members</span></Link>
        <Link class={currentPath === '/activity' ? 'sitem on' : 'sitem'} href={activityIndex().url}>{@html ic('activity', 16)}<span>Activity</span></Link>
      </div>
      <div class="sgroup" id="side-projects">
        <div class="sgroup-h"><span>Projects</span><span class="sp"></span><button class="ibtn ibtn-xs" type="button" aria-label="New project" onclick={createProject}>{@html ic('plus', 14)}</button></div>
        {#each projects as project (project.id)}
          {@const isCurrentProject = projectCrumb && String(projectCrumb.id) === String(project.database_id)}
          {@const projectStatus = PSTAT[project.status] || PSTAT.planning}
          <div class="sproj">
            <div class={`sitem ${isCurrentProject ? 'on' : ''}`} role="link" tabindex="0" onclick={event => openProject(event, project.id)} onkeydown={event => openProject(event, project.id)} aria-current={isCurrentProject ? 'page' : undefined}>
              <button class="pico" type="button" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`} aria-label={`Toggle ${project.name} pages`} aria-expanded={!!expandedProjects[project.id]} onclick={event => { event.stopPropagation(); toggleProject(project.id); }}>{@html ic(project.icon || 'folder', 12)}</button>
              <span class="trunc">{project.name}</span>
              {#if project.fav}<span class="fav">{@html ic('star', 11)}</span>{/if}
              {#if project.private}<span class="faint" style="margin-left:4px">{@html ic('lock', 11)}</span>{/if}
              <span class="sdot" style={`background:${projectStatus.c}`} title={projectStatus.name}></span>
              <span class="hov"><button class="ibtn ibtn-xs" type="button" aria-label={`${project.name} options`} onclick={event => { event.stopPropagation(); router.visit(projectShow(project.database_id).url); }}>{@html ic('ellipsis', 14)}</button><button class="ibtn ibtn-xs" type="button" aria-label={`Expand ${project.name}`} onclick={event => { event.stopPropagation(); toggleProject(project.id); }}>{@html ic('chevron-right', 13, `chev ${expandedProjects[project.id] ? 'open' : ''}`)}</button></span>
            </div>
            <div class={`sub ${expandedProjects[project.id] ? 'open' : ''}`}>
              {#each [['board', 'Board', 'square-kanban'], ['list', 'List', 'list'], ['timeline', 'Timeline', 'chart-gantt'], ['files', 'Files', 'paperclip']] as [tab, label, icon]}
                <button class={`sitem ${isCurrentProject && page.url.includes(`tab=${tab}`) ? 'on' : ''}`} type="button" onclick={() => openProjectView(project, tab)}>{@html ic(icon, 14)}<span>{label}</span></button>
              {/each}
            </div>
          </div>
        {/each}
        <Link class={`sitem ${currentPath === '/archive' ? 'on' : ''}`} href={archiveIndex().url} aria-current={currentPath === '/archive' ? 'page' : undefined}>{@html ic('archive', 16)}<span>Archive</span>{#if page.props.archiveCount}<span class="ct">{page.props.archiveCount}</span>{/if}</Link>
      </div>
      <div class="sgroup">
        <div class="sgroup-h"><span>Teams</span><span class="sp"></span><button class="ibtn ibtn-xs" type="button" onclick={createTeam} aria-label="New team">{@html ic('plus', 14)}</button></div>
        {#each teams as team (team.id)}
          <Link class={`sitem ${teamCrumb && String(teamCrumb.id) === String(team.id) ? 'on' : ''}`} href={teamShow(team.id).url}>{@html ic(team.icon || 'users-round', 16)}<span>{team.name}</span></Link>
        {/each}
      </div>
    </div>
    <div class="side-bot">
      <div class="help-control">
        <button class="sitem" type="button" aria-haspopup="menu" aria-expanded={helpOpen} onclick={toggleHelp}>{@html ic('circle-help', 16)}<span>Help & resources</span></button>
        {#if helpOpen}
          <div class="pop floating enter" id="help-resources-menu" role="menu" style={`left:${helpPosition.left}px;top:${helpPosition.top}px;width:260px`}>
            <div class="mh">Help &amp; resources</div>
            <Link class="mi" role="menuitem" href={settingsRoutes.index({ query: { sec: 'shortcuts' } }).url} onclick={() => helpOpen = false}>{@html ic('keyboard', 15)}Keyboard shortcuts<span class="r"><kbd>?</kbd></span></Link>
            <button class="mi" type="button" role="menuitem" onclick={openCommandMenu}>{@html ic('command', 15)}Command menu<span class="r"><kbd>⌘K</kbd></span></button>
            <Link class="mi" role="menuitem" href={systemRoutes.design().url} onclick={() => helpOpen = false}>{@html ic('component', 15)}Design system</Link>
            <Link class="mi" role="menuitem" href={systemRoutes.states().url} onclick={() => helpOpen = false}>{@html ic('layers', 15)}System states</Link>
            <div class="msep"></div>
            <div class="mh">Prototype</div>
            <Link class="mi" role="menuitem" href={onboardingRoute.index().url} onclick={() => helpOpen = false}>{@html ic('sparkles', 15)}Replay onboarding</Link>
            <button class="mi" type="button" role="menuitem" onclick={toggleOffline}>{@html ic(offline ? 'wifi' : 'wifi-off', 15)}{offline ? 'Go back online' : 'Simulate offline'}</button>
            <Link class="mi" role="menuitem" href="/help/broken-link" onclick={() => helpOpen = false}>{@html ic('file-question', 15)}Open a broken link</Link>
            <button class="mi" type="button" role="menuitem" onclick={resetLocalDemo}>{@html ic('rotate-ccw', 15)}Reset demo data</button>
          </div>
        {/if}
      </div>
      <Link class="sitem" href={settingsRoutes.index().url}>{@html ic('settings', 16)}<span>Settings</span></Link>
      <div class="user-control">
        <button class="sitem" style="height:36px" type="button" aria-haspopup="menu" aria-expanded={userOpen} aria-label="Account menu" onclick={toggleUserMenu}><span class="av presence">{initials}</span><span class="trunc" style="color:var(--text);font-weight:500">{page.props.auth?.user?.name}</span><span class="ct">{@html ic('chevrons-up-down', 13)}</span></button>
        {#if userOpen}
          {@const currentSourceMember = D().members.find(member => member.id === (page.props.workspaceData?.me || D().me))}
          <div class="pop floating enter account-menu" id="account-menu" role="menu" aria-label="Account" style={`left:${userPosition.left}px;top:${userPosition.top}px;width:260px`}>
            <div class="row" style="padding:8px 8px 10px;gap:10px"><span class="av lg presence">{initials}</span><div style="min-width:0"><div style="font-weight:600">{page.props.auth?.user?.name}</div><div class="faint trunc" style="font-size:12px">{page.props.auth?.user?.email}</div></div></div>
            <div class="msep"></div>
            {#if currentSourceMember}<Link class="mi" role="menuitem" href={memberShow(currentSourceMember.id).url} onclick={() => userOpen = false}>{@html ic('user', 15)}View profile</Link>{/if}
            <Link class="mi" role="menuitem" href={settingsRoutes.index({ query: { sec: 'profile' } }).url} onclick={() => userOpen = false}>{@html ic('settings', 15)}Account settings</Link>
            <div class="msep"></div><div class="mh">Theme</div><div style="padding:2px 6px 6px"><div class="seg" style="width:100%">{#each [['light', 'sun', 'Light'], ['dark', 'moon', 'Dark'], ['system', 'monitor', 'System']] as [theme, icon, label]}<button type="button" class:on={S.prefs.theme === theme} style="flex:1;justify-content:center" onclick={() => chooseTheme(theme)}>{@html ic(icon, 13)}{label}</button>{/each}</div></div>
            <div class="msep"></div><Link class="mi" role="menuitem" href={settingsRoutes.index({ query: { sec: 'shortcuts' } }).url} onclick={() => userOpen = false}>{@html ic('keyboard', 15)}Keyboard shortcuts<span class="r"><kbd>?</kbd></span></Link><button class="mi" type="button" role="menuitem" onclick={signOut}>{@html ic('log-out', 15)}Sign out</button>
          </div>
        {/if}
      </div>
    </div>
  </nav>
  <main class="main" id="main">
    <header class="topbar">
      <nav class="crumbs trunc" aria-label="Breadcrumb">
        {#if projectCrumb}
          <Link class="parent" href={projectsIndex().url}>Projects</Link><span class="sep">/</span>
          <span class="cur"><span class="pico" style={`--c:${PCOLORS[projectCrumb.color] || PCOLORS.slate};width:16px;height:16px;border-radius:4px;display:grid;place-items:center;color:${PCOLORS[projectCrumb.color] || PCOLORS.slate}`}>{@html ic(projectCrumb.icon || 'folder', 11)}</span>{projectCrumb.name}</span>
        {:else if memberCrumb}
          <Link class="parent" href={membersIndex().url}>Members</Link><span class="sep">/</span><span class="cur">{memberCrumb.name}</span>
        {:else if teamCrumb}
          <Link class="parent" href={membersIndex({ query: { tab: 'teams' } }).url}>Teams</Link><span class="sep">/</span><span class="cur">{teamCrumb.name}</span>
        {:else}
          <Link class="cur" href={currentHref}>{currentLabel}</Link>
        {/if}
      </nav>
      <span class="sp"></span>
      <Link class="topsearch" href={searchIndex().url} aria-label="Search and commands">{@html ic('search', 14)}<span class="lbltxt">Search or jump to…</span><span class="kbd">⌘K</span></Link>
      <Link class="ibtn" href={notificationsIndex().url} aria-label="Notifications">{@html ic('bell', 16)}</Link>
      <div class="create-control">
        <button class="btn btn-secondary btn-sm" type="button" aria-haspopup="menu" aria-expanded={createOpen} aria-label="Create new" onclick={toggleCreateMenu}>{@html ic('plus', 14)}<span class="hide-m">New</span>{@html ic('chevron-down', 12, 'hide-m')}</button>
        {#if createOpen}
          <div class="pop floating enter create-menu" id="create-menu" role="menu" aria-label="Create" style={`left:${createPosition.left}px;top:${createPosition.top}px;width:220px`}>
            <div class="mh">Create</div>
            <button class="mi" type="button" role="menuitem" onclick={createTask}>{@html ic('circle-check', 15)}Task<span class="r"><kbd>N</kbd></span></button>
            <button class="mi" type="button" role="menuitem" onclick={createProject}>{@html ic('folder-plus', 15)}Project<span class="r"><kbd>P</kbd></span></button>
            <button class="mi" type="button" role="menuitem" onclick={createTeam}>{@html ic('users', 15)}Team</button>
            <div class="msep"></div><button class="mi" type="button" role="menuitem" onclick={inviteMember}>{@html ic('user-plus', 15)}Invite member</button>
          </div>
        {/if}
      </div>
      <div class="user-control"><button class="ibtn hide-m" type="button" aria-label="Account menu" aria-haspopup="menu" aria-expanded={userOpen} onclick={toggleUserMenu}><span class="av md">{initials}</span></button></div>
    </header>
    {#if offline}<div class="offline-bar" role="alert">{@html ic('wifi-off', 15)}<span><b>You're offline.</b> Changes won't be saved until your connection is back.</span><span class="sp"></span><button class="btn btn-sm btn-secondary" onclick={toggleOffline}>Try again</button></div>{/if}
    <div class="content" id="main-content" tabindex="-1">
      {@render children?.()}
    </div>
  </main>
</div>
{#if commandOpen}
  <div class="command-menu">
    <div class="scrim enter" aria-hidden="true" onclick={() => commandOpen = false}></div>
    <div class="palette" role="dialog" aria-modal="true" aria-label="Command menu">
      <div class="pal-in">{@html ic('command', 17)}<input id="command-menu-input" bind:value={commandQuery} onkeydown={handleCommandKey} placeholder="Type a command or search…" autocomplete="off" role="combobox" aria-expanded="true" aria-controls="command-menu-list" /><kbd>Esc</kbd></div>
      <div class="pal-list" id="command-menu-list" role="listbox">
        {#if filteredCommands.length}
          <div class="pal-group"><div class="pal-sec">Commands</div>
            {#each filteredCommands as command, index}
              <button class="pi" class:hl={commandIndex === index} role="option" aria-selected={commandIndex === index} onclick={() => chooseCommand(command)}>{@html ic(command.icon, 15)}<span class="grow">{command.label}</span>{#if command.shortcut}<span class="r"><kbd>{command.shortcut}</kbd></span>{/if}</button>
            {/each}
          </div>
        {:else}<div class="empty-state sm"><p>No matching commands.</p></div>{/if}
      </div>
    </div>
  </div>
{/if}
<WorkspaceTaskForm open={taskFormOpen} {projects} projectOrder={snapshotProjects.map(project => project.database_id || project.id).filter(Boolean)} onClose={() => taskFormOpen = false} onCreated={taskCreated} />
