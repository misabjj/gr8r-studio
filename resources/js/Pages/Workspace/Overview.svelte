<script>
  import { Link, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { show as projectShow, index as projectsIndex } from '../../routes/projects';
  import { index as timelineRoute } from '../../routes/timeline';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS } from '@/src/core/constants.js';
  import { stIcon } from '@/src/ui/helpers.js';

  let { projects = [], stats = {}, statusCounts = {}, workload = [] } = $props();
  const page = usePage();
  const statuses = [
    { id: 'backlog', name: 'Backlog' },
    { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' },
    { id: 'review', name: 'Review' },
    { id: 'done', name: 'Done' },
  ];

  let completionRate = $derived(Math.round((stats.completedTasks / Math.max(stats.tasks, 1)) * 100));
  let totalTasks = $derived(statuses.reduce((total, status) => total + (statusCounts[status.id] || 0), 0));
  let maximumWorkload = $derived(Math.max(...workload.map(person => person.tasks), 1));

  function statusName(status) {
    return ({ active: 'In Progress', planning: 'Planning', risk: 'At Risk', hold: 'On Hold', complete: 'Completed', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[status] || status;
  }

  function completionFor(project) {
    return project.tasks_count ? Math.round((project.completed_tasks_count / project.tasks_count) * 100) : 0;
  }

  function formatDate(value) {
    if (!value) return '—';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }
</script>

<svelte:head><title>Workspace overview · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page">
    <div class="ph">
      <div><h1>Workspace overview</h1><p>Health of every project in {page.props.appName || 'Gr8r Studio'}.</p></div>
      <div class="acts"><Link class="btn btn-secondary" href={timelineRoute().url}>{@html ic('chart-gantt', 14)}Timeline</Link><Link class="btn btn-primary" href={projectsIndex().url}>{@html ic('plus', 14)}New project</Link></div>
    </div>
    <div class="stats" style="margin-bottom:16px">
      <Link class="stat" href={projectsIndex().url}><span class="k">Projects</span><span class="v">{stats.projects || 0}</span><span class="d">{stats.completedProjects || 0} completed</span></Link>
      <Link class="stat" href={projectsIndex().url}><span class="k">Tasks</span><span class="v">{stats.tasks || 0}</span><span class="d">{stats.openTasks || 0} open</span></Link>
      <Link class="stat" href={projectsIndex().url}><span class="k">Completion rate</span><span class="v">{completionRate}%</span><span class="d">across all projects</span></Link>
    </div>
    <div class="grid2">
      <section class="panel">
        <div class="panel-h"><h2>Portfolio</h2></div>
        <div style="overflow-x:auto">
          <table class="perm-t" style="min-width:600px">
            <thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Status</th><th style="text-align:left;width:22%">Progress</th><th>Open</th><th>Overdue</th><th style="text-align:left">Due</th></tr></thead>
            <tbody>
              {#each projects as project (project.id)}
                {@const progress = completionFor(project)}
                <tr><td style="padding-left:14px"><Link class="row" href={projectShow(project.id).url}><span class="pico" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span><span class="trunc" style="font-weight:500">{project.name}</span></Link></td><td style="text-align:left"><span class={`pill ${['completed', 'complete'].includes(project.status) ? 'st-done' : ['paused', 'hold', 'planning'].includes(project.status) ? 'st-backlog' : 'st-progress'}`}>{statusName(project.status)}</span></td><td><span class="row"><span class="prog"><i style={`width:${progress}%`}></i></span><span class="num faint" style="font-size:11.5px;width:30px">{progress}%</span></span></td><td class="num">{(project.tasks_count || 0) - (project.completed_tasks_count || 0)}</td><td class="num" style={`color:${project.overdue_tasks_count ? 'var(--red)' : 'var(--text-3)'}`}>{project.overdue_tasks_count || 0}</td><td style="text-align:left" class="num muted">{formatDate(project.due_date)}</td></tr>
              {/each}
            </tbody>
          </table>
        </div>
      </section>
      <div class="stack">
        <section class="panel">
          <div class="panel-h"><h2>Tasks by status</h2></div>
          <div class="panel-b">
            <div class="stackbar" style="height:10px;margin-bottom:12px">{#each statuses as status (status.id)}<i style={`width:${((statusCounts[status.id] || 0) / Math.max(totalTasks, 1)) * 100}%;background:var(--st-${status.id})`} title={`${status.name}: ${statusCounts[status.id] || 0}`}></i>{/each}</div>
            {#each statuses as status (status.id)}
              {@const count = statusCounts[status.id] || 0}
              <div class="row" style="height:28px;font-size:13px">{@html stIcon(status.id)}<span class="grow">{status.name}</span><span class="num muted">{count}</span><span class="num faint" style="width:36px;text-align:right">{Math.round((count / Math.max(totalTasks, 1)) * 100)}%</span></div>
            {/each}
          </div>
        </section>
        <section class="panel">
          <div class="panel-h"><h2>Workload</h2><div class="acts"><span class="faint" style="font-size:11.5px">Open tasks per person</span></div></div>
          <div class="panel-b">
            {#each workload as person (person.id)}
              <div class="row" style="height:30px;font-size:13px">
                <span class="av sm">{person.name.split(' ').map(part => part[0]).slice(0, 2).join('')}</span>
                <span style="width:96px" class="trunc">{person.name.split(' ')[0]}</span>
                <span class="grow" style="display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--surface-3)">
                  <span style={`display:flex;width:${(person.tasks / maximumWorkload) * 100}%`}>
                    {#each statuses.filter(status => status.id !== 'done') as status (status.id)}
                      {@const count = person.statusCounts[status.id] || 0}
                      {#if count}<i style={`display:block;flex:${count};background:var(--st-${status.id})`}></i>{/if}
                    {/each}
                  </span>
                </span>
                <span class="num muted" style="width:22px;text-align:right">{person.tasks}</span>
              </div>
            {/each}
          </div>
        </section>
        <section class="panel"><div class="panel-h"><h2>Upcoming milestones</h2></div><div class="panel-b faint">No upcoming milestones</div></section>
      </div>
    </div>
  </div>
</AppLayout>
