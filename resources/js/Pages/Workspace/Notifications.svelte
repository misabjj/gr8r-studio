<script>
  import { router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { index as inboxRoute } from '../../routes/inbox';
  import { ago, dayBucket } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';

  let { workspaceData = {} } = $props();
  let filter = $state('all');
  let notifications = $state([]);

  $effect(() => { notifications = (workspaceData.notifs || []).map(notification => ({ ...notification })); });

  let unreadCount = $derived(notifications.filter(notification => !notification.read).length);
  let visibleNotifications = $derived(notifications
    .filter(notification => filter !== 'unread' || !notification.read)
    .sort((first, second) => second.at - first.at));
  let notificationGroups = $derived.by(() => {
    const groups = new Map();
    for (const notification of visibleNotifications) {
      const bucket = dayBucket(notification.at);
      groups.set(bucket, [...(groups.get(bucket) || []), notification]);
    }
    return [...groups.entries()];
  });

  const members = $derived(workspaceData.members || []);
  const memberById = $derived(new Map(members.map(member => [member.id, member])));
  const projectById = $derived(new Map((workspaceData.projects || []).map(project => [project.id, project])));
  const taskById = $derived(new Map((workspaceData.tasks || []).map(task => [task.id, task])));

  function notificationProject(notification) {
    const task = taskById.get(notification.task);
    return projectById.get(notification.project || task?.project);
  }

  function toggleRead(notification) {
    notifications = notifications.map(item => item.id === notification.id ? { ...item, read: !item.read } : item);
  }

  function markAllRead() {
    notifications = notifications.map(notification => ({ ...notification, read: true }));
  }

  function openNotification(notification) {
    notifications = notifications.map(item => item.id === notification.id ? { ...item, read: true } : item);
    router.visit(`${inboxRoute().url}?notification=${encodeURIComponent(notification.id)}`);
  }
</script>

<svelte:head><title>Notifications · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:820px">
    <div class="ph">
      <div><h1 class="row" style="gap:10px">Notifications {#if unreadCount}<span class="badge accent">{unreadCount} unread</span>{/if}</h1><p>Updates on tasks and projects you follow.</p></div>
      <div class="acts">
        <div class="seg">{#each [['all', 'All'], ['unread', 'Unread']] as [key, label]}<button class:on={filter === key} onclick={() => filter = key}>{label}</button>{/each}</div>
        <button class="btn btn-secondary" onclick={markAllRead} disabled={!unreadCount}>{@html ic('check-check', 14)}Mark all as read</button>
        <button class="ibtn" aria-label="Notification preferences">{@html ic('settings-2', 16)}</button>
      </div>
    </div>
    {#if visibleNotifications.length}
      {#each notificationGroups as [bucket, entries] (bucket)}
        <div class="day-h">{bucket}</div>
        <div class="panel" style="overflow:hidden">
          {#each entries as notification (notification.id)}
            {@const member = memberById.get(notification.by)}
            {@const task = taskById.get(notification.task)}
            {@const project = notificationProject(notification)}
            <div class={`nrow ${notification.read ? '' : 'unread'}`} role="button" tabindex="0" onclick={() => openNotification(notification)} onkeydown={event => event.key === 'Enter' && openNotification(notification)}>
              {#if member}<span class="av md" style={`--c:${member.c}`} aria-label={member.name}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>
              {:else}<span class="av md" style={`--c:${project ? 'var(--red)' : 'var(--amber)'}`}>{@html ic(notification.type === 'update' && project ? 'triangle-alert' : 'clock', 13)}</span>{/if}
              <div class="grow" style="font-size:13px">
                <div>
                  {#if !member && project && !task}<b style="font-weight:600">{notification.text}</b>
                  {:else if !member && task}<b style="font-weight:600">{task.title}</b> <span class="muted">{notification.text}</span>
                  {:else}<b style="font-weight:600">{member?.name}</b> <span class="muted">{notification.text}</span> {#if task}<b style="font-weight:500">{task.title}</b>{/if}{/if}
                </div>
                <div class="muted trunc" style="font-size:12.5px;margin-top:2px">{notification.snippet}</div>
                <div class="faint" style="font-size:11.5px;margin-top:4px">{project ? `${project.name} · ` : ''}{ago(notification.at)}</div>
              </div>
              {#if !notification.read}<span style="width:7px;height:7px;border-radius:50%;background:var(--accent);margin-top:8px" aria-label="Unread"></span>{/if}
              <button class="ibtn ibtn-sm" onclick={event => { event.stopPropagation(); toggleRead(notification); }} aria-label={notification.read ? 'Mark as unread' : 'Mark as read'}>{@html ic(notification.read ? 'mail' : 'check', 14)}</button>
            </div>
          {/each}
        </div>
      {/each}
    {:else}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('bell-off', 20)}</span><h2>You're all caught up.</h2><p>No unread notifications. We'll let you know when something needs you.</p>{#if filter === 'unread'}<button class="btn btn-secondary btn-sm" onclick={() => filter = 'all'}>Show all</button>{/if}</div></div>
    {/if}
  </div>
</AppLayout>
