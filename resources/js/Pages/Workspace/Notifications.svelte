<script>
  import Button from "../../Components/Button.svelte";
  import { Link, router } from '@inertiajs/svelte';
  import { notifEmail as notificationPreferences } from '../../routes/settings';
  import { read as notificationReadRoute, readAll as notificationReadAllRoute } from '../../routes/notifications';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { show as projectShow } from '../../routes/projects';
  import { useHttpRequest } from '../../lib/http.js';
  import { toast } from '@/src/ui/toast.js';
  import { ago, dayBucket } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { S } from '@/src/core/store.js';
  import { fmtComment } from '@/src/ui/helpers.js';

  const httpRequest = useHttpRequest();
  let { workspaceData = {}, tasks: initialTasks = [] } = $props();
  let filter = $state(S.ui.notifFilter || 'all');
  let notifications = $state([]);
  let tasks = $state([]);
  let drawerTaskId = $state(null);

  $effect(() => { notifications = (workspaceData.notifs || []).map(notification => ({ ...notification })); });
  $effect(() => { tasks = [...initialTasks]; });

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
  let drawerTask = $derived(tasks.find(task => task.id === drawerTaskId) || null);

  function setFilter(nextFilter) {
    filter = nextFilter;
    S.ui.notifFilter = nextFilter;
  }

  function notificationProject(notification) {
    const task = taskById.get(notification.task);
    return projectById.get(notification.project || task?.project);
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

  function toggleRead(notification) {
    void setRead(notification, !notification.read);
  }

  async function markAllRead() {
    const unreadIds = notifications.filter(notification => !notification.read).map(notification => notification.id);
    if (!unreadIds.length) return;

    notifications = notifications.map(notification => ({ ...notification, read: true }));

    try {
      await httpRequest(notificationReadAllRoute().url, 'PATCH', {});
      toast("You're all caught up.");
    } catch (error) {
      const unreadIdSet = new Set(unreadIds);
      notifications = notifications.map(notification => unreadIdSet.has(notification.id) ? { ...notification, read: false } : notification);
      toast(error.message || 'Could not mark notifications as read.', { kind: 'err' });
    }
  }

  function openNotification(notification) {
    if (!notification.read) void setRead(notification, true);

    const task = tasks.find(item => item.source_key === notification.task);
    if (task) {
      drawerTaskId = task.id;
      return;
    }

    const project = notificationProject(notification);
    if (project?.database_id) {
      router.visit(projectShow(project.database_id, { query: { tab: 'overview' } }).url);
    }
  }

  function handleNotificationKeydown(event, notification) {
    if (event.target !== event.currentTarget || !['Enter', ' '].includes(event.key)) return;
    event.preventDefault();
    openNotification(notification);
  }
</script>

<svelte:head><title>Notifications · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:820px">
    <div class="ph">
      <div><h1 class="row" style="gap:10px">Notifications {#if unreadCount}<span class="badge accent">{unreadCount} unread</span>{/if}</h1><p>Updates on tasks and projects you follow.</p></div>
      <div class="acts">
        <div class="seg">{#each [['all', 'All'], ['unread', 'Unread']] as [key, label]}<button class:on={filter === key} onclick={() => setFilter(key)}>{label}</button>{/each}</div>
        <Button variant="secondary" size="md" onclick={markAllRead} disabled={!unreadCount}>{@html ic('check-check', 14)}Mark all as read</Button>
        <Link class="ibtn" href={notificationPreferences().url} aria-label="Notification preferences" title="Notification preferences">{@html ic('settings-2', 16)}</Link>
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
            <div class={`nrow ${notification.read ? '' : 'unread'}`} role="button" tabindex="0" onclick={() => openNotification(notification)} onkeydown={event => handleNotificationKeydown(event, notification)}>
              {#if member}<span class="av md" style={`--c:${member.c}`} aria-label={member.name}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>
              {:else}<span class="av md" style={`--c:${notification.project ? 'var(--red)' : 'var(--amber)'}`}>{@html ic(notification.type === 'update' && notification.project ? 'triangle-alert' : 'clock', 13)}</span>{/if}
              <div class="grow" style="font-size:13px">
                <div>
                  {#if !member && project && !task}<b style="font-weight:600">{notification.text}</b>
                  {:else if !member && task}<b style="font-weight:600">{task.title}</b> <span class="muted">{notification.text}</span>
                  {:else}<b style="font-weight:600">{member?.name}</b> <span class="muted">{notification.text}</span> {#if task}<b style="font-weight:500">{task.title}</b>{/if}{/if}
                </div>
                <div class="muted trunc" style="font-size:12.5px;margin-top:2px">{@html fmtComment(notification.snippet)}</div>
                <div class="faint" style="font-size:11.5px;margin-top:4px">{project ? `${project.name} · ` : ''}{ago(notification.at)}</div>
              </div>
              {#if !notification.read}<span style="width:7px;height:7px;border-radius:50%;background:var(--accent);margin-top:8px" aria-label="Unread"></span>{/if}
              <button class="ibtn ibtn-sm" onclick={event => { event.stopPropagation(); toggleRead(notification); }} aria-label={notification.read ? 'Mark as unread' : 'Mark as read'}>{@html ic(notification.read ? 'mail' : 'check', 14)}</button>
            </div>
          {/each}
        </div>
      {/each}
    {:else}
      <div class="panel"><div class="empty"><span class="empty-ic">{@html ic('bell-off', 20)}</span><h2>You're all caught up.</h2><p>No unread notifications. We'll let you know when something needs you.</p>{#if filter === 'unread'}<Button variant="secondary" size="sm" onclick={() => setFilter('all')}>Show all</Button>{/if}</div></div>
    {/if}
  </div>
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={updatedTask => tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task)} />{/if}
</AppLayout>
