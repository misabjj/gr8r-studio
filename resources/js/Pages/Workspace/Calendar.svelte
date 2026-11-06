<script>
  import Button from "../../Components/Button.svelte";
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectCalendar from '../../Components/ProjectCalendar.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import { update as taskUpdate } from '../../routes/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';

  const httpRequest = useHttpRequest();
  let { tasks: incomingTasks = [], projects = [], workspaceData = {} } = $props();
  let tasks = $state([]);
  let taskFormOpen = $state(false);
  let initialDate = $state('');
  let drawerTaskId = $state(null);
  $effect(() => { tasks = [...incomingTasks]; });

  function openTaskForm(date = '') {
    initialDate = date;
    taskFormOpen = true;
  }

  function addTask(task) {
    tasks = [task, ...tasks];
  }

  function updateTask(updatedTask) {
    tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }

  async function rescheduleTask(task, changes) {
    const updatedTask = await httpRequest(taskUpdate(task.id).url, 'PATCH', changes);
    updateTask(updatedTask);
  }

  let drawerTask = $derived(tasks.find(task => task.id === drawerTaskId) || null);
</script>

<svelte:head><title>Calendar · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="ph"><div><h1>Calendar</h1><p>Deadlines and events across every project.</p></div><div class="acts"><Button variant="primary" size="md" onclick={() => openTaskForm()}>{@html ic('plus', 14)}New task</Button></div></div>
    <ProjectCalendar {tasks} {projects} {workspaceData} onCreate={openTaskForm} onTask={task => drawerTaskId = task.id} onReschedule={rescheduleTask} />
  </div>
  <WorkspaceTaskForm open={taskFormOpen} {projects} {initialDate} onClose={() => taskFormOpen = false} onCreated={addTask} />
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateTask} />{/if}
</AppLayout>
