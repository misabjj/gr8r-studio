<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { ic } from '@/src/core/icons.js';

  let filter = $state('all');
</script>

<svelte:head><title>Notifications · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:820px">
    <div class="ph">
      <div><h1>Notifications</h1><p>Updates on tasks and projects you follow.</p></div>
      <div class="acts">
        <div class="seg">{#each [['all', 'All'], ['unread', 'Unread']] as [key, label]}<button class:on={filter === key} onclick={() => filter = key}>{label}</button>{/each}</div>
        <button class="btn btn-secondary" disabled>{@html ic('check-check', 14)}Mark all as read</button>
        <button class="ibtn" aria-label="Notification preferences">{@html ic('settings-2', 16)}</button>
      </div>
    </div>
    <div class="panel">
      <div class="empty"><span class="empty-ic">{@html ic('bell-off', 20)}</span><h2>You're all caught up.</h2><p>No unread notifications. We'll let you know when something needs you.</p><button class="btn btn-secondary btn-sm" onclick={() => filter = 'all'}>Show all</button></div>
    </div>
  </div>
</AppLayout>
