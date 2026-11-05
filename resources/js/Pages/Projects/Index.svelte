<script>
  import { Link, router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { show as projectShow, store as projectStore } from '../../routes/projects';
  import { jsonRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';

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
  let busy = $state(false);
  let errors = $state({});

  let activeCount = $derived(projects.filter(project => project.status === 'active').length);
  let completedCount = $derived(projects.filter(project => project.status === 'completed').length);
  let visibleProjects = $derived.by(() => {
    const filtered = projects.filter(project => {
      const matchesQuery = !query || `${project.name} ${project.description || ''}`.toLowerCase().includes(query.toLowerCase());
      const matchesStatus = statusFilter === 'all' || project.status === statusFilter;
      return matchesQuery && matchesStatus;
    });
    return filtered.sort((first, second) => {
      if (sortBy === 'name') return first.name.localeCompare(second.name);
      if (sortBy === 'tasks') return (second.tasks_count || 0) - (first.tasks_count || 0);
      return new Date(second.created_at || 0) - new Date(first.created_at || 0);
    });
  });

  function statusName(status) {
    return ({ active: 'In Progress', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[status] || status;
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
        <option value="all">All statuses</option><option value="active">In Progress</option><option value="paused">On Hold</option><option value="completed">Completed</option><option value="archived">Archived</option>
      </select>
      <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={sortBy} aria-label="Sort projects">
        <option value="recent">Sort: Recently added</option><option value="name">Sort: Name</option><option value="tasks">Sort: Task count</option>
      </select>
      <span class="sp"></span>
      <div class="seg" role="tablist" aria-label="Project layout">
        <button class:on={view === 'grid'} aria-label="Grid" aria-pressed={view === 'grid'} onclick={() => view = 'grid'}>{@html ic('layout-grid', 13)}<span class="hide-m">Grid</span></button>
        <button class:on={view === 'list'} aria-label="List" aria-pressed={view === 'list'} onclick={() => view = 'list'}>{@html ic('list', 13)}<span class="hide-m">List</span></button>
      </div>
    </div>

    {#if visibleProjects.length === 0}
      <section class="panel"><div class="empty"><span class="empty-ic">{@html ic('folder-kanban', 20)}</span><h2>{projects.length ? 'No results found' : 'No projects yet'}</h2><p>{projects.length ? 'No projects match your search or filters.' : 'Create your first project to start organizing your work.'}</p>{#if !projects.length}<button class="btn btn-primary btn-sm" onclick={openCreateProject}>{@html ic('plus', 14)}Create project</button>{/if}</div></section>
    {:else if view === 'grid'}
      <div class="pgrid">
        {#each visibleProjects as project (project.id)}
          <Link class="pcard" href={projectShow(project.id).url} aria-label={project.name}>
            <div class="row">
              <span class="pico" style="--c:var(--accent)">{@html ic('folder', 14)}</span>
              <div class="grow" style="min-width:0"><div class="row" style="gap:6px"><b class="trunc" style="font-weight:600;font-size:14px">{project.name}</b></div></div>
              <span class={`pill ${project.status === 'completed' ? 'st-done' : project.status === 'paused' ? 'st-backlog' : 'st-progress'}`}>{statusName(project.status)}</span>
            </div>
            <div class="desc">{project.description || 'No description yet.'}</div>
            <div class="row" style="gap:10px"><span class="prog"><i style={`width:${completionFor(project)}%`}></i></span><span class="num" style="font-size:12px;font-weight:500">{project.tasks_count || 0} tasks</span></div>
            <div class="foot"><span class="row" style="gap:4px">{@html ic('hash', 12)}{project.key}</span><span class="sp"></span><span class="row faint" style="gap:4px">{@html ic('clock', 12)}{project.created_at ? new Date(project.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''}</span></div>
          </Link>
        {/each}
      </div>
    {:else}
      <div class="panel" style="overflow:hidden">
        {#each visibleProjects as project (project.id)}
          <Link class="mini" href={projectShow(project.id).url}>
            <span class="pico" style="--c:var(--accent)">{@html ic('folder', 14)}</span>
            <span class="grow"><b>{project.name}</b><span class="trunc faint" style="display:block;font-size:12px">{project.description}</span></span>
            <span class={`pill ${project.status === 'completed' ? 'st-done' : 'st-progress'}`}>{statusName(project.status)}</span>
            <span class="num muted">{project.tasks_count || 0} tasks</span>
            <span class="key">{project.key}</span>
          </Link>
        {/each}
      </div>
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
            <div class="hint">Project key: <b>{name ? makeKey(name) : '—'}</b></div>
          </div>
          <div class="modal-f"><button type="button" class="btn btn-secondary" onclick={() => modalOpen = false}>Cancel</button><button class="btn btn-primary" disabled={busy}>{busy ? 'Creating…' : 'Create project'}</button></div>
        </form>
      </div>
    </div>
  {/if}
</AppLayout>
