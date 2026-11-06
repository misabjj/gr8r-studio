<script>
  import Select from "../../Components/Select.svelte";
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { show as projectShow } from '../../routes/projects';
  import { ago, dayBucket } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { S } from '@/src/core/store.js';

  let { workspaceData = {}, tasks: initialTasks = [] } = $props();
  let filter = $state(S.ui.actFilter || 'all');
  let person = $state(S.ui.actWho || 'all');
  let databaseTasks = $state([]);
  let drawerTaskId = $state(null);

  $effect(() => { databaseTasks = [...initialTasks]; });
  $effect(() => {
    S.ui.actFilter = filter;
    S.ui.actWho = person;
  });

  let members = $derived(workspaceData.members || []);
  let projects = $derived(workspaceData.projects || []);
  let tasks = $derived(workspaceData.tasks || []);
  let memberById = $derived(new Map(members.map(member => [member.id, member])));
  let projectById = $derived(new Map(projects.map(project => [project.id, project])));
  let taskById = $derived(new Map(tasks.map(task => [task.id, task])));
  let drawerTaskBySourceKey = $derived(new Map(databaseTasks.map(task => [task.source_key, task])));
  let drawerTask = $derived(databaseTasks.find(task => task.id === drawerTaskId) || null);

  function openTask(sourceKey) {
    const task = drawerTaskBySourceKey.get(sourceKey);
    if (task) drawerTaskId = task.id;
  }

  function updateTask(updatedTask) {
    databaseTasks = databaseTasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }

  let visibleActivity = $derived((workspaceData.activity || [])
    .filter(activity => {
      const project = projectById.get(activity.project);
      return !project?.private || project.members?.includes(workspaceData.me);
    })
    .filter(activity => {
      if (filter === 'comments') return activity.verb.includes('comment');
      if (filter === 'status') return /moved|completed|reopened/.test(activity.verb);
      if (filter === 'projects') return activity.verb.includes('project');
      return true;
    })
    .filter(activity => person === 'all' || activity.by === person));

  let activityGroups = $derived.by(() => {
    const groups = new Map();
    for (const activity of visibleActivity) {
      const bucket = dayBucket(activity.at);
      groups.set(bucket, [...(groups.get(bucket) || []), activity]);
    }
    return [...groups.entries()];
  });

  const filters = [['all', 'All'], ['status', 'Status changes'], ['comments', 'Comments'], ['projects', 'Projects']];
</script>

<svelte:head><title>Activity</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:820px">
    <div class="ph"><div><h1>Activity</h1><p>Everything that's changed across the workspace.</p></div></div>
    <div class="row" style="gap:8px;margin-bottom:6px;flex-wrap:wrap">
      <div class="seg">
        {#each filters as [key, label]}
          <button class:on={filter === key} onclick={() => filter = key}>{label}</button>
        {/each}
      </div>
      <Select class="select" style="height:26px;width:auto;font-size:12px" bind:value={person} aria-label="Filter by person">
        <option value="all">Everyone</option>
        {#each members as member (member.id)}<option value={member.id}>{member.name}</option>{/each}
      </Select>
    </div>
    {#if activityGroups.length}
      {#each activityGroups as [bucket, entries] (bucket)}
        <div class="day-h">{bucket}</div>
        <div class="feed lined">
          {#each entries as entry (entry.id)}
            {@const member = memberById.get(entry.by)}
            {@const task = taskById.get(entry.task)}
            {@const drawerTask = drawerTaskBySourceKey.get(entry.task)}
            {@const project = projectById.get(entry.project)}
            <div class="fitem">
              <span class="av sm" style={`--c:${member?.c || '#8A867E'}`} aria-label={member?.name || 'Someone'}>{member?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span>
              <div class="grow">
                <b>{entry.by === workspaceData.me ? 'You' : member?.name || 'Someone'}</b>
                <span class="muted"> {entry.verb} </span>
                {#if task && drawerTask}
                  <span class="obj" role="button" tabindex="0" onclick={() => openTask(task.id)} onkeydown={event => event.key === 'Enter' && openTask(task.id)}>{task.title}</span>
                {:else if task && project?.database_id}
                  <Link class="obj" href={projectShow(project.database_id).url}>{task.title}</Link>
                {:else if task}
                  <span class="obj">{task.title}</span>
                {:else if project?.database_id}
                  <Link class="obj" href={projectShow(project.database_id).url}>{project.name}</Link>
                {:else if project}
                  <span class="obj">{project.name}</span>
                {/if}
                {#if entry.extra}<span class="muted"> {entry.extra}</span>{/if}
                {#if project && task}<span class="faint"> in {project.name}</span>{/if}
              </div>
              <time>{ago(entry.at)}</time>
            </div>
          {/each}
        </div>
      {/each}
    {:else}
      <div class="empty"><span class="empty-ic">{@html ic('activity', 20)}</span><h2>No activity yet</h2><p>Changes to tasks and projects will appear here.</p></div>
    {/if}
  </div>
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateTask} />{/if}
</AppLayout>
