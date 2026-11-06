<script>
  import { Link, router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { index as projectsRoute } from '../../routes/projects';
  import { index as calendarRoute } from '../../routes/calendar';
  import { index as activityRoute } from '../../routes/activity';
  import { index as myTasksRoute } from '../../routes/my-tasks';
  import { show as projectShowRoute } from '../../routes/projects';
  import { update as updateTaskRoute } from '../../routes/tasks';
  import { index as tasksIndexRoute } from '../../routes/tasks';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import InviteMemberDialog from '../../Components/InviteMemberDialog.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { setNavigationIntent } from '../../lib/navigation-intents.js';
  import { useHttpRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS, PR, PSTAT, ST } from '@/src/core/constants.js';
  import { S } from '@/src/core/store.js';
  import { dueHtml, prIcon, stIcon } from '@/src/ui/helpers.js';
  import { ago, DAY, diffD, fmtDate, parse, TODAY } from '@/src/core/utils.js';

  const httpRequest = useHttpRequest();
  let { projects = [], tasks = [], myTasks = [], stats = {}, workspaceData = {} } = $props();
  const page = usePage();
  let localTasks = $state([]);
  let localMyTasks = $state([]);
  let selectedTab = $state(S.ui.homeTab || 'upcoming');
  let errorMessage = $state('');
  let taskFormOpen = $state(false);
  let inviteOpen = $state(false);
  let drawerTaskId = $state(null);
  let drawerTaskUpdates = $state({});

  const workspaceMembers = $derived(workspaceData.members || []);
  const workspaceProjects = $derived(workspaceData.projects || []);
  const hasWorkspaceSnapshot = $derived(workspaceProjects.length > 0);
  const accessibleSnapshotProjects = $derived(workspaceProjects.filter(project =>
    !project.private || project.members?.includes(workspaceData.me)
  ));
  const visibleSnapshotProjects = $derived(accessibleSnapshotProjects.filter(project =>
    !project.archived && !['complete', 'completed', 'archived'].includes(project.status)
  ));
  const visibleSnapshotProjectIds = $derived(new Set(accessibleSnapshotProjects.map(project => project.id)));
  const workspaceSourceTasks = $derived((workspaceData.tasks || [])
    .filter(task => !task.archived && visibleSnapshotProjectIds.has(task.project))
    .map(task => ({ ...task, database_id: task.database_id || task.id })));
  const workspaceMemberById = $derived(new Map(workspaceMembers.map(member => [member.id, member])));
  const workspaceProjectById = $derived(new Map(workspaceProjects.map(project => [project.id, project])));
  const workspaceTaskById = $derived(new Map(workspaceSourceTasks.map(task => [task.id, task])));
  let recentActivity = $derived((workspaceData.activity || []).slice(0, 7));
  let drawerTask = $derived.by(() => {
    const task = tasks.find(item => String(item.id) === String(drawerTaskId));
    return task ? { ...task, ...drawerTaskUpdates[String(task.id)] } : null;
  });

  $effect(() => {
    localTasks = hasWorkspaceSnapshot
      ? workspaceSourceTasks.map(task => ({ ...task }))
      : tasks.map(task => ({
        ...task,
        id: String(task.id),
        database_id: task.id,
        project: workspaceProjects.find(project => project.database_id === task.project_id)?.id || task.project?.id || '',
        assignee: String(task.assignee_id || ''),
        due: task.due_date,
        completedAt: task.completed_at ? new Date(task.completed_at).getTime() : null,
      }));
  });
  $effect(() => {
    localMyTasks = hasWorkspaceSnapshot
      ? localTasks.filter(task => task.assignee === workspaceData.me)
      : myTasks.map(task => ({
        ...task,
        id: String(task.id),
        database_id: task.id,
        project: workspaceProjects.find(project => project.database_id === task.project_id)?.id || task.project?.id || '',
        assignee: String(task.assignee_id || ''),
        due: task.due_date,
        completedAt: task.completed_at ? new Date(task.completed_at).getTime() : null,
      }));
  });
  let openTasks = $derived(localTasks.filter(task => task.status !== 'done'));
  let completedTasks = $derived(localTasks.filter(task => task.status === 'done'));
  let myOverdueTasks = $derived(localMyTasks.filter(task => task.status !== 'done' && task.due && diffD(parse(task.due), TODAY) < 0));
  let shownTasks = $derived.by(() => {
    if (selectedTab === 'overdue') return myOverdueTasks;
    const filteredTasks = selectedTab === 'completed'
      ? localMyTasks.filter(task => task.status === 'done')
      : localMyTasks.filter(task => task.status !== 'done' && !myOverdueTasks.includes(task));
    return filteredTasks.sort((first, second) => {
      const firstDate = first.due ? parse(first.due).getTime() : Number.MAX_SAFE_INTEGER;
      const secondDate = second.due ? parse(second.due).getTime() : Number.MAX_SAFE_INTEGER;
      return selectedTab === 'completed'
        ? (first.updated || first.completedAt || 0) - (second.updated || second.completedAt || 0)
        : firstDate - secondDate;
    });
  });
  let completedThisWeekCount = $derived(completedTasks.filter(task => task.completedAt && Date.now() - task.completedAt < 7 * DAY).length);
  let assignedOpenCount = $derived(localMyTasks.filter(task => task.status !== 'done').length);
  let activeProjects = $derived(hasWorkspaceSnapshot
    ? visibleSnapshotProjects
    : projects.filter(project => !['complete', 'completed', 'archived'].includes(project.status)));
  let overdueTasks = $derived(openTasks.filter(task => task.due && diffD(parse(task.due), TODAY) < 0));
  let upcomingTasks = $derived(openTasks.filter(task => !overdueTasks.includes(task)));

  function shortDate(value) {
    if (!value) return '—';
    return fmtDate(value.slice(0, 10));
  }

  function greeting() {
    const hour = new Date().getHours();
    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  }

  function selectHomeTab(tab) {
    selectedTab = tab;
    S.ui.homeTab = tab;
  }

  function completionFor(project) {
    const projectTasks = localTasks.filter(task => task.project === project.id);
    if (projectTasks.length) return Math.round((projectTasks.filter(task => task.status === 'done').length / projectTasks.length) * 100);
    if (!project.tasks_count) return 0;
    return Math.round(((project.completed_tasks_count || 0) / project.tasks_count) * 100);
  }

  function projectStatusLabel(status) {
    return ({ active: 'In Progress', planning: 'Planning', risk: 'At Risk', hold: 'On Hold', complete: 'Completed', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[status] || status;
  }

  async function toggleComplete(task) {
    errorMessage = '';
    const nextStatus = task.status === 'done' ? 'todo' : 'done';
    try {
      const updated = await httpRequest(updateTaskRoute(task.database_id || task.id).url, 'PATCH', { status: nextStatus });
      const completedAt = nextStatus === 'done' ? Date.now() : null;
      localTasks = localTasks.map(item => item.id === task.id ? { ...item, status: updated.status, completedAt } : item);
      localMyTasks = localMyTasks.map(item => item.id === task.id ? { ...item, status: updated.status, completedAt } : item);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  function startProjectCreation() {
    setNavigationIntent({ type: 'create-project' });
    router.visit(projectsRoute().url);
  }

  function taskCreated(task) {
    const projectKey = workspaceProjects.find(project => project.database_id === task.project_id)?.id || task.project?.id || '';
    const newTask = { ...task, id: String(task.id), database_id: task.id, project: projectKey, assignee: String(task.assignee_id || ''), due: task.due_date };
    localTasks = [newTask, ...localTasks];
    if (task.assignee_id === page.props.auth?.user?.id || task.assignee_id === workspaceData.me) localMyTasks = [newTask, ...localMyTasks];
  }

  function openTaskFromRow(event, task) {
    if (event.target.closest('button')) return;
    if (event.type === 'keydown') {
      if (event.target !== event.currentTarget) return;
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
    }
    drawerTaskId = task.database_id || task.id;
  }

  function openProjectFromRow(event, project) {
    if (event.target.closest('a, button')) return;
    if (event.type === 'keydown') {
      if (event.target !== event.currentTarget) return;
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
    }
    router.visit(projectShowRoute(projectRouteId(project), { query: { tab: 'overview' } }).url);
  }

  function updateDrawerTask(updatedTask) {
    drawerTaskUpdates = { ...drawerTaskUpdates, [String(updatedTask.id)]: updatedTask };
    const completedAt = updatedTask.status === 'done'
      ? updatedTask.completed_at ? new Date(updatedTask.completed_at).getTime() : Date.now()
      : null;
    const updateLocalTask = task => String(task.database_id || task.id) === String(updatedTask.id)
      ? {
        ...task,
        title: updatedTask.title,
        description: updatedTask.description,
        status: updatedTask.status,
        priority: updatedTask.priority,
        due: updatedTask.due_date,
        completedAt,
      }
      : task;

    localTasks = localTasks.map(updateLocalTask);
    localMyTasks = localMyTasks.map(updateLocalTask);
  }

  function taskProject(task) {
    return workspaceProjects.find(project => project.id === task.project)
      || projects.find(project => project.id === task.project_id || String(project.id) === String(task.project))
      || null;
  }

  function projectMembers(project) {
    if (Array.isArray(project.members) && typeof project.members[0] === 'string') {
      return project.members.map(memberId => workspaceMemberById.get(memberId)).filter(Boolean);
    }
    return project.members || [];
  }

  function projectProgress(project) {
    const projectTasks = localTasks.filter(task => task.project === project.id);
    if (projectTasks.length) return Math.round(projectTasks.filter(task => task.status === 'done').length / projectTasks.length * 100);
    return completionFor(project);
  }

  function projectRouteId(project) {
    return project.database_id || project.id;
  }

  function priorityOrder(priority) {
    return ({ urgent: 0, high: 1, medium: 2, low: 3, none: 4 })[priority] ?? 5;
  }

  function deadlineGroups() {
    return [{ label: 'Today', from: 0, to: 0 }, { label: 'Tomorrow', from: 1, to: 1 }, { label: 'This week', from: 2, to: 7 }].map(group => ({
      ...group,
      tasks: openTasks
        .filter(task => task.due && diffD(parse(task.due), TODAY) >= group.from && diffD(parse(task.due), TODAY) <= group.to)
        .sort((first, second) => priorityOrder(first.priority) - priorityOrder(second.priority)),
    }));
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
        <button type="button" class="btn btn-secondary hide-m" onclick={() => inviteOpen = true}>{@html ic('user-plus', 14)}Invite member</button>
        <button type="button" class="btn btn-secondary hide-m" onclick={startProjectCreation}>{@html ic('folder-plus', 14)}New project</button>
        <button class="btn btn-primary" type="button" onclick={() => taskFormOpen = true}>{@html ic('plus', 14)}New task</button>
      </div>
    </div>

    {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}

    <div class="stats" style="margin-bottom:16px">
      <Link class="stat" href={projectsRoute().url}><span class="k">{@html ic('folder-kanban', 14)}Active projects</span><span class="v">{activeProjects.length}</span><span class="d">{projects.filter(project => project.status === 'risk').length} at risk</span></Link>
      <Link class="stat" href={tasksIndexRoute({ query: { filter: 'open' } }).url}><span class="k">{@html ic('circle-dashed', 14)}Open tasks</span><span class="v">{openTasks.length}</span><span class="d">{openTasks.filter(task => task.assignee === workspaceData.me || String(task.assignee_id) === String(page.props.auth?.user?.id)).length} assigned to you</span></Link>
      <Link class="stat" href={tasksIndexRoute({ query: { filter: 'done' } }).url}><span class="k">{@html ic('circle-check', 14)}Completed</span><span class="v">{completedThisWeekCount}</span><span class="d up">this week</span></Link>
      <Link class="stat" href={tasksIndexRoute({ query: { filter: 'overdue' } }).url}><span class="k">{@html ic('clock-alert', 14)}Overdue</span><span class="v" style={overdueTasks.length ? 'color:var(--red)' : ''}>{overdueTasks.length}</span><span class:bad={overdueTasks.length > 0} class="d">{overdueTasks.length ? 'need attention' : 'all on track'}</span></Link>
    </div>

    <div class="grid2">
      <div class="stack">
        <section class="panel" aria-labelledby="h-mytasks">
          <div class="panel-h">
            <h2 id="h-mytasks">My tasks</h2>
            <div class="acts">
              <div class="seg" role="tablist" aria-label="Task status">
                <button role="tab" aria-selected={selectedTab === 'upcoming'} class:on={selectedTab === 'upcoming'} onclick={() => selectHomeTab('upcoming')}>Upcoming <span class="faint">{localMyTasks.filter(task => task.status !== 'done' && !myOverdueTasks.includes(task)).length}</span></button>
                <button role="tab" aria-selected={selectedTab === 'overdue'} class:on={selectedTab === 'overdue'} onclick={() => selectHomeTab('overdue')}>Overdue <span class="faint">{myOverdueTasks.length}</span></button>
                <button role="tab" aria-selected={selectedTab === 'completed'} class:on={selectedTab === 'completed'} onclick={() => selectHomeTab('completed')}>Completed <span class="faint">{localMyTasks.filter(task => task.status === 'done').length}</span></button>
              </div>
              <Link class="ibtn ibtn-sm" href={myTasksRoute().url} aria-label="Open My Tasks">{@html ic('arrow-up-right', 15)}</Link>
            </div>
          </div>
          {#if shownTasks.length}
            {#each shownTasks.slice(0, 7) as task (task.id)}
              {@const project = taskProject(task)}
              <div class={`mini ${task.status === 'done' ? 'done' : ''}`} role="button" tabindex="0" onclick={event => openTaskFromRow(event, task)} onkeydown={event => openTaskFromRow(event, task)}>
                <input type="checkbox" class="check round" checked={task.status === 'done'} aria-label={`${task.status === 'done' ? 'Reopen' : 'Complete'} ${task.title}`} onclick={event => event.stopPropagation()} onchange={() => toggleComplete(task)} />
                <span data-tip={ST[task.status]?.name || task.status}>{@html stIcon(task.status)}</span>
                <span class="tt">{task.title}</span>
                {#if project}<span class="pj hide-m"><span class="pdot" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}></span><span class="trunc">{project.name}</span></span>{/if}
                <span data-tip={`${PR[task.priority || 'none']?.name || 'No'} priority`}>{@html prIcon(task.priority)}</span>
                <span class="hide-m" style="width:74px;text-align:right">{@html dueHtml(task, false)}</span>
              </div>
            {/each}
          {:else}
            <div class="panel-b faint">{selectedTab === 'overdue' ? 'Nothing overdue. Every task assigned to you is on schedule.' : selectedTab === 'completed' ? 'No completed tasks yet.' : 'No open tasks. You are all caught up.'}</div>
          {/if}
          {#if shownTasks.length > 7}<Link class="addrow" style="padding-left:14px;border-top:1px solid var(--divider);border-bottom:0" href={myTasksRoute().url}>View all {shownTasks.length} tasks {@html ic('arrow-right', 13)}</Link>{/if}
        </section>

        <section class="panel">
          <div class="panel-h"><h2>Project progress</h2><div class="acts"><Link class="btn btn-sm btn-ghost" href={projectsRoute().url}>All projects</Link></div></div>
          <div style="overflow-x:auto">
            <table class="perm-t" style="min-width:560px">
              <thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Status</th><th style="text-align:left;width:26%">Progress</th><th style="text-align:left">Due</th><th style="text-align:right;padding-right:14px">Team</th></tr></thead>
              <tbody>
                {#each activeProjects.filter(project => !project.private || project.members?.includes(workspaceData.me)) as project (project.id)}
                  {@const projectProgressPercent = projectProgress(project)}
                  {@const statusDetails = PSTAT[project.status] || { name: projectStatusLabel(project.status), c: 'var(--gray)' }}
                  <tr role="link" tabindex="0" style="cursor:pointer" onclick={event => openProjectFromRow(event, project)} onkeydown={event => openProjectFromRow(event, project)}>
                    <td style="padding-left:14px"><Link class="row" href={projectShowRoute(projectRouteId(project), { query: { tab: 'overview' } }).url}><span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span><b style="font-weight:500">{project.name}</b></Link></td>
                    <td style="text-align:left"><span class="pstatus" style={`--c:${statusDetails.c}`}><i></i>{statusDetails.name}</span></td>
                    <td><span class="row"><span class="prog" role="progressbar" aria-valuenow={projectProgressPercent} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${projectProgressPercent / 100}`}></i></span><span class="num faint" style="font-size:11.5px;width:30px">{projectProgressPercent}%</span></span></td>
                    <td class="num muted">{shortDate(project.due || project.due_date)}</td>
                    <td style="text-align:right;padding-right:14px"><span class="avs">{#each projectMembers(project).slice(0, 3) as member (member.id)}<span class="av sm" style={`--c:${member.c || member.color || '#8A867E'}`} aria-label={member.name}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{/each}{#if projectMembers(project).length > 3}<span class="av sm more" style="--c:var(--gray)">+{projectMembers(project).length - 3}</span>{/if}</span></td>
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
            {#each deadlineGroups() as group}
              {@const allGroupedTasks = group.tasks}
              {@const groupedTasks = allGroupedTasks.slice(0, 4)}
              <div style="margin-bottom:10px">
                <div class="row" style="font-size:11.5px;font-weight:600;color:var(--text-2);margin-bottom:4px">{group.label}<span class="faint" style="font-weight:500">{allGroupedTasks.length}</span></div>
                {#if groupedTasks.length}
                  {#each groupedTasks as task (task.id)}
                    <div class="row" role="button" tabindex="0" style="height:30px;cursor:pointer;gap:8px;font-size:13px" onclick={event => openTaskFromRow(event, task)} onkeydown={event => openTaskFromRow(event, task)}><span class="pdot" style={`--c:${PCOLORS[taskProject(task)?.color] || 'var(--accent)'}`}></span><span class="trunc grow">{task.title}</span>{@html prIcon(task.priority, 13)}<span class="av sm" style={`--c:${workspaceMemberById.get(task.assignee)?.c || '#8A867E'}`} aria-label={workspaceMemberById.get(task.assignee)?.name || ''}>{workspaceMemberById.get(task.assignee)?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || ''}</span></div>
                  {/each}
                  {#if allGroupedTasks.length > groupedTasks.length}<div class="faint" style="font-size:11.5px;padding-left:16px">+{allGroupedTasks.length - groupedTasks.length} more</div>{/if}
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
  <WorkspaceTaskForm open={taskFormOpen} {projects} onClose={() => taskFormOpen = false} onCreated={taskCreated} />
  {#if inviteOpen}<InviteMemberDialog onClose={() => inviteOpen = false} />{/if}
</AppLayout>
{#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateDrawerTask} />{/if}
