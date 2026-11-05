<script>
  import { store as taskStore } from '../routes/projects/tasks';
  import { jsonRequest } from '../lib/http.js';
  import { ic } from '@/src/core/icons.js';

  let { open = false, projects = [], initialDate = '', initialAssignee = null, onClose, onCreated } = $props();
  let title = $state('');
  let projectId = $state('');
  let status = $state('todo');
  let priority = $state('none');
  let dueDate = $state('');
  let busy = $state(false);
  let errorMessage = $state('');

  const statuses = [
    { id: 'backlog', name: 'Backlog' },
    { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' },
    { id: 'review', name: 'Review' },
    { id: 'done', name: 'Done' },
  ];
  const priorities = ['none', 'low', 'medium', 'high', 'urgent'];

  $effect(() => {
    if (open) {
      title = '';
      projectId = projects[0] ? String(projects[0].id) : '';
      status = 'todo';
      priority = 'none';
      dueDate = initialDate;
      errorMessage = '';
    }
  });

  async function submit(event) {
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
        assignee_id: initialAssignee,
      });
      onCreated(task);
    } catch (error) {
      errorMessage = error.errors?.title?.[0] || error.message;
    } finally {
      busy = false;
    }
  }
</script>

{#if open}
  <div class="modal-wrap" role="presentation" onclick={event => event.target === event.currentTarget && onClose()}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="workspace-new-task-title" tabindex="-1">
      <form onsubmit={submit}>
        <div class="modal-h"><h2 id="workspace-new-task-title">New task</h2><button type="button" class="ibtn" aria-label="Close" onclick={onClose}>{@html ic('x', 16)}</button></div>
        <div class="modal-b">
          {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}
          <div class="field"><label class="label" for="workspace-task-title">Task name</label><input class="input" id="workspace-task-title" bind:value={title} required maxlength="255" /></div>
          <div class="field"><label class="label" for="workspace-task-project">Project</label><select class="select" id="workspace-task-project" bind:value={projectId} required>{#each projects as project (project.id)}<option value={String(project.id)}>{project.name}</option>{/each}</select></div>
          <div class="row" style="gap:12px"><div class="field grow"><label class="label" for="workspace-task-status">Status</label><select class="select" id="workspace-task-status" bind:value={status}>{#each statuses as item}<option value={item.id}>{item.name}</option>{/each}</select></div><div class="field grow"><label class="label" for="workspace-task-priority">Priority</label><select class="select" id="workspace-task-priority" bind:value={priority}>{#each priorities as item}<option value={item}>{item === 'none' ? 'No priority' : item[0].toUpperCase() + item.slice(1)}</option>{/each}</select></div></div>
          <div class="field"><label class="label" for="workspace-task-due">Due date</label><input class="input" id="workspace-task-due" type="date" bind:value={dueDate} /></div>
        </div>
        <div class="modal-f"><button type="button" class="btn btn-secondary" onclick={onClose}>Cancel</button><button class="btn btn-primary" disabled={busy || !projects.length}>{busy ? 'Creating…' : 'Create task'}</button></div>
      </form>
    </div>
  </div>
{/if}
