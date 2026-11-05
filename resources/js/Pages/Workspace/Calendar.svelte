<script>
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectCalendar from '../../Components/ProjectCalendar.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import { ic } from '@/src/core/icons.js';

  let { tasks: incomingTasks = [], projects = [] } = $props();
  let tasks = $state([]);
  let taskFormOpen = $state(false);
  let initialDate = $state('');
  $effect(() => { tasks = [...incomingTasks]; });

  function openTaskForm(date = '') {
    initialDate = date;
    taskFormOpen = true;
  }

  function addTask(task) {
    tasks = [task, ...tasks];
    taskFormOpen = false;
  }
</script>

<svelte:head><title>Calendar · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="ph"><div><h1>Calendar</h1><p>Deadlines and events across every project.</p></div><div class="acts"><button class="btn btn-primary" onclick={() => openTaskForm()}>{@html ic('plus', 14)}New task</button></div></div>
    <ProjectCalendar {tasks} onCreate={openTaskForm} />
  </div>
  <WorkspaceTaskForm open={taskFormOpen} {projects} {initialDate} onClose={() => taskFormOpen = false} onCreated={addTask} />
</AppLayout>
