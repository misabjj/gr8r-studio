<script lang="ts">
  import type { HTMLTextareaAttributes } from 'svelte/elements';

  type Props = Omit<HTMLTextareaAttributes, 'class' | 'value'> & {
    class?: string;
    element?: HTMLTextAreaElement | null;
    error?: boolean;
    value?: string;
  };

  let {
    class: className = '',
    element = $bindable(null),
    error = false,
    value = $bindable(),
    ...attributes
  }: Props = $props();
</script>

<textarea {...attributes} class={`textarea ${className} ${error ? 'is-error' : ''}`} aria-invalid={error ? 'true' : undefined} bind:this={element} bind:value></textarea>
