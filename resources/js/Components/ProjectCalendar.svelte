<script>
  import { ic } from '@/src/core/icons.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';

  let { tasks = [], project = null, onCreate } = $props();
  let month = $state(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
  let mode = $state('month');
  const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  function iso(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
  }

  function startOfWeek(date) {
    const start = new Date(date);
    start.setDate(start.getDate() - ((start.getDay() + 6) % 7));
    start.setHours(0, 0, 0, 0);
    return start;
  }

  function formatDate(date) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  let days = $derived.by(() => {
    const first = mode === 'month' ? new Date(month.getFullYear(), month.getMonth(), 1) : month;
    const start = startOfWeek(first);
    const count = mode === 'month' ? 42 : 7;
    const cells = Array.from({ length: count }, (_, index) => addDays(start, index));
    return mode === 'month' && cells[35].getMonth() !== month.getMonth() ? cells.slice(0, 35) : cells;
  });

  let label = $derived(mode === 'month'
    ? month.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    : `${formatDate(days[0])} – ${formatDate(days[6])}, ${days[6].getFullYear()}`);

  function tasksOn(date) {
    return tasks.filter(task => task.due_date?.slice(0, 10) === iso(date));
  }

  function move(amount) {
    if (amount === 0) {
      month = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
      return;
    }
    month = new Date(month.getFullYear(), month.getMonth() + (mode === 'month' ? amount : 0), month.getDate() + (mode === 'week' ? amount * 7 : 0));
  }
</script>

<div class="cal" style="min-height:640px">
  <div class="toolbar" style="gap:8px">
    <button class="btn btn-secondary btn-sm" onclick={() => move(0)}>Today</button>
    <div class="row" style="gap:0">
      <button class="ibtn ibtn-sm" onclick={() => move(-1)} aria-label="Previous">{@html ic('chevron-left', 16)}</button>
      <button class="ibtn ibtn-sm" onclick={() => move(1)} aria-label="Next">{@html ic('chevron-right', 16)}</button>
    </div>
    <h2 style="font-size:15px;font-weight:600;margin:0 4px;letter-spacing:-.01em" aria-live="polite">{label}</h2>
    <span class="sp"></span>
    <div class="seg">
      <button class:on={mode === 'month'} onclick={() => mode = 'month'}>Month</button>
      <button class:on={mode === 'week'} onclick={() => mode = 'week'}>Week</button>
    </div>
  </div>
  {#if mode === 'month'}
    <div class="cal-h">{#each weekdays as weekday}<div>{weekday}</div>{/each}</div>
    <div class="cal-g" style={`grid-template-rows:repeat(${days.length / 7},minmax(112px,1fr))`}>
      {#each days as date (iso(date))}
        {@const dateTasks = tasksOn(date)}
        {@const today = iso(date) === iso(new Date())}
        <div class={`cday ${date.getMonth() !== month.getMonth() ? 'out' : ''} ${today ? 'today' : ''}`}>
          <span class="dn" aria-current={today ? 'date' : undefined}>{date.getDate()}</span>
          <button class="ibtn ibtn-xs add" onclick={() => onCreate(iso(date))} aria-label={`Add task on ${formatDate(date)}`}>{@html ic('plus', 13)}</button>
          {#each dateTasks.slice(0, 3) as task (task.id)}
            <button class={`cev ${task.status === 'done' ? 'done' : ''}`} style={`--c:var(--${project?.color || task.project?.color || 'indigo'})`} title={task.title}>{@html task.status === 'done' ? stIcon('done', 11) : prIcon(task.priority, 11)}<span class="trunc">{task.title}</span></button>
          {/each}
          {#if dateTasks.length > 3}<span class="cmore">+{dateTasks.length - 3} more</span>{/if}
        </div>
      {/each}
    </div>
  {:else}
    <div class="week">
      {#each days as date (iso(date))}
        {@const dateTasks = tasksOn(date)}
        {@const today = iso(date) === iso(new Date())}
        <div class="wcol">
          <div class={`wcol-h ${today ? 'today' : ''}`}><span class="n">{date.getDate()}</span><span class="muted" style="font-size:12px">{weekdays[(date.getDay() + 6) % 7]}</span><span class="sp"></span><button class="ibtn ibtn-xs" onclick={() => onCreate(iso(date))} aria-label="Add task">{@html ic('plus', 13)}</button></div>
          <div class="wcol-b">
            {#each dateTasks as task (task.id)}
              <button class="wcard" style={`--c:var(--${project?.color || task.project?.color || 'indigo'})`}><span class="t" class:done={task.status === 'done'}><span class="pdot" style={`--c:var(--${project?.color || task.project?.color || 'indigo'})`}></span>{task.title}</span><span class="m">{@html stIcon(task.status, 11)}{@html prIcon(task.priority, 11)}<span class="trunc grow">{project?.name || task.project?.name}</span></span></button>
            {:else}
              <span class="faint" style="font-size:12px;padding:4px">No tasks</span>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
