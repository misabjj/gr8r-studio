<script>
  import { Link, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { index as projectsRoute } from '../../routes/projects';
  import { update as updateTaskRoute } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';

  let { projects = [], tasks = [], stats = {} } = $props();
  const page = usePage();
  let localTasks = $state([]);
  let localStats = $state({ openTasks: 0, completedTasks: 0, projects: 0 });
  let selectedTab = $state('upcoming');
  let errorMessage = $state('');

  $effect(() => { localTasks = [...tasks]; });
  $effect(() => { localStats = { openTasks: 0, completedTasks: 0, projects: 0, ...stats }; });

  let openTasks = $derived(localTasks.filter(task => task.status !== 'done'));
  let completedTasks = $derived(localTasks.filter(task => task.status === 'done'));
  let shownTasks = $derived(selectedTab === 'completed' ? completedTasks : openTasks);
  let activeProjects = $derived(projects.filter(project => project.status === 'active'));
  let overdueTasks = $derived(openTasks.filter(task => task.due_date && new Date(`${task.due_date}T00:00:00`) < new Date(new Date().toDateString())));

  function formatDate(value) {
    if (!value) return 'No date';
    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function greeting() {
    const hour = new Date().getHours();
    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  }

  function statusLabel(status) {
    return ({ backlog: 'Backlog', todo: 'To Do', progress: 'In Progress', review: 'Review', done: 'Done' })[status] || status;
  }

  function completionFor(project) {
    if (!project.tasks_count) return 0;
    return Math.round(((project.completed_tasks_count || 0) / project.tasks_count) * 100);
  }

  async function toggleComplete(task) {
    errorMessage = '';
    const nextStatus = task.status === 'done' ? 'todo' : 'done';
    try {
      const updated = await jsonRequest(updateTaskRoute(task.id).url, 'PATCH', { status: nextStatus });
      localTasks = localTasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
      localStats = {
        ...localStats,
        openTasks: localStats.openTasks + (nextStatus === 'done' ? -1 : 1),
        completedTasks: localStats.completedTasks + (nextStatus === 'done' ? 1 : -1),
      };
    } catch (error) {
      errorMessage = error.message;
    }
  }
</script>

<svelte:head><title>Home · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page">
    <div class="ph">
      <div>
        <h1>{greeting()}, {(page.props.auth?.user?.name || 'Tanjim Islam').split(' ')[0]}</h1>
        <p><span class="num">{new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}</span> · Here's what's happening across your workspace.</p>
      </div>
      <div class="acts">
        <Link class="btn btn-secondary hide-m" href={projectsRoute().url}>{@html ic('folder-plus', 14)}New project</Link>
        <Link class="btn btn-primary" href={projectsRoute().url}>{@html ic('plus', 14)}New task</Link>
      </div>
    </div>

    {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}

    <div class="stats" style="margin-bottom:16px">
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('folder-kanban', 14)}Active projects</span><span class="v">{activeProjects.length}</span><span class="d">{projects.filter(project => project.status === 'paused').length} on hold</span></Link>
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('circle-dashed', 14)}Open tasks</span><span class="v">{localStats.openTasks}</span><span class="d">Across your projects</span></Link>
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('circle-check', 14)}Completed</span><span class="v">{localStats.completedTasks}</span><span class="d up">All time</span></Link>
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('clock-alert', 14)}Overdue</span><span class="v" style={overdueTasks.length ? 'color:var(--red)' : ''}>{overdueTasks.length}</span><span class:bad={overdueTasks.length > 0} class="d">{overdueTasks.length ? 'need attention' : 'all on track'}</span></Link>
    </div>

    <div class="grid2">
      <div class="stack">
        <section class="panel" aria-labelledby="h-mytasks">
          <div class="panel-h">
            <h2 id="h-mytasks">My tasks</h2>
            <div class="acts">
              <div class="seg" role="tablist" aria-label="Task status">
                <button role="tab" aria-selected={selectedTab === 'upcoming'} class:on={selectedTab === 'upcoming'} onclick={() => selectedTab = 'upcoming'}>Upcoming <span class="faint">{openTasks.length}</span></button>
                <button role="tab" aria-selected={selectedTab === 'completed'} class:on={selectedTab === 'completed'} onclick={() => selectedTab = 'completed'}>Completed <span class="faint">{completedTasks.length}</span></button>
              </div>
            </div>
          </div>
          {#if shownTasks.length}
            {#each shownTasks.slice(0, 7) as task (task.id)}
              <div class="mini" class:done={task.status === 'done'}>
                <button class="check round" class:checked={task.status === 'done'} aria-label={`${task.status === 'done' ? 'Reopen' : 'Complete'} ${task.title}`} onclick={() => toggleComplete(task)}>{#if task.status === 'done'}{@html ic('check', 12)}{/if}</button>
                <span class="trunc grow"><span class="tt">{task.title}</span></span>
                <span class={`pill st-${task.status}`}>{statusLabel(task.status)}</span>
                <span class="num muted" style="font-size:12px">{formatDate(task.due_date)}</span>
              </div>
            {/each}
          {:else}
            <div class="panel-b faint">{selectedTab === 'completed' ? 'No completed tasks yet.' : 'No open tasks. You are all caught up.'}</div>
          {/if}
        </section>

        <section class="panel">
          <div class="panel-h"><h2>Project progress</h2><div class="acts"><Link class="btn btn-sm btn-ghost" href={projectsRoute().url}>All projects</Link></div></div>
          <div style="overflow-x:auto">
            <table class="perm-t" style="min-width:560px">
              <thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Status</th><th style="text-align:left;width:26%">Progress</th><th style="text-align:left">Tasks</th><th style="text-align:right;padding-right:14px">Key</th></tr></thead>
              <tbody>
                {#each activeProjects as project (project.id)}
                  <tr>
                    <td style="padding-left:14px"><Link class="row" href={`/projects/${project.id}`}><span class="pico" style="--c:var(--accent)">{@html ic('folder', 14)}</span><b style="font-weight:500">{project.name}</b></Link></td>
                    <td style="text-align:left"><span class="pill st-progress">In Progress</span></td>
                    <td><span class="row"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num faint" style="font-size:11.5px;width:30px">{completionFor(project)}%</span></span></td>
                    <td class="num">{project.tasks_count || 0}</td>
                    <td class="num muted" style="text-align:right;padding-right:14px">{project.key}</td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
          {#if activeProjects.length === 0}<div class="panel-b faint">Create a project to start organizing your work.</div>{/if}
        </section>
      </div>

      <div class="stack">
        <section class="panel">
          <div class="panel-h"><h2>Upcoming deadlines</h2><div class="acts"><span class="btn btn-sm btn-ghost">{@html ic('calendar', 13)}Calendar</span></div></div>
          <div class="panel-b" style="padding-bottom:6px">
            {#if openTasks.some(task => task.due_date)}
              {#each openTasks.filter(task => task.due_date).slice(0, 7) as task (task.id)}
                <div class="row" style="height:34px;gap:8px;font-size:13px"><span class="pdot" style="--c:var(--accent)"></span><span class="trunc grow">{task.title}</span><span class="num muted">{formatDate(task.due_date)}</span></div>
              {/each}
            {:else}
              <div class="faint" style="font-size:12.5px;padding:4px 0 2px">Nothing due</div>
            {/if}
          </div>
        </section>
        <section class="panel">
          <div class="panel-h"><h2>Recent activity</h2><div class="acts"><span class="btn btn-sm btn-ghost">View all</span></div></div>
          <div class="panel-b faint">Activity will appear here as your team works.</div>
        </section>
      </div>
    </div>
  </div>
</AppLayout>
