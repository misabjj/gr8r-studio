<script>
  import { tick } from 'svelte';
  import { fade, scale } from 'svelte/transition';
  import { onMount } from 'svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
import { destroy as projectDestroy, duplicate as projectDuplicate, favorite as projectFavorite, show as projectShow, store as projectStore, update as projectUpdate } from '../../routes/projects';
  import { useHttpRequest } from '../../lib/http.js';
  import { consumeNavigationIntent } from '../../lib/navigation-intents.js';
import { setNavigationIntent } from '../../lib/navigation-intents.js';
  import { toast } from '@/src/ui/toast.js';
import { S, save as saveWorkspaceStore } from '@/src/core/store.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS, PICONS, PSTAT, TEAMS_SEED } from '@/src/core/constants.js';
  import { ago, fmtDate } from '@/src/core/utils.js';

  const httpRequest = useHttpRequest();
  let { projects: incomingProjects = [], teams = [], members = [] } = $props();
  const page = usePage();
  let projects = $state([]);
  $effect(() => { projects = [...incomingProjects]; });
  let activeMembers = $derived(members.filter(member => member.status === 'active'));
  onMount(() => {
    if (consumeNavigationIntent('create-project')) openCreateProjectForm();
  });
  let query = $state(S.ui.projQ || '');
  let statusFilter = $state(S.ui.projStatus || 'all');
  let sortBy = $state(S.ui.projSort || 'recent');
  let view = $state(S.ui.projView || 'grid');
  let modalOpen = $state(false);
  let name = $state('');
  let description = $state('');
  let dueDate = $state('');
  let icon = $state('folder');
  let color = $state('indigo');
  let teamId = $state('product');
  let leadId = $state('');
  let template = $state('blank');
  let projectNameInput = $state(null);
  let busy = $state(false);
  let errors = $state({});
  let projectMenu = $state(null);
  let projectConfirmation = $state(null);
  let confirmationText = $state('');
  let confirmationBusy = $state(false);
  const teamsById = new Map(TEAMS_SEED.map(team => [team.id, team]));
  const templates = [
    { id: 'blank', name: 'Blank project', icon: 'file', desc: 'Start from scratch', tasks: [] },
    { id: 'product', name: 'Product Development', icon: 'target', desc: 'Discovery to launch', tasks: ['Define problem statement', 'User research plan', 'Write PRD', 'Design exploration', 'Build MVP', 'Beta launch'] },
    { id: 'web', name: 'Website', icon: 'globe', desc: 'IA, design, build, launch', tasks: ['Sitemap & information architecture', 'Wireframes', 'Visual design', 'Build page templates', 'Content migration', 'QA & launch'] },
    { id: 'mkt', name: 'Marketing Campaign', icon: 'megaphone', desc: 'Brief, assets, channels', tasks: ['Campaign brief', 'Audience research', 'Creative assets', 'Channel plan', 'Launch campaign', 'Performance report'] },
    { id: 'design', name: 'Design Project', icon: 'palette', desc: 'Discovery to handoff', tasks: ['Discovery workshop', 'Moodboard', 'Concept directions', 'Refinement', 'Developer handoff'] },
    { id: 'software', name: 'Software Development', icon: 'code', desc: 'Spec, build, ship', tasks: ['Technical spec', 'Set up repo & CI', 'Implement core API', 'Write tests', 'Code review', 'Deploy to staging'] },
    { id: 'personal', name: 'Personal Project', icon: 'heart', desc: 'Plan your own goals', tasks: ['Brain dump ideas', 'Pick top 3 priorities', 'Schedule focus time', 'Weekly review'] },
  ];

  $effect(() => {
    S.ui.projQ = query;
    S.ui.projStatus = statusFilter;
    S.ui.projSort = sortBy;
    S.ui.projView = view;
  });

  let activeCount = $derived(projects.filter(project => !['complete', 'completed', 'archived'].includes(project.status)).length);
  let completedCount = $derived(projects.filter(project => ['complete', 'completed'].includes(project.status)).length);
  let visibleProjects = $derived.by(() => {
    const filtered = projects.filter(project => {
      const matchesQuery = !query || `${project.name} ${project.description || ''}`.toLowerCase().includes(query.toLowerCase());
      const matchesStatus = statusFilter === 'all' || project.status === statusFilter;
      return matchesQuery && matchesStatus;
    });
    return filtered.sort((first, second) => {
      if (sortBy === 'name') return first.name.localeCompare(second.name);
      if (sortBy === 'due') return (first.due_date || '9999-12-31').localeCompare(second.due_date || '9999-12-31');
      if (sortBy === 'progress') return completionFor(second) - completionFor(first);
      return new Date(second.updated_at || 0) - new Date(first.updated_at || 0);
    });
  });

  function statusName(status) {
    return ({ active: 'In Progress', planning: 'Planning', risk: 'At Risk', hold: 'On Hold', complete: 'Completed', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[status] || status;
  }

  function statusDetails(status) {
    return PSTAT[status] || { name: statusName(status), c: 'var(--gray)' };
  }

  function projectIsLocked(project) {
    const userId = page.props.auth?.user?.id;
    return project.is_private && !project.members?.some(member => member.id === userId);
  }

  function memberInitials(member) {
    return member.name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function projectProgressClass(project) {
    if (project.status === 'complete') return 'green';
    if (project.status === 'risk') return 'red';
    return '';
  }

  function formatDate(value) {
    return (typeof value === 'string' ? fmtDate(value.slice(0, 10)) : fmtDate(value)) || '—';
  }

  function completionFor(project) {
    return project.tasks_count ? Math.round((project.completed_tasks_count / project.tasks_count) * 100) : 0;
  }

  function makeKey(value) {
    let key = value.trim().split(/\s+/).map(word => word[0] || '').join('').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3);
    if (key.length < 2) key = value.replace(/[^a-z]/gi, '').slice(0, 3).toUpperCase() || 'PRJ';
    while (projects.some(project => project.key === key)) key += 'X';
    return key;
  }

  function selectProjectTemplate(templateId) {
    template = templateId;
    const selectedTemplate = templates.find(item => item.id === templateId);
    if (icon === 'folder' && selectedTemplate) icon = selectedTemplate.icon === 'file' ? 'folder' : selectedTemplate.icon;
  }

  function activateProjectCard(event, projectId) {
    if (event.type === 'keydown') {
      if (event.target !== event.currentTarget) return;
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
    }
    router.visit(projectShow(projectId).url);
  }

  async function openProjectMenu(event, projectId) {
    event.stopPropagation();
    if (projectMenu?.projectId === projectId) {
      projectMenu = null;
      return;
    }

    const bounds = event.currentTarget.getBoundingClientRect();
    projectMenu = {
      projectId,
      left: Math.max(8, Math.min(bounds.right - 220, window.innerWidth - 228)),
      top: Math.max(8, bounds.bottom + 4),
    };
    await tick();
    const menu = document.querySelector('[data-project-menu]');
    if (!menu) return;
    const height = menu.getBoundingClientRect().height;
    projectMenu = {
      ...projectMenu,
      top: bounds.bottom + height > window.innerHeight - 8 ? Math.max(8, bounds.top - height - 4) : bounds.bottom + 4,
    };
  }

  function closeProjectMenuOnWindowClick(event) {
    if (!event.target.closest('[data-project-menu]')) projectMenu = null;
  }

  function handleProjectMenuKeydown(event) {
    if (event.key !== 'Escape') return;
    if (modalOpen) {
      modalOpen = false;
      return;
    }
    projectMenu = null;
  }

  async function runProjectMenuAction(event, action) {
    event.stopPropagation();
    const selectedProject = projects.find(project => project.id === projectMenu?.projectId);
    projectMenu = null;
    if (!selectedProject) return;

    if (action === 'open') {
      router.visit(projectShow(selectedProject.id).url);
      return;
    }

    if (action === 'edit' || action === 'share') {
      setNavigationIntent({ type: 'project-action', action });
      router.visit(projectShow(selectedProject.id).url);
      return;
    }

    if (action === 'favorite') {
      try {
        const updatedProject = await httpRequest(projectFavorite(selectedProject.id).url, 'PATCH', {});
        projects = projects.map(project => project.id === selectedProject.id
          ? { ...project, is_favorite: updatedProject.is_favorite }
          : project);
      } catch (error) {
        errors = { ...errors, form: error.message };
      }
      return;
    }

    if (action === 'duplicate') {
      try {
        const copy = await httpRequest(projectDuplicate(selectedProject.id).url, 'POST', {});
        toast(`Duplicated ${selectedProject.name}`, {
          action: 'Open',
          onAction: () => router.visit(projectShow(copy.id).url),
        });
        router.reload({ preserveScroll: true });
      } catch (error) {
        errors = { ...errors, form: error.message };
      }
      return;
    }

    if (action === 'archive' || action === 'delete') {
      confirmationText = '';
      projectConfirmation = { action, project: selectedProject };
      return;
    }

    if (action === 'move-up' || action === 'move-down') {
      const sourceProjectId = selectedProject.source_key;
      const order = S.data.projOrder || [];
      const currentIndex = order.indexOf(sourceProjectId);
      const nextIndex = action === 'move-up' ? currentIndex - 1 : currentIndex + 1;
      if (currentIndex >= 0 && nextIndex >= 0 && nextIndex < order.length) {
        const nextOrder = [...order];
        [nextOrder[currentIndex], nextOrder[nextIndex]] = [nextOrder[nextIndex], nextOrder[currentIndex]];
        S.data.projOrder = nextOrder;
        saveWorkspaceStore();
      }
      return;
    }

    try {
      await navigator.clipboard.writeText(`https://gr8rstudio.com/${S.data.ws.url}/project/${selectedProject.key.toLowerCase()}`);
    } catch {
      errors = { ...errors, form: 'Could not copy the project link.' };
    }
  }

  async function confirmProjectAction() {
    const confirmation = projectConfirmation;
    if (!confirmation || confirmationBusy) return;
    if (confirmation.action === 'delete' && confirmationText !== confirmation.project.name) return;

    confirmationBusy = true;
    errors = { ...errors, form: '' };

    try {
      if (confirmation.action === 'archive') {
        await httpRequest(projectUpdate(confirmation.project.id).url, 'PATCH', { status: 'archived' });
        toast(`Archived ${confirmation.project.name}`);
      } else {
        await httpRequest(projectDestroy(confirmation.project.id).url, 'DELETE', {});
        toast(`Deleted ${confirmation.project.name}`);
      }
      projectConfirmation = null;
      confirmationText = '';
      router.reload({ preserveScroll: true });
    } catch (error) {
      errors = { ...errors, form: error.message };
    } finally {
      confirmationBusy = false;
    }
  }

  async function openCreateProjectForm() {
    name = '';
    description = '';
    const target = new Date();
    target.setDate(target.getDate() + 30);
    dueDate = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}-${String(target.getDate()).padStart(2, '0')}`;
    icon = 'folder';
    color = 'indigo';
    teamId = teamsById.has(page.props.auth?.user?.team) ? page.props.auth.user.team : teams[0]?.id || 'product';
    leadId = String(page.props.auth?.user?.id || members[0]?.id || '');
    template = 'blank';
    errors = {};
    modalOpen = true;
    await tick();
    projectNameInput?.focus();
  }

  async function createProject(event) {
    event.preventDefault();
    busy = true;
    errors = {};
    try {
      const project = await httpRequest(projectStore().url, 'POST', {
        name: name.trim(),
        key: makeKey(name),
        description: description.trim() || null,
        icon,
        color,
        team: teamId,
        lead_id: Number(leadId),
        due_date: dueDate || null,
        template,
      });
      modalOpen = false;
      router.visit(`${projectShow(project.id).url}${template === 'blank' ? '?tab=overview' : ''}`);
    } catch (error) {
      errors = error.errors || { form: error.message };
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>Projects · Gr8r Studio</title></svelte:head>

<svelte:window onclick={closeProjectMenuOnWindowClick} onkeydown={handleProjectMenuKeydown} />

<AppLayout>
  <div class="page wide">
    <div class="ph">
      <div><h1>Projects</h1><p>{activeCount} active · {completedCount} completed</p></div>
      <div class="acts"><button class="btn btn-primary" onclick={openCreateProjectForm}>{@html ic('plus', 14)}New project</button></div>
    </div>
    <div class="row" style="margin-bottom:16px;flex-wrap:wrap;gap:8px">
      <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={query} placeholder="Search projects" aria-label="Search projects" /></div>
      <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={statusFilter} aria-label="Filter by status">
        <option value="all">All statuses</option><option value="planning">Planning</option><option value="active">In Progress</option><option value="risk">At Risk</option><option value="hold">On Hold</option><option value="complete">Completed</option>
      </select>
      <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={sortBy} aria-label="Sort projects">
        <option value="recent">Sort: Recently active</option><option value="name">Sort: Name</option><option value="due">Sort: Due date</option><option value="progress">Sort: Progress</option>
      </select>
      <span class="sp"></span>
      <div class="seg" role="tablist" aria-label="Project layout">
        <button class:on={view === 'grid'} aria-label="Grid" aria-pressed={view === 'grid'} onclick={() => view = 'grid'}>{@html ic('layout-grid', 13)}<span class="hide-m">Grid</span></button>
        <button class:on={view === 'list'} aria-label="List" aria-pressed={view === 'list'} onclick={() => view = 'list'}>{@html ic('list', 13)}<span class="hide-m">List</span></button>
        <button class:on={view === 'table'} aria-label="Table" aria-pressed={view === 'table'} onclick={() => view = 'table'}>{@html ic('table-2', 13)}<span class="hide-m">Table</span></button>
      </div>
    </div>

    {#if visibleProjects.length === 0}
      <section class="panel"><div class="empty"><span class="empty-ic">{@html ic(projects.length ? 'search-x' : 'folder-kanban', 20)}</span><h2>{projects.length ? 'No results found' : 'No projects yet'}</h2><p>{projects.length ? 'No projects match your search or filters. Try a different term.' : 'Create your first project to start organizing your work.'}</p>{#if projects.length}<button class="btn btn-secondary btn-sm" onclick={() => { query = ''; statusFilter = 'all'; }}>Clear filters</button>{:else}<button class="btn btn-primary btn-sm" onclick={openCreateProjectForm}>{@html ic('plus', 14)}Create project</button>{/if}</div></section>
    {:else if view === 'grid'}
      <div class="pgrid">
        {#each visibleProjects as project (project.id)}
          {@const projectStatus = statusDetails(project.status)}
          <div class="pcard" role="link" tabindex="0" onclick={event => activateProjectCard(event, project.id)} onkeydown={event => activateProjectCard(event, project.id)} aria-label={project.name}>
            <div class="row">
              <span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 15)}</span>
              <div class="grow" style="min-width:0"><div class="row" style="gap:6px"><b class="trunc" style="font-weight:600;font-size:14px">{project.name}</b>{#if project.is_favorite}<span style="color:var(--amber)">{@html ic('star', 12)}</span>{/if}{#if project.is_private}<span class="faint" title="Private project">{@html ic('lock', 12)}</span>{/if}</div></div>
              <span class="pstatus" style={`--c:${projectStatus.c}`}><i></i>{projectStatus.name}</span>
            </div>
            <div class="desc">{project.description || ''}</div>
            {#if projectIsLocked(project)}
              <div class="row faint" style="font-size:12px">{@html ic('lock', 12)}Restricted — request access to see tasks</div>
            {:else}
              <div class="row" style="gap:10px"><span class={`prog ${projectProgressClass(project)}`} role="progressbar" aria-valuenow={completionFor(project)} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${completionFor(project) / 100}`}></i></span><span class="num" style="font-size:12px;font-weight:500">{completionFor(project)}%</span></div>
            {/if}
            <div class="foot"><span class="row" style="gap:4px">{@html ic('circle-check', 12)}{projectIsLocked(project) ? '—' : `${project.completed_tasks_count || 0}/${project.tasks_count || 0}`}</span><span class="row" style="gap:4px">{@html ic('calendar', 12)}{formatDate(project.due_date)}</span><span class="row hide-m" style="gap:4px">{@html ic('clock', 12)}{ago(new Date(project.updated_at).getTime())}</span><span class="sp"></span><span class="avs">{#each project.members.slice(0, 3) as member (member.id)}<span class="av sm" style={`--c:${member.color}`} aria-label={member.name}>{memberInitials(member)}</span>{/each}{#if project.members.length > 3}<span class="av sm more" style="--c:var(--gray)">+{project.members.length - 3}</span>{/if}</span></div>
            <button type="button" class="ibtn ibtn-sm more" aria-label="Project options" style="background:var(--surface);box-shadow:0 0 0 1px var(--border)" onclick={event => openProjectMenu(event, project.id)}>{@html ic('ellipsis', 14)}</button>
          </div>
        {/each}
      </div>
    {:else if view === 'list'}
      <div class="panel" style="overflow:hidden">
        {#each visibleProjects as project (project.id)}
          {@const projectStatus = statusDetails(project.status)}
          <div class="mini" role="link" tabindex="0" style="min-height:52px;gap:12px" onclick={event => activateProjectCard(event, project.id)} onkeydown={event => activateProjectCard(event, project.id)}>
            <span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span>
            <span class="grow" style="min-width:0"><span class="row" style="gap:6px"><b style="font-weight:500">{project.name}</b>{#if project.is_private}{@html ic('lock', 12)}{/if}</span><span class="trunc faint" style="display:block;font-size:12px">{project.description || ''}</span></span>
            <span class="hide-m pstatus" style={`--c:${projectStatus.c}`}><i></i>{projectStatus.name}</span>
            <span class="row hide-m" style="width:140px"><span class="prog" role="progressbar" aria-valuenow={completionFor(project)} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${completionFor(project) / 100}`}></i></span><span class="num faint" style="font-size:11.5px">{completionFor(project)}%</span></span>
            <span class="num muted hide-m" style="width:70px;font-size:12px">{formatDate(project.due_date)}</span>
            <span class="avs">{#each project.members.slice(0, 3) as member (member.id)}<span class="av sm" style={`--c:${member.color}`} aria-label={member.name}>{memberInitials(member)}</span>{/each}{#if project.members.length > 3}<span class="av sm more" style="--c:var(--gray)">+{project.members.length - 3}</span>{/if}</span>
            <button class="ibtn ibtn-sm" aria-label="Project options" onclick={event => openProjectMenu(event, project.id)}>{@html ic('ellipsis', 14)}</button>
          </div>
        {/each}
      </div>
    {:else}
      <div class="panel" style="overflow-x:auto"><table class="perm-t" style="min-width:860px"><thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Status</th><th style="text-align:left">Lead</th><th style="text-align:left">Team</th><th style="text-align:left;width:16%">Progress</th><th>Tasks</th><th style="text-align:left">Start</th><th style="text-align:left">Due</th><th style="text-align:left">Last activity</th><th></th></tr></thead><tbody>
        {#each visibleProjects as project (project.id)}
          {@const projectStatus = statusDetails(project.status)}
          {@const projectLead = project.lead}
          <tr role="link" tabindex="0" style="cursor:pointer" onclick={event => activateProjectCard(event, project.id)} onkeydown={event => activateProjectCard(event, project.id)}><td style="padding-left:14px"><span class="row"><span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span><span style="font-weight:500">{project.name}</span></span></td><td style="text-align:left"><span class="pstatus" style={`--c:${projectStatus.c}`}><i></i>{projectStatus.name}</span></td><td style="text-align:left"><span class="row">{#if projectLead}<span class="av sm" style={`--c:${projectLead.color}`} aria-label={projectLead.name}>{memberInitials(projectLead)}</span>{projectLead.name}{/if}</span></td><td style="text-align:left" class="muted">{teamsById.get(project.team)?.name || 'No team'}</td><td><span class="row"><span class="prog" role="progressbar" aria-valuenow={completionFor(project)} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${completionFor(project) / 100}`}></i></span><span class="num faint" style="font-size:11.5px">{completionFor(project)}%</span></span></td><td class="num">{project.tasks_count || 0}</td><td style="text-align:left" class="num muted">{formatDate(project.start_date)}</td><td style="text-align:left" class="num muted">{formatDate(project.due_date)}</td><td style="text-align:left" class="muted">{ago(new Date(project.updated_at).getTime())}</td><td><button class="ibtn ibtn-sm" aria-label="Project options" onclick={event => openProjectMenu(event, project.id)}>{@html ic('ellipsis', 14)}</button></td></tr>
        {/each}
      </tbody></table></div>
    {/if}
  </div>

  {#if projectMenu}
    {@const selectedProject = projects.find(project => project.id === projectMenu.projectId)}
    {#if selectedProject}
      <div class="pop enter" data-project-menu role="menu" tabindex="-1" aria-label={`${selectedProject.name} options`} style={`left:${projectMenu.left}px;top:${projectMenu.top}px;width:220px`}>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'open')}>{@html ic('arrow-up-right', 15)}Open</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'favorite')}>{@html ic('star', 15)}{selectedProject.is_favorite ? 'Remove from favorites' : 'Add to favorites'}</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'edit')}>{@html ic('pencil', 15)}Rename &amp; edit</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'share')}>{@html ic('share-2', 15)}Share</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'copy')}>{@html ic('link', 15)}Copy link</button>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'duplicate')}>{@html ic('copy', 15)}Duplicate</button>
        <div class="msep"></div>
        {#if projects.findIndex(project => project.id === selectedProject.id) > 0}<button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'move-up')}>{@html ic('arrow-up', 15)}Move up in sidebar</button>{/if}
        {#if projects.findIndex(project => project.id === selectedProject.id) < projects.length - 1}<button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'move-down')}>{@html ic('arrow-down', 15)}Move down in sidebar</button>{/if}
        <div class="msep"></div>
        <button class="mi" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'archive')}>{@html ic('archive', 15)}{selectedProject.status === 'complete' ? 'Archive' : 'Archive project'}</button>
        <button class="mi danger" type="button" role="menuitem" onclick={event => runProjectMenuAction(event, 'delete')}>{@html ic('trash-2', 15)}Delete project</button>
      </div>
    {/if}
  {/if}

  {#if projectConfirmation}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (projectConfirmation = null)}>
      <div class="modal sm" transition:scale={{ duration: 260, start: 0.97 }} role="alertdialog" aria-modal="true" aria-labelledby="project-confirm-title" aria-describedby="project-confirm-description">
        <div class="modal-b" style="padding-top:18px;gap:10px">
          <div class="row" style="gap:12px;align-items:flex-start"><span class="ftype" style={`--c:${projectConfirmation.action === 'delete' ? 'var(--red)' : 'var(--amber)'};width:34px;height:34px;border-radius:9px`}>{@html ic(projectConfirmation.action === 'delete' ? 'trash-2' : 'archive', 17)}</span><div class="grow"><h2 id="project-confirm-title" style="font-size:15px;font-weight:600;margin:4px 0 6px">{projectConfirmation.action === 'delete' ? `Delete ${projectConfirmation.project.name}?` : `Archive ${projectConfirmation.project.name}?`}</h2><p id="project-confirm-description" class="muted" style="margin:0;font-size:13px;line-height:1.55">{projectConfirmation.action === 'delete' ? `This permanently deletes the project and its ${projectConfirmation.project.tasks_count || 0} task${projectConfirmation.project.tasks_count === 1 ? '' : 's'}, files, and comments for everyone. This can't be undone.` : 'The project will be hidden from the sidebar and project lists. Tasks, files, and comments are kept, and you can restore it from Settings → Projects.'}</p></div></div>
          {#if projectConfirmation.action === 'delete'}<div class="field" style="margin-top:6px"><label class="label" for="project-delete-confirm">Type <b>{projectConfirmation.project.name}</b> to confirm</label><input class="input" id="project-delete-confirm" bind:value={confirmationText} autocomplete="off" /></div>{/if}
          {#if errors.form}<div class="err" role="alert">{@html ic('circle-alert', 12)}{errors.form}</div>{/if}
        </div>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => projectConfirmation = null}>Cancel</button><button type="button" class={projectConfirmation.action === 'delete' ? 'btn btn-danger' : 'btn btn-primary'} disabled={confirmationBusy || (projectConfirmation.action === 'delete' && confirmationText !== projectConfirmation.project.name)} onclick={confirmProjectAction}>{confirmationBusy ? 'Working…' : projectConfirmation.action === 'delete' ? 'Delete project' : 'Archive project'}</button></div>
      </div>
    </div>
  {/if}

  {#if modalOpen}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (modalOpen = false)}>
      <div class="modal" transition:scale={{ duration: 260, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="new-project-title" tabindex="-1">
        <div class="modal-h"><div><h2 id="new-project-title">New project</h2><div class="muted" style="font-size:12.5px;margin-top:2px">Projects hold tasks, files, and conversations for one body of work.</div></div><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={() => modalOpen = false}>{@html ic('x', 16)}</button></div>
        <form id="project-create-form" class="modal-b" onsubmit={createProject}>
          {#if errors.form}<div class="alert danger" role="alert">{errors.form}</div>{/if}
          <div class="row" style="gap:10px;align-items:flex-start">
            <span class="picon lg" style={`--c:${PCOLORS[color]};cursor:default;flex-shrink:0;margin-top:22px`}>{@html ic(icon, 18)}</span>
            <div class="field grow"><label class="label" for="project-name">Project name</label><input class={`input ${errors.name ? 'is-error' : ''}`} id="project-name" bind:this={projectNameInput} bind:value={name} maxlength="120" required aria-invalid={!!errors.name} placeholder="e.g. Website Redesign" />{#if errors.name}<span class="err" role="alert">{errors.name[0]}</span>{/if}</div>
          </div>
          <div class="field"><label class="label" for="project-description">Description</label><textarea class="textarea" id="project-description" bind:value={description} rows="2" maxlength="5000" placeholder="What is this project about?"></textarea></div>
          <div class="row" style="gap:16px;align-items:flex-start;flex-wrap:wrap">
            <div class="field" style="flex:1;min-width:220px"><span class="label">Icon</span><div class="iconpick" role="radiogroup" aria-label="Icon">{#each PICONS as projectIcon (projectIcon)}<button type="button" role="radio" aria-checked={icon === projectIcon} class={icon === projectIcon ? 'on' : ''} aria-label={projectIcon} onclick={() => icon = projectIcon}>{@html ic(projectIcon, 15)}</button>{/each}</div></div>
            <div class="field"><span class="label">Color</span><div class="swatches" role="radiogroup" aria-label="Color" style="max-width:140px">{#each Object.entries(PCOLORS) as [projectColor, hex] (projectColor)}<button type="button" role="radio" aria-checked={color === projectColor} class={`sw ${color === projectColor ? 'on' : ''}`} style={`--c:${hex};width:22px;height:22px`} aria-label={projectColor} onclick={() => color = projectColor}>{#if color === projectColor}{@html ic('check', 12)}{/if}</button>{/each}</div></div>
          </div>
          <div class="row" style="gap:12px;flex-wrap:wrap">
            <div class="field" style="flex:1;min-width:150px"><label class="label" for="project-team">Team</label><select class="select" id="project-team" bind:value={teamId}>{#each teams.length ? teams : TEAMS_SEED as team (team.id)}<option value={team.id}>{team.name}</option>{/each}</select></div>
            <div class="field" style="flex:1;min-width:150px"><label class="label" for="project-lead">Lead</label><select class="select" id="project-lead" bind:value={leadId}>{#each activeMembers as member (member.id)}<option value={String(member.id)}>{member.name}</option>{/each}</select></div>
            <div class="field" style="flex:1;min-width:150px"><label class="label" for="project-target-date">Target date</label><input class="input" id="project-target-date" type="date" bind:value={dueDate} /></div>
          </div>
          <div class="field"><span class="label">Template</span><div class="tmpls" role="radiogroup" aria-label="Template">{#each templates as projectTemplate (projectTemplate.id)}<button type="button" role="radio" aria-checked={template === projectTemplate.id} class={`opt ${template === projectTemplate.id ? 'on' : ''}`} style="padding:10px" onclick={() => selectProjectTemplate(projectTemplate.id)}>{@html ic(projectTemplate.icon, 16)}<b style="font-size:12.5px">{projectTemplate.name}</b><span>{projectTemplate.tasks.length ? `${projectTemplate.tasks.length} starter tasks` : projectTemplate.desc}</span></button>{/each}</div></div>
        </form>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => modalOpen = false}>Cancel</button><button class="btn btn-primary" type="submit" form="project-create-form" disabled={busy}>{busy ? 'Creating…' : 'Create project'}</button></div>
      </div>
    </div>
  {/if}
</AppLayout>
