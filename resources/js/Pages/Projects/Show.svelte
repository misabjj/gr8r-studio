<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectCalendar from '../../Components/ProjectCalendar.svelte';
  import ProjectTimeline from '../../Components/ProjectTimeline.svelte';
  import { store as taskStore } from '../../routes/projects/tasks';
  import { update as taskUpdate } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';

  let { project } = $props();
  let tasks = $state([]);
  $effect(() => { tasks = [...(project.tasks || [])]; });
  let tab = $state('board');
  let showTaskForm = $state(false);
  let title = $state('');
  let status = $state('todo');
  let priority = $state('medium');
  let dueDate = $state('');
  let busy = $state(false);
  let errorMessage = $state('');

  const columns = [
    { id: 'backlog', name: 'Backlog', icon: 'circle-dashed' },
    { id: 'todo', name: 'To Do', icon: 'circle' },
    { id: 'progress', name: 'In Progress', icon: 'circle-dot' },
    { id: 'review', name: 'Review', icon: 'circle-check' },
    { id: 'done', name: 'Done', icon: 'check-circle-2' },
  ];

  let completedCount = $derived(tasks.filter(task => task.status === 'done').length);
  let progress = $derived(tasks.length ? Math.round(completedCount / tasks.length * 100) : 0);
  let statusCounts = $derived(columns.map(column => ({ ...column, count: tasks.filter(task => task.status === column.id).length })));
  let comingUp = $derived(tasks.filter(task => task.status !== 'done' && task.due_date).sort((first, second) => new Date(first.due_date) - new Date(second.due_date)).slice(0, 6));

  function statusName(value) {
    return columns.find(column => column.id === value)?.name || value;
  }

  function projectStatusName(value) {
    return ({ active: 'In Progress', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[value] || value;
  }

  function openCreateTask(date = '') {
    dueDate = date;
    status = 'todo';
    showTaskForm = true;
  }

  async function createTask(event) {
    event.preventDefault();
    errorMessage = '';
    busy = true;
    try {
      const created = await jsonRequest(taskStore({ project: project.id }).url, 'POST', {
        title: title.trim(),
        status,
        priority,
        due_date: dueDate || null,
      });
      tasks = [created, ...tasks];
      title = '';
      dueDate = '';
      showTaskForm = false;
    } catch (error) {
      errorMessage = error.errors?.title?.[0] || error.message;
    } finally {
      busy = false;
    }
  }

  async function changeStatus(task, nextStatus) {
    if (task.status === nextStatus) return;
    errorMessage = '';
    try {
      const updated = await jsonRequest(taskUpdate(task.id).url, 'PATCH', { status: nextStatus });
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }
</script>

<svelte:head><title>{tab === 'overview' ? 'Overview' : statusName(tab)} · {project.name}</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="proj-h">
      <div class="t">
        <span class="pico lg" style="--c:var(--accent)">{@html ic('folder', 18)}</span>
        <h1>{project.name}</h1>
        <span class={`pill ${project.status === 'completed' ? 'st-done' : project.status === 'paused' ? 'st-backlog' : 'st-progress'}`}>{projectStatusName(project.status)}</span>
        <span class="sp"></span>
        <span class="key">{project.key}</span>
        <button class="btn btn-secondary btn-sm hide-m" onclick={() => openCreateTask()}>{@html ic('plus', 13)}New task</button>
      </div>
      <nav class="tabs" aria-label="Project views">
        {#each [['overview', 'Overview', 'layout-dashboard'], ['board', 'Board', 'square-kanban'], ['list', 'List', 'list'], ['table', 'Table', 'table-2'], ['calendar', 'Calendar', 'calendar'], ['timeline', 'Timeline', 'chart-gantt'], ['files', 'Files', 'paperclip'], ['activity', 'Activity', 'activity']] as [id, label, icon]}
          <button class="tab" class:on={tab === id} aria-current={tab === id ? 'page' : undefined} onclick={() => tab = id}>{@html ic(icon, 14)}{label}</button>
        {/each}
      </nav>
    </div>

    <div class="pbody">
      {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}
      {#if tab === 'overview'}
        <div class="page" style="max-width:1160px;padding-top:22px">
          <div class="grid2">
            <div class="stack">
              <section class="panel"><div class="panel-h"><h2>About</h2></div><div class="panel-b" style="font-size:14px;line-height:1.6;color:var(--text)">{project.description || ''}</div></section>
              <section class="panel"><div class="panel-h"><h2>Progress</h2><div class="acts"><span class="faint" style="font-size:12px">{completedCount} of {tasks.length} tasks done</span></div></div>
                <div class="panel-b">
                  <div class="row" style="align-items:baseline;gap:10px;margin-bottom:10px"><span style="font-size:30px;font-weight:600;letter-spacing:-.03em" class="num">{progress}%</span></div>
                  <div class="stackbar" style="height:8px;margin-bottom:12px">{#each statusCounts as column (column.id)}<i style={`width:${(column.count / Math.max(tasks.length, 1)) * 100}%;background:var(--st-${column.id})`} title={`${column.name}: ${column.count}`}></i>{/each}</div>
                  <div class="row" style="flex-wrap:wrap;gap:14px;font-size:12.5px">{#each statusCounts as column (column.id)}<span class="row" style="gap:5px">{@html ic(column.icon, 12)}<span class="muted">{column.name}</span><b class="num" style="font-weight:600">{column.count}</b></span>{/each}</div>
                </div>
              </section>
              <section class="panel"><div class="panel-h"><h2>Coming up</h2><div class="acts"><button class="btn btn-sm btn-ghost" onclick={() => tab = 'list'}>View list</button></div></div>
                {#if comingUp.length}{#each comingUp as task (task.id)}<div class="mini"><span>{@html stIcon(task.status, 13)}</span><span class="trunc grow"><span class="tt">{task.title}</span></span>{@html prIcon(task.priority, 13)}<span class="num muted" style="font-size:12px">{formatDate(task.due_date)}</span></div>{/each}{:else}<div class="empty sm"><span class="empty-ic">{@html ic('circle-check', 20)}</span><h2>Nothing scheduled</h2><p>Tasks with due dates will show up here.</p></div>{/if}
              </section>
            </div>
            <div class="stack">
              <section class="panel"><div class="panel-h"><h2>Details</h2></div><div class="panel-b"><dl class="details"><dt>{@html ic('circle-dot', 14)}Status</dt><dd>{projectStatusName(project.status)}</dd></dl></div></section>
              <section class="panel"><div class="panel-h"><h2>Milestones</h2></div><div class="panel-b faint">No milestones yet</div></section>
              <section class="panel"><div class="panel-h"><h2>Recent activity</h2></div><div class="panel-b feed"><span class="faint">No activity yet</span></div></section>
            </div>
          </div>
        </div>
      {:else if tab === 'board'}
        <div class="board">
          {#each columns as column (column.id)}
            {@const columnTasks = tasks.filter(task => task.status === column.id)}
            <section class="bcol">
              <div class="bcol-h">{@html ic(column.icon, 14)}<span>{column.name}</span><span class="cnt">{columnTasks.length}</span><span class="sp"></span><button class="ibtn ibtn-xs" aria-label={`Add task to ${column.name}`} onclick={() => { status = column.id; showTaskForm = true; }}>{@html ic('plus', 13)}</button></div>
              <div class="bcol-b">
                {#each columnTasks as task (task.id)}
                  <article class="kcard" class:done={task.status === 'done'}>
                    {#if task.status === 'done'}<div class="top"><span style="display:inline-flex;margin:2px 7px 0 0">{@html stIcon('done', 14)}</span><span class="title">{task.title}</span></div>{:else}<div class="top"><span class="title">{task.title}</span></div>{/if}
                    <div class="meta"><span class="sp"></span>{@html prIcon(task.priority, 13)}{#if task.due_date}<span>{formatDate(task.due_date)}</span>{/if}</div>
                    <label class="sr" for={`task-status-${task.id}`}>Status for {task.title}</label>
                    <select class="select" id={`task-status-${task.id}`} value={task.status} onchange={event => changeStatus(task, event.currentTarget.value)} aria-label={`Status for ${task.title}`}>
                      {#each columns as option}<option value={option.id}>{option.name}</option>{/each}
                    </select>
                  </article>
                {/each}
                {#if columnTasks.length === 0}<div class="faint" style="padding:8px;font-size:12px">No tasks</div>{/if}
                <button class="addcard" onclick={() => { status = column.id; openCreateTask(); }}>{@html ic('plus', 14)}Add task</button>
              </div>
            </section>
          {/each}
        </div>
      {:else if tab === 'list' || tab === 'table'}
        <div class="page wide" style="padding-top:14px">
          <section class="panel" style="overflow-x:auto">
            <table class="perm-t" style="min-width:650px"><thead><tr><th>Task</th><th style="text-align:left">Status</th><th style="text-align:left">Priority</th><th style="text-align:left">Due date</th></tr></thead>
              <tbody>{#each tasks as task (task.id)}<tr><td>{task.title}</td><td><select class="select" value={task.status} onchange={event => changeStatus(task, event.currentTarget.value)} aria-label={`Status for ${task.title}`}>{#each columns as option}<option value={option.id}>{option.name}</option>{/each}</select></td><td>{task.priority}</td><td class="num muted">{formatDate(task.due_date)}</td></tr>{/each}</tbody>
            </table>
          </section>
        </div>
      {:else if tab === 'calendar'}
        <ProjectCalendar {tasks} {project} onCreate={openCreateTask} />
      {:else if tab === 'files'}
        <div class="page wide" style="padding-top:16px"><section class="panel"><div class="empty"><span class="empty-ic">{@html ic('folder-open', 20)}</span><h2>No files yet</h2><p>Upload designs, documents, and assets to share them with the project.</p></div></section></div>
      {:else if tab === 'activity'}
        <div class="page" style="max-width:780px;padding-top:18px"><div class="empty"><span class="empty-ic">{@html ic('activity', 20)}</span><h2>No activity yet</h2><p>Changes to this project will appear here.</p></div></div>
      {:else if tab === 'timeline'}
        <ProjectTimeline {tasks} onCreate={openCreateTask} />
      {/if}
    </div>
  </div>

  {#if showTaskForm}
    <div class="modal-wrap" role="presentation" onclick={event => event.target === event.currentTarget && (showTaskForm = false)}>
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="new-task-title" tabindex="-1">
        <form onsubmit={createTask}>
          <div class="modal-h"><h2 id="new-task-title">New task</h2><button type="button" class="ibtn" aria-label="Close" onclick={() => showTaskForm = false}>{@html ic('x', 16)}</button></div>
          <div class="modal-b">
            <div class="field"><label class="label" for="task-title">Task name</label><input class="input" id="task-title" bind:value={title} required maxlength="255" placeholder="Give this task a name" /></div>
            <div class="row" style="gap:12px">
              <div class="field grow"><label class="label" for="task-status">Status</label><select class="select" id="task-status" bind:value={status}>{#each columns as column}<option value={column.id}>{column.name}</option>{/each}</select></div>
              <div class="field grow"><label class="label" for="task-priority">Priority</label><select class="select" id="task-priority" bind:value={priority}><option value="none">No priority</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
            </div>
            <div class="field"><label class="label" for="task-due">Due date</label><input class="input" id="task-due" type="date" bind:value={dueDate} /></div>
          </div>
          <div class="modal-f"><button type="button" class="btn btn-secondary" onclick={() => showTaskForm = false}>Cancel</button><button class="btn btn-primary" disabled={busy}>{busy ? 'Creating…' : 'Create task'}</button></div>
        </form>
      </div>
    </div>
  {/if}
</AppLayout>
