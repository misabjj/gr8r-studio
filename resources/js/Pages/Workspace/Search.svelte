<script>
  import { Link } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { index as searchIndex } from '../../routes/search';
  import { show as projectShow } from '../../routes/projects';
  import { ic } from '@/src/core/icons.js';
  import { stIcon } from '@/src/ui/helpers.js';

  let { query = '', tasks = [], projects = [] } = $props();
  let category = $state('all');
  let categories = $derived([
    ['all', 'All', tasks.length + projects.length],
    ['tasks', 'Tasks', tasks.length],
    ['projects', 'Projects', projects.length],
    ['people', 'People', 0],
    ['files', 'Files', 0],
    ['comments', 'Comments', 0],
  ]);
  let hasResults = $derived(tasks.length > 0 || projects.length > 0);
</script>

<svelte:head><title>Search · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:860px">
    <form class="inwrap" style="margin-bottom:14px" action={searchIndex().url} method="get">
      {@html ic('search', 16)}
      <input class="input input-lg" id="search-page-q" name="q" placeholder="Search tasks, projects, people, files, and comments" value={query} style="padding-left:34px;font-size:15px" aria-label="Search" />
    </form>
    <div class="tabs" style="margin-bottom:18px">
      {#each categories as [key, label, count]}
        <button class="tab" class:on={category === key} onclick={() => category = key}>{label}<span class="cnt">{count}</span></button>
      {/each}
    </div>
    {#if !query}
      <div class="row" style="gap:6px;flex-wrap:wrap"><span class="faint" style="font-size:12.5px">Recent:</span></div>
    {:else if hasResults}
      {#if (category === 'all' || category === 'tasks') && tasks.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">Tasks · {tasks.length}</div><div class="panel" style="overflow:hidden">
          {#each tasks as task (task.id)}
            <Link class="mini" href={projectShow(task.project_id).url}>{@html stIcon(task.status)}<span class="tt">{task.title}</span><span class="pj"><span class="pdot" style={`--c:var(--${task.project?.color || 'indigo'})`}></span>{task.project?.name}</span></Link>
          {/each}
        </div></section>
      {/if}
      {#if (category === 'all' || category === 'projects') && projects.length}
        <section style="margin-bottom:22px"><div class="eyebrow" style="margin-bottom:6px">Projects · {projects.length}</div><div class="panel" style="overflow:hidden">
          {#each projects as project (project.id)}
            <Link class="mini" href={projectShow(project.id).url}><span class="pico" style={`--c:var(--${project.color || 'indigo'})`}>{@html ic('folder', 13)}</span><span class="tt">{project.name} <span class="faint" style="font-size:12px">— {project.description?.slice(0, 70)}{project.description?.length > 70 ? '…' : ''}</span></span></Link>
          {/each}
        </div></section>
      {/if}
    {:else}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('search-x', 20)}</span><h2>No results found</h2><p>Nothing matches “{query}”. Check the spelling or try a broader term.</p><Link class="btn btn-secondary btn-sm" href={searchIndex().url}>Clear search</Link></div></div>
    {/if}
  </div>
</AppLayout>
