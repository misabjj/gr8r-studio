<script>
  import { router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { show as projectShow } from '../../routes/projects';
  import { ago, MOD } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { prIcon, stIcon } from '@/src/ui/helpers.js';

  let { workspaceData = {} } = $props();
  const page = usePage();
  let category = $state('all');
  let unreadOnly = $state(false);
  let selectedId = $state('');
  let notifications = $state([]);
  let comments = $state([]);
  let reply = $state('');

  $effect(() => {
    notifications = (workspaceData.notifs || []).map(notification => ({ ...notification }));
    comments = (workspaceData.comments || []).map(comment => ({ ...comment }));
  });

  $effect(() => {
    const notificationId = new URLSearchParams(page.url.split('?')[1] || '').get('notification');
    if (notificationId && notifications.some(notification => notification.id === notificationId)) {
      selectedId = notificationId;
      notifications = notifications.map(notification => notification.id === notificationId ? { ...notification, read: true } : notification);
    }
  });

  const categories = [['all', 'All'], ['mention', 'Mentions'], ['assign', 'Assignments'], ['comment', 'Comments'], ['update', 'Updates']];
  const members = $derived(workspaceData.members || []);
  const memberById = $derived(new Map(members.map(member => [member.id, member])));
  const projects = $derived(workspaceData.projects || []);
  const projectById = $derived(new Map(projects.map(project => [project.id, project])));
  const tasks = $derived(workspaceData.tasks || []);
  const taskById = $derived(new Map(tasks.map(task => [task.id, task])));
  let unreadCounts = $derived(Object.fromEntries(categories.map(([key]) => [key, notifications.filter(notification => !notification.read && (key === 'all' || notification.type === key)).length])));
  let visibleNotifications = $derived(notifications
    .filter(notification => category === 'all' || notification.type === category)
    .filter(notification => !unreadOnly || !notification.read)
    .sort((first, second) => second.at - first.at));
  let selectedNotification = $derived(notifications.find(notification => notification.id === selectedId) || null);
  let selectedTask = $derived(selectedNotification ? taskById.get(selectedNotification.task) || null : null);
  let selectedProject = $derived(selectedNotification ? projectById.get(selectedNotification.project || selectedTask?.project) || null : null);

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

    const project = notificationProject(notification);
    return {
      initials: '',
      color: project ? 'var(--red)' : 'var(--amber)',
      icon: notification.type === 'update' && project ? 'triangle-alert' : 'clock',
    };
  }

  function categoryIcon(type) {
    return ({ mention: 'at-sign', assign: 'user-plus', comment: 'message-square', update: 'refresh-cw' })[type] || 'bell';
  }

  function selectNotification(notification) {
    selectedId = notification.id;
    notifications = notifications.map(item => item.id === notification.id ? { ...item, read: true } : item);
    reply = '';
  }

  function toggleRead(event, notification) {
    event.stopPropagation();
    notifications = notifications.map(item => item.id === notification.id ? { ...item, read: !item.read } : item);
  }

  function markAllRead() {
    notifications = notifications.map(notification => ({ ...notification, read: true }));
  }

  function backToList() {
    selectedId = '';
    router.visit('/inbox', { preserveState: true, replace: true });
  }

  function openProject() {
    if (selectedProject?.database_id) router.visit(projectShow(selectedProject.database_id).url);
  }

  function postReply(event) {
    event.preventDefault();
    const text = reply.trim();
    if (!text || !selectedTask) return;

    comments = [...comments, {
      id: `reply-${Date.now()}`,
      task: selectedTask.id,
      by: workspaceData.me,
      at: Date.now(),
      text,
      re: {},
    }];
    reply = '';
  }

  function handleReplyKeydown(event) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
      postReply(event);
    }
  }
</script>

