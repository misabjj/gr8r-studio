<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { ic } from '@/src/core/icons.js';

  let filter = $state('all');
</script>

<svelte:head><title>Activity · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page" style="max-width:820px">
    <div class="ph"><div><h1>Activity</h1><p>Everything that's changed across the workspace.</p></div></div>
    <div class="row" style="gap:8px;margin-bottom:6px;flex-wrap:wrap">
      <div class="seg">
        {#each [['all', 'All'], ['status', 'Status changes'], ['comments', 'Comments'], ['projects', 'Projects']] as [key, label]}
          <button class:on={filter === key} onclick={() => filter = key}>{label}</button>
        {/each}
      </div>
      <select class="select" style="height:26px;width:auto;font-size:12px" aria-label="Filter by person"><option value="all">Everyone</option></select>
    </div>
    <div class="empty"><span class="empty-ic">{@html ic('activity', 20)}</span><h2>No activity yet</h2><p>Changes to tasks and projects will appear here.</p></div>
  </div>
</AppLayout>
