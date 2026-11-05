<script>
  import LegacyPage from '../LegacyPage.svelte';

  let { ...props } = $props();
</script>

<LegacyPage route="Projects/Index" {props} />
