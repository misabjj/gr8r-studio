<script>
  import { Link, router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { show as projectShow, index as projectsIndex } from '../../routes/projects';
  import { show as memberShow } from '../../routes/members';
  import { index as timelineRoute } from '../../routes/timeline';
  import { ago, diffD, esc, fmtDate, parse, relDate, TODAY } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS, PSTAT, STATUSES } from '@/src/core/constants.js';
  import { stIcon } from '@/src/ui/helpers.js';
  import { setNavigationIntent } from '../../lib/navigation-intents.js';

  let { projects = [], stats = {}, statusCounts = {}, workload = [], workspaceData = {} } = $props();

  function startProjectCreation() {
    setNavigationIntent({ type: 'create-project' });
    router.visit(projectsIndex().url);
  }

  let sourceProjects = $derived(workspaceData.projects || []);
  let sourceTasks = $derived(workspaceData.tasks || []);
  let sourceMembers = $derived(workspaceData.members || []);
  let memberById = $derived(new Map(sourceMembers.map(member => [member.id, member])));
  let seededWorkspace = $derived(sourceProjects.length > 0);
  let visibleProjects = $derived(seededWorkspace
    ? sourceProjects.filter(project => !project.archived && (!project.private || project.members?.includes(workspaceData.me)))
    : projects);
  let visibleTasks = $derived(seededWorkspace
    ? sourceTasks.filter(task => !task.archived && visibleProjects.some(project => project.id === task.project))
    : []);
  let statusSummary = $derived(seededWorkspace
    ? STATUSES.map(status => ({ ...status, count: visibleTasks.filter(task => task.status === status.id).length }))
    : STATUSES.map(status => ({ ...status, count: statusCounts[status.id] || 0 })));
  let totalTasks = $derived(statusSummary.reduce((total, status) => total + status.count, 0));
  let statsProjects = $derived(seededWorkspace ? visibleProjects.length : stats.projects || 0);
  let statsCompletedProjects = $derived(seededWorkspace
    ? visibleProjects.filter(project => ['complete', 'completed'].includes(project.status)).length
    : stats.completedProjects || 0);
  let statsTaskCount = $derived(seededWorkspace ? visibleTasks.length : stats.tasks || 0);
  let statsOpenTasks = $derived(seededWorkspace ? visibleTasks.filter(task => task.status !== 'done').length : stats.openTasks || 0);
  let completionRate = $derived(Math.round((statusSummary.find(status => status.id === 'done')?.count || stats.completedTasks || 0) / Math.max(totalTasks || statsTaskCount, 1) * 100));
  let members = $derived(seededWorkspace ? sourceMembers : []);
  let pendingInvites = $derived(members.filter(member => member.status === 'invited').length);
  let maximumWorkload = $derived(Math.max(...workload.map(person => person.tasks), 1));

  let workloadRows = $derived.by(() => {
    if (!seededWorkspace) return workload;
    return members.filter(member => member.status === 'active').map(member => {
      const assignedTasks = visibleTasks.filter(task => task.assignee === member.id && task.status !== 'done');
      return {
        id: member.id,
        name: member.name,
        color: member.c,
        tasks: assignedTasks.length,
        statusCounts: Object.fromEntries(STATUSES.filter(status => status.id !== 'done').map(status => [
          status.id,
          assignedTasks.filter(task => task.status === status.id).length,
        ])),
      };
    }).sort((first, second) => second.tasks - first.tasks);
  });
  let maxWorkload = $derived(Math.max(...workloadRows.map(person => person.tasks), 1));
  let milestones = $derived.by(() => visibleProjects.flatMap(project => (project.milestones || [])
    .filter(milestone => diffD(parse(milestone.date), TODAY) >= 0)
    .map(milestone => ({ ...milestone, project })))
    .sort((first, second) => first.date.localeCompare(second.date))
    .slice(0, 6));

  function projectProgress(project) {
    if (!seededWorkspace) {
      return project.tasks_count ? Math.round((project.completed_tasks_count / project.tasks_count) * 100) : 0;
    }
    const tasks = visibleTasks.filter(task => task.project === project.id);
    return tasks.length ? Math.round(tasks.filter(task => task.status === 'done').length / tasks.length * 100) : 0;
  }

  function projectTaskCount(project) {
    if (!seededWorkspace) return { open: (project.tasks_count || 0) - (project.completed_tasks_count || 0), overdue: project.overdue_tasks_count || 0 };
    const tasks = visibleTasks.filter(task => task.project === project.id);
    return {
      open: tasks.filter(task => task.status !== 'done').length,
      overdue: tasks.filter(task => task.status !== 'done' && task.due && diffD(parse(task.due), TODAY) < 0).length,
    };
  }

  function memberInitials(name) {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function openProjectRow(event, project) {
    if (event.type === 'keydown') {
      if (event.target !== event.currentTarget || !['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
    } else if (event.target.closest('a, button, input, select, textarea')) {
      return;
    }

    router.visit(projectShow(project.database_id || project.id, { query: { tab: 'overview' } }).url);
  }
</script>

<svelte:head><title>Workspace overview · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page">
    <div class="ph">
      <div><h1>Workspace overview</h1><p>Health of every project in {workspaceData.ws?.name || 'Gr8r Studio'}.</p></div>
      <div class="acts"><Link class="btn btn-secondary" href={timelineRoute().url}>{@html ic('chart-gantt', 14)}Timeline</Link><button class="btn btn-primary" type="button" onclick={startProjectCreation}>{@html ic('plus', 14)}New project</button></div>
    </div>
    <div class="stats" style="margin-bottom:16px">
      <div class="stat"><span class="k">Projects</span><span class="v">{statsProjects}</span><span class="d">{statsCompletedProjects} completed</span></div>
      <div class="stat"><span class="k">Tasks</span><span class="v">{statsTaskCount}</span><span class="d">{statsOpenTasks} open</span></div>
      <div class="stat"><span class="k">Completion rate</span><span class="v">{completionRate}%</span><span class="d">across all projects</span></div>
      <div class="stat"><span class="k">Members</span><span class="v">{members.length}</span><span class="d">{pendingInvites} pending invite</span></div>
    </div>
    <div class="grid2">
      <section class="panel">
        <div class="panel-h"><h2>Portfolio</h2></div>
        <div style="overflow-x:auto">
          <table class="perm-t" style="min-width:600px">
            <thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Lead</th><th style="text-align:left">Status</th><th style="text-align:left;width:22%">Progress</th><th>Open</th><th>Overdue</th><th style="text-align:left">Due</th></tr></thead>
            <tbody>
              {#each visibleProjects as project (project.id)}
                {@const progress = projectProgress(project)}
                {@const taskCounts = projectTaskCount(project)}
                {@const projectLead = memberById.get(project.lead)}
                {@const projectStatus = PSTAT[project.status] || PSTAT.planning}
                <tr role="link" tabindex="0" style="cursor:pointer" onclick={event => openProjectRow(event, project)} onkeydown={event => openProjectRow(event, project)}>
                  <td style="padding-left:14px"><Link class="row" href={projectShow(project.database_id || project.id, { query: { tab: 'overview' } }).url}><span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span><span class="trunc" style="font-weight:500">{project.name}</span></Link></td>
                  <td style="text-align:left"><span class="row">{#if projectLead}<span class="av sm" style={`--c:${projectLead.c}`} aria-label={projectLead.name}>{memberInitials(projectLead.name)}</span><span class="muted trunc">{projectLead.name.split(' ')[0]}</span>{:else}<span class="muted">—</span>{/if}</span></td>
                  <td style="text-align:left"><span class="pstatus" style={`--c:${projectStatus.c}`}><i></i>{projectStatus.name}</span></td>
                  <td><span class="row"><span class={`prog ${project.status === 'complete' ? 'green' : project.status === 'risk' ? 'red' : ''}`} role="progressbar" aria-valuenow={progress} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${progress / 100}`}></i></span><span class="num faint" style="font-size:11.5px;width:30px">{progress}%</span></span></td>
                  <td class="num">{taskCounts.open}</td>
                  <td class="num" style={`color:${taskCounts.overdue ? 'var(--red)' : 'var(--text-3)'}`}>{taskCounts.overdue}</td>
                  <td style="text-align:left" class="num muted">{fmtDate(project.due || project.due_date)}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      </section>
      <div class="stack">
        <section class="panel">
          <div class="panel-h"><h2>Tasks by status</h2></div>
          <div class="panel-b">
            <div class="stackbar" style="height:10px;margin-bottom:12px">{#each statusSummary as status (status.id)}<i style={`width:${(status.count / Math.max(totalTasks, 1)) * 100}%;background:var(--st-${status.id})`} title={`${status.name}: ${status.count}`}></i>{/each}</div>
            {#each statusSummary as status (status.id)}
              <div class="row" style="height:28px;font-size:13px">{@html stIcon(status.id)}<span class="grow">{status.name}</span><span class="num muted">{status.count}</span><span class="num faint" style="width:36px;text-align:right">{Math.round((status.count / Math.max(totalTasks, 1)) * 100)}%</span></div>
            {/each}
          </div>
        </section>
        <section class="panel">
          <div class="panel-h"><h2>Workload</h2><div class="acts"><span class="faint" style="font-size:11.5px">Open tasks per person</span></div></div>
          <div class="panel-b">
            {#each workloadRows as person (person.id)}
              <Link class="row" style="height:30px;font-size:13px;color:inherit" href={memberShow(person.id).url}>
                <span class="av sm" style={`--c:${person.color || '#8A867E'}`} aria-label={person.name}>{memberInitials(person.name)}</span>
                <span style="width:96px" class="trunc">{person.name.split(' ')[0]}</span>
                <span class="grow" style="display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--surface-3)">
                  <span style={`display:flex;width:${(person.tasks / maxWorkload) * 100}%`}>
                    {#each STATUSES.filter(status => status.id !== 'done') as status (status.id)}
                      {@const count = person.statusCounts[status.id] || 0}
                      {#if count}<i style={`display:block;flex:${count};background:var(--st-${status.id})`}></i>{/if}
                    {/each}
                  </span>
                </span>
                <span class="num muted" style="width:22px;text-align:right">{person.tasks}</span>
              </Link>
            {/each}
          </div>
        </section>
        <section class="panel">
          <div class="panel-h"><h2>Upcoming milestones</h2></div>
          <div class="panel-b">
            {#if milestones.length}
              {#each milestones as milestone (`${milestone.project.id}-${milestone.name}`)}
                <Link class="row" style="height:32px;font-size:13px" href={projectShow(milestone.project.database_id || milestone.project.id, { query: { tab: 'timeline' } }).url}>
                  <span style={`width:9px;height:9px;transform:rotate(45deg);background:${PCOLORS[milestone.project.color] || PCOLORS.slate};border-radius:2px;flex-shrink:0;margin:0 3px`}></span>
                  <span class="grow trunc">{milestone.name} <span class="faint">· {milestone.project.name}</span></span>
                  <span class="num muted">{relDate(milestone.date)}</span>
                </Link>
              {/each}
            {:else}<div class="faint">No upcoming milestones</div>{/if}
          </div>
        </section>
      </div>
    </div>
  </div>
</AppLayout>
