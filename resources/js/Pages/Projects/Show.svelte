<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { store as taskStore } from '../../routes/projects/tasks';
  import { update as taskUpdate } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';

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

  function statusName(value) {
    return columns.find(column => column.id === value)?.name || value;
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
    if (!value) return 'No due date';
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
        <span class={`pill ${project.status === 'completed' ? 'st-done' : 'st-progress'}`}>{project.status === 'completed' ? 'Completed' : 'In Progress'}</span>
        <span class="sp"></span>
        <span class="key">{project.key}</span>
        <button class="btn btn-secondary btn-sm hide-m" onclick={() => showTaskForm = true}>{@html ic('plus', 13)}New task</button>
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
        <div class="page" style="padding-top:16px">
          <div class="grid2">
            <section class="panel"><div class="panel-h"><h2>Project overview</h2></div><div class="panel-b"><p>{project.description || 'No description yet.'}</p><dl class="details"><dt>Status</dt><dd>{project.status}</dd><dt>Project key</dt><dd>{project.key}</dd><dt>Created</dt><dd>{formatDate(project.created_at?.slice(0, 10))}</dd></dl></div></section>
            <section class="panel"><div class="panel-h"><h2>Progress</h2><span class="sp"></span><span class="num">{progress}%</span></div><div class="panel-b"><div class="prog"><i style={`width:${progress}%`}></i></div><p class="muted">{completedCount} of {tasks.length} tasks complete</p><button class="btn btn-secondary btn-sm" onclick={() => tab = 'board'}>Open board</button></div></section>
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
                    <div class="top"><span class="title">{task.title}</span></div>
                    {#if task.description}<p class="desc">{task.description}</p>{/if}
                    <div class="meta"><span class="key">{project.key}-{task.id}</span><span class="sp"></span><span>{formatDate(task.due_date)}</span></div>
                    <label class="sr" for={`task-status-${task.id}`}>Status for {task.title}</label>
                    <select class="select" id={`task-status-${task.id}`} value={task.status} onchange={event => changeStatus(task, event.currentTarget.value)} aria-label={`Status for ${task.title}`}>
                      {#each columns as option}<option value={option.id}>{option.name}</option>{/each}
                    </select>
                  </article>
                {/each}
                {#if columnTasks.length === 0}<div class="faint" style="padding:8px;font-size:12px">No tasks</div>{/if}
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
      {:else}
        <div class="page"><section class="panel"><div class="empty"><span class="empty-ic">{@html ic(tab === 'files' ? 'paperclip' : 'activity', 20)}</span><h2>{tab === 'files' ? 'No files yet' : 'No activity yet'}</h2><p>Updates will appear here as work moves forward.</p></div></section></div>
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
