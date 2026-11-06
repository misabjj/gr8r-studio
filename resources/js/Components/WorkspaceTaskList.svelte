<script>
  import Button from "./Button.svelte";
  import { tick } from 'svelte';
  import { ic } from '@/src/core/icons.js';
  import { relDate } from '@/src/core/utils.js';
  import { LABELS, PCOLORS } from '@/src/core/constants.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';

  let {
    groups = [],
    projects = [],
    members = [],
    workspaceData = {},
    selectedSort = 'manual',
    selectedSortDirection = 1,
    columns = ['status', 'assignee', 'priority', 'due', 'project'],
    complete = false,
    initialCollapsedGroups = [],
    onOpen,
    onUpdate,
    onToggleComplete,
    onSort,
    onCreate,
  } = $props();

  let selectedIds = $state(new Set());
  let toggledGroups = $state(new Set());
  let expandedTasks = $state(new Set());
  let activeMenu = $state(null);
  let search = $state('');
  let menuPosition = $state({ left: 0, top: 0 });
  let composerGroup = $state(null);
  let composerTitle = $state('');
  let composerInput = $state(null);

  const statuses = [
    { id: 'backlog', name: 'Backlog' },
    { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' },
    { id: 'review', name: 'Review' },
    { id: 'done', name: 'Done' },
  ];
  const priorities = [
    { id: 'urgent', name: 'Urgent' },
    { id: 'high', name: 'High' },
    { id: 'medium', name: 'Medium' },
    { id: 'low', name: 'Low' },
    { id: 'none', name: 'No priority' },
  ];
  const columnNames = { project: 'Project', status: 'Status', assignee: 'Assignee', priority: 'Priority', due: 'Due date', labels: 'Labels' };
  const columnWidths = { project: '160px', status: '126px', assignee: '150px', priority: '108px', due: '104px', labels: '168px' };
  let columnsStyle = $derived(`18px 30px minmax(220px,1fr) ${columns.map(column => columnWidths[column]).join(' ')} 40px`);

  let taskIds = $derived(groups.flatMap(group => group.tasks.map(task => task.id)));
  let selectedCount = $derived(taskIds.filter(id => selectedIds.has(id)).length);
  let allSelected = $derived(taskIds.length > 0 && selectedCount === taskIds.length);
  let visibleMembers = $derived(members.filter(member => member.name.toLowerCase().includes(search.trim().toLowerCase())));
  let visibleProjects = $derived(projects
    .filter(project => !['complete', 'completed', 'archived'].includes(project.status))
    .filter(project => project.name.toLowerCase().includes(search.trim().toLowerCase())));

  function toggleSelected(id) {
    const next = new Set(selectedIds);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    selectedIds = next;
  }

  function toggleAll() {
    selectedIds = allSelected ? new Set() : new Set(taskIds);
  }

  function toggleGroup(groupId) {
    const next = new Set(toggledGroups);
    if (next.has(groupId)) next.delete(groupId);
    else next.add(groupId);
    toggledGroups = next;
  }

  function isGroupCollapsed(groupId) {
    const initiallyCollapsed = initialCollapsedGroups.includes(groupId);
    return toggledGroups.has(groupId) ? !initiallyCollapsed : initiallyCollapsed;
  }

  function toggleExpanded(taskId) {
    const next = new Set(expandedTasks);
    if (next.has(taskId)) next.delete(taskId);
    else next.add(taskId);
    expandedTasks = next;
  }

  function sortColumn(column) {
    if (!onSort) return;
    onSort(column === 'project' ? 'project' : column);
  }

  function openMenuFor(event, field, task = null) {
    event.stopPropagation();
    if (activeMenu?.field === field && activeMenu.task?.id === task?.id) {
      activeMenu = null;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = field === 'due' ? 250 : 250;
    const left = Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8));
    menuPosition = { left, top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 360)) };
    search = '';
    activeMenu = { field, task };
  }

  function closeMenu(event) {
    if (!activeMenu) return;
    const target = event.target;
    if (target instanceof Element && target.closest('[data-task-list-menu], [data-task-list-trigger]')) return;
    activeMenu = null;
  }

  function handleKeydown(event) {
    if (event.key === 'Escape') {
      if (activeMenu) activeMenu = null;
      else if (composerGroup) cancelComposer();
    }
  }

  function dueLabel(task) {
    if (!task.due_date) return 'Set date';
    return relDate(String(task.due_date).slice(0, 10));
  }

  function isoOffset(days) {
    const date = new Date();
    date.setHours(0, 0, 0, 0);
    date.setDate(date.getDate() + days);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function initials(name = '') {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function memberFor(task) {
    return task.assignee || members.find(member => String(member.id) === String(task.assignee_id));
  }

  function commentsCount(task) {
    return (workspaceData.comments || []).filter(comment => comment.task === task.source_key).length;
  }

  function openTask(event, task) {
    if (event.target.closest('button, input, a, select')) return;
    if (event.type === 'keydown') {
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
    }
    onOpen(task);
  }

  async function updateOne(task, values) {
    activeMenu = null;
    await onUpdate(task, values);
  }

  async function toggleSubtask(task, subtask) {
    const subtasks = task.subtasks.map(item => item.id === subtask.id ? { ...item, done: !item.done } : item);
    await onUpdate(task, { subtasks });
  }

  async function applyBulk(values) {
    const selected = groups.flatMap(group => group.tasks).filter(task => selectedIds.has(task.id));
    activeMenu = null;
    await Promise.all(selected.map(task => onUpdate(task, values)));
    selectedIds = new Set();
  }

  function setDate(value) {
    if (activeMenu?.task) updateOne(activeMenu.task, { due_date: value || null });
  }

  async function createFromComposer() {
    const title = composerTitle.trim();
    if (!title || !composerGroup) return;
    await onCreate(title, composerGroup);
    composerTitle = '';
    composerGroup = null;
  }

  async function beginComposer(group) {
    composerGroup = group;
    composerTitle = '';
    await tick();
    composerInput?.focus();
  }

  function cancelComposer() {
    composerGroup = null;
    composerTitle = '';
  }

</script>

<svelte:window onpointerdown={closeMenu} onkeydown={handleKeydown} />

<div class="list-wrap">
  <div class="tlist" role="table" aria-label="Tasks">
    <div class="thead" style={`--cols:${columnsStyle}`} role="row">
      <div></div>
      <div>{#if !complete}<input class="check" type="checkbox" aria-label="Select all" checked={allSelected} onchange={toggleAll} />{/if}</div>
      <div class="sort-head" role="button" tabindex="0" onclick={() => onSort('title')} onkeydown={event => event.key === 'Enter' && onSort('title')}>Task {#if selectedSort === 'title'}{@html ic(selectedSortDirection > 0 ? 'arrow-up' : 'arrow-down', 11)}{/if}</div>
      {#each columns as column (column)}<div class="sort-head" role="button" tabindex="0" onclick={() => sortColumn(column)} onkeydown={event => event.key === 'Enter' && sortColumn(column)}>{columnNames[column]}{#if selectedSort === column}{@html ic(selectedSortDirection > 0 ? 'arrow-up' : 'arrow-down', 11)}{/if}</div>{/each}
      <div></div>
    </div>
    {#each groups as group (group.id)}
      {@const isCollapsed = isGroupCollapsed(group.id)}
      <div class="grp">
        <button type="button" class="ibtn ibtn-xs" aria-label="Toggle group" aria-expanded={!isCollapsed} onclick={() => toggleGroup(group.id)}>{@html ic(isCollapsed ? 'chevron-right' : 'chevron-down', 13)}</button>
        {#if group.icon}{@html group.iconType === 'status' ? stIcon(group.id, 14) : ic(group.icon, 14)}{/if}
        <span>{group.name}</span><span class="cnt">{group.tasks.length}</span>
        <button type="button" class="ibtn ibtn-xs" aria-label={`Add task to ${group.name}`} onclick={() => beginComposer(group)}>{@html ic('plus', 13)}</button>
      </div>
      {#if !isCollapsed}
        {#each group.tasks as task (task.id)}
          {@const member = memberFor(task)}
          {@const project = task.project || projects.find(item => item.id === task.project_id)}
          {@const selected = selectedIds.has(task.id)}
          <div class={`trow ${task.status === 'done' ? 'done' : ''} ${selected ? 'sel' : ''}`} style={`--cols:${columnsStyle}`} role="row">
            <div class="handle" aria-hidden="true">{#if !complete}{@html ic('grip-vertical', 14)}{/if}</div>
            <div class="c-check"><input class={`check ${complete ? 'round' : ''}`} type="checkbox" aria-label={complete ? `Mark ${task.title} ${task.status === 'done' ? 'incomplete' : 'complete'}` : `Select ${task.title}`} checked={complete ? task.status === 'done' : selected} onchange={() => complete ? onToggleComplete?.(task) : toggleSelected(task.id)} /></div>
            <div class="ttl" role="button" tabindex="0" onclick={event => openTask(event, task)} onkeydown={event => openTask(event, task)}>
              {#if task.subtasks?.length}<button type="button" class="ibtn ibtn-xs" aria-label={`${expandedTasks.has(task.id) ? 'Hide' : 'Show'} subtasks`} aria-expanded={expandedTasks.has(task.id)} onclick={event => { event.stopPropagation(); toggleExpanded(task.id); }}>{@html ic(expandedTasks.has(task.id) ? 'chevron-down' : 'chevron-right', 13)}</button>{:else}<span style="width:18px;flex-shrink:0"></span>{/if}
              <span class="key">{task.key}</span><span class="tt">{task.title}</span>
              {#if task.recurrence}<span class="meta-mini" aria-label={`Repeats ${task.recurrence}`}>{@html ic('repeat', 11)}</span>{/if}
              {#if task.subtasks?.length}<span class="meta-mini">{@html ic('list-checks', 11)}{task.subtasks.filter(subtask => subtask.done).length}/{task.subtasks.length}</span>{/if}
              {#if commentsCount(task)}<span class="meta-mini">{@html ic('message-square', 11)}{commentsCount(task)}</span>{/if}
            </div>
            {#each columns as column (column)}
              {#if column === 'project'}<div class="c-meta c-project"><button type="button" class="pillbtn" aria-label="Project" data-task-list-trigger onclick={event => openMenuFor(event, 'project', task)}><span class="pdot" style={`--c:${PCOLORS[project?.color] || PCOLORS.slate}`}></span><span class="trunc">{project?.name || 'Project'}</span></button></div>
              {:else if column === 'status'}<div class="c-meta c-status"><button type="button" class="pillbtn" aria-label={`Status: ${statuses.find(item => item.id === task.status)?.name || task.status}`} data-task-list-trigger onclick={event => openMenuFor(event, 'status', task)}>{@html stIcon(task.status, 13)}{statuses.find(item => item.id === task.status)?.name || task.status}</button></div>
              {:else if column === 'assignee'}<div class="c-meta c-assignee"><button type="button" class={`pillbtn ${member ? '' : 'empty'}`} aria-label="Assignee" data-task-list-trigger onclick={event => openMenuFor(event, 'assignee', task)}>{#if member}<span class="av sm" style={`--c:${member.color || '#8A867E'}`}>{initials(member.name)}</span><span class="trunc">{member.name}</span>{:else}{@html ic('user-round', 13)}Unassigned{/if}</button></div>
              {:else if column === 'priority'}<div class="c-meta c-priority"><button type="button" class={`pillbtn ${task.priority === 'none' ? 'empty' : ''}`} aria-label="Priority" data-task-list-trigger onclick={event => openMenuFor(event, 'priority', task)}>{@html prIcon(task.priority, 13)}{task.priority === 'none' ? 'No priority' : task.priority[0].toUpperCase() + task.priority.slice(1)}</button></div>
              {:else if column === 'due'}<div class="c-meta c-due"><button type="button" class={`pillbtn ${task.due_date && task.status !== 'done' && new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString()) ? 'over' : ''} ${task.due_date ? '' : 'empty'}`} aria-label="Due date" data-task-list-trigger onclick={event => openMenuFor(event, 'due', task)}>{@html ic('calendar', 13)}<span class="num">{dueLabel(task)}</span></button></div>
              {:else if column === 'labels'}<div class="c-meta c-project"><span class="row" style="gap:4px;overflow:hidden">{#each (task.labels || []).slice(0, 2) as label (label)}{@const labelDetails = LABELS.find(item => item.id === label)}<span class="lbl" style={`--c:${labelDetails?.c || 'var(--gray)'}`}>{labelDetails?.name || label}</span>{/each}</span></div>{/if}
            {/each}
            <div class="c-more"><button type="button" class="ibtn ibtn-sm" aria-label="More actions" data-task-list-trigger onclick={event => openMenuFor(event, 'more', task)}>{@html ic('ellipsis', 15)}</button></div>
          </div>
          {#if expandedTasks.has(task.id)}<div class="subrows">{#each task.subtasks || [] as subtask (subtask.id)}<div class:done={subtask.done} class="subrow"><input type="checkbox" class="check" checked={subtask.done} aria-label={`Complete subtask ${subtask.title}`} onchange={() => toggleSubtask(task, subtask)} /><span>{subtask.title}</span></div>{/each}</div>{/if}
        {/each}
        {#if composerGroup?.id === group.id}
          <div class="addrow" style="background:var(--surface-2)">{@html ic('plus', 14)}<input bind:this={composerInput} class="inline-in" bind:value={composerTitle} placeholder="Task name — press Enter to add, Esc to cancel" aria-label="New task name" onkeydown={event => event.key === 'Enter' && createFromComposer()} onblur={() => !composerTitle.trim() && cancelComposer()} /></div>
        {:else}
          <button type="button" class="addrow" onclick={() => beginComposer(group)}>{@html ic('plus', 14)}Add task</button>
        {/if}
      {/if}
    {/each}
  </div>
</div>

{#if selectedCount}
  <div class="bulkbar enter" role="toolbar" aria-label="Bulk actions">
    <b style="font-weight:600">{selectedCount} selected</b><span class="sep"></span>
    <Button type="button" variant="base" size="sm" data-task-list-trigger onclick={event => openMenuFor(event, 'bulk-status')}>{@html ic('circle-dot', 14)}Status</Button>
    <Button type="button" variant="base" size="sm" data-task-list-trigger onclick={event => openMenuFor(event, 'bulk-assignee')}>{@html ic('user', 14)}Assignee</Button>
    <Button type="button" variant="base" size="sm" data-task-list-trigger onclick={event => openMenuFor(event, 'bulk-priority')}>{@html ic('signal-high', 14)}Priority</Button>
    <span class="sep"></span><button type="button" class="ibtn ibtn-sm" aria-label="Clear selection" onclick={() => selectedIds = new Set()}>{@html ic('x', 14)}</button>
  </div>
{/if}

{#if activeMenu}
  <div class="pop enter" data-task-list-menu role="menu" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:250px`}>
    {#if activeMenu.field === 'status' || activeMenu.field === 'bulk-status'}
      {#each statuses as item (item.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={activeMenu.task?.status === item.id} onclick={() => activeMenu.task ? updateOne(activeMenu.task, { status: item.id }) : applyBulk({ status: item.id })}>{@html stIcon(item.id, 14)}<span>{item.name}</span>{#if activeMenu.task?.status === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
    {:else if activeMenu.field === 'priority' || activeMenu.field === 'bulk-priority'}
      {#each priorities as item (item.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={activeMenu.task?.priority === item.id} onclick={() => activeMenu.task ? updateOne(activeMenu.task, { priority: item.id }) : applyBulk({ priority: item.id })}>{@html prIcon(item.id, 14)}<span>{item.name === 'none' ? 'No priority' : item.name[0].toUpperCase() + item.name.slice(1)}</span>{#if activeMenu.task?.priority === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
    {:else if activeMenu.field === 'assignee' || activeMenu.field === 'bulk-assignee'}
      <div class="pin"><input bind:value={search} placeholder="Assign to…" aria-label="Assign to…" /></div>
      <button type="button" class="mi" role="menuitemradio" aria-checked={!activeMenu.task?.assignee_id} onclick={() => activeMenu.task ? updateOne(activeMenu.task, { assignee_id: null }) : applyBulk({ assignee_id: null })}>{@html ic('user-round', 14)}<span>Unassigned</span></button>
      {#each visibleMembers as member (member.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(activeMenu.task?.assignee_id || '') === String(member.id)} onclick={() => activeMenu.task ? updateOne(activeMenu.task, { assignee_id: member.id }) : applyBulk({ assignee_id: member.id })}><span class="av sm" style={`--c:${member.color || '#8A867E'}`}>{initials(member.name)}</span><span class="trunc">{member.name}</span></button>{/each}
    {:else if activeMenu.field === 'project'}
      <div class="pin"><input bind:value={search} placeholder="Move to project…" aria-label="Move to project…" /></div>
      {#each visibleProjects as project (project.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(activeMenu.task.project_id) === String(project.id)} onclick={() => updateOne(activeMenu.task, { project_id: project.id })}><span class="pdot" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}></span><span class="trunc">{project.name}</span></button>{/each}
    {:else if activeMenu.field === 'due'}
      <div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">
        {#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7], ['In 2 weeks', 14]] as [label, days] (label)}
          <button type="button" class="badge" onclick={() => setDate(isoOffset(days))}>{label}</button>
        {/each}
        {#if activeMenu.task.due_date}<button type="button" class="badge" onclick={() => setDate('')}>Clear</button>{/if}
      </div>
      <div class="msep"></div>
      <div class="pin"><input type="date" aria-label="Due date" value={activeMenu.task.due_date?.slice(0, 10) || ''} onchange={event => setDate(event.currentTarget.value)} /></div>
    {:else if activeMenu.field === 'more'}
      <button type="button" class="mi" onclick={() => { const task = activeMenu.task; activeMenu = null; onOpen(task); }}>{@html ic('panel-right', 14)}<span>Open task</span></button>
    {/if}
  </div>
{/if}
