<script>
  import { fade, scale } from 'svelte/transition';
  import { router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { destroy as deleteProject, restore as restoreProjectRoute } from '../../routes/projects';
  import { destroy as deleteTask, restore as restoreTaskRoute } from '../../routes/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { PSTAT } from '@/src/core/constants.js';
  import { ago } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { S } from '@/src/core/store.js';
  import { pIcon, stIcon } from '@/src/ui/helpers.js';
  import { toast } from '@/src/ui/toast.js';

  const httpRequest = useHttpRequest();
  let { archivedTasks: initialArchivedTasks = [], archivedProjects: initialArchivedProjects = [], workspaceData = {} } = $props();
  let archivedTasks = $state([]);
  let archivedProjects = $state([]);
  let tab = $state(S.ui.archTab || 'tasks');
  let confirmation = $state(null);
  let confirmationName = $state('');
  let busy = $state(false);
  let errorMessage = $state('');
  let drawerTask = $state(null);

  $effect(() => { archivedTasks = [...initialArchivedTasks]; });
  $effect(() => { archivedProjects = [...initialArchivedProjects]; });

  function selectTab(value) {
    tab = value;
    S.ui.archTab = value;
  }

  function projectStatusName(project) {
    const status = PSTAT[project.archived_status || project.status];
    return status?.name || 'Archived';
  }

  async function restoreArchivedTask(task) {
    try {
      await httpRequest(restoreTaskRoute(task.id).url, 'PATCH', {});
      archivedTasks = archivedTasks.filter(item => item.id !== task.id);
      toast(`Restored “${task.title}”`, { action: 'Open', onAction: () => drawerTask = task });
      router.reload({ only: ['archiveCount'], preserveScroll: true });
    } catch (error) {
      toast(error.message || 'Could not restore task.', { kind: 'err' });
    }
  }

  async function restoreArchivedProject(project) {
    try {
      await httpRequest(restoreProjectRoute(project.id).url, 'PATCH', {});
      archivedProjects = archivedProjects.filter(item => item.id !== project.id);
      toast(`Restored ${project.name}`);
      router.reload({ only: ['projects', 'archiveCount'], preserveScroll: true });
    } catch (error) {
      toast(error.message || 'Could not restore project.', { kind: 'err' });
    }
  }

  function askToDelete(type, item) {
    confirmation = { type, item };
    confirmationName = '';
    errorMessage = '';
  }

  async function deleteArchivedWork() {
    if (!confirmation || busy) return;
    const { type, item } = confirmation;
    if (type === 'project' && confirmationName !== item.name) return;
    busy = true;
    errorMessage = '';
    try {
      const route = type === 'task' ? deleteTask(item.id) : deleteProject(item.id);
      await httpRequest(route.url, 'DELETE', {});
      if (type === 'task') {
        archivedTasks = archivedTasks.filter(task => task.id !== item.id);
        toast(`Deleted “${item.title}”`);
      } else {
        archivedProjects = archivedProjects.filter(project => project.id !== item.id);
        toast(`Deleted ${item.name}`);
      }
      confirmation = null;
      confirmationName = '';
      router.reload({ only: ['archiveCount'], preserveScroll: true });
    } catch (error) {
      errorMessage = error.message || 'Could not delete archived work.';
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>Archive · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:900px">
    <div class="ph"><div><h1>Archive</h1><p>Archived work is hidden everywhere else. Restore it any time, or delete it for good.</p></div></div>
    <div class="tabs" style="margin-bottom:14px" role="tablist" aria-label="Archive">
      <button role="tab" aria-selected={tab === 'tasks'} class={`tab ${tab === 'tasks' ? 'on' : ''}`} onclick={() => selectTab('tasks')}>Tasks<span class="cnt">{archivedTasks.length}</span></button>
      <button role="tab" aria-selected={tab === 'projects'} class={`tab ${tab === 'projects' ? 'on' : ''}`} onclick={() => selectTab('projects')}>Projects<span class="cnt">{archivedProjects.length}</span></button>
    </div>
    {#if tab === 'tasks'}
      {#if archivedTasks.length}
        <div class="panel" style="overflow:hidden">
          {#each archivedTasks as task (task.id)}
            <div class="mini" style="min-height:52px;cursor:default;gap:12px">
              {@html stIcon(task.status, 15)}
              <div class="grow" style="min-width:0"><div class="trunc" style="font-weight:500">{task.title}</div><div class="faint trunc" style="font-size:12px"><span class="mono">{task.key || task.source_key || task.id}</span> · {task.project?.name || ''}</div></div>
              <span class="faint hide-m" style="font-size:12px;white-space:nowrap">{task.archived_at ? `Archived ${ago(new Date(task.archived_at).getTime())}` : 'Archived'}</span>
              <button class="btn btn-secondary btn-sm" type="button" onclick={() => restoreArchivedTask(task)}>{@html ic('rotate-ccw', 13)}Restore</button>
              <button class="ibtn ibtn-sm" type="button" style="color:var(--red)" aria-label={`Delete ${task.title} permanently`} title="Delete permanently" onclick={() => askToDelete('task', task)}>{@html ic('trash-2', 15)}</button>
            </div>
          {/each}
        </div>
      {:else}
        <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('archive', 20)}</span><h2>No archived tasks</h2><p>Archive finished or abandoned tasks to keep boards focused. They wait here until you restore them.</p></div></div>
      {/if}
    {:else if archivedProjects.length}
      <div class="panel" style="overflow:hidden">
        {#each archivedProjects as project (project.id)}
          <div class="mini" style="min-height:52px;cursor:default;gap:12px">
            {@html pIcon(project, '', 14)}
            <div class="grow" style="min-width:0"><div class="trunc" style="font-weight:500">{project.name}</div><div class="faint trunc" style="font-size:12px">{project.tasks_count || 0} tasks · {projectStatusName(project)}</div></div>
            <span class="faint hide-m" style="font-size:12px;white-space:nowrap">{project.archived_at ? `Archived ${ago(new Date(project.archived_at).getTime())}` : 'Archived'}</span>
            <button class="btn btn-secondary btn-sm" type="button" onclick={() => restoreArchivedProject(project)}>{@html ic('rotate-ccw', 13)}Restore</button>
            <button class="ibtn ibtn-sm" type="button" style="color:var(--red)" aria-label={`Delete ${project.name} permanently`} title="Delete permanently" onclick={() => askToDelete('project', project)}>{@html ic('trash-2', 15)}</button>
          </div>
        {/each}
      </div>
    {:else}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('archive', 20)}</span><h2>No archived projects</h2><p>Archived projects disappear from the sidebar and project lists but keep every task, file, and comment.</p></div></div>
    {/if}
  </div>

  {#if confirmation}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (confirmation = null)}>
      <div class="modal sm" transition:scale={{ duration: 260, start: 0.97 }} role="alertdialog" aria-modal="true" aria-labelledby="archive-confirm-title" aria-describedby="archive-confirm-description">
        <div class="modal-b" style="padding-top:18px;gap:10px">
          <div class="row" style="gap:12px;align-items:flex-start"><span class="ftype" style="--c:var(--red);width:34px;height:34px;border-radius:9px">{@html ic('trash-2', 17)}</span><div class="grow"><h2 id="archive-confirm-title" style="font-size:15px;font-weight:600;margin:4px 0 6px">{confirmation.type === 'task' ? 'Delete task permanently?' : `Delete ${confirmation.item.name}?`}</h2><p id="archive-confirm-description" class="muted" style="margin:0;font-size:13px;line-height:1.55">{#if confirmation.type === 'task'}<b>{confirmation.item.title}</b>, its subtasks, comments, and attachments will be deleted for everyone. This can't be undone.{:else}This permanently deletes the project and its {confirmation.item.tasks_count || 0} tasks, files, and comments for everyone. This can't be undone.{/if}</p></div></div>
          {#if confirmation.type === 'project'}<div class="field" style="margin-top:6px"><label class="label" for="archive-delete-confirm">Type <b>{confirmation.item.name}</b> to confirm</label><input class="input" id="archive-delete-confirm" bind:value={confirmationName} autocomplete="off" /></div>{/if}
          {#if errorMessage}<div class="err" role="alert">{@html ic('circle-alert', 12)}{errorMessage}</div>{/if}
        </div>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => confirmation = null}>Cancel</button><button type="button" class="btn btn-danger" disabled={busy || (confirmation.type === 'project' && confirmationName !== confirmation.item.name)} onclick={deleteArchivedWork}>{busy ? 'Working…' : 'Delete permanently'}</button></div>
      </div>
    </div>
  {/if}
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTask = null} onUpdated={updatedTask => drawerTask = { ...drawerTask, ...updatedTask }} />{/if}
</AppLayout>
