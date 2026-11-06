<script lang="ts">
  import type { Snippet } from 'svelte';
  import type { HTMLAttributes } from 'svelte/elements';

  type Props = Omit<HTMLAttributes<HTMLDivElement>, 'class'> & {
    children: Snippet;
    class?: string;
  };

  let { children, class: className = 'pop', ...attributes }: Props = $props();
</script>

<div {...attributes} class={className}>
  {@render children()}
</div>
