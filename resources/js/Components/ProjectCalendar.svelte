<script>
  import Select from "./Select.svelte";
  import Dropdown from "./Dropdown.svelte";
  import Button from "./Button.svelte";
  import Input from "./Input.svelte";
  import { Link } from '@inertiajs/svelte';
  import { show as projectShow } from '../routes/projects';
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS } from '@/src/core/constants.js';
  import { addD, diffD, fmtDate, MONL, parse } from '@/src/core/utils.js';
  import { S } from '@/src/core/store.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';
  import { toast } from '@/src/ui/toast.js';

  let { tasks = [], projects = [], workspaceData = {}, project = null, showToolbar = true, legendProjectIds = [], onCreate, onTask, onReschedule } = $props();
  let date = $state(parse(S.ui.calDate) || new Date());
  let mode = $state(S.ui.calMode || 'month');
  let query = $state('');
  let filters = $state([]);
  let sort = $state('manual');
  let descending = $state(false);
  let openMenu = $state('');
  let menuPosition = $state({ left: 8, top: 8 });
  let editingFilter = $state(-1);
  let selectedEvent = $state(null);
  let selectedDay = $state(null);
  let dropDay = $state('');
  let menuTrigger = $state(null);
  const weekStart = Number(S.prefs.weekStart ?? 1);
  const weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const weekdays = Array.from({ length: 7 }, (_, index) => weekdayNames[(index + weekStart) % 7].slice(0, 3));
  const statuses = [
    { id: 'backlog', name: 'Backlog' }, { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' }, { id: 'review', name: 'Review' }, { id: 'done', name: 'Done' },
  ];
  const priorities = [
    { id: 'urgent', name: 'Urgent' }, { id: 'high', name: 'High' }, { id: 'medium', name: 'Medium' },
    { id: 'low', name: 'Low' }, { id: 'none', name: 'No priority' },
  ];
  const sortable = [
    { id: 'manual', name: 'Manual' }, { id: 'title', name: 'Title' }, { id: 'due', name: 'Due date' },
    { id: 'priority', name: 'Priority' }, { id: 'status', name: 'Status' }, { id: 'created', name: 'Created' },
    { id: 'updated', name: 'Updated' }, { id: 'assignee', name: 'Assignee' }, { id: 'project', name: 'Project' },
  ];
  const filterFields = [
    { id: 'status', name: 'Status', icon: 'circle-dot' }, { id: 'assignee', name: 'Assignee', icon: 'user' },
    { id: 'priority', name: 'Priority', icon: 'signal-high' }, { id: 'due', name: 'Due date', icon: 'calendar' },
    { id: 'project', name: 'Project', icon: 'folder' },
  ];

  function iso(value) {
    return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
  }

  function addDays(value, count) {
    const result = new Date(value);
    result.setDate(result.getDate() + count);
    return result;
  }

  function startOfWeek(value) {
    const start = new Date(value);
    start.setDate(start.getDate() - ((start.getDay() - weekStart + 7) % 7));
    start.setHours(0, 0, 0, 0);
    return start;
  }

  function formatDate(value) {
    return fmtDate(iso(value));
  }

  function formatLongDate(value) {
    return value.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  }

  let days = $derived.by(() => {
    const first = mode === 'month' ? new Date(date.getFullYear(), date.getMonth(), 1) : date;
    const start = startOfWeek(first);
    const cells = Array.from({ length: mode === 'month' ? 42 : 7 }, (_, index) => addDays(start, index));
    return mode === 'month' && cells[35].getMonth() !== date.getMonth() ? cells.slice(0, 35) : cells;
  });

  let label = $derived(mode === 'month'
    ? `${MONL[date.getMonth()]} ${date.getFullYear()}`
    : `${fmtDate(iso(days[0]))} – ${fmtDate(iso(days[6]))}, ${days[6].getFullYear()}`);

  $effect(() => {
    S.ui.calDate = iso(date);
    S.ui.calMode = mode;
  });

  function move(amount) {
    if (amount === 0) {
      date = new Date();
      return;
    }

    if (mode === 'month') date = new Date(date.getFullYear(), date.getMonth() + amount, 1);
    else date = addDays(date, amount * 7);
  }

  function sourceProject(sourceId) {
    return (workspaceData.projects || []).find(item => String(item.id) === String(sourceId));
  }

  function projectForSource(sourceId) {
    const source = sourceProject(sourceId);
    return projects.find(item => String(item.id) === String(source?.database_id)) || null;
  }

  let calendarEvents = $derived((workspaceData.events || [])
    .map(event => ({ ...event, source: sourceProject(event.project), project: projectForSource(event.project) }))
    .filter(event => event.project && (!project || String(event.project.id) === String(project.id))));

  function sourceProjectForTask(task) {
    return (workspaceData.projects || []).find(item => String(item.database_id) === String(task.project_id));
  }

  function taskIsVisible(task) {
    const source = sourceProjectForTask(task);
    if (!source) return true;
    if (['complete', 'completed', 'archived'].includes(source.status)) return false;
    if (source.private && !source.members?.includes(workspaceData.me)) return false;
    return true;
  }

  function taskOrder(task) {
    const sourceTask = (workspaceData.tasks || []).find(item => String(item.id) === String(task.source_key));
    return sourceTask?.order ?? task.order ?? 0;
  }

  let visibleTasks = $derived.by(() => {
    let result = tasks.filter(task => {
      if (!taskIsVisible(task)) return false;
      const search = query.trim().toLowerCase();
      if (search && !`${task.title} ${task.key || task.source_key || ''}`.toLowerCase().includes(search)) return false;
      return filters.every(filter => matchesFilter(task, filter));
    });

    const priorityRank = { urgent: 0, high: 1, medium: 2, low: 3, none: 4 };
    const statusRank = Object.fromEntries(statuses.map((item, index) => [item.id, index]));
    const value = task => ({
      manual: taskOrder(task),
      title: task.title?.toLowerCase() || '',
      due: task.due_date || '9999-99-99',
      priority: priorityRank[task.priority] ?? 4,
      status: statusRank[task.status] ?? 9,
      created: task.created_at || '',
      updated: task.updated_at || '',
      assignee: task.assignee?.name?.toLowerCase() || 'zzz',
      project: task.project?.name?.toLowerCase() || 'zzz',
    })[sort];
    result = [...result].sort((first, second) => {
      const left = value(first);
      const right = value(second);
      const comparison = left > right ? 1 : left < right ? -1 : 0;
      return descending ? -comparison : comparison;
    });
    return result;
  });

  let legendProjects = $derived.by(() => {
    if (legendProjectIds.length) {
      return [...new Set(legendProjectIds)]
        .map(id => projects.find(item => String(item.id) === String(id)))
        .filter(Boolean);
    }

    return (workspaceData.projects || [])
      .filter(source => visibleTasks.some(task => String(task.project_id) === String(source.database_id)))
      .map(source => projects.find(item => String(item.id) === String(source.database_id)))
      .filter(Boolean);
  });

  function matchesFilter(task, filter) {
    if (!filter.values.length) return true;
    let hit = false;
    if (filter.field === 'due') {
      const due = task.due_date?.slice(0, 10);
      const today = iso(new Date());
      hit = filter.values.some(value => {
        if (value === 'nodate') return !due;
        if (!due) return false;
        if (value === 'overdue') return due < today && task.status !== 'done';
        if (value === 'today') return due === today;
        if (value === 'week') return due >= today && due <= iso(addDays(new Date(), 7));
        return due > iso(addDays(new Date(), 7));
      });
    } else if (filter.field === 'assignee') {
      hit = filter.values.includes(String(task.assignee_id || 'none'));
    } else if (filter.field === 'project') {
      hit = filter.values.includes(String(task.project_id));
    } else {
      hit = filter.values.includes(String(task[filter.field] || 'none'));
    }
    return filter.operator === 'not' ? !hit : hit;
  }

  function filterOptions(field) {
    if (field === 'status') return statuses;
    if (field === 'priority') return priorities;
    if (field === 'project') return projects.filter(item => !item.is_private).map(item => ({ id: String(item.id), name: item.name }));
    if (field === 'assignee') {
      const members = new Map(tasks.filter(task => task.assignee).map(task => [String(task.assignee.id), task.assignee.name]));
      return [{ id: 'none', name: 'Unassigned' }, ...[...members].map(([id, name]) => ({ id, name }))];
    }
    return [
      { id: 'overdue', name: 'Overdue' }, { id: 'today', name: 'Today' },
      { id: 'week', name: 'Next 7 days' }, { id: 'later', name: 'Later' }, { id: 'nodate', name: 'No due date' },
    ];
  }

  function showMenu(menu, event) {
    menuTrigger = event.currentTarget;
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = menu === 'filter' ? 440 : 200;
    menuPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.min(bounds.bottom + 4, window.innerHeight - 340),
    };
    openMenu = openMenu === menu ? '' : menu;
  }

  function closeMenu() {
    openMenu = '';
    editingFilter = -1;
  }

  function addFilter(field) {
    filters = [...filters, { field, operator: 'is', values: [] }];
    editingFilter = filters.length - 1;
  }

  function changeFilter(index, patch) {
    filters = filters.map((filter, filterIndex) => filterIndex === index ? { ...filter, ...patch } : filter);
  }

  function toggleFilterValue(index, value) {
    const current = filters[index].values;
    changeFilter(index, { values: current.includes(String(value)) ? current.filter(item => item !== String(value)) : [...current, String(value)] });
  }

  function filterSummary(filter) {
    const field = filterFields.find(item => item.id === filter.field);
    const names = filter.values.map(value => filterOptions(filter.field).find(item => String(item.id) === value)?.name || value);
    return `${field?.name || filter.field} ${filter.operator === 'not' ? 'is not' : 'is'} ${names.length ? names.length > 2 ? `${names.length} selected` : names.join(', ') : 'any'}`;
  }

  function changeSort(value) {
    if (sort === value) descending = !descending;
    else {
      sort = value;
      descending = false;
    }
  }

  function eventFor(item) {
    return item.type === 'event' ? item.value : null;
  }

  function itemsOn(value) {
    const dateKey = iso(value);
    const events = calendarEvents.filter(event => event.date === dateKey).map(event => ({ type: 'event', value: event }));
    const dayTasks = visibleTasks
      .filter(task => task.due_date?.slice(0, 10) === dateKey)
      .sort((first, second) => taskOrder(first) - taskOrder(second))
      .map(task => ({ type: 'task', value: task }));
    return [...events, ...dayTasks];
  }

  function startTaskDrag(event, task) {
    if (!task.due_date) return;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(task.id));
  }

  function dragOverDay(event, value) {
    event.preventDefault();
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    dropDay = iso(value);
  }

  async function dropTask(event, value) {
    event.preventDefault();
    const targetDate = iso(value);
    dropDay = '';
    const taskId = event.dataTransfer?.getData('text/plain');
    const task = tasks.find(item => String(item.id) === taskId);
    const currentDueDate = String(task?.due_date || '').slice(0, 10);
    if (!task || !currentDueDate || currentDueDate === targetDate || !onReschedule) return;

    const startDate = String(task.start_date || '').slice(0, 10);
    const duration = startDate ? diffD(parse(currentDueDate), parse(startDate)) : null;
    const changes = {
      due_date: targetDate,
      ...(duration != null ? { start_date: iso(addD(parse(targetDate), -duration)) } : {}),
    };

    try {
      const saved = await onReschedule(task, changes);
      if (saved !== false) toast(`Rescheduled “${task.title}” to ${fmtDate(targetDate)}`, { ms: 2200 });
    } catch (error) {
      toast(error.message || 'Unable to reschedule task', { kind: 'err' });
    }
  }

  function memberFor(task) {
    return (workspaceData.members || []).find(member => member.name === task.assignee?.name);
  }

  function initials(name) {
    return name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '';
  }

  function taskColor(task) {
    return projectColor(task.project);
  }

  function projectColor(project) {
    return PCOLORS[project?.color] || PCOLORS.slate;
  }

  function showEvent(event, target) {
    const bounds = target.getBoundingClientRect();
    selectedEvent = event;
    selectedDay = null;
    menuPosition = { left: Math.max(8, Math.min(bounds.left, window.innerWidth - 278)), top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 220)) };
  }

  function showDay(value, target) {
    const bounds = target.getBoundingClientRect();
    selectedDay = value;
    selectedEvent = null;
    menuPosition = { left: Math.max(8, Math.min(bounds.left, window.innerWidth - 288)), top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 330)) };
  }

  function handleWindowPointerdown(event) {
    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.closest('[data-calendar-menu], [data-calendar-trigger], [data-calendar-event], [data-calendar-more]')) return;
    closeMenu();
    selectedDay = null;
    selectedEvent = null;
  }

  function handleKeydown(event) {
    if (event.key === 'Escape') {
      closeMenu();
      selectedDay = null;
      selectedEvent = null;
    }
  }
