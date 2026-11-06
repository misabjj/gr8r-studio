<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectTimeline from '../../Components/ProjectTimeline.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { update as taskUpdate } from '../../routes/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { toast } from '@/src/ui/toast.js';

  const httpRequest = useHttpRequest();
  let { tasks: incomingTasks = [], projects = [], workspaceData = {} } = $props();
  let tasks = $state([]);
  let taskFormOpen = $state(false);
  let drawerTaskId = $state(null);
  $effect(() => { tasks = [...incomingTasks]; });

  let drawerTask = $derived(tasks.find(task => task.id === drawerTaskId) || null);

  function addTask(task) {
    tasks = [task, ...tasks];
  }

  async function updateTimelineTask(task, changes) {
    try {
      const updatedTask = await httpRequest(taskUpdate(task.id).url, 'PATCH', changes);
      tasks = tasks.map(item => item.id === updatedTask.id ? { ...item, ...updatedTask } : item);
    } catch (error) {
      toast(error.message, { kind: 'err' });
    }
  }
</script>

<svelte:head><title>Timeline</title></svelte:head>

<AppLayout>
  <div class="page flush"><div class="ph"><div><h1>Timeline</h1><p>Schedules, dependencies, and milestones across projects.</p></div></div><ProjectTimeline {tasks} initialGroup="project" sourceProjects={workspaceData.projects || []} sourceTaskOrder={(workspaceData.tasks || []).map(task => task.database_id)} onCreate={() => taskFormOpen = true} onTask={task => drawerTaskId = task.id} onUpdateTask={updateTimelineTask} /></div>
  <WorkspaceTaskForm open={taskFormOpen} {projects} onClose={() => taskFormOpen = false} onCreated={addTask} />
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updatedTask => tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task)} />{/if}
</AppLayout>