<svelte:head><title>Inbox · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class:has-selection={selectedNotification} class="inbox-grid" style="display:grid;grid-template-columns:minmax(0,420px) minmax(0,1fr);flex:1;min-height:0">
      <section style="border-right:1px solid var(--border);display:flex;flex-direction:column;min-height:0">
        <div class="row" style="padding:18px var(--gutter) 0">
          <h1 style="font-size:var(--fs-xl);margin:0;font-weight:600;letter-spacing:-.015em">Inbox</h1><span class="sp"></span>
          <label class="row" style="font-size:12px;color:var(--text-2);gap:6px;cursor:pointer"><input class="toggle" type="checkbox" bind:checked={unreadOnly} />Unread</label>
          <button class="ibtn ibtn-sm" aria-label="Mark all as read" onclick={markAllRead} disabled={!notifications.some(notification => !notification.read)}>{@html ic('check-check', 15)}</button>
        </div>
        <div class="tabs inbox-tabs" role="tablist">{#each categories as [key, label]}<button role="tab" class="tab" class:on={category === key} aria-selected={category === key} onclick={() => category = key}>{label}{#if unreadCounts[key]}<span class="cnt">{unreadCounts[key]}</span>{/if}</button>{/each}</div>
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
                  <div><b style="font-weight:600">{copy.strong}</b>{#if copy.text}<span class="muted"> {copy.text}</span>{/if}{#if copy.task}<b style="font-weight:500"> {copy.task}</b>{/if}</div>
                  <div class="trunc muted" style="font-size:12.5px;margin-top:2px">{notification.snippet}</div>
                  <div class="row" style="gap:6px;margin-top:5px;font-size:11.5px;color:var(--text-3)">{@html ic(categoryIcon(notification.type), 11)}{#if project}<span class="pdot" style={`--c:var(--${project.color || 'indigo'});width:6px;height:6px`}></span>{project.name}{/if}<span>·</span><span>{ago(notification.at)}</span></div>
                </div>
                <button class="ibtn ibtn-xs" onclick={event => toggleRead(event, notification)} aria-label={notification.read ? 'Mark as unread' : 'Mark as read'}>{@html ic(notification.read ? 'mail' : 'mail-open', 13)}</button>
              </div>
            {/each}
          {:else}
            <div class="empty"><span class="empty-ic">{@html ic('inbox', 20)}</span><h2>You're all caught up.</h2><p>{unreadOnly ? 'No unread notifications in this category.' : 'New mentions, assignments, and comments will show up here.'}</p></div>
          {/if}
        </div>
      </section>
      <section class="inbox-preview" style="display:flex;flex-direction:column;min-height:0;overflow-y:auto">
        {#if selectedNotification}
          {#if selectedTask}
            <div class="row" style="height:46px;padding:0 16px;border-bottom:1px solid var(--border);gap:8px;flex-shrink:0">
              <button class="ibtn ibtn-sm" onclick={backToList} aria-label="Back">{@html ic('arrow-left', 15)}</button>
              <span class="pdot" style={`--c:${selectedProject?.color || 'indigo'}`}></span><span class="muted" style="font-size:12.5px">{selectedProject?.name}</span><span class="faint mono">{selectedTask.key}</span><span class="sp"></span>
              {#if selectedProject?.database_id}<button class="btn btn-sm btn-secondary" onclick={openProject}>{@html ic('panel-right-open', 14)}Open task</button>{/if}
            </div>
            <div style="padding:24px 28px;max-width:720px;width:100%">
              <h2 style="font-size:var(--fs-xl);margin:0 0 10px;font-weight:600;letter-spacing:-.015em">{selectedTask.title}</h2>
              <div class="row" style="flex-wrap:wrap;gap:4px;margin-bottom:18px">
                <span class="pillbtn">{@html stIcon(selectedTask.status, 13)}{selectedTask.status}</span>
                <span class="pillbtn">{@html ic('user', 13)}{memberById.get(selectedTask.assignee)?.name || 'Unassigned'}</span>
                <span class="pillbtn">{@html prIcon(selectedTask.priority, 13)}{selectedTask.priority}</span>
                {#if selectedTask.due}<span class="pillbtn">{@html ic('calendar', 13)}{selectedTask.due}</span>{/if}
              </div>
              <div class="eyebrow" style="margin-bottom:4px">Conversation</div>
              {#each comments.filter(comment => comment.task === selectedTask.id).slice(-4) as comment (comment.id)}
                {@const member = memberById.get(comment.by)}
                <div class="cmt"><span class="av md" style={`--c:${member?.c || '#8A867E'}`}>{member?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><div class="body"><div class="who"><b>{member?.name}</b><time>{ago(comment.at)}</time></div><div class="txt">{comment.text}</div></div></div>
              {:else}
                <p class="muted">{selectedNotification.snippet}</p>
              {/each}
              <form class="cbox" style="margin-top:10px" onsubmit={postReply}>
                <textarea bind:value={reply} onkeydown={handleReplyKeydown} placeholder="Reply… use @ to mention" aria-label="Reply"></textarea>
                <div class="row"><span class="faint" style="font-size:11.5px">{MOD}+Enter to send</span><span class="sp"></span><button class="btn btn-primary btn-sm" disabled={!reply.trim()}>Reply</button></div>
              </form>
            </div>
          {:else}
            <div style="padding:24px 28px">
              <button class="btn btn-sm btn-ghost" onclick={backToList} style="margin:-4px 0 12px -8px">{@html ic('arrow-left', 14)}Back</button>
              <div class="alert danger">{@html ic('triangle-alert', 16)}<div><b>{selectedNotification.text}</b><div class="muted" style="margin-top:2px">{selectedNotification.snippet}</div></div></div>
              {#if selectedProject?.database_id}<div style="margin-top:14px"><button class="btn btn-secondary" onclick={openProject}>Open project</button></div>{/if}
            </div>
          {/if}
        {:else}
          <div class="fullstate"><div class="box"><div class="empty-state" style="padding:0"><div class="glyph">{@html ic('mail-open', 20)}</div><h2 class="es-h">Select a notification</h2><p>Read the conversation and reply without leaving your inbox.</p></div></div></div>
        {/if}
      </section>
    </div>
  </div>
</AppLayout>

<style>
  @media (max-width: 900px) {
    .inbox-grid { grid-template-columns: minmax(0, 1fr) !important; }
    .inbox-grid:not(.has-selection) > section:last-child { display: none !important; }
    .inbox-grid.has-selection > section:last-child { position: fixed; inset: 0; z-index: 48; background: var(--surface); }
  }
</style>
