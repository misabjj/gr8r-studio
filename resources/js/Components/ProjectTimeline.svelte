<script>
  import { ic } from '@/src/core/icons.js';
  import { stIcon } from '@/src/ui/helpers.js';

  let { tasks = [], onCreate } = $props();
  let zoom = $state('week');
  let group = $state('status');
  let anchor = $state(new Date());
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
    result.setDate(result.getDate() - ((result.getDay() + 6) % 7));
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
  let groups = $derived(group === 'none' ? [{ id: 'all', name: '', tasks: datedTasks }] : statuses.map(status => ({ ...status, tasks: datedTasks.filter(task => task.status === status.id) })).filter(status => status.tasks.length));
  let todayOffset = $derived(Math.floor((new Date().setHours(0, 0, 0, 0) - firstDate.getTime()) / 86400000) * dayWidth);
</script>

<div class="toolbar">
  <div class="seg" style="margin-left:4px"><button class:on={zoom === 'week'} onclick={() => zoom = 'week'}>Weeks</button><button class:on={zoom === 'month'} onclick={() => zoom = 'month'}>Months</button></div>
  <select class="select" style="height:26px;width:auto;font-size:12px;margin-left:4px" bind:value={group} aria-label="Group by"><option value="status">Group: Status</option><option value="project">Group: Project</option><option value="none">No grouping</option></select>
  <button class="btn btn-ghost" onclick={() => anchor = new Date()}>{@html ic('crosshair', 14)}Today</button>
</div>
{#if !datedTasks.length}
  <div class="page"><section class="panel"><div class="empty"><span class="empty-ic">{@html ic('chart-gantt', 20)}</span><h2>Nothing on the timeline</h2><p>Give tasks a start and due date to see them here.</p><button class="btn btn-primary btn-sm" onclick={() => onCreate()}>{@html ic('plus', 14)}New task</button></div></section></div>
{:else}
  <div class="tl">
    <div class="tl-left"><div class="tl-head">Task</div><div class="tl-rows">
      {#each groups as taskGroup (taskGroup.id)}
        {#if group !== 'none'}<div class="tl-row g">{@html ic('circle-dot', 12)}{taskGroup.name}<span class="faint" style="font-weight:500">{taskGroup.tasks.length}</span></div>{/if}
        {#each taskGroup.tasks as task (task.id)}<div class="tl-row">{@html stIcon(task.status, 13)}<span class="trunc grow" style={task.status === 'done' ? 'color:var(--text-3)' : ''}>{task.title}</span></div>{/each}
      {/each}
      <div style="height:40px"></div>
    </div></div>
    <div class="tl-right">
      <div class="tl-head" style={`width:${width}px`}><div class="tl-months">{#each monthLabels as month (iso(month.date))}<div style={`width:${month.count * dayWidth}px`}><span>{month.date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</span></div>{/each}</div><div class="tl-days">{#each dayDates as date (iso(date))}<div style={`width:${dayWidth}px`} class:today={iso(date) === iso(new Date())}>{#if zoom === 'week'}{date.getDate()}{:else if date.getDay() === 1}{date.toLocaleDateString('en-US', { month: 'short' })} {date.getDate()}{/if}</div>{/each}</div></div>
      <div class="tl-grid" style={`width:${width}px;height:${(datedTasks.length + (group === 'none' ? 0 : groups.length)) * 36 + 40}px`}>
        {#each dayDates as date, index (iso(date))}{#if zoom === 'week' && (date.getDay() === 0 || date.getDay() === 6)}<div class="tl-we" style={`left:${index * dayWidth}px;width:${dayWidth}px`}></div>{/if}{#if date.getDay() === 1}<div class="tl-gl" style={`left:${index * dayWidth}px`}></div>{/if}{/each}
        <div class="tl-today" style={`left:${todayOffset + dayWidth / 2}px`} title="Today"></div>
        {#each groups as taskGroup, groupIndex (taskGroup.id)}
          {@const startRow = groupIndex === 0 ? 0 : groups.slice(0, groupIndex).reduce((count, previous) => count + previous.tasks.length + (group === 'none' ? 0 : 1), 0)}
          {#if group !== 'none'}<div class="tl-rowbg g" style={`top:${startRow * rowHeight}px`}></div>{/if}
          {#each taskGroup.tasks as task, taskIndex (task.id)}
            {@const row = startRow + (group === 'none' ? taskIndex : taskIndex + 1)}
            {@const taskDate = new Date(`${task.due_date}T00:00:00`)}
            {@const offset = Math.floor((taskDate.getTime() - firstDate.getTime()) / 86400000)}
            <div class="tl-rowbg" style={`top:${row * rowHeight}px`}></div>
            <div class={`bar ${task.status === 'done' ? 'done' : ''}`} style={`--c:var(--st-${task.status});left:${offset * dayWidth}px;top:${row * rowHeight + 7}px;width:${dayWidth}px`} title={task.title}></div>
            <div class="bar-lbl" style={`left:${offset * dayWidth + dayWidth + 8}px;top:${row * rowHeight + 10}px`}>{task.title}</div>
          {/each}
        {/each}
      </div>
    </div>
  </div>
{/if}
