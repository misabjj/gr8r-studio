<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { ic } from '@/src/core/icons.js';

  let category = $state('all');
  let unreadOnly = $state(false);
  const categories = [['all', 'All'], ['mention', 'Mentions'], ['assign', 'Assignments'], ['comment', 'Comments'], ['update', 'Updates']];
</script>

<svelte:head><title>Inbox · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="inbox-grid" style="display:grid;grid-template-columns:minmax(0,420px) minmax(0,1fr);flex:1;min-height:0">
      <section style="border-right:1px solid var(--border);display:flex;flex-direction:column;min-height:0">
        <div class="row" style="padding:18px var(--gutter) 0">
          <h1 style="font-size:var(--fs-xl);margin:0;font-weight:600;letter-spacing:-.015em">Inbox</h1><span class="sp"></span>
          <label class="row" style="font-size:12px;color:var(--text-2);gap:6px;cursor:pointer"><input class="toggle" type="checkbox" bind:checked={unreadOnly} />Unread</label>
          <button class="ibtn ibtn-sm" aria-label="Mark all as read" disabled>{@html ic('check-check', 15)}</button>
        </div>
        <div class="tabs inbox-tabs" role="tablist">{#each categories as [key, label]}<button role="tab" class="tab" class:on={category === key} aria-selected={category === key} onclick={() => category = key}>{label}</button>{/each}</div>
        <div class="inbox-list">
          <div class="empty"><span class="empty-ic">{@html ic('inbox', 20)}</span><h2>You're all caught up.</h2><p>{unreadOnly ? 'No unread notifications in this category.' : 'New mentions, assignments, and comments will show up here.'}</p></div>
        </div>
      </section>
      <section style="display:flex;flex-direction:column;min-height:0;overflow-y:auto">
        <div class="fullstate"><div class="box"><div class="empty-state" style="padding:0"><div class="glyph">{@html ic('mail-open', 20)}</div><h2 class="es-h">Select a notification</h2><p>Read the conversation and reply without leaving your inbox.</p></div></div></div>
      </section>
    </div>
  </div>
</AppLayout>

<style>
  @media (max-width: 900px) {
    .inbox-grid { grid-template-columns: minmax(0, 1fr) !important; }
    .inbox-grid > section:last-child { display: none !important; }
  }
</style>
