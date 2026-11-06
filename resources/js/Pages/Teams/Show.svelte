<script>
  import { Link, router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import TeamEditDialog from '../../Components/TeamEditDialog.svelte';
  import { show as projectShow } from '../../routes/projects';
  import { show as memberShow } from '../../routes/members';
  import { index as membersIndex } from '../../routes/members';
  import { update as taskUpdate } from '../../routes/tasks';
  import { jsonRequest } from '../../lib/http.js';
  import { PCOLORS, PSTAT } from '@/src/core/constants.js';
  import { ic } from '@/src/core/icons.js';
  import { dueHtml, prIcon, progBar, pStatus, stIcon } from '@/src/ui/helpers.js';

  let { team, workspaceData = {} } = $props();
  const page = usePage();
  let teamName = $state('');
  let teamDescription = $state('');
  let teamIcon = $state('');
  let teamColor = $state('');
  let editTeamOpen = $state(false);
  let memberTeams = $state(new Map());
  let tasks = $state([]);
  let members = $derived((workspaceData.members || []).filter(member => memberTeams.get(member.id) === team.id));
  let projects = $derived((workspaceData.projects || []).filter(project => project.team === team.id
    && !project.archived
    && (!project.private || project.members?.includes(workspaceData.me))));
  let memberIds = $derived(new Set(members.map(member => member.id)));
  let openTasks = $derived(tasks
    .filter(task => memberIds.has(task.assignee) && task.status !== 'done' && !task.archived)
    .sort((first, second) => (first.due || '9999').localeCompare(second.due || '9999')));
  let memberById = $derived(new Map(members.map(member => [member.id, member])));
  let projectById = $derived(new Map((workspaceData.projects || []).map(project => [project.id, project])));
  let drawerTaskId = $state(null);
  let taskError = $state('');
  let teamStateInitialized = false;
  $effect(() => {
    if (teamStateInitialized) return;
    teamStateInitialized = true;
    teamName = team.name;
    teamDescription = team.description || '';
    teamIcon = team.icon;
    teamColor = team.color;
    memberTeams = new Map((workspaceData.members || []).map(member => [member.id, member.team]));
  });
  $effect(() => {
    tasks = [...(workspaceData.tasks || [])];
  });
  let drawerTask = $derived.by(() => {
    const sourceTask = tasks.find(task => String(task.database_id) === String(drawerTaskId));
    if (!sourceTask) return null;

    const project = (workspaceData.projects || []).find(item => item.id === sourceTask.project);
    const assignee = (workspaceData.members || []).find(item => item.id === sourceTask.assignee);

    return {
      ...sourceTask,
      id: sourceTask.database_id,
      source_key: sourceTask.id,
      project_id: project?.database_id,
      assignee_id: assignee?.database_id,
      description: sourceTask.desc || '',
      due_date: sourceTask.due,
      start_date: sourceTask.start,
      project: project ? { ...project, color: project.color || 'indigo' } : null,
      assignee: assignee || null,
    };
  });

  function initials(name) {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function projectProgress(projectId) {
    const projectTasks = tasks.filter(task => task.project === projectId && !task.archived);
    if (!projectTasks.length) return 0;

    return Math.round((projectTasks.filter(task => task.status === 'done').length / projectTasks.length) * 100);
  }

  function projectStatus(status) {
    return PSTAT[status]?.name || status;
  }

  function projectColor(project) {
    return PCOLORS[project.color] || project.color || teamColor;
  }

  function updateTeam(updatedTeam, selectedMemberIds) {
    teamName = updatedTeam.name;
    teamDescription = updatedTeam.description;
    teamIcon = updatedTeam.icon;
    teamColor = updatedTeam.color;
    const selectedIds = new Set(selectedMemberIds.map(String));
    memberTeams = new Map((workspaceData.members || []).map(member => [
      member.id,
      selectedIds.has(String(member.database_id)) ? team.id : member.team === team.id ? '' : member.team,
    ]));
    editTeamOpen = false;
    router.reload({ only: ['teams'], preserveScroll: true, preserveState: true });
  }

  function openTask(task) {
    drawerTaskId = task.database_id;
    taskError = '';
  }

  function handleTaskRowKeydown(event, task) {
    if (event.target !== event.currentTarget) return;
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      openTask(task);
    }
  }

  async function toggleTask(task) {
    taskError = '';
    try {
      const updated = await jsonRequest(taskUpdate(task.database_id).url, 'PATCH', {
        status: task.status === 'done' ? 'todo' : 'done',
      });
      tasks = tasks.map(item => String(item.database_id) === String(updated.id)
        ? { ...item, status: updated.status }
        : item);
    } catch (error) {
      taskError = error.message;
    }
  }

  function updateDrawerTask(updatedTask) {
    tasks = tasks.map(task => String(task.database_id) === String(updatedTask.id)
      ? {
          ...task,
          title: updatedTask.title,
          status: updatedTask.status,
          priority: updatedTask.priority,
          due: updatedTask.due_date?.slice(0, 10) || null,
          start: updatedTask.start_date?.slice(0, 10) || null,
          desc: updatedTask.description,
          labels: updatedTask.labels,
          subtasks: updatedTask.subtasks,
          attachments: updatedTask.attachments,
        }
      : task);
  }
</script>

<svelte:head><title>{teamName} · Teams · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page">
    <div class="ph"><div class="row" style="gap:12px"><span class="picon lg" style={`--c:${teamColor}`}>{@html ic(teamIcon, 18)}</span><div><h1>{teamName}</h1><p style="margin:2px 0 0">{teamDescription}</p></div></div><div class="acts"><button type="button" class="btn btn-secondary" onclick={() => editTeamOpen = true}>{@html ic('pencil', 14)}Edit team</button><Link class="btn btn-secondary" href="/onboarding?step=4">{@html ic('user-plus', 14)}Invite to workspace</Link></div></div>
    <div class="grid2"><div class="stack">
      <section class="panel"><div class="panel-h"><h2>Projects</h2><span class="faint">{projects.length}</span></div>{#each projects as project (project.id)}<Link class="mini" href={project.database_id ? projectShow(project.database_id).url : '/projects'}><span class="picon" style={`--c:${projectColor(project)}`}>{@html ic(project.icon || 'folder', 14)}</span><span class="tt">{project.name}</span><span>{@html pStatus(project.status)}</span><span class="row" style="width:120px">{@html progBar(projectProgress(project.id))}</span></Link>{:else}<div class="panel-b faint">No projects</div>{/each}</section>
      {#if taskError}<div class="alert danger" role="alert">{@html ic('circle-alert', 14)}<span>{taskError}</span></div>{/if}
      <section class="panel"><div class="panel-h"><h2>Open tasks</h2><span class="faint">{openTasks.length}</span></div>{#each openTasks.slice(0, 8) as task (task.id)}{@const member = memberById.get(task.assignee)}{@const project = projectById.get(task.project)}<div class="mini" role="button" tabindex="0" onclick={() => openTask(task)} onkeydown={event => handleTaskRowKeydown(event, task)}>
        <input type="checkbox" class="check round" aria-label={`Complete ${task.title}`} checked={task.status === 'done'} onclick={event => event.stopPropagation()} onchange={() => toggleTask(task)} />
        <span>{@html stIcon(task.status)}</span><span class="tt">{task.title}</span>
        {#if project}<span class="pj hide-m"><span class="pdot" style={`--c:${projectColor(project)}`}></span><span class="trunc">{project.name}</span></span>{/if}
        <span aria-label={`${task.priority || 'none'} priority`}>{@html prIcon(task.priority)}</span>
        <span class="hide-m" style="width:74px;text-align:right">{@html dueHtml(task, false)}</span>
        {#if member}<span class="av sm" style={`--c:${member.c}`} aria-label={member.name}>{initials(member.name)}</span>{:else}<span class="av empty sm" aria-label="Unassigned">{@html ic('user', 11)}</span>{/if}
      </div>{:else}<div class="panel-b faint">No open tasks</div>{/each}</section>
    </div>
    <section class="panel" style="align-self:start"><div class="panel-h"><h2>Members</h2><span class="faint">{members.length}</span></div>{#each members as member (member.id)}<Link class="mini" style="min-height:48px" href={memberShow(member.id).url}><span class="av md" style={`--c:${member.c}`} aria-label={member.name}>{initials(member.name)}</span><div class="grow"><div style="font-weight:500">{member.name}</div><div class="faint" style="font-size:12px">{member.title}</div></div><span class="badge">{member.role}</span></Link>{/each}</section></div>
  </div>
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateDrawerTask} />{/if}
  {#if editTeamOpen}<TeamEditDialog {team} members={workspaceData.members || []} teams={page.props.teams || []} {projects} onClose={() => editTeamOpen = false} onUpdated={updateTeam} onDeleted={() => router.visit(membersIndex({ query: { tab: 'teams' } }).url)} />{/if}
</AppLayout>
