<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';

  type Props = Omit<HTMLInputAttributes, 'class' | 'value'> & {
    class?: string;
    value?: string | number;
    element?: HTMLInputElement | null;
  };

  let { class: className = '', value = $bindable(), element = $bindable(null), ...attributes }: Props = $props();
</script>

<input class={`input ${className}`} bind:this={element} bind:value {...attributes} />
