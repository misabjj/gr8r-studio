<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectTimeline from '../../Components/ProjectTimeline.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';

  let { tasks: incomingTasks = [], projects = [] } = $props();
  let tasks = $state([]);
  let taskFormOpen = $state(false);
  $effect(() => { tasks = [...incomingTasks]; });

  function addTask(task) {
    tasks = [task, ...tasks];
    taskFormOpen = false;
  }
</script>

<svelte:head><title>Timeline · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush"><div class="ph"><div><h1>Timeline</h1><p>Schedules, dependencies, and milestones across projects.</p></div></div><ProjectTimeline {tasks} initialGroup="project" onCreate={() => taskFormOpen = true} /></div>
  <WorkspaceTaskForm open={taskFormOpen} {projects} onClose={() => taskFormOpen = false} onCreated={addTask} />
</AppLayout>
