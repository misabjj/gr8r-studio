<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';

  let { page = 'design' } = $props();
  let content = $state('');
  $effect(() => {
    import('@/src/pages/design-system.js').then(module => {
      content = page === 'states' ? module.pageStates() : module.pageSystem();
    });
  });
</script>

<svelte:head><title>{page === 'states' ? 'System states' : 'Design system'} · Gr8r Studio</title></svelte:head>

<AppLayout>
  {@html content}
</AppLayout>
