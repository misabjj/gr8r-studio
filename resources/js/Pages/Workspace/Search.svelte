<script>
  import Input from "../../Components/Input.svelte";
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { index as searchIndex } from '../../routes/search';
  import { show as projectShow } from '../../routes/projects';
  import { ago, esc } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS, PSTAT } from '@/src/core/constants.js';
  import { FT, stIcon } from '@/src/ui/helpers.js';

  let { query = '', tasks: databaseTasks = [], projects: databaseProjects = [], workspaceData = {} } = $props();
  let searchQuery = $state('');
  let category = $state('all');

  $effect(() => { searchQuery = query; });

  let members = $derived(workspaceData.members || []);
  let sourceProjects = $derived(workspaceData.projects || []);
  let sourceTasks = $derived(workspaceData.tasks || []);
  let comments = $derived(workspaceData.comments || []);
  let files = $derived(workspaceData.files || []);
  let memberById = $derived(new Map(members.map(member => [member.id, member])));
  let projectById = $derived(new Map(sourceProjects.map(project => [project.id, project])));
  let taskById = $derived(new Map(sourceTasks.map(task => [task.id, task])));
  let projectByDatabaseId = $derived(new Map(sourceProjects.filter(project => project.database_id).map(project => [project.database_id, project])));
  let taskByDatabaseId = $derived(new Map(sourceTasks.filter(task => task.database_id).map(task => [task.database_id, task])));

  function canSeeProject(project) {
    return !project?.private || project.members?.includes(workspaceData.me);
  }

  function highlight(value) {
    const text = String(value || '');
    const term = searchQuery.trim();
    if (!term) return esc(text);
    const index = text.toLowerCase().indexOf(term.toLowerCase());
    if (index < 0) return esc(text);
    return `${esc(text.slice(0, index))}<mark>${esc(text.slice(index, index + term.length))}</mark>${esc(text.slice(index + term.length))}`;
  }

  let results = $derived.by(() => {
    const term = searchQuery.trim().toLowerCase();
    const contains = value => String(value || '').toLowerCase().includes(term);
    if (!term) return { tasks: [], projects: [], people: [], files: [], comments: [] };

    const foundTasks = sourceTasks.filter(task =>
      contains(task.title) || contains(task.key) || contains((task.desc || '').replace(/<[^>]+>/g, '')),
    ).slice(0, 30);
    const knownTaskIds = new Set(sourceTasks.map(task => task.database_id).filter(Boolean));
    for (const task of databaseTasks) {
      if (knownTaskIds.has(task.id)) continue;
      const description = task.description || '';
      if (contains(task.title) || contains(task.key) || contains(description)) {
        const project = projectByDatabaseId.get(task.project_id);
        foundTasks.push({
          id: `database-${task.id}`,
          database_id: task.id,
          key: task.key,
          project: project?.id || task.project_id,
          title: task.title,
          status: task.status,
          assignee: null,
          desc: description,
        });
      }
    }

    const foundProjects = sourceProjects.filter(project =>
      canSeeProject(project) && (contains(project.name) || contains(project.desc)),
    );
    const knownProjectIds = new Set(sourceProjects.map(project => project.database_id).filter(Boolean));
    for (const project of databaseProjects) {
      if (knownProjectIds.has(project.id) || !contains(project.name) && !contains(project.description)) continue;
      foundProjects.push({
        id: `database-${project.id}`,
        database_id: project.id,
        name: project.name,
        desc: project.description || '',
        icon: project.icon || 'folder',
        color: project.color || 'slate',
        status: project.status,
        members: [],
      });
    }

    return {
      tasks: foundTasks,
      projects: foundProjects,
      people: members.filter(member => contains(member.name) || contains(member.email) || contains(member.title)),
      files: files.filter(file => contains(file.name) && canSeeProject(projectById.get(file.project))),
      comments: comments.filter(comment => {
        const task = taskById.get(comment.task);
        return contains(comment.text) && task && canSeeProject(projectById.get(task.project));
      }),
    };
  });

  const categoryOptions = [
    ['all', 'All'],
    ['tasks', 'Tasks'],
    ['projects', 'Projects'],
    ['people', 'People'],
    ['files', 'Files'],
    ['comments', 'Comments'],
  ];
  let total = $derived(Object.values(results).reduce((count, items) => count + items.length, 0));
  let categories = $derived(categoryOptions.map(([key, label]) => [key, label, key === 'all' ? total : results[key].length]));
  let hasResults = $derived(total > 0);

  function openProject(project) {
    if (project.database_id) router.visit(projectShow(project.database_id).url);
  }

  function openTask(task) {
    const project = projectById.get(task.project) || projectByDatabaseId.get(task.project_id);
    if (project?.database_id) router.visit(projectShow(project.database_id).url);
  }

  function openFile(file) {
    openProject(projectById.get(file.project));
  }

  function openComment(comment) {
    const task = taskById.get(comment.task);
    if (task) openTask(task);
  }

  function searchOnServer(event) {
    event.preventDefault();
    router.get(searchIndex().url, { q: searchQuery.trim() }, { preserveState: true, preserveScroll: true, replace: true });
  }
</script>

