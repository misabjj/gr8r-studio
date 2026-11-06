<script>
  import Button from "../../Components/Button.svelte";
  import { tick } from 'svelte';
  import { router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { read as notificationReadRoute, readAll as notificationReadAllRoute } from '../../routes/notifications';
  import { show as projectShow } from '../../routes/projects';
  import { update as taskUpdateRoute } from '../../routes/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { S } from '@/src/core/store.js';
  import { PCOLORS, PRIOS, STATUSES, ST } from '@/src/core/constants.js';
  import { addD, ago, diffD, fmtDate, iso, MOD, MONL, relDate, TODAY, uid, WD } from '@/src/core/utils.js';
  import { save as saveWorkspaceStore } from '@/src/core/store.js';
  import { ic } from '@/src/core/icons.js';
  import { empty, prIcon, prPill, stIcon, stPill } from '@/src/ui/helpers.js';
  import { fmtComment } from '@/src/ui/helpers.js';
  import { toast } from '@/src/ui/toast.js';

  const httpRequest = useHttpRequest();
  let { workspaceData = {}, tasks: initialTasks = [] } = $props();
  let category = $state(S.ui.inboxCat || 'all');
  let unreadOnly = $state(S.ui.inboxUnread || false);
  let selectedId = $state(S.ui.inboxSel || '');
  let notifications = $state([]);
  let comments = $state([]);
  let databaseTasks = $state([]);
  let drawerTaskId = $state(null);
  let reply = $state('');
  let propertyMenu = $state('');
  let propertyMenuPosition = $state({ left: 0, top: 0 });
  let propertyMenuTrigger = $state(null);
  let assigneeQuery = $state('');
  let calendarMonth = $state(new Date(TODAY.getFullYear(), TODAY.getMonth(), 1));

  $effect(() => {
    notifications = (workspaceData.notifs || []).map(notification => ({ ...notification }));
    const sourceComments = (workspaceData.comments || []).map(comment => ({ ...comment }));
    const sourceCommentIds = new Set(sourceComments.map(comment => comment.id));
    const localComments = (S.data.comments || []).filter(comment => !sourceCommentIds.has(comment.id));
    comments = [...sourceComments, ...localComments];
  });
  $effect(() => { databaseTasks = [...initialTasks]; });

  const categories = [['all', 'All'], ['mention', 'Mentions'], ['assign', 'Assignments'], ['comment', 'Comments'], ['update', 'Updates']];
  const members = $derived(workspaceData.members || []);
  const memberById = $derived(new Map(members.map(member => [member.id, member])));
  const projects = $derived(workspaceData.projects || []);
  const projectById = $derived(new Map(projects.map(project => [project.id, project])));
  let workspaceTasks = $state([]);
  $effect(() => { workspaceTasks = (workspaceData.tasks || []).map(task => ({ ...task })); });
  const taskById = $derived(new Map(workspaceTasks.map(task => [task.id, task])));
  const drawerTaskBySourceKey = $derived(new Map(databaseTasks.map(task => [task.source_key, task])));
  let drawerTask = $derived(databaseTasks.find(task => task.id === drawerTaskId) || null);
  let unreadCounts = $derived(Object.fromEntries(categories.map(([key]) => [key, notifications.filter(notification => !notification.read && (key === 'all' || notification.type === key)).length])));
  let visibleNotifications = $derived(notifications
    .filter(notification => category === 'all' || notification.type === category)
    .filter(notification => !unreadOnly || !notification.read)
    .sort((first, second) => second.at - first.at));
  let selectedNotification = $derived(notifications.find(notification => notification.id === selectedId) || null);
  let selectedTask = $derived(selectedNotification ? taskById.get(selectedNotification.task) || null : null);
  let selectedProject = $derived(selectedNotification ? projectById.get(selectedNotification.project || selectedTask?.project) || null : null);
  let assignableMembers = $derived(members.filter(member => member.status !== 'deactivated' && member.database_id && member.name.toLowerCase().includes(assigneeQuery.trim().toLowerCase())));
  let calendarDays = $derived.by(() => {
    const first = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), 1);
    const weekStart = Number(S.prefs.weekStart ?? 1);
    const offset = (first.getDay() - weekStart + 7) % 7;
    return Array.from({ length: 42 }, (_, index) => addD(first, index - offset));
  });
  let calendarWeekdays = $derived.by(() => {
    const weekStart = Number(S.prefs.weekStart ?? 1);
    return Array.from({ length: 7 }, (_, index) => WD[(index + weekStart) % 7].slice(0, 2));
  });

  function selectCategory(value) {
    category = value;
    S.ui.inboxCat = value;
  }

  function setUnreadOnly(value) {
    unreadOnly = value;
    S.ui.inboxUnread = value;
  }

  function notificationText(notification) {
    const task = taskById.get(notification.task);
    const project = projectById.get(notification.project || task?.project);
    const member = memberById.get(notification.by);

    if (!member && project && !task) return { strong: notification.text, text: '' };
    if (!member && task) return { strong: task.title, text: notification.text };
    return { strong: member?.name || '', text: notification.text, task: task?.title || '' };
  }

  function notificationProject(notification) {
    const task = taskById.get(notification.task);
    return projectById.get(notification.project || task?.project);
  }

  function notificationIcon(notification) {
    const member = memberById.get(notification.by);
    if (member) {
      return { initials: member.name.split(' ').map(part => part[0]).join('').slice(0, 2), color: member.c, icon: '' };
    }

    const hasProjectReference = Boolean(notification.project);
    return {
      initials: '',
      color: hasProjectReference ? 'var(--red)' : 'var(--amber)',
      icon: notification.type === 'update' && hasProjectReference ? 'triangle-alert' : 'clock',
    };
  }

  function categoryIcon(type) {
    return ({ mention: 'at-sign', assign: 'user-plus', comment: 'message-square', update: 'refresh-cw' })[type] || 'bell';
  }

  function projectColor(project) {
    return PCOLORS[project?.color] || PCOLORS.slate;
  }

  async function setRead(notification, read) {
    const previousRead = notification.read;
    notifications = notifications.map(item => item.id === notification.id ? { ...item, read } : item);

    try {
      await httpRequest(notificationReadRoute(notification.id).url, 'PATCH', { read });
    } catch (error) {
      notifications = notifications.map(item => item.id === notification.id ? { ...item, read: previousRead } : item);
      toast(error.message || 'Could not update notification.', { kind: 'err' });
    }
  }

  function selectNotification(notification) {
    propertyMenu = '';
    selectedId = notification.id;
    S.ui.inboxSel = notification.id;
    if (!notification.read) void setRead(notification, true);
    reply = '';
  }

  function toggleRead(event, notification) {
    event.stopPropagation();
    void setRead(notification, !notification.read);
  }

  async function markAllRead() {
    const unreadIds = notifications.filter(notification => !notification.read).map(notification => notification.id);
    if (!unreadIds.length) return;

    notifications = notifications.map(notification => ({ ...notification, read: true }));

    try {
      await httpRequest(notificationReadAllRoute().url, 'PATCH', {});
    } catch (error) {
      const unreadIdSet = new Set(unreadIds);
      notifications = notifications.map(notification => unreadIdSet.has(notification.id) ? { ...notification, read: false } : notification);
      toast(error.message || 'Could not mark notifications as read.', { kind: 'err' });
    }
  }

  function backToList() {
    propertyMenu = '';
    selectedId = '';
    S.ui.inboxSel = null;
  }

  function openProject() {
    if (selectedProject?.database_id) router.visit(projectShow(selectedProject.database_id).url);
  }

  function openTask() {
    const task = drawerTaskBySourceKey.get(selectedTask?.id);
    if (task) drawerTaskId = task.id;
  }

  function updateTask(updatedTask) {
    databaseTasks = databaseTasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }

  async function showPropertyMenu(menu, event) {
    propertyMenuTrigger = event.currentTarget;
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = menu === 'due' ? 312 : 200;
    const left = Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8));
    propertyMenu = propertyMenu === menu ? '' : menu;
    if (!propertyMenu) return;

    if (menu === 'assignee') assigneeQuery = '';
    if (menu === 'due') {
      const date = selectedTask?.due ? new Date(`${selectedTask.due}T00:00:00`) : TODAY;
      calendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    }

    propertyMenuPosition = { left, top: Math.max(8, bounds.bottom + 4) };
    await tick();
    const popup = document.querySelector('[data-inbox-property-popup]');
    if (!popup) return;

    const focusTarget = popup.querySelector('input')
      || popup.querySelector('[aria-checked="true"]')
      || popup.querySelector('[aria-pressed="true"]')
      || popup.querySelector('button');
    focusTarget?.focus({ preventScroll: true });
    const height = popup.getBoundingClientRect().height;
    const top = bounds.bottom + height > window.innerHeight - 8
      ? Math.max(8, bounds.top - height - 4)
      : bounds.bottom + 4;
    propertyMenuPosition = { left, top };
  }

  function closePropertyMenu() {
    const trigger = propertyMenuTrigger;
    propertyMenu = '';
    tick().then(() => trigger?.focus({ preventScroll: true }));
  }

  function handleWindowPointerdown(event) {
    const { target } = event;
    if (!propertyMenu || !(target instanceof Element) || target.closest('[data-inbox-property-popup], [data-inbox-property-trigger]')) return;
    propertyMenu = '';
  }

  function handlePropertyKeydown(event) {
    if (!propertyMenu) return;
    if (event.key === 'Escape') {
      closePropertyMenu();
      return;
    }

    if (propertyMenu === 'status' && /^[1-5]$/.test(event.key)) {
      void saveTaskProperty('status', STATUSES[Number(event.key) - 1].id);
    }
  }

  async function saveTaskProperty(field, value) {
    const task = drawerTaskBySourceKey.get(selectedTask?.id);
    if (!task) return;
    const previousTask = task;

    try {
      const updated = await httpRequest(taskUpdateRoute(task.id).url, 'PATCH', { [field]: value });
      updateTask({ ...previousTask, ...updated, project: updated.project || previousTask.project, assignee: updated.assignee || previousTask.assignee });
      let snapshotField = field;
      let snapshotValue = value;
      if (field === 'assignee_id') {
        snapshotField = 'assignee';
        snapshotValue = members.find(member => String(member.database_id) === String(value))?.id || '';
      } else if (field === 'due_date') {
        snapshotField = 'due';
        snapshotValue = value || null;
      }
      workspaceTasks = workspaceTasks.map(item => item.id === selectedTask?.id ? { ...item, [snapshotField]: snapshotValue } : item);
      closePropertyMenu();
    } catch (error) {
      toast(error.message || 'Could not update task.', { kind: 'err' });
    }
  }

  function shiftCalendarMonth(amount) {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + amount, 1);
  }

  function postReply(event) {
    event.preventDefault();
    const text = reply.trim();
    if (!text || !selectedTask) return;

    const at = Date.now();
    const comment = {
      id: uid('c'),
      task: selectedTask.id,
      by: workspaceData.me,
      at,
      text,
      re: {},
    };
    comments = [...comments, comment];
    S.data.comments = [...(S.data.comments || []), comment];
    const localTask = S.data.tasks.find(task => task.id === selectedTask.id);
    if (localTask) localTask.updated = at;
    S.data.activity.unshift({ id: uid('a'), by: workspaceData.me, verb: 'commented on', task: selectedTask.id, project: selectedTask.project, at, extra: '' });
    saveWorkspaceStore();
    reply = '';
    toast('Reply posted');
  }

  function deleteComment(comment) {
    if (comment.by !== workspaceData.me) return;
    comments = comments.filter(item => item.id !== comment.id);
    S.data.comments = (S.data.comments || []).filter(item => item.id !== comment.id);
    saveWorkspaceStore();
    toast('Comment deleted');
  }

  function handleReplyKeydown(event) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
      postReply(event);
    }
  }
