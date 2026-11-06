<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';

  type Variant = 'default' | 'large';

  type Props = Omit<HTMLInputAttributes, 'class' | 'value'> & {
    class?: string;
    error?: boolean;
    variant?: Variant;
    value?: string | number;
    element?: HTMLInputElement | null;
  };

  let { class: className = '', error = false, variant = 'default', value = $bindable(), element = $bindable(null), ...attributes }: Props = $props();
</script>

<input {...attributes} class={`input ${variant === 'large' ? 'input-lg' : ''} ${className} ${error ? 'is-error' : ''}`} aria-invalid={error ? 'true' : undefined} bind:this={element} bind:value />
