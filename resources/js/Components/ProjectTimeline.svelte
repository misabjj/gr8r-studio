<script>
  import Select from "./Select.svelte";
  import Button from "./Button.svelte";
  import { ic } from '@/src/core/icons.js';
  import { PCOLORS } from '@/src/core/constants.js';
  import { S } from '@/src/core/store.js';
  import { toast } from '@/src/ui/toast.js';
  import { av, stIcon } from '@/src/ui/helpers.js';

  let { tasks = [], onCreate, onTask = () => {}, onUpdateTask = () => {}, initialGroup = 'status', project = null, sourceProjects = [], sourceTaskOrder = [], milestones = [] } = $props();
  let zoom = $state('week');
  let group = $state('status');
  let anchor = $state(new Date());
  let timelineScroller = $state(null);
  let draggingBar = $state(null);
  let suppressTaskOpen = false;
  const weekStart = Number(S.prefs.weekStart ?? 1);
  $effect(() => { group = initialGroup; });
  const statuses = [
    { id: 'backlog', name: 'Backlog' },
    { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' },
    { id: 'review', name: 'Review' },
    { id: 'done', name: 'Done' },
  ];
  const rowHeight = 36;

  function addDays(date, count) {
    const result = new Date(date);
    result.setDate(result.getDate() + count);
    return result;
  }

  function iso(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function startOfWeek(date) {
    const result = new Date(date);
    result.setDate(result.getDate() - ((result.getDay() - weekStart + 7) % 7));
    result.setHours(0, 0, 0, 0);
    return result;
  }

  let dayWidth = $derived(zoom === 'week' ? 32 : 11);
  let firstDate = $derived(startOfWeek(addDays(anchor, zoom === 'week' ? -21 : -56)));
  let dayCount = $derived(zoom === 'week' ? 91 : 238);
  let width = $derived(dayCount * dayWidth);
  let dayDates = $derived(Array.from({ length: dayCount }, (_, index) => addDays(firstDate, index)));
  let monthLabels = $derived.by(() => {
    const months = [];
    let monthKey = '';
    for (const [index, date] of dayDates.entries()) {
      const key = `${date.getMonth()}-${date.getFullYear()}`;
      if (key !== monthKey) {
        months.push({ date, count: 0 });
        monthKey = key;
      }
      months[months.length - 1].count++;
    }
    return months;
  });
  let datedTasks = $derived(tasks.filter(task => task.due_date));
  let sourceTaskOrderById = $derived(new Map(sourceTaskOrder.map((id, index) => [String(id), index])));
  function taskStart(task) {
    return task.start_date && task.start_date.slice(0, 10) <= task.due_date.slice(0, 10) ? task.start_date.slice(0, 10) : task.due_date.slice(0, 10);
  }

  function taskDays(task) {
    const start = new Date(`${taskStart(task)}T00:00:00`);
    const due = new Date(`${task.due_date.slice(0, 10)}T00:00:00`);
    return Math.max(1, Math.floor((due - start) / 86400000) + 1);
  }

  function taskProgress(task) {
    if (task.status === 'done') return 100;
    return { backlog: 0, todo: 5, progress: 45, review: 80 }[task.status] || 0;
  }

  function startBarDrag(event, task) {
    if (event.button !== 0) return;
    draggingBar = { taskId: task.id, startX: event.clientX, delta: 0, dayWidth };
  }

  function moveBar(event) {
    if (!draggingBar) return;
    const delta = event.clientX - draggingBar.startX;
    if (Math.abs(delta) > 4) {
      event.preventDefault();
      draggingBar = { ...draggingBar, delta: Math.round(delta / draggingBar.dayWidth) * draggingBar.dayWidth };
    }
  }

  function endBarDrag(event) {
    if (!draggingBar) return;
    const movedDays = Math.round((event.clientX - draggingBar.startX) / draggingBar.dayWidth);
    const task = tasks.find(item => item.id === draggingBar.taskId);
    draggingBar = null;
    if (!task || movedDays === 0) return;
    suppressTaskOpen = true;
    window.setTimeout(() => suppressTaskOpen = false, 80);
    const dueDate = addDays(new Date(`${task.due_date.slice(0, 10)}T00:00:00`), movedDays);
    const startDate = task.start_date ? addDays(new Date(`${task.start_date.slice(0, 10)}T00:00:00`), movedDays) : null;
    onUpdateTask(task, { due_date: iso(dueDate), start_date: startDate ? iso(startDate) : null });
    toast(`Moved “${task.title}” ${Math.abs(movedDays)} day${Math.abs(movedDays) > 1 ? 's' : ''} ${movedDays > 0 ? 'later' : 'earlier'}`, { ms: 2200 });
  }

  function openTimelineTask(task) {
    if (suppressTaskOpen) return;
    onTask(task);
  }

  let groups = $derived.by(() => {
    if (group === 'none') return [{ id: 'all', name: '', tasks: [...datedTasks] }];
    if (group === 'project') {
      const orderedProjects = sourceProjects.length
        ? sourceProjects.map(sourceProject => ({
          id: String(sourceProject.database_id),
          name: sourceProject.name,
          color: sourceProject.color,
          tasks: datedTasks.filter(task => String(task.project_id) === String(sourceProject.database_id)),
        }))
        : [...new Map(datedTasks.map(task => [String(task.project_id), task.project])).entries()]
          .map(([id, taskProject]) => ({ id, name: taskProject?.name || '', color: taskProject?.color, tasks: datedTasks.filter(task => String(task.project_id) === id) }));
      return orderedProjects.filter(taskGroup => taskGroup.tasks.length);
    }
    if (group === 'assignee') {
      const assignees = new Map(datedTasks.map(task => [String(task.assignee_id || 'unassigned'), task.assignee]));
      return [...assignees.entries()].map(([id, assignee]) => ({ id, name: assignee?.name || 'Unassigned', tasks: datedTasks.filter(task => String(task.assignee_id || 'unassigned') === id) }));
    }
    return statuses.map(status => ({ ...status, tasks: datedTasks.filter(task => task.status === status.id) })).filter(status => status.tasks.length);
  });
  let sortedGroups = $derived(groups.map(taskGroup => ({
    ...taskGroup,
    tasks: [...taskGroup.tasks].sort((first, second) => taskStart(first).localeCompare(taskStart(second))
      || (sourceTaskOrderById.get(String(first.id)) ?? Number.MAX_SAFE_INTEGER)
        - (sourceTaskOrderById.get(String(second.id)) ?? Number.MAX_SAFE_INTEGER)),
  })));
  let timelineRowByTask = $derived.by(() => {
    const rows = new Map();
    let row = timelineMilestones.length ? 1 : 0;
    for (const taskGroup of sortedGroups) {
      if (group !== 'none') row++;
      for (const task of taskGroup.tasks) {
        rows.set(String(task.source_key || task.id), row * rowHeight + rowHeight / 2);
        row++;
      }
    }
    return rows;
  });
  let dependencyPaths = $derived.by(() => {
    const paths = [];
    const x = date => Math.floor((new Date(`${date.slice(0, 10)}T00:00:00`).getTime() - firstDate.getTime()) / 86400000) * dayWidth;
    for (const task of datedTasks) {
      const taskY = timelineRowByTask.get(String(task.source_key || task.id));
      if (taskY == null) continue;
      for (const dependencyId of task.dependencies || []) {
        const dependency = tasks.find(item => String(item.source_key || item.id) === String(dependencyId) || item.key === dependencyId);
        if (!dependency?.due_date) continue;
        const dependencyY = timelineRowByTask.get(String(dependency.source_key || dependency.id));
        if (dependencyY == null) continue;
        const x1 = x(dependency.due_date) + dayWidth;
        const x2 = x(taskStart(task));
        const middle = Math.max(x1 + 10, x2 - 10);
        paths.push({
          id: `${dependency.id}:${task.id}`,
          d: `M${x1} ${dependencyY} C ${middle} ${dependencyY}, ${Math.min(x1 + 10, x2 - 10)} ${taskY}, ${x2 - 1} ${taskY}`,
          arrow: `M${x2 - 6} ${taskY - 3.5} L${x2 - 1} ${taskY} L${x2 - 6} ${taskY + 3.5}`,
          dashed: x2 < x1,
        });
      }
    }
    return paths;
  });
  let timelineMilestones = $derived.by(() => {
    if (project) return milestones.map(milestone => ({ ...milestone, projectName: project.name, color: project.color }));

    return sourceProjects.flatMap(sourceProject => (sourceProject.milestones || []).map(milestone => ({
      ...milestone,
      projectName: sourceProject.name,
      color: sourceProject.color,
    })));
  });
  let milestoneDates = $derived.by(() => {
    const byDate = new Map();
    for (const milestone of timelineMilestones) {
      const sameDate = byDate.get(milestone.date) || [];
      sameDate.push(milestone);
      byDate.set(milestone.date, sameDate);
    }
    return [...byDate.entries()].sort(([first], [second]) => first.localeCompare(second)).map(([date, list]) => ({ date, list }));
  });
  let todayOffset = $derived(Math.floor((new Date().setHours(0, 0, 0, 0) - firstDate.getTime()) / 86400000) * dayWidth);
  $effect(() => {
    if (timelineScroller) timelineScroller.scrollLeft = Math.max(0, todayOffset - 160);
  });
</script>

<svelte:window onpointermove={moveBar} onpointerup={endBarDrag} />

<div class="toolbar">
  <div class="seg" style="margin-left:4px"><button class:on={zoom === 'week'} onclick={() => zoom = 'week'}>Weeks</button><button class:on={zoom === 'month'} onclick={() => zoom = 'month'}>Months</button></div>
  <Select class="select" style="height:26px;width:auto;font-size:12px;margin-left:4px" bind:value={group} aria-label="Group by"><option value="status">Group: Status</option><option value="assignee">Group: Assignee</option><option value="project">Group: Project</option><option value="none">No grouping</option></Select>
  <Button variant="ghost" size="md" onclick={() => anchor = new Date()}>{@html ic('crosshair', 14)}Today</Button>
</div>
{#if !datedTasks.length}
  <div class="page"><section class="panel"><div class="empty"><span class="empty-ic">{@html ic('chart-gantt', 20)}</span><h2>Nothing on the timeline</h2><p>Give tasks a start and due date to see them here.</p><Button variant="primary" size="sm" onclick={() => onCreate()}>{@html ic('plus', 14)}New task</Button></div></section></div>
{:else}
  <div class="tl">
    <div class="tl-left"><div class="tl-head">Task</div><div class="tl-rows">
      {#if timelineMilestones.length}<div class="tl-row g">{@html ic('diamond', 12)}Milestones<span class="faint" style="font-weight:500">{timelineMilestones.length}</span></div>{/if}
      {#each sortedGroups as taskGroup (taskGroup.id)}
        {#if group !== 'none'}<div class="tl-row g">{#if group === 'project'}<span class="pdot" style={`--c:var(--${taskGroup.color || 'indigo'})`}></span>{:else}{@html stIcon(taskGroup.id, 12)}{/if}{taskGroup.name}<span class="faint" style="font-weight:500">{taskGroup.tasks.length}</span></div>{/if}
        {#each taskGroup.tasks as task (task.id)}<!-- svelte-ignore a11y_no_noninteractive_element_to_interactive_role --><div class="tl-row" role="button" tabindex="0" aria-label={`Open task ${task.title}`} onclick={() => onTask(task)} onkeydown={event => event.key === 'Enter' && onTask(task)}>{@html stIcon(task.status, 13)}<span class="trunc grow" style={task.status === 'done' ? 'color:var(--text-3)' : ''}>{task.title}</span>{#if task.assignee?.name}<span class="av sm" style={`--c:${task.assignee.color || '#8A867E'}`} aria-label={task.assignee.name}>{task.assignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{/if}</div>{/each}
      {/each}
      <div style="height:40px"></div>
    </div></div>
    <div class="tl-right" bind:this={timelineScroller}>
      <div class="tl-head" style={`width:${width}px`}><div class="tl-months">{#each monthLabels as month (iso(month.date))}<div style={`width:${month.count * dayWidth}px`}><span>{month.date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</span></div>{/each}</div><div class="tl-days">{#each dayDates as date (iso(date))}<div style={`width:${dayWidth}px`} class:today={iso(date) === iso(new Date())}>{#if zoom === 'week'}{date.getDate()}{:else if date.getDay() === 1}{date.toLocaleDateString('en-US', { month: 'short' })} {date.getDate()}{/if}</div>{/each}</div></div>
      <div class="tl-grid" style={`width:${width}px;height:${(datedTasks.length + (group === 'none' ? 0 : sortedGroups.length) + (timelineMilestones.length ? 1 : 0)) * 36 + 40}px`}>
        {#each dayDates as date, index (iso(date))}{#if zoom === 'week' && (date.getDay() === 0 || date.getDay() === 6)}<div class="tl-we" style={`left:${index * dayWidth}px;width:${dayWidth}px`}></div>{/if}{#if date.getDay() === 1}<div class="tl-gl" style={`left:${index * dayWidth}px`}></div>{/if}{/each}
        <div class="tl-today" style={`left:${todayOffset + dayWidth / 2}px`} title="Today"></div>
        {#if timelineMilestones.length}
          <div class="tl-rowbg g" style="top:0"></div>
          {#each milestoneDates as milestoneDate (milestoneDate.date)}
            {@const x = Math.floor((new Date(`${milestoneDate.date}T00:00:00`).getTime() - firstDate.getTime()) / 86400000) * dayWidth + dayWidth / 2}
            {@const nextMilestoneDate = milestoneDates[milestoneDates.indexOf(milestoneDate) + 1]}
            {@const room = nextMilestoneDate ? Math.floor((new Date(`${nextMilestoneDate.date}T00:00:00`).getTime() - new Date(`${milestoneDate.date}T00:00:00`).getTime()) / 86400000) * dayWidth - 24 : Number.POSITIVE_INFINITY}
            {@const firstMilestone = milestoneDate.list[0]}
            {@const label = `${firstMilestone.name}${milestoneDate.list.length > 1 ? ` +${milestoneDate.list.length - 1}` : ''}`}
            {@const tooltip = `${milestoneDate.list.map(milestone => `${milestone.name}${milestone.projectName ? ` (${milestone.projectName})` : ''}`).join(' · ')} · ${milestoneDate.date}`}
            <div class="ms-w" style={`left:${x - 6}px;top:9px`} title={tooltip} aria-label={`Milestone: ${tooltip}`}><span class="ms" style={`background:${PCOLORS[firstMilestone.color] || 'var(--text)'}`}></span>{#if room >= 28}<span class="ms-l" style={`max-width:${Number.isFinite(room) ? `${room}px` : 'none'}`}>{label}</span>{/if}</div>
          {/each}
        {/if}
        {#each sortedGroups as taskGroup, groupIndex (taskGroup.id)}
          {@const startRow = (timelineMilestones.length ? 1 : 0) + (groupIndex === 0 ? 0 : sortedGroups.slice(0, groupIndex).reduce((count, previous) => count + previous.tasks.length + (group === 'none' ? 0 : 1), 0))}
          {#if group !== 'none'}<div class="tl-rowbg g" style={`top:${startRow * rowHeight}px`}></div>{/if}
          {#each taskGroup.tasks as task, taskIndex (task.id)}
            {@const row = startRow + (group === 'none' ? taskIndex : taskIndex + 1)}
            {@const offset = Math.floor((new Date(`${taskStart(task)}T00:00:00`).getTime() - firstDate.getTime()) / 86400000)}
            {@const span = taskDays(task) * dayWidth}
            {@const barColor = group === 'status' || group === 'none' ? `var(--${project?.color || task.project?.color || 'indigo'})` : `var(--st-${task.status})`}
            <div class="tl-rowbg" style={`top:${row * rowHeight}px`}></div>
            <!-- svelte-ignore a11y_no_noninteractive_element_to_interactive_role --><div class={`bar ${task.status === 'done' ? 'done' : ''}`} class:dragging={draggingBar?.taskId === task.id && draggingBar.delta !== 0} role="button" tabindex="0" aria-label={`Open task ${task.title}`} style={`--c:${barColor};left:${offset * dayWidth + (draggingBar?.taskId === task.id ? draggingBar.delta : 0)}px;top:${row * rowHeight + 7}px;width:${span}px`} title={`${task.title} · ${taskStart(task)} → ${task.due_date.slice(0, 10)}`} onpointerdown={event => startBarDrag(event, task)} onclick={() => openTimelineTask(task)} onkeydown={event => event.key === 'Enter' && openTimelineTask(task)}><span class="pf" style={`width:${taskProgress(task)}%`}></span>{#if span > 110}{#if task.assignee?.name}<span class="av sm" style={`--c:${task.assignee.color || '#8A867E'}`} aria-label={task.assignee.name}>{task.assignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{/if}<span class="trunc">{task.title}</span>{/if}</div>
            {#if span <= 110}<div class="bar-lbl" style={`left:${offset * dayWidth + span + 8}px;top:${row * rowHeight + 10}px`}>{task.title}</div>{/if}
          {/each}
        {/each}
        <svg class="tl-deps" width={width} height={(datedTasks.length + (group === 'none' ? 0 : sortedGroups.length) + (timelineMilestones.length ? 1 : 0)) * rowHeight} viewBox={`0 0 ${width} ${(datedTasks.length + (group === 'none' ? 0 : sortedGroups.length) + (timelineMilestones.length ? 1 : 0)) * rowHeight}`} aria-hidden="true">
          {#each dependencyPaths as path (path.id)}<path d={path.d} fill="none" stroke="var(--text-3)" stroke-width="1.3" stroke-dasharray={path.dashed ? '3 3' : undefined} /><path d={path.arrow} fill="none" stroke="var(--text-3)" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />{/each}
        </svg>
      </div>
    </div>
  </div>
{/if}
