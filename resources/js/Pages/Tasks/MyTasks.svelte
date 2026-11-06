<script>
  import Input from "../../Components/Input.svelte";
  import { usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import ProjectCalendar from '../../Components/ProjectCalendar.svelte';
  import WorkspaceTaskList from '../../Components/WorkspaceTaskList.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { store as taskStore } from '../../routes/projects/tasks';
  import { update as taskUpdateRoute } from '../../routes/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { ic } from '@/src/core/icons.js';
  import { LABELS } from '@/src/core/constants.js';
  import { S, save } from '@/src/core/store.js';
  import { viewOf } from '@/src/shell/view-engine.js';

  const httpRequest = useHttpRequest();
  let { tasks: incomingTasks = [], projects = [], workspaceData = {} } = $props();
  const page = usePage();
  const savedView = viewOf('mytasks');
  let pageUserId = $derived(page.props.auth?.user?.id || null);
  let tasks = $state([]);
  let view = $state(S.ui.myView || 'list');
  let formOpen = $state(false);
  let initialDate = $state('');
  let query = $state(savedView.q || '');
  let sortBy = $state(savedView.sort?.f || 'manual');
  let sortDirection = $state(savedView.sort?.dir || 1);
  let filters = $state((savedView.filters || []).map((filter, index) => ({
    id: index,
    field: filter.f,
    operator: filter.op || 'is',
    values: [...(filter.v || [])],
  })));
  let toolbarMenu = $state('');
  let filterField = $state('');
  let filterIndex = $state(null);
  let menuPosition = $state({ left: 0, top: 0 });
  let previousStatuses = $state(new Map());
  let errorMessage = $state('');
  let drawerTaskId = $state(null);
  let selectedTask = $derived(tasks.find(task => task.id === drawerTaskId) || null);

  $effect(() => { tasks = [...incomingTasks]; });
  $effect(() => {
    savedView.q = query;
    savedView.sort = { f: sortBy, dir: sortDirection };
    savedView.filters = filters.map(filter => ({ f: filter.field, op: filter.operator, v: [...filter.values] }));
    save();
  });

  const members = $derived((workspaceData.members || [])
    .filter(member => member.database_id)
    .map(member => ({ id: member.database_id, source_key: member.id, name: member.name, color: member.c, status: member.status })));
  const sortNames = { manual: 'Manual', title: 'Title', due: 'Due date', start: 'Start date', priority: 'Priority', status: 'Status', created: 'Created', updated: 'Updated', assignee: 'Assignee', estimate: 'Estimate', project: 'Project' };
  const filterNames = { status: 'Status', assignee: 'Assignee', priority: 'Priority', due: 'Due date', project: 'Project', labels: 'Labels', created: 'Created date', updated: 'Updated date' };

  function filterOptions(field) {
    if (field === 'status') return ['backlog', 'todo', 'progress', 'review', 'done'].map(value => ({ value, label: ({ backlog: 'Backlog', todo: 'To Do', progress: 'In Progress', review: 'Review', done: 'Done' })[value] }));
    if (field === 'priority') return ['urgent', 'high', 'medium', 'low', 'none'].map(value => ({ value, label: value === 'none' ? 'No priority' : value[0].toUpperCase() + value.slice(1) }));
    if (field === 'assignee') return [{ value: 'none', label: 'Unassigned' }, ...members.map(member => ({ value: String(member.id), label: member.name }))];
    if (field === 'project') return projects.map(project => ({ value: String(project.id), label: project.name }));
    if (field === 'labels') return LABELS.map(label => ({ value: label.id, label: label.name }));
    if (field === 'due') return [{ value: 'overdue', label: 'Overdue' }, { value: 'today', label: 'Today' }, { value: 'week', label: 'Next 7 days' }, { value: 'later', label: 'Later' }, { value: 'nodate', label: 'No due date' }];
    return [{ value: '1', label: 'Last 24 hours' }, { value: '7', label: 'Last 7 days' }, { value: '30', label: 'Last 30 days' }];
  }

  function matchesFilter(task, filter) {
    if (!filter.values.length) return true;
    if (filter.field === 'labels') return filter.values.some(value => (task.labels || []).includes(value));
    if (filter.field === 'assignee') return filter.values.includes(String(task.assignee_id || 'none'));
    if (filter.field === 'project') return filter.values.includes(String(task.project_id));
    if (filter.field === 'due') return filter.values.some(value => {
      if (value === 'nodate') return !task.due_date;
      if (!task.due_date) return false;
      const difference = dayDifference(task.due_date);
      if (value === 'overdue') return difference < 0 && task.status !== 'done';
      if (value === 'today') return difference === 0;
      if (value === 'week') return difference >= 0 && difference <= 7;
      return difference > 7;
    });
    if (filter.field === 'created' || filter.field === 'updated') {
      const timestamp = new Date(filter.field === 'created' ? task.created_at : task.updated_at).getTime();
      return Number.isFinite(timestamp) && Date.now() - timestamp <= Number(filter.values[0]) * 86400000;
    }
    return filter.values.includes(String(task[filter.field]));
  }

  function matchesFilterWithOperator(task, filter) {
    if (!filter.values.length) return true;
    const matches = matchesFilter(task, filter);
    return filter.operator === 'not' ? !matches : matches;
  }

  let visibleTasks = $derived(tasks.filter(task => {
    const matchesQuery = !query.trim() || `${task.title} ${task.key} ${task.project?.name || ''} ${task.project?.key || ''}`.toLowerCase().includes(query.trim().toLowerCase());
    return matchesQuery && filters.every(filter => matchesFilterWithOperator(task, filter));
  }));

  function isOverdue(task) {
    if (!task.due_date || task.status === 'done') return false;
    const due = new Date(`${task.due_date.slice(0, 10)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return due < today;
  }

  function dayDifference(value) {
    const due = new Date(`${value.slice(0, 10)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.floor((due - today) / 86400000);
  }

  let groups = $derived.by(() => {
    const order = taskList => [...taskList].sort((first, second) => {
      if (sortBy === 'manual') return (first.due_date || '9999-12-31').localeCompare(second.due_date || '9999-12-31');
      const compare = (firstValue, secondValue) => sortDirection * (firstValue > secondValue ? 1 : firstValue < secondValue ? -1 : 0);
      if (sortBy === 'due') return compare(first.due_date || '9999', second.due_date || '9999');
      if (sortBy === 'start') return compare(first.start_date || '9999', second.start_date || '9999');
      if (sortBy === 'title') return compare(first.title.toLowerCase(), second.title.toLowerCase());
      if (sortBy === 'priority') return compare(-['none', 'low', 'medium', 'high', 'urgent'].indexOf(first.priority || 'none'), -['none', 'low', 'medium', 'high', 'urgent'].indexOf(second.priority || 'none'));
      if (sortBy === 'status') {
        const statusOrder = ['backlog', 'todo', 'progress', 'review', 'done'];
        return compare(statusOrder.indexOf(first.status), statusOrder.indexOf(second.status));
      }
      if (sortBy === 'assignee') return compare(first.assignee?.name || 'zzz', second.assignee?.name || 'zzz');
      if (sortBy === 'estimate') return compare(first.estimate || 'zzz', second.estimate || 'zzz');
      if (sortBy === 'project') return compare(first.project?.name || 'zzz', second.project?.name || 'zzz');
      const firstTimestamp = new Date(sortBy === 'created' ? first.created_at : first.updated_at).getTime();
      const secondTimestamp = new Date(sortBy === 'created' ? second.created_at : second.updated_at).getTime();
      return compare(secondTimestamp, firstTimestamp);
    });
    return [
      { id: 'overdue', name: 'Overdue', icon: 'clock-alert', iconType: 'icon', tasks: order(visibleTasks.filter(isOverdue)) },
      { id: 'today', name: 'Today', icon: 'sun', iconType: 'icon', tasks: order(visibleTasks.filter(task => task.status !== 'done' && task.due_date && dayDifference(task.due_date) === 0)) },
      { id: 'upcoming', name: 'Upcoming', icon: 'calendar-days', iconType: 'icon', tasks: order(visibleTasks.filter(task => task.status !== 'done' && !isOverdue(task) && (!task.due_date || dayDifference(task.due_date) > 0))) },
      { id: 'completed', name: 'Completed', icon: 'circle-check', iconType: 'icon', tasks: order(visibleTasks.filter(task => task.status === 'done')) },
    ];
  });
  let overdueCount = $derived(groups[0].tasks.length);
  let todayCount = $derived(groups[1].tasks.length);
  let upcomingCount = $derived(groups[2].tasks.length);

  function openCreateTask(date = '') {
    initialDate = date;
    formOpen = true;
  }

  function setView(nextView) {
    view = nextView;
    S.ui.myView = nextView;
  }

  function toggleToolbarMenu(menu, event) {
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = menu === 'sort' ? 220 : 300;
    menuPosition = { left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)), top: Math.min(bounds.bottom + 4, window.innerHeight - 320) };
    toolbarMenu = toolbarMenu === menu ? '' : menu;
    filterField = '';
    filterIndex = null;
  }

  function openFilterMenu(event) {
    if (toolbarMenu !== 'filter') toggleToolbarMenu('filter', event);
    else {
      filterField = '';
      filterIndex = null;
    }
  }

  function addFilter(field) {
    const id = filters.reduce((maximum, filter) => Math.max(maximum, filter.id), -1) + 1;
    filters = [...filters, { id, field, operator: 'is', values: [] }];
    filterIndex = id;
    filterField = field;
  }

  function toggleFilterValue(id, value) {
    filters = filters.map(filter => filter.id !== id ? filter : {
      ...filter,
      values: filter.values.includes(value) ? filter.values.filter(item => item !== value) : [...filter.values, value],
    });
  }

  function removeFilter(id) {
    filters = filters.filter(filter => filter.id !== id);
    if (filterIndex === id) {
      filterIndex = null;
      filterField = '';
    }
  }

  function clearFilters() {
    filters = [];
    query = '';
    filterIndex = null;
    filterField = '';
    toolbarMenu = '';
  }

  function openFilter(filter, event) {
    if (toolbarMenu !== 'filter') toggleToolbarMenu('filter', event);
    filterIndex = filter.id;
    filterField = filter.field;
  }

  function toggleFilterOperator(id) {
    filters = filters.map(filter => filter.id === id
      ? { ...filter, operator: filter.operator === 'not' ? 'is' : 'not' }
      : filter);
  }

  function handleWindowPointerdown(event) {
    if (!toolbarMenu || !(event.target instanceof Element) || event.target.closest('[data-mytasks-pop], [data-mytasks-trigger]')) return;
    toolbarMenu = '';
    filterField = '';
    filterIndex = null;
  }

  function handleWindowKeydown(event) {
    if (event.key !== 'Escape') return;
    toolbarMenu = '';
    filterField = '';
    filterIndex = null;
  }

  function taskCreated(task) {
    tasks = [task, ...tasks];
  }

  function taskUpdated(updatedTask) {
    tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }

  async function toggleComplete(task) {
    const status = task.status === 'done' ? previousStatuses.get(task.id) || 'todo' : 'done';
    errorMessage = '';
    try {
      const updated = await httpRequest(taskUpdateRoute(task.id).url, 'PATCH', { status });
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
      const nextPreviousStatuses = new Map(previousStatuses);
      if (status === 'done') nextPreviousStatuses.set(task.id, task.status);
      else nextPreviousStatuses.delete(task.id);
      previousStatuses = nextPreviousStatuses;
    } catch (error) {
      errorMessage = error.message;
    }
  }

  async function updateTask(task, values) {
    errorMessage = '';
    try {
      const updated = await httpRequest(taskUpdateRoute(task.id).url, 'PATCH', values);
      taskUpdated(updated);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  async function createInlineTask(title, group) {
    const project = projects.find(item => !['complete', 'completed', 'archived'].includes(item.status)) || projects[0];
    if (!project) return;
    try {
      const created = await httpRequest(taskStore({ project: project.id }).url, 'POST', {
        title,
        status: group.id === 'completed' ? 'done' : 'todo',
        due_date: group.id === 'today' ? new Date().toISOString().slice(0, 10) : group.id === 'upcoming' ? new Date(Date.now() + 3 * 86400000).toISOString().slice(0, 10) : null,
        assignee_id: pageUserId,
      });
      taskCreated(created);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }
</script>

<svelte:head><title>My Tasks · Gr8r Studio</title></svelte:head>
<svelte:window onpointerdown={handleWindowPointerdown} onkeydown={handleWindowKeydown} />

<AppLayout>
  <div class="page flush">
    <div class="ph">
      <div><h1>My Tasks</h1><p>{todayCount} due today · {overdueCount} overdue · {upcomingCount} upcoming</p></div>
      <div class="acts">
        <div class="seg" role="tablist" aria-label="View">
          <button class:on={view === 'list'} aria-pressed={view === 'list'} onclick={() => setView('list')}>{@html ic('list', 13)}List</button>
          <button class:on={view === 'calendar'} aria-pressed={view === 'calendar'} onclick={() => setView('calendar')}>{@html ic('calendar', 13)}Calendar</button>
        </div>
        <button class="btn btn-primary" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</button>
      </div>
    </div>

    {#if errorMessage}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errorMessage}</span></div>{/if}
    <div class="toolbar" role="toolbar">
      <div class="inwrap">{@html ic('search', 13)}<Input class="search-sm" bind:value={query} placeholder="Search tasks" aria-label="Search tasks" /></div>
      <button class={`btn btn-ghost ${filters.length ? 'on' : ''}`} type="button" aria-label="Filter" data-mytasks-trigger onclick={openFilterMenu}>{@html ic('list-filter', 14)}Filter{#if filters.length}<span class="badge accent" style="height:16px;padding:0 5px">{filters.length}</span>{/if}</button>
      <button class="btn btn-ghost" type="button" aria-label={`Sort: ${sortNames[sortBy]}`} data-mytasks-trigger onclick={event => toggleToolbarMenu('sort', event)}>{@html ic('arrow-up-down', 14)}<span class="hide-m">Sort:</span> {sortNames[sortBy]}</button>
    </div>
    {#if filters.length}<div class="chipsbar">{@html ic('list-filter', 13)}{#each filters as filter (filter.id)}{@const options = filterOptions(filter.field)}<span class="chip"><button type="button" onclick={event => openFilter(filter, event)}>{filterNames[filter.field]} {filter.operator === 'not' ? 'is not' : 'is'} {filter.values.map(value => options.find(option => option.value === value)?.label || value).join(', ') || 'any'}</button><button type="button" class="ibtn" aria-label={`Remove ${filterNames[filter.field]} filter`} onclick={() => removeFilter(filter.id)}>{@html ic('x', 12)}</button></span>{/each}<button type="button" class="btn btn-sm btn-ghost" data-mytasks-trigger onclick={openFilterMenu}>{@html ic('plus', 12)}Add</button><span class="sp"></span><button type="button" class="btn btn-sm btn-ghost" onclick={clearFilters}>Clear all</button></div>{/if}
    {#if view === 'calendar'}
      <ProjectCalendar tasks={visibleTasks} projects={projects} workspaceData={workspaceData} project={null} showToolbar={false} onCreate={openCreateTask} />
    {:else}
      <WorkspaceTaskList {groups} {projects} {members} {workspaceData} complete columns={['project', 'status', 'priority', 'due']} initialCollapsedGroups={['completed']} selectedSort={sortBy} selectedSortDirection={sortDirection} onOpen={task => drawerTaskId = task.id} onUpdate={updateTask} onToggleComplete={toggleComplete} onSort={field => { if (sortBy === field) sortDirection = -sortDirection; else { sortBy = field; sortDirection = 1; } }} onCreate={createInlineTask} />
    {/if}
  </div>
  <WorkspaceTaskForm open={formOpen} {projects} {initialDate} initialAssignee={pageUserId} onClose={() => formOpen = false} onCreated={taskCreated} />
  {#if selectedTask}<TaskDrawer task={selectedTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={taskUpdated} />{/if}
</AppLayout>
{#if toolbarMenu === 'filter'}
  <div class="pop enter" data-mytasks-pop role="menu" aria-label="Filter tasks" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:300px`}>
    {#if filterField}
      {@const activeFilter = filters.find(filter => filter.id === filterIndex)}
      <div class="row" style="padding:6px 8px"><button type="button" class="ibtn ibtn-xs" aria-label="Back to filters" onclick={() => { filterField = ''; filterIndex = null; }}>{@html ic('arrow-left', 13)}</button><b style="font-size:12px;font-weight:600">{filterNames[filterField]}</b><span class="sp"></span><button type="button" class="btn btn-sm btn-ghost" onclick={() => toggleFilterOperator(filterIndex)}>{activeFilter?.operator === 'not' ? 'is not' : 'is'}{@html ic('chevron-down', 12)}</button><button type="button" class="btn btn-sm btn-ghost" onclick={() => { toolbarMenu = ''; filterField = ''; filterIndex = null; }}>Done</button></div>
      <div class="msep"></div>
      {#if activeFilter}{#each filterOptions(filterField) as option (option.value)}{@const selected = activeFilter.values.includes(option.value)}<button type="button" class="mi" role="menuitemcheckbox" aria-checked={selected} onclick={() => toggleFilterValue(filterIndex, option.value)}><span class="trunc">{option.label}</span>{#if selected}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}{/if}
    {:else}
      <div style="padding:9px 8px"><div class="eyebrow">FILTER BY</div><div class="faint" style="font-size:12px;margin-top:4px">{filters.length ? 'Combine filters to narrow the task list.' : 'No filters applied. Combine filters to narrow the task list, e.g. Status is In Progress and Assignee is Sarah.'}</div></div>
      <div class="msep"></div>
      {#each Object.entries(filterNames) as [field, name] (field)}<button type="button" class="mi" role="menuitem" onclick={() => addFilter(field)}>{@html ic(({ status: 'circle-dot', assignee: 'user', priority: 'signal-high', due: 'calendar', project: 'folder', labels: 'tag', created: 'calendar-plus', updated: 'history' })[field], 14)}<span>{name}</span></button>{/each}
    {/if}
  </div>
{:else if toolbarMenu === 'sort'}
  <div class="pop enter" data-mytasks-pop role="menu" aria-label="Sort tasks" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`}>
    <div class="eyebrow" style="padding:6px 8px">SORT BY</div>
    {#each Object.entries(sortNames) as [field, name] (field)}<button type="button" class="mi" role="menuitemradio" aria-checked={sortBy === field} onclick={() => { sortBy = field; toolbarMenu = ''; }}>{name}{#if sortBy === field}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
    <div class="msep"></div>
    <button type="button" class="mi" role="menuitemradio" aria-checked={sortDirection > 0} onclick={() => sortDirection = 1}>Ascending{#if sortDirection > 0}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
    <button type="button" class="mi" role="menuitemradio" aria-checked={sortDirection < 0} onclick={() => sortDirection = -1}>Descending{#if sortDirection < 0}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
  </div>
{/if}
