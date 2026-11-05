<script>
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { store as taskStore } from '../../routes/projects/tasks';
  import { update as taskUpdate } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { show as projectShow } from '../../routes/projects';
  import { ic } from '@/src/core/icons.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';

  let { tasks: incomingTasks = [], projects = [] } = $props();
  let tasks = $state([]);
  $effect(() => { tasks = [...incomingTasks]; });
  let query = $state('');
  let statusFilter = $state('all');
  let groupBy = $state('status');
  let sortBy = $state('recent');
  let view = $state('list');
  let createOpen = $state(false);
  let title = $state('');
  let projectId = $state('');
  let status = $state('todo');
  let priority = $state('none');
  let dueDate = $state('');
  let startDate = $state('');
  let busy = $state(false);
  let errorMessage = $state('');

  const statuses = [
    { id: 'backlog', name: 'Backlog', icon: 'circle-dashed' },
    { id: 'todo', name: 'To Do', icon: 'circle' },
    { id: 'progress', name: 'In Progress', icon: 'circle-dot' },
    { id: 'review', name: 'Review', icon: 'circle-check' },
    { id: 'done', name: 'Done', icon: 'check-circle-2' },
  ];
  const priorities = ['none', 'low', 'medium', 'high', 'urgent'];

  let visibleTasks = $derived.by(() => {
    const filtered = tasks.filter(task => {
      const matchesQuery = !query || `${task.title} ${task.project?.name || ''} ${task.project?.key || ''}`.toLowerCase().includes(query.toLowerCase());
      return matchesQuery && (statusFilter === 'all' || task.status === statusFilter);
    });
    return filtered.sort((first, second) => {
      if (sortBy === 'title') return first.title.localeCompare(second.title);
      if (sortBy === 'due') return (first.due_date || '9999').localeCompare(second.due_date || '9999');
      if (sortBy === 'priority') return priorities.indexOf(second.priority) - priorities.indexOf(first.priority);
      return new Date(second.created_at || 0) - new Date(first.created_at || 0);
    });
  });

  function taskGroups() {
    if (groupBy === 'none') return [{ id: 'all', name: 'All tasks', tasks: visibleTasks }];
    if (groupBy === 'project') return projects.map(project => ({ id: String(project.id), name: project.name, tasks: visibleTasks.filter(task => task.project_id === project.id) })).filter(group => group.tasks.length);
    return statuses.map(item => ({ ...item, name: item.name, tasks: visibleTasks.filter(task => task.status === item.id) })).filter(group => group.tasks.length || groupBy === 'status');
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function openCreateTask() {
    title = '';
    projectId = projects[0] ? String(projects[0].id) : '';
    status = 'todo';
    priority = 'none';
    dueDate = '';
    startDate = '';
    errorMessage = '';
    createOpen = true;
  }

  async function createTask(event) {
    event.preventDefault();
    if (!projectId) return;
    busy = true;
    errorMessage = '';
    try {
      const task = await jsonRequest(taskStore({ project: projectId }).url, 'POST', {
        title: title.trim(),
        status,
        priority,
        due_date: dueDate || null,
        start_date: startDate || null,
      });
      tasks = [task, ...tasks];
      createOpen = false;
    } catch (error) {
      errorMessage = error.errors?.title?.[0] || error.message;
    } finally {
      busy = false;
    }
  }

  async function updateTask(task, values) {
    errorMessage = '';
    try {
      const updated = await jsonRequest(taskUpdate(task.id).url, 'PATCH', values);
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
    } catch (error) {
      errorMessage = error.message;
    }
  }
</script>

<svelte:head><title>Tasks · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="ph"><div><h1>Tasks</h1><p>Every task across {projects.length} projects.</p></div><div class="acts"><button class="btn btn-primary" onclick={openCreateTask}>{@html ic('plus', 14)}New task</button></div></div>
    {#if errorMessage}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errorMessage}</span></div>{/if}
    <div class="toolbar" role="toolbar">
      <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={query} placeholder="Search tasks" aria-label="Search tasks" /></div>
      <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={statusFilter} aria-label="Filter by status"><option value="all">All statuses</option>{#each statuses as item}<option value={item.id}>{item.name}</option>{/each}</select>
      <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={sortBy} aria-label="Sort tasks"><option value="recent">Sort: Recently created</option><option value="title">Sort: Title</option><option value="due">Sort: Due date</option><option value="priority">Sort: Priority</option></select>
      {#if view !== 'board'}<select class="select" style="height:26px;width:auto;font-size:12px" bind:value={groupBy} aria-label="Group tasks"><option value="status">Group: Status</option><option value="project">Group: Project</option><option value="none">Group: None</option></select>{/if}
      <span class="sp"></span>
      <div class="seg" role="tablist" aria-label="Task view"><button class:on={view === 'list'} aria-pressed={view === 'list'} onclick={() => view = 'list'}>{@html ic('list', 13)}<span class="hide-m">List</span></button><button class:on={view === 'board'} aria-pressed={view === 'board'} onclick={() => view = 'board'}>{@html ic('square-kanban', 13)}<span class="hide-m">Board</span></button><button class:on={view === 'table'} aria-pressed={view === 'table'} onclick={() => view = 'table'}>{@html ic('table-2', 13)}<span class="hide-m">Table</span></button></div>
    </div>
    {#if !visibleTasks.length}
      <section class="panel"><div class="empty"><span class="empty-ic">{@html ic(query || statusFilter !== 'all' ? 'search-x' : 'list-checks', 20)}</span><h2>{query || statusFilter !== 'all' ? 'No results found' : 'No tasks here'}</h2><p>{query || statusFilter !== 'all' ? 'No tasks match these filters.' : 'Add a task to get things moving.'}</p>{#if !query && statusFilter === 'all'}<button class="btn btn-primary btn-sm" onclick={openCreateTask}>{@html ic('plus', 14)}Add task</button>{/if}</div></section>
    {:else if view === 'board'}
      <div class="board">{#each statuses as item (item.id)}{@const columnTasks = visibleTasks.filter(task => task.status === item.id)}<section class="bcol"><div class="bcol-h">{@html stIcon(item.id, 14)}<span>{item.name}</span><span class="cnt">{columnTasks.length}</span><span class="sp"></span><button class="ibtn ibtn-xs" aria-label={`Add task to ${item.name}`} onclick={() => { status = item.id; openCreateTask(); }}>{@html ic('plus', 13)}</button></div><div class="bcol-b">{#each columnTasks as task (task.id)}<article class="kcard" class:done={task.status === 'done'}><div class="top"><span class="title">{task.title}</span></div><div class="meta"><Link class="m" href={projectShow(task.project_id).url} aria-label={`Project ${task.project?.name}`}><span class="pdot" style={`--c:var(--${task.project?.color || 'indigo'})`}></span></Link><span class="sp"></span>{@html prIcon(task.priority, 13)}{#if task.due_date}<span>{formatDate(task.due_date)}</span>{/if}</div><select class="select" value={task.status} aria-label={`Status for ${task.title}`} onchange={event => updateTask(task, { status: event.currentTarget.value })}>{#each statuses as option}<option value={option.id}>{option.name}</option>{/each}</select></article>{/each}<button class="addcard" onclick={() => { status = item.id; openCreateTask(); }}>{@html ic('plus', 14)}Add task</button></div></section>{/each}</div>
    {:else if view === 'table'}
      <section class="panel" style="overflow-x:auto"><table class="perm-t" style="min-width:720px"><thead><tr><th style="padding-left:14px">Task</th><th style="text-align:left">Status</th><th style="text-align:left">Priority</th><th style="text-align:left">Project</th><th style="text-align:left">Due date</th></tr></thead><tbody>{#each visibleTasks as task (task.id)}<tr><td style="padding-left:14px"><span class="row" style="gap:8px">{@html stIcon(task.status, 13)}<span>{task.title}</span></span></td><td style="text-align:left"><select class="select" value={task.status} aria-label={`Status for ${task.title}`} onchange={event => updateTask(task, { status: event.currentTarget.value })}>{#each statuses as option}<option value={option.id}>{option.name}</option>{/each}</select></td><td style="text-align:left"><select class="select" value={task.priority} aria-label={`Priority for ${task.title}`} onchange={event => updateTask(task, { priority: event.currentTarget.value })}>{#each priorities as item}<option value={item}>{item === 'none' ? 'No priority' : item[0].toUpperCase() + item.slice(1)}</option>{/each}</select></td><td style="text-align:left"><Link href={projectShow(task.project_id).url}>{task.project?.name}</Link></td><td class="num muted" style="text-align:left">{formatDate(task.due_date)}</td></tr>{/each}</tbody></table></section>
    {:else}
      <div class="list-wrap"><div class="tlist" role="table" aria-label="Tasks"><div class="thead" style="--cols:minmax(220px,1fr) 132px 112px 104px 160px" role="row"><div>Task</div><div>Status</div><div>Priority</div><div>Due date</div><div>Project</div></div>{#each taskGroups() as group (group.id)}<div class="grp">{#if group.icon}{@html stIcon(group.id, 14)}{/if}<span>{group.name}</span><span class="cnt">{group.tasks.length}</span></div>{#each group.tasks as task (task.id)}<div class="trow" style="--cols:minmax(220px,1fr) 132px 112px 104px 160px"><span class="tt">{task.title}</span><span class="c-meta"><select class="select" value={task.status} aria-label={`Status for ${task.title}`} onchange={event => updateTask(task, { status: event.currentTarget.value })}>{#each statuses as option}<option value={option.id}>{option.name}</option>{/each}</select></span><span class="c-meta"><span>{@html prIcon(task.priority, 13)}{task.priority === 'none' ? '' : task.priority}</span></span><span class="c-meta num muted">{formatDate(task.due_date)}</span><span class="c-meta"><Link class="trunc" href={projectShow(task.project_id).url}>{task.project?.name}</Link></span></div>{/each}{/each}<button class="addrow" onclick={openCreateTask}>{@html ic('plus', 14)}Add task</button></div></div>
    {/if}
  </div>

  {#if createOpen}
    <div class="modal-wrap" role="presentation" onclick={event => event.target === event.currentTarget && (createOpen = false)}><div class="modal" role="dialog" aria-modal="true" aria-labelledby="new-task-title" tabindex="-1"><form onsubmit={createTask}><div class="modal-h"><h2 id="new-task-title">New task</h2><button type="button" class="ibtn" aria-label="Close" onclick={() => createOpen = false}>{@html ic('x', 16)}</button></div><div class="modal-b">{#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}<div class="field"><label class="label" for="task-title">Task name</label><input class="input" id="task-title" bind:value={title} required maxlength="255" /></div><div class="field"><label class="label" for="task-project">Project</label><select class="select" id="task-project" bind:value={projectId} required>{#each projects as project (project.id)}<option value={String(project.id)}>{project.name}</option>{/each}</select></div><div class="row" style="gap:12px"><div class="field grow"><label class="label" for="task-status">Status</label><select class="select" id="task-status" bind:value={status}>{#each statuses as item}<option value={item.id}>{item.name}</option>{/each}</select></div><div class="field grow"><label class="label" for="task-priority">Priority</label><select class="select" id="task-priority" bind:value={priority}>{#each priorities as item}<option value={item}>{item === 'none' ? 'No priority' : item[0].toUpperCase() + item.slice(1)}</option>{/each}</select></div></div><div class="field"><label class="label" for="task-due">Due date</label><input class="input" id="task-due" type="date" bind:value={dueDate} /></div><div class="field"><label class="label" for="task-start">Start date</label><input class="input" id="task-start" type="date" bind:value={startDate} /></div></div><div class="modal-f"><button type="button" class="btn btn-secondary" onclick={() => createOpen = false}>Cancel</button><button class="btn btn-primary" disabled={busy || !projects.length}>{busy ? 'Creating…' : 'Create task'}</button></div></form></div></div>
  {/if}
</AppLayout>
