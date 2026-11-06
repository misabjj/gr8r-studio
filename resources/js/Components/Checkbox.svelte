<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';

  type Props = Omit<HTMLInputAttributes, 'checked' | 'class' | 'type'> & {
    checked?: boolean;
    class?: string;
  };

  let { checked = $bindable(false), class: className = 'check', ...attributes }: Props = $props();
</script>

<input {...attributes} type="checkbox" class={className} bind:checked />
