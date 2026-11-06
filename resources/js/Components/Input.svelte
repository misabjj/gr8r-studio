<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';

  type Variant = 'default' | 'large';

  type Props = Omit<HTMLInputAttributes, 'class' | 'size' | 'value'> & {
    class?: string;
    error?: boolean;
    size?: Variant;
    value?: string | number;
    element?: HTMLInputElement | null;
  };

  let { class: className = '', error = false, size = 'default', value = $bindable(), element = $bindable(null), ...attributes }: Props = $props();
</script>

<input {...attributes} class={`input ${size === 'large' ? 'input-lg' : ''} ${className} ${error ? 'is-error' : ''}`} aria-invalid={error ? 'true' : undefined} bind:this={element} bind:value />