<svelte:head><title>Search · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:860px">
    <form class="inwrap" style="margin-bottom:14px" action={searchIndex().url} method="get" onsubmit={searchOnServer}>
      {@html ic('search', 16)}
      <Input class="input-lg" id="search-page-q" name="q" bind:value={searchQuery} placeholder="Search tasks, projects, people, files, and comments" style="padding-left:34px;font-size:15px" aria-label="Search" />
    </form>
    <div class="tabs" style="margin-bottom:18px" role="tablist">
      {#each categories as [key, label, count]}
        <button type="button" role="tab" class="tab" class:on={category === key} aria-selected={category === key} onclick={() => category = key}>{label}<span class="cnt">{count}</span></button>
      {/each}
    </div>
    {#if !searchQuery.trim()}
      <div class="row" style="gap:6px;flex-wrap:wrap"><span class="faint" style="font-size:12.5px">Recent:</span>
        {#each workspaceData.recentSearches || [] as recentSearch (recentSearch)}
          <button class="badge" onclick={() => searchQuery = recentSearch}>{@html ic('history', 11)}{recentSearch}</button>
        {/each}
      </div>
    {:else if hasResults}
      {#if (category === 'all' || category === 'tasks') && results.tasks.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">Tasks · {results.tasks.length}</div><div class="panel" style="overflow:hidden">
          {#each results.tasks as task (task.id)}
            {@const taskProject = projectById.get(task.project) || projectByDatabaseId.get(task.project_id)}
            {@const assignee = memberById.get(task.assignee)}
            <button type="button" class="mini" style="width:100%;text-align:left" onclick={() => openTask(task)}>{@html stIcon(task.status)}<span class="mono faint" style="font-size:11px">{task.key}</span><span class="tt">{@html highlight(task.title)}</span><span class="pj"><span class="pdot" style={`--c:var(--${taskProject?.color || 'indigo'})`}></span>{taskProject?.name || '—'}</span>
              {#if assignee}<span class="av sm" style={`--c:${assignee.c}`} aria-label={assignee.name}>{assignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{/if}
            </button>
          {/each}
        </div></section>
      {/if}
      {#if (category === 'all' || category === 'projects') && results.projects.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">Projects · {results.projects.length}</div><div class="panel" style="overflow:hidden">
          {#each results.projects as project (project.id)}
            {@const projectStatus = PSTAT[project.status] || PSTAT.planning}
            <button type="button" class="mini" style="width:100%;text-align:left" onclick={() => openProject(project)}><span class="picon" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 13)}</span><span class="tt">{@html highlight(project.name)} <span class="faint" style="font-size:12px">— {project.desc?.slice(0, 70)}{project.desc?.length > 70 ? '…' : ''}</span></span><span class="pstatus" style={`--c:${projectStatus.c}`}><i></i>{projectStatus.name}</span></button>
          {/each}
        </div></section>
      {/if}
      {#if (category === 'all' || category === 'people') && results.people.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">People · {results.people.length}</div><div class="panel" style="overflow:hidden">
          {#each results.people as member (member.id)}
            <div class="mini"><span class="av md" style={`--c:${member.c}`} aria-label={member.name}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span><span class="tt">{@html highlight(member.name)} <span class="faint" style="font-size:12px">{member.title}</span></span><span class="faint" style="font-size:12px">{@html highlight(member.email)}</span></div>
          {/each}
        </div></section>
      {/if}
      {#if (category === 'all' || category === 'files') && results.files.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">Files · {results.files.length}</div><div class="panel" style="overflow:hidden">
          {#each results.files as file (file.id)}
            {@const fileType = FT[file.type] || FT.other}
            <button type="button" class="mini" style="width:100%;text-align:left" onclick={() => openFile(file)}><span class="ftype" style={`--c:${fileType.c}`}>{@html ic(fileType.i, 14)}</span><span class="tt">{@html highlight(file.name)}</span><span class="faint" style="font-size:12px">{file.size} · {projectById.get(file.project)?.name}</span></button>
          {/each}
        </div></section>
      {/if}
      {#if (category === 'all' || category === 'comments') && results.comments.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">Comments · {results.comments.length}</div><div class="panel" style="overflow:hidden">
          {#each results.comments as comment (comment.id)}
            {@const author = memberById.get(comment.by)}
            {@const task = taskById.get(comment.task)}
            <button type="button" class="mini" style="width:100%;align-items:flex-start;text-align:left;padding-top:10px;padding-bottom:10px" onclick={() => openComment(comment)}><span class="av sm" style={`--c:${author?.c || '#8A867E'}`} aria-label={author?.name || 'Someone'}>{author?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><span class="grow" style="min-width:0"><span style="display:block;font-size:12px" class="muted"><b style="color:var(--text);font-weight:500">{author?.name || 'Someone'}</b> on {task?.title} · {ago(comment.at)}</span><span style="display:block;font-size:13px">{@html highlight(comment.text)}</span></span></button>
          {/each}
        </div></section>
      {/if}
    {:else}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('search-x', 20)}</span><h2>No results found</h2><p>Nothing matches “{searchQuery}”. Check the spelling or try a broader term.</p><button class="btn btn-secondary btn-sm" onclick={() => searchQuery = ''}>Clear search</button></div></div>
    {/if}
  </div>
</AppLayout>
