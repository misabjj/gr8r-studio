<script>
  import { router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { index as projectsIndex, show as projectShow, favorite as projectFavorite } from '../../routes/projects';
  import { update as taskUpdate } from '../../routes/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { ago, fmtDate, minsAgo } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS, PR, PSTAT, ST } from '@/src/core/constants.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';
  import { dueHtml } from '@/src/ui/helpers.js';

  const httpRequest = useHttpRequest();
  let { projects: initialProjects = [], tasks: initialTasks = [], workspaceData = {} } = $props();
  const page = usePage();
  let projects = $state([]);
  let tasks = $state([]);
  let errorMessage = $state('');
  let previousStatuses = $state(new Map());
  let drawerTaskId = $state(null);
  let projectMenu = $state(null);

  $effect(() => { projects = [...initialProjects]; });
  $effect(() => { tasks = [...initialTasks]; });

  let sourceProjects = $derived(workspaceData.projects || []);
  let sourceTasks = $derived(workspaceData.tasks || []);
  let sourceMembers = $derived(workspaceData.members || []);
  let projectOrder = $derived(new Map(sourceProjects.map((project, index) => [String(project.database_id || project.id), index])));
  let taskOrder = $derived(new Map(sourceTasks.map((task, index) => [String(task.database_id || task.id), index])));
  let membersByDatabaseId = $derived(new Map(sourceMembers.map(member => [String(member.database_id), member])));
  let projectsByDatabaseId = $derived(new Map(sourceProjects.map(project => [String(project.database_id), project])));
  let favoriteProjects = $derived([...projects].sort((first, second) =>
    (projectOrder.get(String(first.id)) ?? Number.MAX_SAFE_INTEGER) - (projectOrder.get(String(second.id)) ?? Number.MAX_SAFE_INTEGER)));
  let favoriteTasks = $derived([...tasks].sort((first, second) =>
    (taskOrder.get(String(first.id)) ?? Number.MAX_SAFE_INTEGER) - (taskOrder.get(String(second.id)) ?? Number.MAX_SAFE_INTEGER)));
  let drawerTask = $derived(tasks.find(task => task.id === drawerTaskId) || null);

  function completionFor(project) {
    return project.tasks_count ? Math.round((project.completed_tasks_count / project.tasks_count) * 100) : 0;
  }

  function memberInitials(name) {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function projectLastActivity(project) {
    const sourceProject = projectsByDatabaseId.get(String(project.id));
    return sourceProject?.last == null ? ago(new Date(project.updated_at).getTime()) : ago(minsAgo(sourceProject.last));
  }

  function canSeeProject(project) {
    return !project.is_private || project.members?.some(member => member.id === page.props.auth?.user?.id);
  }

  function openProject(project) {
    router.visit(projectShow(project.id).url);
  }

  function openProjectMenu(event, projectId) {
    event.stopPropagation();
    if (projectMenu?.projectId === projectId) {
      projectMenu = null;
      return;
    }

    const bounds = event.currentTarget.getBoundingClientRect();
    projectMenu = {
      projectId,
      left: Math.max(8, Math.min(bounds.right - 220, window.innerWidth - 228)),
      top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 140)),
    };
  }

  function closeProjectMenuOnWindowClick(event) {
    if (!(event.target instanceof Element) || !event.target.closest('[data-project-menu]')) projectMenu = null;
  }

  function handleProjectMenuKeydown(event) {
    if (event.key === 'Escape') projectMenu = null;
  }

  async function runProjectMenuAction(event, action) {
    event.stopPropagation();
    const selectedProject = projects.find(project => project.id === projectMenu?.projectId);
    projectMenu = null;
    if (!selectedProject) return;

    if (action === 'open') {
      openProject(selectedProject);
      return;
    }

    if (action === 'favorite') {
      errorMessage = '';
      try {
        const updatedProject = await httpRequest(projectFavorite(selectedProject.id).url, 'PATCH', {});
        projects = projects.map(project => project.id === selectedProject.id
          ? { ...project, is_favorite: updatedProject.is_favorite }
          : project);
        if (!updatedProject.is_favorite) projects = projects.filter(project => project.id !== selectedProject.id);
      } catch (error) {
        errorMessage = error.message;
      }
      return;
    }

    try {
      await navigator.clipboard.writeText(`${window.location.origin}${projectShow(selectedProject.id).url}`);
    } catch {
      errorMessage = 'Could not copy the project link.';
    }
  }

  function openTaskRow(event, task) {
    if (event.type === 'keydown') {
      if (event.target !== event.currentTarget || !['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
    } else if (event.target.closest('a, button, input, select, textarea')) {
      return;
    }

    drawerTaskId = task.id;
  }

  function updateDrawerTask(updatedTask) {
    if (!updatedTask.is_favorite) {
      tasks = tasks.filter(task => task.id !== updatedTask.id);
      drawerTaskId = null;
      return;
    }

    tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }

  async function toggleComplete(task) {
    const status = task.status === 'done' ? previousStatuses.get(task.id) || 'todo' : 'done';
    errorMessage = '';
    try {
      const updated = await httpRequest(taskUpdate(task.id).url, 'PATCH', { status });
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
      const nextPreviousStatuses = new Map(previousStatuses);
      if (status === 'done') nextPreviousStatuses.set(task.id, task.status);
      else nextPreviousStatuses.delete(task.id);
      previousStatuses = nextPreviousStatuses;
    } catch (error) {
      errorMessage = error.message;
    }
  }
</script>

<svelte:head><title>Favorites · Gr8r Studio</title></svelte:head>
<svelte:window onclick={closeProjectMenuOnWindowClick} onkeydown={handleProjectMenuKeydown} />

<AppLayout>
  <div class="page wide">
    <div class="ph"><div><h1>Favorites</h1><p>Projects and tasks you've starred for quick access.</p></div></div>
    {#if errorMessage}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errorMessage}</span></div>{/if}
    {#if !favoriteProjects.length && !favoriteTasks.length}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('star', 20)}</span><h2>No favorites yet</h2><p>Star a project or task to pin it here.</p><a class="btn btn-secondary btn-sm" href={projectsIndex().url}>Browse projects</a></div></div>
    {:else}
      <h2 class="sec" style="margin-bottom:10px">Projects <span class="faint" style="font-weight:500">{favoriteProjects.length}</span></h2>
      {#if favoriteProjects.length}
        <div class="pgrid" style="margin-bottom:28px">
          {#each favoriteProjects as project (project.id)}
            {@const locked = !canSeeProject(project)}
            {@const projectStatus = PSTAT[project.status] || PSTAT.planning}
            <div class="pcard" role="link" tabindex="0" aria-label={project.name} onclick={() => openProject(project)} onkeydown={event => event.key === 'Enter' && openProject(project)}>
              <div class="row"><span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 15)}</span><div class="grow" style="min-width:0"><div class="row" style="gap:6px"><b class="trunc" style="font-weight:600;font-size:14px">{project.name}</b>{#if project.is_favorite}<span style="color:var(--amber)">{@html ic('star', 12)}</span>{/if}{#if project.is_private}<span class="faint" title="Private project">{@html ic('lock', 12)}</span>{/if}</div></div><span class="pstatus" style={`--c:${projectStatus.c}`}><i></i>{projectStatus.name}</span></div>
              <div class="desc">{project.description || ''}</div>
              {#if locked}<div class="row faint" style="font-size:12px">{@html ic('lock', 12)}Restricted — request access to see tasks</div>{:else}<div class="row" style="gap:10px"><span class={`prog ${project.status === 'complete' ? 'green' : project.status === 'risk' ? 'red' : ''}`} role="progressbar" aria-valuenow={completionFor(project)} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${completionFor(project) / 100}`}></i></span><span class="num" style="font-size:12px;font-weight:500">{completionFor(project)}%</span></div>{/if}
              <div class="foot"><span class="row" style="gap:4px">{@html ic('circle-check', 12)}{locked ? '—' : `${project.completed_tasks_count || 0}/${project.tasks_count || 0}`}</span><span class="row" style="gap:4px">{@html ic('calendar', 12)}{fmtDate(typeof project.due_date === 'string' ? project.due_date.slice(0, 10) : project.due_date)}</span><span class="row hide-m" style="gap:4px">{@html ic('clock', 12)}{projectLastActivity(project)}</span><span class="sp"></span><span class="avs">{#each (project.members || []).slice(0, 3) as member (member.id)}<span class="av sm" style={`--c:${member.color}`} aria-label={member.name}>{memberInitials(member.name)}</span>{/each}{#if project.members?.length > 3}<span class="av sm more" style="--c:var(--gray)">+{project.members.length - 3}</span>{/if}</span></div>
              <button type="button" class="ibtn ibtn-sm more" aria-label="Project options" onclick={event => openProjectMenu(event, project.id)}>{@html ic('ellipsis', 14)}</button>
            </div>
          {/each}
        </div>
      {:else}<p class="faint" style="margin-bottom:28px">No favorite projects.</p>{/if}
      <h2 class="sec" style="margin-bottom:6px">Tasks <span class="faint" style="font-weight:500">{favoriteTasks.length}</span></h2>
      {#if favoriteTasks.length}
        <div class="panel" style="overflow:hidden">
          {#each favoriteTasks as task (task.id)}
            {@const sourceAssignee = membersByDatabaseId.get(String(task.assignee_id))}
            <div class="mini" class:done={task.status === 'done'} role="button" tabindex="0" onclick={event => openTaskRow(event, task)} onkeydown={event => openTaskRow(event, task)}>
              <input type="checkbox" class="check round" checked={task.status === 'done'} aria-label={`Complete ${task.title}`} onclick={event => event.stopPropagation()} onchange={() => toggleComplete(task)} />
              <span data-tip={ST[task.status]?.name || task.status}>{@html stIcon(task.status)}</span>
              <span class="tt">{task.title}</span>
              <span class="pj hide-m"><span class="pdot" style={`--c:${PCOLORS[task.project?.color] || PCOLORS.slate}`}></span><span class="trunc">{task.project?.name}</span></span>
              <span data-tip={`${PR[task.priority || 'none']?.name || 'No priority'} priority`}>{@html prIcon(task.priority)}</span>
              <span class="hide-m" style="width:74px;text-align:right">{@html dueHtml({ due: typeof task.due_date === 'string' ? task.due_date.slice(0, 10) : '', status: task.status }, false)}</span>
              {#if task.assignee}<span class="av sm" style={`--c:${sourceAssignee?.c || task.assignee.color || '#8A867E'}`} aria-label={task.assignee.name}>{memberInitials(task.assignee.name)}</span>{:else}<span class="av empty sm" aria-label="Unassigned">{@html ic('user', 11)}</span>{/if}
            </div>
          {/each}
        </div>
      {:else}<p class="faint">Open a task and click the star to add it here.</p>{/if}
    {/if}
  </div>
  {#if projectMenu}
    {@const selectedProject = projects.find(project => project.id === projectMenu.projectId)}
    {#if selectedProject}
      <div class="pop enter" data-project-menu role="menu" tabindex="-1" aria-label={`${selectedProject.name} options`} style={`left:${projectMenu.left}px;top:${projectMenu.top}px;width:220px`}>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'open')}>{@html ic('arrow-up-right', 15)}Open</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'favorite')}>{@html ic('star', 15)}Remove from favorites</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'copy')}>{@html ic('link', 15)}Copy link</button>
      </div>
    {/if}
  {/if}
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateDrawerTask} />{/if}
</AppLayout>
