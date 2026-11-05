<script>
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { home, logout, overview } from '../routes';
  import { index as projectsIndex, show as projectShow } from '../routes/projects';
  import { index as tasksIndex } from '../routes/tasks';
  import { index as myTasksIndex } from '../routes/my-tasks';
  import { index as activityIndex } from '../routes/activity';
  import { index as favoritesIndex } from '../routes/favorites';
  import { index as inboxIndex } from '../routes/inbox';
  import { index as notificationsIndex } from '../routes/notifications';
  import { index as searchIndex } from '../routes/search';
  import { index as calendarIndex } from '../routes/calendar';
  import { index as timelineIndex } from '../routes/timeline';
  import { ic, wsLogo } from '@/src/core/icons.js';

  const page = usePage();
  let { children } = $props();
  let collapsed = $state(false);
  let projects = $derived(page.props.projects || []);
  let currentPath = $derived(page.url.split('?')[0]);
  let currentLabel = $derived(currentPath === '/overview' ? 'Overview' : currentPath === '/my-tasks' ? 'My Tasks' : currentPath === '/tasks' ? 'Tasks' : currentPath === '/calendar' ? 'Calendar' : currentPath === '/timeline' ? 'Timeline' : currentPath === '/activity' ? 'Activity' : currentPath === '/favorites' ? 'Favorites' : currentPath === '/inbox' ? 'Inbox' : currentPath === '/notifications' ? 'Notifications' : currentPath === '/search' ? 'Search' : currentPath.startsWith('/projects') ? 'Projects' : 'Home');
  let currentHref = $derived(currentPath === '/overview' ? overview().url : currentPath === '/my-tasks' ? myTasksIndex().url : currentPath === '/tasks' ? tasksIndex().url : currentPath === '/calendar' ? calendarIndex().url : currentPath === '/timeline' ? timelineIndex().url : currentPath === '/activity' ? activityIndex().url : currentPath === '/favorites' ? favoritesIndex().url : currentPath === '/inbox' ? inboxIndex().url : currentPath === '/notifications' ? notificationsIndex().url : currentPath === '/search' ? searchIndex().url : currentPath.startsWith('/projects') ? projectsIndex().url : home().url);
  let initials = $derived((page.props.auth?.user?.name || '').split(' ').map(name => name[0]).slice(0, 2).join(''));

  function signOut() {
    router.post(logout().url);
  }
</script>

<div class:collapsed class="shell">
  <nav class="side" aria-label="Main">
    <div class="side-top">
      <div class="row" style="gap:2px">
        <Link class="ws grow" href={home().url} aria-label="Gr8r Studio home">
          {@html wsLogo({ name: page.props.appName || 'Gr8r Studio', c: '#5A67D8' }, 22)}
          <span class="ws-name trunc">{page.props.appName || 'Gr8r Studio'}</span>
          <span class="chev-d faint">{@html ic('chevrons-up-down', 13)}</span>
        </Link>
        <button class="ibtn ibtn-sm hide-m" onclick={() => collapsed = !collapsed} aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}>{@html ic('panel-left', 15)}</button>
      </div>
    </div>
    <div class="side-scroll">
      <Link class={currentPath === '/' ? 'sitem active' : 'sitem'} href={home().url}>{@html ic('house', 16)}<span>Home</span></Link>
      <Link class={currentPath === '/inbox' ? 'sitem active' : 'sitem'} href={inboxIndex().url}>{@html ic('inbox', 16)}<span>Inbox</span></Link>
      <Link class={currentPath === '/my-tasks' ? 'sitem active' : 'sitem'} href={myTasksIndex().url}>{@html ic('circle-check', 16)}<span>My Tasks</span></Link>
      <Link class={currentPath === '/favorites' ? 'sitem active' : 'sitem'} href={favoritesIndex().url}>{@html ic('star', 16)}<span>Favorites</span></Link>
      <Link class={currentPath === '/notifications' ? 'sitem active' : 'sitem'} href={notificationsIndex().url}>{@html ic('bell', 16)}<span>Notifications</span></Link>
      <div class="sgroup">
        <div class="sgroup-h">Workspace</div>
        <Link class={currentPath === '/overview' ? 'sitem active' : 'sitem'} href={overview().url}>{@html ic('layout-dashboard', 16)}<span>Overview</span></Link>
        <Link class={currentPath.startsWith('/projects') ? 'sitem active' : 'sitem'} href={projectsIndex().url}>{@html ic('folder-kanban', 16)}<span>Projects</span></Link>
        <Link class={currentPath === '/tasks' ? 'sitem active' : 'sitem'} href={tasksIndex().url}>{@html ic('list-checks', 16)}<span>Tasks</span></Link>
        <Link class={currentPath === '/calendar' ? 'sitem active' : 'sitem'} href={calendarIndex().url}>{@html ic('calendar', 16)}<span>Calendar</span></Link>
        <Link class={currentPath === '/timeline' ? 'sitem active' : 'sitem'} href={timelineIndex().url}>{@html ic('chart-gantt', 16)}<span>Timeline</span></Link>
        <button class="sitem" type="button">{@html ic('users', 16)}<span>Members</span></button>
        <Link class={currentPath === '/activity' ? 'sitem active' : 'sitem'} href={activityIndex().url}>{@html ic('activity', 16)}<span>Activity</span></Link>
      </div>
      <div class="sgroup" id="side-projects">
        <div class="sgroup-h"><span>Projects</span><span class="sp"></span><Link class="ibtn ibtn-xs" href={projectsIndex().url} aria-label="New project">{@html ic('plus', 14)}</Link></div>
        {#each projects as project (project.id)}
          <Link class="sitem" href={projectShow(project.id).url}>
            <span class="pico" style={`--c:var(--${project.color || 'indigo'})`}>{@html ic('folder', 12)}</span>
            <span class="trunc">{project.name}</span>
            <span class="sdot" style={`background:var(--${project.status === 'completed' ? 'green' : 'blue'})`}></span>
          </Link>
        {/each}
        <Link class="sitem" href={projectsIndex().url}>{@html ic('archive', 16)}<span>All projects</span></Link>
      </div>
    </div>
    <div class="side-bot">
      <button class="sitem" type="button">{@html ic('circle-help', 16)}<span>Help & resources</span></button>
      <button class="sitem" type="button">{@html ic('settings', 16)}<span>Settings</span></button>
      <button class="sitem" style="height:36px" onclick={signOut} aria-label="Sign out"><span class="av presence">{initials}</span><span class="trunc" style="color:var(--text);font-weight:500">{page.props.auth?.user?.name}</span><span class="ct">{@html ic('log-out', 13)}</span></button>
    </div>
  </nav>
  <main class="main" id="main">
    <header class="topbar">
      <nav class="crumbs trunc" aria-label="Breadcrumb">
        <Link class="cur" href={currentHref}>{currentLabel}</Link>
      </nav>
      <span class="sp"></span>
      <Link class="topsearch" href={searchIndex().url} aria-label="Search and commands">{@html ic('search', 14)}<span class="lbltxt">Search or jump to…</span><span class="kbd">⌘K</span></Link>
      <Link class="ibtn" href={notificationsIndex().url} aria-label="Notifications">{@html ic('bell', 16)}</Link>
      <Link class="btn btn-secondary btn-sm" href={projectsIndex().url}>{@html ic('plus', 14)}<span class="hide-m">New</span></Link>
      <button class="ibtn hide-m" onclick={signOut} aria-label="Account menu"><span class="av md">{initials}</span></button>
    </header>
    <div class="content" id="main-content" tabindex="-1">
      {@render children?.()}
    </div>
  </main>
</div>
