<script>
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { home, logout } from '../routes';
  import { index as projectsIndex, show as projectShow } from '../routes/projects';
  import { ic, wsLogo } from '@/src/core/icons.js';

  const page = usePage();
  let { children } = $props();
  let collapsed = $state(false);
  let projects = $derived(page.props.projects || []);
  let currentPath = $derived(page.url.split('?')[0]);
  let initials = $derived((page.props.auth?.user?.name || 'Tanjim Islam').split(' ').map(name => name[0]).slice(0, 2).join(''));

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
      <button class="sitem" type="button">{@html ic('inbox', 16)}<span>Inbox</span><span class="ct">4</span></button>
      <button class="sitem" type="button">{@html ic('circle-check', 16)}<span>My Tasks</span></button>
      <button class="sitem" type="button">{@html ic('star', 16)}<span>Favorites</span></button>
      <button class="sitem" type="button">{@html ic('bell', 16)}<span>Notifications</span></button>
      <div class="sgroup">
        <div class="sgroup-h">Workspace</div>
        <Link class="sitem" href={home().url}>{@html ic('layout-dashboard', 16)}<span>Overview</span></Link>
        <Link class={currentPath.startsWith('/projects') ? 'sitem active' : 'sitem'} href={projectsIndex().url}>{@html ic('folder-kanban', 16)}<span>Projects</span></Link>
        <button class="sitem" type="button">{@html ic('list-checks', 16)}<span>Tasks</span></button>
        <button class="sitem" type="button">{@html ic('calendar', 16)}<span>Calendar</span></button>
        <button class="sitem" type="button">{@html ic('chart-gantt', 16)}<span>Timeline</span></button>
        <button class="sitem" type="button">{@html ic('users', 16)}<span>Members</span></button>
        <button class="sitem" type="button">{@html ic('activity', 16)}<span>Activity</span></button>
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
      <button class="sitem" style="height:36px" onclick={signOut} aria-label="Sign out"><span class="av presence">{initials}</span><span class="trunc" style="color:var(--text);font-weight:500">{page.props.auth?.user?.name || 'Tanjim Islam'}</span><span class="ct">{@html ic('log-out', 13)}</span></button>
    </div>
  </nav>
  <main class="main" id="main">
    <header class="topbar">
      <nav class="crumbs trunc" aria-label="Breadcrumb">
        <Link class="cur" href={currentPath.startsWith('/projects') ? projectsIndex().url : home().url}>{currentPath.startsWith('/projects') ? 'Projects' : 'Home'}</Link>
      </nav>
      <span class="sp"></span>
      <button class="topsearch" aria-label="Search and commands">{@html ic('search', 14)}<span class="lbltxt">Search or jump to…</span><span class="kbd">⌘K</span></button>
      <button class="ibtn" aria-label="Notifications">{@html ic('bell', 16)}</button>
      <Link class="btn btn-secondary btn-sm" href={projectsIndex().url}>{@html ic('plus', 14)}<span class="hide-m">New</span></Link>
      <button class="ibtn hide-m" onclick={signOut} aria-label="Account menu"><span class="av md">{initials}</span></button>
    </header>
    <div class="content" id="main-content" tabindex="-1">
      {@render children?.()}
    </div>
  </main>
</div>