</script>

<svelte:window onkeydown={handlePropertyKeydown} onpointerdown={handleWindowPointerdown} />

<svelte:head><title>Inbox · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class:has-selection={selectedNotification} class="inbox-grid" style="display:grid;grid-template-columns:minmax(0,420px) minmax(0,1fr);flex:1;min-height:0">
      <section style="border-right:1px solid var(--border);display:flex;flex-direction:column;min-height:0">
        <div class="row" style="padding:18px var(--gutter) 0">
          <h1 style="font-size:var(--fs-xl);margin:0;font-weight:600;letter-spacing:-.015em">Inbox</h1><span class="sp"></span>
          <label class="row" style="font-size:12px;color:var(--text-2);gap:6px;cursor:pointer"><input class="toggle" type="checkbox" checked={unreadOnly} onchange={event => setUnreadOnly(event.currentTarget.checked)} />Unread</label>
          <button class="ibtn ibtn-sm" aria-label="Mark all as read" onclick={markAllRead} disabled={!notifications.some(notification => !notification.read)}>{@html ic('check-check', 15)}</button>
        </div>
        <div class="tabs inbox-tabs" role="tablist">{#each categories as [key, label]}<button role="tab" class="tab" class:on={category === key} aria-selected={category === key} onclick={() => selectCategory(key)}>{label}{#if unreadCounts[key]}<span class="cnt">{unreadCounts[key]}</span>{/if}</button>{/each}</div>
        <div class="inbox-list">
          {#if visibleNotifications.length}
            {#each visibleNotifications as notification (notification.id)}
              {@const member = memberById.get(notification.by)}
              {@const project = notificationProject(notification)}
              {@const copy = notificationText(notification)}
                {@const icon = notificationIcon(notification)}
              <div class="row" class:inbox-selected={selectedId === notification.id} style="align-items:flex-start;gap:10px;padding:12px var(--gutter);border-bottom:1px solid var(--divider);cursor:pointer;position:relative" role="button" tabindex="0" onclick={() => selectNotification(notification)} onkeydown={event => event.key === 'Enter' && selectNotification(notification)}>
                {#if !notification.read}<span style="position:absolute;left:6px;top:22px;width:6px;height:6px;border-radius:50%;background:var(--accent)" aria-label="Unread"></span>{/if}
                <span class="av md" style={`--c:${icon.color}`} aria-label={member?.name || undefined}>{#if icon.icon}{@html ic(icon.icon, 13)}{:else}{icon.initials}{/if}</span>
                <div class="grow" style="font-size:13px;line-height:1.45">
                  <div><b style="font-weight:600">{copy.strong}</b>{#if copy.text}{' '}<span class="muted">{copy.text}</span>{/if}{#if copy.task}{' '}<b style="font-weight:500">{copy.task}</b>{/if}</div>
                  <div class="trunc muted" style="font-size:12.5px;margin-top:2px">{@html fmtComment(notification.snippet)}</div>
                  <div class="row" style="gap:6px;margin-top:5px;font-size:11.5px;color:var(--text-3)">{@html ic(categoryIcon(notification.type), 11)}{#if project}<span class="pdot" style={`--c:${projectColor(project)};width:6px;height:6px`}></span>{project.name}{/if}<span>·</span><span>{ago(notification.at)}</span></div>
                </div>
                <button class="ibtn ibtn-xs" onclick={event => toggleRead(event, notification)} aria-label={notification.read ? 'Mark as unread' : 'Mark as read'}>{@html ic(notification.read ? 'mail' : 'mail-open', 13)}</button>
              </div>
            {/each}
          {:else}
            {@html empty('inbox', "You're all caught up.", unreadOnly ? 'No unread notifications in this category.' : 'New mentions, assignments, and comments will show up here.', '', 'sm')}
          {/if}
        </div>
      </section>
      <section class="inbox-preview" style="display:flex;flex-direction:column;min-height:0;overflow-y:auto">
        {#if selectedNotification}
          {#if selectedTask}
            <div class="row" style="height:46px;padding:0 16px;border-bottom:1px solid var(--border);gap:8px;flex-shrink:0">
              <button class="ibtn ibtn-sm" onclick={backToList} aria-label="Back">{@html ic('arrow-left', 15)}</button>
              <span class="pdot" style={`--c:${projectColor(selectedProject)}`}></span><span class="muted" style="font-size:12.5px">{selectedProject?.name}</span><span class="faint mono">{selectedTask.key}</span><span class="sp"></span>
              <Button variant="secondary" size="sm" onclick={openTask}>{@html ic('panel-right-open', 14)}Open task</Button>
            </div>
            <div style="padding:24px 28px;max-width:720px;width:100%">
              <h2 style="font-size:var(--fs-xl);margin:0 0 10px;font-weight:600;letter-spacing:-.015em">{selectedTask.title}</h2>
              <div class="row" style="flex-wrap:wrap;gap:4px;margin-bottom:18px">
                <button type="button" class="pillbtn" data-inbox-property-trigger aria-label={`Status: ${ST[selectedTask.status]?.name || selectedTask.status}`} onclick={event => showPropertyMenu('status', event)}>{@html stPill(selectedTask.status)}</button>
                <button type="button" class="pillbtn" data-inbox-property-trigger aria-label="Assignee" onclick={event => showPropertyMenu('assignee', event)}>{#if memberById.get(selectedTask.assignee)}{@const assignee = memberById.get(selectedTask.assignee)}<span class="av sm" style={`--c:${assignee.c}`} aria-label={assignee.name}>{assignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span><span class="trunc">{assignee.name}</span>{:else}{@html ic('user', 13)}Unassigned{/if}</button>
                <button type="button" class="pillbtn" data-inbox-property-trigger aria-label="Priority" onclick={event => showPropertyMenu('priority', event)}>{@html prPill(selectedTask.priority)}</button>
                {#if selectedTask.due}<button type="button" class="pillbtn" data-inbox-property-trigger aria-label="Due date" onclick={event => showPropertyMenu('due', event)}>{@html ic('calendar', 13)}<span class="num">{relDate(selectedTask.due)}</span></button>{/if}
              </div>
              <div class="eyebrow" style="margin-bottom:4px">Conversation</div>
              {#each comments.filter(comment => comment.task === selectedTask.id).slice(-4) as comment (comment.id)}
                {@const member = memberById.get(comment.by)}
                <div class="cmt"><span class="av md" style={`--c:${member?.c || '#8A867E'}`} aria-label={member?.name || 'Someone'}>{member?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><div class="body"><div class="who"><b>{member?.name}</b><time>{ago(comment.at)}</time>{#if comment.by === workspaceData.me}<span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Delete comment" title="Delete" onclick={() => deleteComment(comment)}>{@html ic('trash-2', 12)}</button>{/if}</div><div class="txt">{@html fmtComment(comment.text)}</div></div></div>
              {:else}
                <p class="muted">{selectedNotification.snippet}</p>
              {/each}
              <form class="cbox" style="margin-top:10px" onsubmit={postReply}>
                <textarea bind:value={reply} onkeydown={handleReplyKeydown} placeholder="Reply… use @ to mention" aria-label="Reply"></textarea>
                <div class="row"><span class="faint" style="font-size:11.5px">{MOD}+Enter to send</span><span class="sp"></span><Button variant="primary" size="sm" disabled={!reply.trim()}>Reply</Button></div>
              </form>
            </div>
          {:else}
            <div style="padding:24px 28px">
              <Button variant="ghost" size="sm" onclick={backToList} style="margin:-4px 0 12px -8px">{@html ic('arrow-left', 14)}Back</Button>
              <div class="alert danger">{@html ic('triangle-alert', 16)}<div><b>{selectedNotification.text}</b><div class="muted" style="margin-top:2px">{selectedNotification.snippet}</div></div></div>
              {#if selectedProject?.database_id}<div style="margin-top:14px"><Button variant="secondary" size="md" onclick={openProject}>Open project</Button></div>{/if}
            </div>
          {/if}
        {:else}
          <div class="fullstate"><div class="box"><div class="empty-state" style="padding:0"><div class="glyph">{@html ic('mail-open', 20)}</div><h2 class="es-h">Select a notification</h2><p>Read the conversation and reply without leaving your inbox.</p></div></div></div>
        {/if}
      </section>
    </div>
  </div>
  {#if propertyMenu}
    <div class="pop enter" data-inbox-property-popup role="menu" style={`left:${propertyMenuPosition.left}px;top:${propertyMenuPosition.top}px;${propertyMenu === 'due' ? 'min-width:0' : 'width:200px'}`}>
      {#if propertyMenu === 'status'}
        {#each STATUSES as item, index (item.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={selectedTask?.status === item.id} onclick={() => void saveTaskProperty('status', item.id)}>{@html stIcon(item.id, 14)}<span>{item.name}</span><span class="r"><kbd>{index + 1}</kbd></span>{#if selectedTask?.status === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      {:else if propertyMenu === 'priority'}
        {#each PRIOS as item (item.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={selectedTask?.priority === item.id} onclick={() => void saveTaskProperty('priority', item.id)}>{@html prIcon(item.id, 14)}<span>{item.name}</span>{#if selectedTask?.priority === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      {:else if propertyMenu === 'assignee'}
        <div class="pin"><input bind:value={assigneeQuery} placeholder="Assign to…" aria-label="Assign to…" /></div>
        <button type="button" class="mi" role="menuitemradio" aria-checked={!selectedTask?.assignee} onclick={() => void saveTaskProperty('assignee_id', null)}>{@html ic('user-round', 14)}<span>Unassigned</span>{#if !selectedTask?.assignee}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
        {#each assignableMembers as member (member.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={selectedTask?.assignee === member.id} onclick={() => void saveTaskProperty('assignee_id', member.database_id)}><span class="av sm" style={`--c:${member.c}`}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span><span class="trunc">{member.name}{member.id === workspaceData.me ? ' (you)' : ''}</span>{#if selectedTask?.assignee === member.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        {#if !assignableMembers.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
      {:else if propertyMenu === 'due'}
        {@const currentDate = selectedTask?.due || ''}
        <div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">
          {#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7 - ((TODAY.getDay() + 6) % 7)], ['In 2 weeks', 14]] as [shortcut, offset] (shortcut)}<button type="button" class="badge" style="cursor:pointer" onclick={() => void saveTaskProperty('due_date', iso(addD(TODAY, offset)))}>{shortcut}</button>{/each}
          {#if currentDate}<button type="button" class="badge" style="cursor:pointer;color:var(--red)" onclick={() => void saveTaskProperty('due_date', null)}>Clear</button>{/if}
        </div>
        <div class="msep"></div>
        <div style="padding:6px 6px 2px;width:252px">
          <div class="row" style="margin-bottom:6px"><b style="font-weight:600;font-size:13px;padding-left:4px">{MONL[calendarMonth.getMonth()]} {calendarMonth.getFullYear()}</b><span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Previous month" onclick={() => shiftCalendarMonth(-1)}>{@html ic('chevron-left', 14)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Next month" onclick={() => shiftCalendarMonth(1)}>{@html ic('chevron-right', 14)}</button></div>
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:11px">{#each calendarWeekdays as weekday}<span class="faint" style="padding:3px 0">{weekday}</span>{/each}
            {#each calendarDays as day (iso(day))}
              {@const dayIso = iso(day)}
              {@const isSelected = dayIso === currentDate}
              {@const isToday = diffD(day, TODAY) === 0}
              <button type="button" class="num" aria-label={fmtDate(dayIso, true)} style={`height:30px;border-radius:6px;font-size:12px;${isSelected ? 'background:var(--accent);color:var(--on-accent);font-weight:600' : isToday ? 'color:var(--accent);font-weight:600;box-shadow:inset 0 0 0 1px var(--accent-line)' : day.getMonth() !== calendarMonth.getMonth() ? 'color:var(--text-3)' : ''}`} onclick={() => void saveTaskProperty('due_date', dayIso)}>{day.getDate()}</button>
            {/each}
          </div>
        </div>
      {/if}
    </div>
  {/if}
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updateTask} />{/if}
</AppLayout>

<style>
  @media (max-width: 900px) {
    .inbox-grid { grid-template-columns: minmax(0, 1fr) !important; }
    .inbox-grid:not(.has-selection) > section:last-child { display: none !important; }
    .inbox-grid.has-selection > section:last-child { position: fixed; inset: 0; z-index: 48; background: var(--surface); }
  }
</style>
