<script>
  import { Link, router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { show as projectShow, store as projectStore } from '../../routes/projects';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS } from '@/src/core/constants.js';

  let { projects: incomingProjects = [] } = $props();
  let projects = $state([]);
  $effect(() => { projects = [...incomingProjects]; });
  let query = $state('');
  let statusFilter = $state('all');
  let sortBy = $state('recent');
  let view = $state('grid');
  let modalOpen = $state(false);
  let name = $state('');
  let description = $state('');
  let dueDate = $state('');
  let busy = $state(false);
  let errors = $state({});

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

  function formatDate(value) {
    if (!value) return '—';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function completionFor(project) {
    return project.tasks_count ? Math.round((project.completed_tasks_count / project.tasks_count) * 100) : 0;
  }

  function makeKey(value) {
    const words = value.trim().split(/\s+/);
    return (words.length > 1 ? words.map(word => word[0]).join('') : value.replace(/[^a-z0-9]/gi, '').slice(0, 3)).toUpperCase().slice(0, 12);
  }

  function openCreateProject() {
    name = '';
    description = '';
    const target = new Date();
    target.setDate(target.getDate() + 30);
    dueDate = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}-${String(target.getDate()).padStart(2, '0')}`;
    errors = {};
    modalOpen = true;
  }

  async function createProject(event) {
    event.preventDefault();
    busy = true;
    errors = {};
    try {
      const project = await jsonRequest(projectStore().url, 'POST', {
        name: name.trim(),
        key: makeKey(name),
        description: description.trim() || null,
        due_date: dueDate || null,
      });
      modalOpen = false;
      router.visit(projectShow(project.id).url);
    } catch (error) {
      errors = error.errors || { form: error.message };
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>Projects · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page wide">
    <div class="ph">
      <div><h1>Projects</h1><p>{activeCount} active · {completedCount} completed</p></div>
      <div class="acts"><button class="btn btn-primary" onclick={openCreateProject}>{@html ic('plus', 14)}New project</button></div>
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
      <section class="panel"><div class="empty"><span class="empty-ic">{@html ic(projects.length ? 'search-x' : 'folder-kanban', 20)}</span><h2>{projects.length ? 'No results found' : 'No projects yet'}</h2><p>{projects.length ? 'No projects match your search or filters. Try a different term.' : 'Create your first project to start organizing your work.'}</p>{#if projects.length}<button class="btn btn-secondary btn-sm" onclick={() => { query = ''; statusFilter = 'all'; }}>Clear filters</button>{:else}<button class="btn btn-primary btn-sm" onclick={openCreateProject}>{@html ic('plus', 14)}Create project</button>{/if}</div></section>
    {:else if view === 'grid'}
      <div class="pgrid">
        {#each visibleProjects as project (project.id)}
          <Link class="pcard" href={projectShow(project.id).url} aria-label={project.name}>
            <div class="row">
              <span class="pico" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span>
              <div class="grow" style="min-width:0"><div class="row" style="gap:6px"><b class="trunc" style="font-weight:600;font-size:14px">{project.name}</b>{#if project.is_favorite}<span style="color:var(--amber)">{@html ic('star', 12)}</span>{/if}</div></div>
              <span class={`pill ${['completed', 'complete'].includes(project.status) ? 'st-done' : ['paused', 'hold', 'planning'].includes(project.status) ? 'st-backlog' : 'st-progress'}`}>{statusName(project.status)}</span>
            </div>
            <div class="desc">{project.description || ''}</div>
            <div class="row" style="gap:10px"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num" style="font-size:12px;font-weight:500">{completionFor(project)}%</span></div>
            <div class="foot"><span class="row" style="gap:4px">{@html ic('circle-check', 12)}{project.completed_tasks_count || 0}/{project.tasks_count || 0}</span><span class="row" style="gap:4px">{@html ic('calendar', 12)}{formatDate(project.due_date)}</span><span class="sp"></span></div>
          </Link>
        {/each}
      </div>
    {:else if view === 'list'}
      <div class="panel" style="overflow:hidden">
        {#each visibleProjects as project (project.id)}
          <Link class="mini" href={projectShow(project.id).url}>
            <span class="pico" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span>
            <span class="grow"><b>{project.name}</b><span class="trunc faint" style="display:block;font-size:12px">{project.description}</span></span>
            <span class={`pill ${['completed', 'complete'].includes(project.status) ? 'st-done' : ['paused', 'hold', 'planning'].includes(project.status) ? 'st-backlog' : 'st-progress'}`}>{statusName(project.status)}</span>
            <span class="row hide-m" style="width:140px"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num faint" style="font-size:11.5px">{completionFor(project)}%</span></span>
            <span class="num muted hide-m" style="width:70px;font-size:12px">{formatDate(project.due_date)}</span>
          </Link>
        {/each}
      </div>
    {:else}
      <div class="panel" style="overflow-x:auto"><table class="perm-t" style="min-width:600px"><thead><tr><th style="padding-left:14px">Project</th><th style="text-align:left">Status</th><th style="text-align:left;width:26%">Progress</th><th>Tasks</th><th style="text-align:left">Due</th></tr></thead><tbody>
        {#each visibleProjects as project (project.id)}
          <tr><td style="padding-left:14px"><Link class="row" href={projectShow(project.id).url}><span class="pico" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 14)}</span><span style="font-weight:500">{project.name}</span></Link></td><td style="text-align:left"><span class={`pill ${['completed', 'complete'].includes(project.status) ? 'st-done' : ['paused', 'hold', 'planning'].includes(project.status) ? 'st-backlog' : 'st-progress'}`}>{statusName(project.status)}</span></td><td><span class="row"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num faint" style="font-size:11.5px">{completionFor(project)}%</span></span></td><td class="num">{project.tasks_count || 0}</td><td class="num muted" style="text-align:left">{formatDate(project.due_date)}</td></tr>
        {/each}
      </tbody></table></div>
    {/if}
  </div>

  {#if modalOpen}
    <div class="modal-wrap" role="presentation" onclick={event => event.target === event.currentTarget && (modalOpen = false)}>
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="new-project-title" tabindex="-1">
        <form onsubmit={createProject}>
          <div class="modal-h"><h2 id="new-project-title">Create project</h2><button type="button" class="ibtn" aria-label="Close" onclick={() => modalOpen = false}>{@html ic('x', 16)}</button></div>
          <div class="modal-b">
            {#if errors.form}<div class="alert danger" role="alert">{errors.form}</div>{/if}
            <div class="field"><label class="label" for="project-name">Project name</label><input class="input" id="project-name" bind:value={name} maxlength="120" required aria-invalid={!!errors.name} placeholder="e.g. Website redesign" />{#if errors.name}<span class="err">{errors.name[0]}</span>{/if}</div>
            <div class="field"><label class="label" for="project-description">Description</label><textarea class="input" id="project-description" bind:value={description} rows="3" maxlength="5000" placeholder="What is this project about?"></textarea></div>
            <div class="field"><label class="label" for="project-target-date">Target date</label><input class="input" id="project-target-date" type="date" bind:value={dueDate} /></div>
            <div class="hint">Project key: <b>{name ? makeKey(name) : '—'}</b></div>
          </div>
          <div class="modal-f"><button type="button" class="btn btn-secondary" onclick={() => modalOpen = false}>Cancel</button><button class="btn btn-primary" disabled={busy}>{busy ? 'Creating…' : 'Create project'}</button></div>
        </form>
      </div>
    </div>
  {/if}
</AppLayout>
