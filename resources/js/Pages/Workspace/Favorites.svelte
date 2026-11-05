<script>
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { index as projectsIndex } from '../../routes/projects';
  import { favorite as taskFavorite } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';

  let { projects = [], tasks = [] } = $props();

  function completionFor(project) {
    return project.tasks_count ? Math.round((project.completed_tasks_count / project.tasks_count) * 100) : 0;
  }

  function timeSince(value) {
    if (!value) return '';
    const minutes = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 60000));
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
  }

  function shortDate(value) {
    if (!value) return '—';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  async function toggleTaskFavorite(task) {
    const updated = await jsonRequest(taskFavorite(task.id).url, 'PATCH', {});
    tasks = tasks.filter(item => item.id !== updated.id);
  }
</script>

<svelte:head><title>Favorites · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page wide">
    <div class="ph"><div><h1>Favorites</h1><p>Projects and tasks you've starred for quick access.</p></div></div>
    {#if !projects.length && !tasks.length}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('star', 20)}</span><h2>No favorites yet</h2><p>Star a project or task to pin it here.</p><Link class="btn btn-secondary btn-sm" href={projectsIndex().url}>Browse projects</Link></div></div>
    {:else}
      <h2 class="sec" style="margin-bottom:10px">Projects <span class="faint" style="font-weight:500">{projects.length}</span></h2>
      <div class="pgrid" style="margin-bottom:28px">
        {#each projects as project (project.id)}
          <Link class="pcard" href={`/projects/${project.id}`} aria-label={project.name}>
            <div class="row"><span class="pico" style={`--c:var(--${project.color || 'indigo'})`}>{@html ic('folder', 14)}</span><div class="grow trunc"><b style="font-weight:600;font-size:14px">{project.name}</b></div><span style="color:var(--amber)">{@html ic('star', 12)}</span></div>
            <div class="desc">{project.description || ''}</div>
            <div class="row" style="gap:10px"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num" style="font-size:12px;font-weight:500">{completionFor(project)}%</span></div>
            <div class="foot"><span class="row" style="gap:4px">{@html ic('circle-check', 12)}{project.completed_tasks_count || 0}/{project.tasks_count || 0}</span><span class="row" style="gap:4px">{@html ic('calendar', 12)}{shortDate(project.due_date)}</span><span class="row hide-m" style="gap:4px">{@html ic('clock', 12)}{timeSince(project.updated_at)}</span><span class="sp"></span></div>
          </Link>
        {/each}
      </div>
      <h2 class="sec" style="margin-bottom:6px">Tasks <span class="faint" style="font-weight:500">{tasks.length}</span></h2>
      {#if tasks.length}
        <div class="panel" style="overflow:hidden">
          {#each tasks as task (task.id)}
            <div class="mini">
              <span>{@html ic(task.status === 'done' ? 'circle-check' : 'circle', 14)}</span>
              <span class="tt grow">{task.title}</span>
              <span class="pj hide-m"><span class="pdot" style={`--c:var(--${task.project.color || 'indigo'})`}></span>{task.project.name}</span>
              <span class="num muted hide-m">{shortDate(task.due_date)}</span>
              <button type="button" class="ibtn ibtn-xs" style="color:var(--amber)" aria-label={`Remove ${task.title} from favorites`} onclick={() => toggleTaskFavorite(task)}>{@html ic('star', 13)}</button>
            </div>
          {/each}
        </div>
      {:else}
        <p class="faint">Open a task and click the star to add it here.</p>
      {/if}
    {/if}
  </div>
</AppLayout>