</script>

<svelte:window onpointerdown={handleWindowPointerdown} onkeydown={handleKeydown} />

{#if showToolbar}
  <div class="toolbar" role="toolbar">
    <div class="inwrap"><span class="i">{@html ic('search', 13)}</span><Input class="search-sm" bind:value={query} placeholder="Search tasks" aria-label="Search tasks" /></div>
    <Button variant="ghost" size="md" class={filters.length ? 'on' : ''} data-calendar-trigger onclick={event => showMenu('filter', event)}>{@html ic('list-filter', 14)}Filter{#if filters.length}<span class="badge accent" style="height:16px;padding:0 5px">{filters.length}</span>{/if}</Button>
    <Button variant="ghost" size="md" data-calendar-trigger onclick={event => showMenu('sort', event)}>{@html ic('arrow-up-down', 14)}<span class="hide-m">Sort:</span> {sortable.find(item => item.id === sort)?.name || 'Manual'}</Button>
    <span class="sp"></span>
  </div>
  {#if filters.length}
    <div class="chipsbar">{@html ic('list-filter', 13)}{#each filters as filter, index (index)}<span class="chip"><button onclick={() => { editingFilter = index; showMenu('filter', { currentTarget: menuTrigger || document.querySelector('[data-calendar-trigger]') }); }}>{filterSummary(filter)}</button><button class="ibtn" aria-label="Remove filter" onclick={() => filters = filters.filter((_, itemIndex) => itemIndex !== index)}>{@html ic('x', 12)}</button></span>{/each}<Button variant="ghost" size="sm" data-calendar-trigger onclick={event => showMenu('filter', event)}>{@html ic('plus', 12)}Add</Button><span class="sp"></span><Button variant="ghost" size="sm" onclick={() => filters = []}>Clear all</Button></div>
  {/if}
{/if}

<div class="cal" style="min-height:640px">
  <div class="toolbar" style="gap:8px">
    <Button variant="secondary" size="sm" onclick={() => move(0)}>Today</Button>
    <div class="row" style="gap:0"><button class="ibtn ibtn-sm" onclick={() => move(-1)} aria-label="Previous">{@html ic('chevron-left', 16)}</button><button class="ibtn ibtn-sm" onclick={() => move(1)} aria-label="Next">{@html ic('chevron-right', 16)}</button></div>
    <h2 style="font-size:15px;font-weight:600;margin:0 4px;letter-spacing:-.01em" aria-live="polite">{label}</h2>
    <span class="sp"></span>
    {#if !project}<span class="row hide-m" style="gap:10px;font-size:11.5px;color:var(--text-2);margin-right:8px">{#each legendProjects.slice(0, 5) as calendarProject (calendarProject.id)}<span class="row" style="gap:4px"><span class="pdot" style={`--c:${projectColor(calendarProject)}`}></span>{calendarProject.name}</span>{/each}</span>{/if}
    <div class="seg"><button class:on={mode === 'month'} onclick={() => mode = 'month'}>Month</button><button class:on={mode === 'week'} onclick={() => mode = 'week'}>Week</button></div>
  </div>
  {#if mode === 'month'}
    <div class="cal-h">{#each weekdays as weekday}<div>{weekday}</div>{/each}</div>
    <div class="cal-g" style={`grid-template-rows:repeat(${days.length / 7},minmax(112px,1fr))`}>
      {#each days as day (iso(day))}
        {@const items = itemsOn(day)}
        {@const today = iso(day) === iso(new Date())}
        <div class={`cday ${day.getMonth() !== date.getMonth() ? 'out' : ''} ${today ? 'today' : ''} ${dropDay === iso(day) ? 'over' : ''}`} role="group" aria-label={formatLongDate(day)} data-drop-day={iso(day)} ondragover={event => dragOverDay(event, day)} ondragleave={event => { if (!event.currentTarget.contains(event.relatedTarget)) dropDay = ''; }} ondrop={event => dropTask(event, day)}>
          <span class="dn" aria-current={today ? 'date' : undefined}>{day.getDate()}</span>
          <button class="ibtn ibtn-xs add" onclick={() => onCreate(iso(day))} aria-label={`Add task on ${formatDate(day)}`}>{@html ic('plus', 13)}</button>
          {#each items.slice(0, 3) as item, index (`${item.type}-${item.value.id}`)}
            {#if item.type === 'event'}
              {@const event = eventFor(item)}
              <button class="cev event" data-calendar-event onclick={eventClick => showEvent(event, eventClick.currentTarget)} style={`--c:${projectColor(event.project)}`} title={`${event.title} · ${event.time}`}><span class="num faint" style="font-size:11px">{event.time}</span><span class="trunc">{event.title}</span></button>
            {:else}
              {@const task = item.value}
              {@const member = memberFor(task)}
              <button class={`cev ${task.status === 'done' ? 'done' : ''}`} draggable="true" ondragstart={event => startTaskDrag(event, task)} ondragend={() => dropDay = ''} style={`--c:${taskColor(task)}`} title={task.title} onclick={() => onTask?.(task)}>{@html task.status === 'done' ? stIcon('done', 11) : prIcon(task.priority, 11)}<span class="trunc">{task.title}</span>{#if task.assignee}<span class="av" style={`--c:${member?.c || 'var(--gray)'}`}>{initials(task.assignee.name)}</span>{/if}</button>
            {/if}
          {/each}
          {#if items.length > 3}<button class="cmore" data-calendar-more onclick={event => showDay(day, event.currentTarget)}>+{items.length - 3} more</button>{/if}
        </div>
      {/each}
    </div>
  {:else}
    <div class="week">
      {#each days as day, index (iso(day))}
        {@const items = itemsOn(day)}
        {@const today = iso(day) === iso(new Date())}
        <div class="wcol">
          <div class={`wcol-h ${today ? 'today' : ''}`}><span class="n">{day.getDate()}</span><span class="muted" style="font-size:12px">{weekdays[index]}</span><span class="sp"></span><button class="ibtn ibtn-xs" onclick={() => onCreate(iso(day))} aria-label="Add task">{@html ic('plus', 13)}</button></div>
          <div class="wcol-b" class:over={dropDay === iso(day)} role="group" aria-label={`Tasks for ${formatLongDate(day)}`} data-drop-day={iso(day)} ondragover={event => dragOverDay(event, day)} ondragleave={event => { if (!event.currentTarget.contains(event.relatedTarget)) dropDay = ''; }} ondrop={event => dropTask(event, day)}>
            {#each items as item (`${item.type}-${item.value.id}`)}
              {#if item.type === 'event'}
                {@const event = eventFor(item)}
                <button class="wcard event" data-calendar-event onclick={eventClick => showEvent(event, eventClick.currentTarget)} style={`--c:${projectColor(event.project)}`}><span class="t"><span class="pdot" style={`--c:${projectColor(event.project)};border-radius:50%`}></span>{event.title}</span><span class="m">{@html ic('clock', 11)}{event.time} · {event.project.name}</span></button>
              {:else}
                {@const task = item.value}
                {@const member = memberFor(task)}
                <button class="wcard" draggable="true" ondragstart={event => startTaskDrag(event, task)} ondragend={() => dropDay = ''} style={`--c:${taskColor(task)}`} onclick={() => onTask?.(task)}><span class="t" class:done={task.status === 'done'}><span class="pdot" style={`--c:${taskColor(task)}`}></span>{task.title}</span><span class="m">{@html stIcon(task.status, 11)}{@html prIcon(task.priority, 11)}<span class="trunc grow">{task.project?.name || ''}</span>{#if task.assignee}<span class="av sm" style={`--c:${member?.c || 'var(--gray)'}`}>{initials(task.assignee.name)}</span>{/if}</span></button>
              {/if}
            {:else}
              <span class="faint" style="font-size:12px;padding:4px">No tasks</span>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

{#if openMenu === 'filter'}
  <Dropdown class="pop fbuild enter" data-calendar-menu role="dialog" aria-label="Filter by" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;max-width:560px`}>
    <div class="mh fb-h">Filter by{#if filters.length}<Button variant="ghost" size="sm" onclick={() => filters = []}>Clear all</Button>{/if}</div>
    {#if filters.length}
      <div class="fb-rows">
        {#each filters as filter, index (index)}
          <div class="frow"><span class="conj">{index ? 'and' : 'Where'}</span><Select class="select" value={filter.field} aria-label="Field" onchange={event => { changeFilter(index, { field: event.currentTarget.value, values: [] }); editingFilter = index; }}><option value="status">Status</option><option value="assignee">Assignee</option><option value="priority">Priority</option><option value="due">Due date</option><option value="project">Project</option></Select><Select class="select" value={filter.operator} aria-label="Operator" onchange={event => changeFilter(index, { operator: event.currentTarget.value })}><option value="is">is</option><option value="not">is not</option></Select><button class="valbtn" onclick={() => editingFilter = editingFilter === index ? -1 : index}><span class="trunc grow">{filter.values.length ? filterSummary(filter).split(' ').slice(2).join(' ') : 'Select…'}</span>{@html ic('chevron-down', 12)}</button><button class="ibtn ibtn-xs" aria-label="Remove filter" onclick={() => filters = filters.filter((_, filterIndex) => filterIndex !== index)}>{@html ic('trash-2', 13)}</button></div>
          {#if editingFilter === index}<div style="margin-left:50px;border:1px solid var(--border);border-radius:var(--r);padding:4px;max-height:200px;overflow:auto">{#each filterOptions(filter.field) as option (option.id)}<label class="mi" style="cursor:pointer;min-height:28px"><input type="checkbox" class="check" checked={filter.values.includes(String(option.id))} onchange={() => toggleFilterValue(index, option.id)} />{option.name}</label>{/each}</div>{/if}
        {/each}
      </div>
    {:else}
      <p class="fb-empty">No filters applied. Combine filters to narrow the task list, e.g. Status is In Progress and Assignee is Sarah.</p>
    {/if}
    <div class="msep"></div><div class="fb-add"><span class="faint">Add filter:</span>{#each filterFields as field (field.id)}<button class="badge" style="cursor:pointer" onclick={() => addFilter(field.id)}>{@html ic(field.icon, 11)}{field.name}</button>{/each}</div>
  </Dropdown>
{:else if openMenu === 'sort'}
  <Dropdown class="pop enter" data-calendar-menu role="menu" aria-label="Sort tasks" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`}>
    <div class="mh">Sort by</div>{#each sortable as option (option.id)}<button class="mi" role="menuitemradio" aria-checked={sort === option.id} onclick={() => changeSort(option.id)}>{@html ic(sort === option.id ? 'check' : 'arrow-up-down', 13)}{option.name}{#if sort === option.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
    <div class="msep"></div><button class="mi" onclick={() => descending = !descending}>{@html ic(descending ? 'arrow-down-wide-narrow' : 'arrow-up-narrow-wide', 14)}{descending ? 'Descending' : 'Ascending'}</button>
  </Dropdown>
{/if}

{#if selectedEvent}
  <Dropdown class="pop enter" data-calendar-menu role="dialog" aria-label={selectedEvent.title} style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:270px`}>
    <div style="padding:10px 10px 6px"><div class="row" style="gap:8px;margin-bottom:6px"><span class="pdot" style={`--c:${projectColor(selectedEvent.project)};border-radius:50%`}></span><b style="font-size:14px;font-weight:600">{selectedEvent.title}</b></div><div class="muted" style="font-size:12.5px;display:flex;flex-direction:column;gap:4px"><span class="row" style="gap:6px">{@html ic('calendar', 13)}{formatLongDate(new Date(`${selectedEvent.date}T00:00:00`))} · {selectedEvent.time}</span><span class="row" style="gap:6px">{@html ic('folder', 13)}{selectedEvent.project.name}</span></div></div><div class="msep"></div><Link class="mi" href={projectShow(selectedEvent.project.id).url}>{@html ic('arrow-up-right', 14)}Open project</Link>
  </Dropdown>
{:else if selectedDay}
  {@const moreItems = itemsOn(selectedDay)}
  <Dropdown class="pop enter" data-calendar-menu role="dialog" aria-label={formatLongDate(selectedDay)} style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:280px`}>
    <div class="mh">{formatLongDate(selectedDay)}</div>
    {#each moreItems as item (`${item.type}-${item.value.id}`)}
      {#if item.type === 'event'}<button class="mi" onclick={() => showEvent(item.value, menuTrigger)}>{@html ic('clock', 14)}<span class="trunc">{item.value.title}</span><span class="r">{item.value.time}</span></button>
      {:else}<button class="mi" onclick={() => { selectedDay = null; onTask?.(item.value); }}>{@html stIcon(item.value.status)}<span class="trunc">{item.value.title}</span><span class="r">{@html prIcon(item.value.priority, 13)}</span></button>{/if}
    {/each}
  </Dropdown>
{/if}
