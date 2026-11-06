<script lang="ts">
  import type { Snippet } from 'svelte';
  import type { HTMLSelectAttributes } from 'svelte/elements';

  type Props = Omit<HTMLSelectAttributes, 'class' | 'value'> & {
    children: Snippet;
    class?: string;
    element?: HTMLSelectElement | null;
    value?: string;
  };

  let {
    children,
    class: className = 'select',
    element = $bindable(null),
    value = $bindable(),
    ...attributes
  }: Props = $props();
</script>

<select {...attributes} class={className} bind:this={element} bind:value>
  {@render children()}
</select>
