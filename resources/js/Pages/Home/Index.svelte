<script>
  import { Link, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { index as projectsRoute } from '../../routes/projects';
  import { index as calendarRoute } from '../../routes/calendar';
  import { index as activityRoute } from '../../routes/activity';
  import { index as myTasksRoute } from '../../routes/my-tasks';
  import { show as projectShowRoute } from '../../routes/projects';
  import { update as updateTaskRoute } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS } from '@/src/core/constants.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';
  import { ago } from '@/src/core/utils.js';

  let { projects = [], tasks = [], myTasks = [], stats = {}, workspaceData = {} } = $props();
  const page = usePage();
  let localTasks = $state([]);
  let localMyTasks = $state([]);
  let localStats = $state({ openTasks: 0, completedTasks: 0, completedThisWeek: 0, projects: 0 });
  let selectedTab = $state('upcoming');
  let errorMessage = $state('');

  const workspaceMembers = $derived(workspaceData.members || []);
  const workspaceProjects = $derived(workspaceData.projects || []);
  const workspaceSourceTasks = $derived(workspaceData.tasks || []);
  const workspaceMemberById = $derived(new Map(workspaceMembers.map(member => [member.id, member])));
  const workspaceProjectById = $derived(new Map(workspaceProjects.map(project => [project.id, project])));
  const workspaceTaskById = $derived(new Map(workspaceSourceTasks.map(task => [task.id, task])));
  let recentActivity = $derived((workspaceData.activity || []).slice(0, 7));

  $effect(() => { localTasks = [...tasks]; });
  $effect(() => { localMyTasks = [...myTasks]; });
  $effect(() => { localStats = { openTasks: 0, completedTasks: 0, completedThisWeek: 0, projects: 0, ...stats }; });

  let openTasks = $derived(localTasks.filter(task => task.status !== 'done'));
  let completedTasks = $derived(localTasks.filter(task => task.status === 'done'));
  let myOverdueTasks = $derived(localMyTasks.filter(task => task.status !== 'done' && task.due_date && new Date(`${task.due_date.slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString())));
  let shownTasks = $derived(selectedTab === 'completed' ? localMyTasks.filter(task => task.status === 'done') : selectedTab === 'overdue' ? myOverdueTasks : localMyTasks.filter(task => task.status !== 'done' && !myOverdueTasks.includes(task)));
  let activeProjects = $derived(projects.filter(project => project.status !== 'complete'));
  let overdueTasks = $derived(openTasks.filter(task => task.due_date && new Date(`${task.due_date.slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString())));
  let upcomingTasks = $derived(openTasks.filter(task => !overdueTasks.includes(task)));

  function formatDate(value) {
    if (!value) return 'No date';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function shortDate(value) {
    if (!value) return '—';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function greeting() {
    const hour = new Date().getHours();
    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  }

  function completionFor(project) {
    if (!project.tasks_count) return 0;
    return Math.round(((project.completed_tasks_count || 0) / project.tasks_count) * 100);
  }

  function projectStatusLabel(status) {
    return ({ active: 'In Progress', planning: 'Planning', risk: 'At Risk', hold: 'On Hold', complete: 'Completed', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[status] || status;
  }

  function isThisWeek(value) {
    if (!value) return false;
    const today = new Date();
    const weekStart = new Date(today);
    weekStart.setDate(today.getDate() - ((today.getDay() + 6) % 7));
    weekStart.setHours(0, 0, 0, 0);
    return new Date(value) >= weekStart;
  }

  async function toggleComplete(task) {
    errorMessage = '';
    const nextStatus = task.status === 'done' ? 'todo' : 'done';
    try {
      const updated = await jsonRequest(updateTaskRoute(task.id).url, 'PATCH', { status: nextStatus });
      localTasks = localTasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
      localMyTasks = localMyTasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
      localStats = {
        ...localStats,
        openTasks: localStats.openTasks + (nextStatus === 'done' ? -1 : 1),
        completedTasks: localStats.completedTasks + (nextStatus === 'done' ? 1 : -1),
        completedThisWeek: localStats.completedThisWeek + (nextStatus === 'done' ? 1 : isThisWeek(task.completed_at) ? -1 : 0),
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
        <h1>{greeting()}, {(page.props.auth?.user?.name || '').split(' ')[0]}</h1>
        <p><span class="num">{new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}</span> · Here's what's happening across your workspace.</p>
      </div>
      <div class="acts">
        <Link class="btn btn-secondary hide-m" href={projectsRoute().url}>{@html ic('folder-plus', 14)}New project</Link>
        <Link class="btn btn-primary" href={projectsRoute().url}>{@html ic('plus', 14)}New task</Link>
      </div>
    </div>

    {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}

    <div class="stats" style="margin-bottom:16px">
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('folder-kanban', 14)}Active projects</span><span class="v">{activeProjects.length}</span><span class="d">{projects.filter(project => project.status === 'risk').length} at risk</span></Link>
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('circle-dashed', 14)}Open tasks</span><span class="v">{localStats.openTasks}</span><span class="d">{localMyTasks.filter(task => task.status !== 'done').length} assigned to you</span></Link>
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('circle-check', 14)}Completed</span><span class="v">{localStats.completedThisWeek}</span><span class="d up">this week</span></Link>
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('clock-alert', 14)}Overdue</span><span class="v" style={overdueTasks.length ? 'color:var(--red)' : ''}>{overdueTasks.length}</span><span class:bad={overdueTasks.length > 0} class="d">{overdueTasks.length ? 'need attention' : 'all on track'}</span></Link>
    </div>

    <div class="grid2">
      <div class="stack">
        <section class="panel" aria-labelledby="h-mytasks">
          <div class="panel-h">
            <h2 id="h-mytasks">My tasks</h2>
            <div class="acts">
              <div class="seg" role="tablist" aria-label="Task status">
                <button role="tab" aria-selected={selectedTab === 'upcoming'} class:on={selectedTab === 'upcoming'} onclick={() => selectedTab = 'upcoming'}>Upcoming <span class="faint">{localMyTasks.filter(task => task.status !== 'done' && !myOverdueTasks.includes(task)).length}</span></button>
                <button role="tab" aria-selected={selectedTab === 'overdue'} class:on={selectedTab === 'overdue'} onclick={() => selectedTab = 'overdue'}>Overdue <span class="faint">{myOverdueTasks.length}</span></button>
                <button role="tab" aria-selected={selectedTab === 'completed'} class:on={selectedTab === 'completed'} onclick={() => selectedTab = 'completed'}>Completed <span class="faint">{localMyTasks.filter(task => task.status === 'done').length}</span></button>
              </div>
            </div>
          </div>
          {#if shownTasks.length}
            {#each shownTasks.slice(0, 7) as task (task.id)}
              <div class="mini" class:done={task.status === 'done'}>
                <button class="check round" class:checked={task.status === 'done'} aria-label={`${task.status === 'done' ? 'Reopen' : 'Complete'} ${task.title}`} onclick={() => toggleComplete(task)}>{#if task.status === 'done'}{@html ic('check', 12)}{/if}</button>
                <span>{@html stIcon(task.status, 13)}</span>
                <span class="trunc grow"><span class="tt">{task.title}</span></span>
                {#if task.project}<span class="key">{task.project.key}</span>{/if}
                <span>{@html prIcon(task.priority, 13)}</span>
                <span class="num muted" style="font-size:12px">{formatDate(task.due_date)}</span>
              </div>
            {/each}
          {:else}
            <div class="panel-b faint">{selectedTab === 'overdue' ? 'Nothing overdue. Every task assigned to you is on schedule.' : selectedTab === 'completed' ? 'No completed tasks yet.' : 'No open tasks. You are all caught up.'}</div>
          {/if}
        </section>

        <section class="panel">
          <div class="panel-h"><h2>Project progress</h2><div class="acts"><Link class="btn btn-sm btn-ghost" href={projectsRoute().url}>All projects</Link></div></div>
          <div style="overflow-x:auto">
            <table class="perm-t" style="min-width:560px">
              <thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Status</th><th style="text-align:left;width:26%">Progress</th><th style="text-align:left">Due</th><th style="text-align:right;padding-right:14px">Team</th></tr></thead>
              <tbody>
                {#each activeProjects as project (project.id)}
                  <tr>
                    <td style="padding-left:14px"><Link class="row" href={`/projects/${project.id}`}><span class="pico" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span><b style="font-weight:500">{project.name}</b></Link></td>
                    <td style="text-align:left"><span class={`pill ${['completed', 'complete'].includes(project.status) ? 'st-done' : ['paused', 'hold', 'planning'].includes(project.status) ? 'st-backlog' : 'st-progress'}`}>{projectStatusLabel(project.status)}</span></td>
                    <td><span class="row"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num faint" style="font-size:11.5px;width:30px">{completionFor(project)}%</span></span></td>
                    <td class="num muted">{shortDate(project.due_date)}</td>
                    <td class="num muted" style="text-align:right;padding-right:14px">—</td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div class="stack">
        <section class="panel">
          <div class="panel-h"><h2>Upcoming deadlines</h2><div class="acts"><Link class="btn btn-sm btn-ghost" href={calendarRoute().url}>{@html ic('calendar', 13)}Calendar</Link></div></div>
          <div class="panel-b" style="padding-bottom:6px">
            {#each [{ label: 'Today', days: 0 }, { label: 'Tomorrow', days: 1 }, { label: 'This week', days: 2 }] as group}
              {@const allGroupedTasks = upcomingTasks.filter(task => {
                if (!task.due_date) return false;
                const due = new Date(`${task.due_date.slice(0, 10)}T00:00:00`);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const days = Math.floor((due - today) / 86400000);
                return group.days === 2 ? days >= 2 && days <= 7 : days === group.days;
              })}
              {@const groupedTasks = allGroupedTasks.slice(0, 4)}
              <div style="margin-bottom:10px">
                <div class="row" style="font-size:11.5px;font-weight:600;color:var(--text-2);margin-bottom:4px">{group.label}<span class="faint" style="font-weight:500">{allGroupedTasks.length}</span></div>
                {#if groupedTasks.length}
                  {#each groupedTasks as task (task.id)}
                    <div class="row" style="height:30px;gap:8px;font-size:13px"><span class="pdot" style={`--c:var(--${task.project?.color || 'accent'})`}></span><span class="trunc grow">{task.title}</span>{@html prIcon(task.priority, 13)}</div>
                  {/each}
                {:else}<div class="faint" style="font-size:12.5px;padding:4px 0 2px">Nothing due</div>{/if}
              </div>
            {/each}
          </div>
        </section>
        <section class="panel">
          <div class="panel-h"><h2>Recent activity</h2><div class="acts"><Link class="btn btn-sm btn-ghost" href={activityRoute().url}>View all</Link></div></div>
          <div class="panel-b feed lined">
            {#each recentActivity as activity (activity.id)}
              {@const member = workspaceMemberById.get(activity.by)}
              {@const task = workspaceTaskById.get(activity.task)}
              {@const project = workspaceProjectById.get(activity.project)}
              <div class="fitem">
                <span class="av sm" style={`--c:${member?.c || '#8A867E'}`} aria-label={member?.name || 'Someone'}>{member?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span>
                <div class="grow">
                  <b>{activity.by === workspaceData.me ? 'You' : member?.name || 'Someone'}</b>
                  <span class="muted"> {activity.verb} </span>
                  {#if task && project?.database_id}
                    <Link class="obj" href={projectShowRoute(project.database_id).url}>{task.title}</Link>
                  {:else if task}
                    <span class="obj">{task.title}</span>
                  {:else if project?.database_id}
                    <Link class="obj" href={projectShowRoute(project.database_id).url}>{project.name}</Link>
                  {:else if project}
                    <span class="obj">{project.name}</span>
                  {/if}
                  {#if activity.extra}<span class="muted"> {activity.extra}</span>{/if}
                  {#if project && task}<span class="faint"> in {project.name}</span>{/if}
                </div>
                <time>{ago(activity.at)}</time>
              </div>
            {/each}
          </div>
        </section>
      </div>
    </div>
  </div>
</AppLayout>
