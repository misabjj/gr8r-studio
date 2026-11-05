<script>
  import { usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectCalendar from '../../Components/ProjectCalendar.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import { ic } from '@/src/core/icons.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';

  let { tasks: incomingTasks = [], projects = [] } = $props();
  const page = usePage();
  let pageUserId = $derived(page.props.auth?.user?.id || null);
  let tasks = $state([]);
  let view = $state('list');
  let formOpen = $state(false);
  let initialDate = $state('');

  $effect(() => { tasks = [...incomingTasks]; });

  function isOverdue(task) {
    if (!task.due_date || task.status === 'done') return false;
    const due = new Date(`${task.due_date.slice(0, 10)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return due < today;
  }

  function dayDifference(value) {
    const due = new Date(`${value.slice(0, 10)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.floor((due - today) / 86400000);
  }

  let groups = $derived([
    { id: 'overdue', name: 'Overdue', icon: 'clock-alert', tasks: tasks.filter(isOverdue) },
    { id: 'today', name: 'Today', icon: 'sun', tasks: tasks.filter(task => task.status !== 'done' && task.due_date && dayDifference(task.due_date) === 0) },
    { id: 'upcoming', name: 'Upcoming', icon: 'calendar-days', tasks: tasks.filter(task => task.status !== 'done' && !isOverdue(task) && (!task.due_date || dayDifference(task.due_date) > 0)) },
    { id: 'completed', name: 'Completed', icon: 'circle-check', tasks: tasks.filter(task => task.status === 'done'), collapsed: true },
  ]);
  let overdueCount = $derived(groups[0].tasks.length);
  let todayCount = $derived(groups[1].tasks.length);
  let upcomingCount = $derived(groups[2].tasks.length);

  function openCreateTask(date = '') {
    initialDate = date;
    formOpen = true;
  }

  function taskCreated(task) {
    tasks = [task, ...tasks];
    formOpen = false;
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }
</script>

<svelte:head><title>My Tasks · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="ph">
      <div><h1>My Tasks</h1><p>{todayCount} due today · {overdueCount} overdue · {upcomingCount} upcoming</p></div>
      <div class="acts">
        <div class="seg" role="tablist" aria-label="View">
          <button class:on={view === 'list'} aria-pressed={view === 'list'} onclick={() => view = 'list'}>{@html ic('list', 13)}List</button>
          <button class:on={view === 'calendar'} aria-pressed={view === 'calendar'} onclick={() => view = 'calendar'}>{@html ic('calendar', 13)}Calendar</button>
        </div>
        <button class="btn btn-primary" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</button>
      </div>
    </div>

    {#if view === 'calendar'}
      <ProjectCalendar {tasks} project={null} onCreate={openCreateTask} />
    {:else}
      <div class="list-wrap">
        {#each groups as group (group.id)}
          <section class="panel" style="margin-bottom:10px">
            <div class="panel-h"><h2 class="row" style="gap:8px">{@html ic(group.icon, 14)}{group.name}<span class="cnt">{group.tasks.length}</span></h2></div>
            {#if group.tasks.length}
              {#each group.tasks as task (task.id)}
                <div class="mini" class:done={task.status === 'done'}>
                  <span>{@html stIcon(task.status, 13)}</span>
                  <span class="trunc grow"><span class="tt">{task.title}</span></span>
                  {#if task.project}<span class="key">{task.project.key}</span>{/if}
                  {@html prIcon(task.priority, 13)}
                  <span class="num muted" style="font-size:12px">{formatDate(task.due_date)}</span>
                </div>
              {/each}
            {:else if group.id !== 'completed'}
              <div class="panel-b faint">{group.id === 'overdue' ? 'Nothing overdue. Every task assigned to you is on schedule.' : group.id === 'today' ? 'Nothing due today.' : 'No tasks here.'}</div>
            {/if}
          </section>
        {/each}
      </div>
    {/if}
  </div>
  <WorkspaceTaskForm open={formOpen} {projects} {initialDate} initialAssignee={pageUserId} onClose={() => formOpen = false} onCreated={taskCreated} />
</AppLayout>
