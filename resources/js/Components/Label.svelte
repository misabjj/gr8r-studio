<script lang="ts">
  import type { Snippet } from 'svelte';

  type Props = {
    for?: string;
    style?: string;
    children: Snippet;
  };

  let { for: htmlFor, style, children }: Props = $props();
</script>

<label class="label" for={htmlFor} {style}>
  {@render children()}
</label>
