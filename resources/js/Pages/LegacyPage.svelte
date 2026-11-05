<script>
  import { onMount } from 'svelte';
  import { mountLegacyPage, unmountLegacyPage } from '../../../src/legacy.js';

  let { route, props = {} } = $props();

  onMount(() => {
    mountLegacyPage({ route, props });
    return unmountLegacyPage;
  });
</script>

<div id="legacy-app"></div>
