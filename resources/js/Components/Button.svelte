<script lang="ts">
  import type { Snippet } from 'svelte';
  import type { HTMLButtonAttributes } from 'svelte/elements';

  type Variant = 'base' | 'primary' | 'secondary' | 'ghost' | 'danger' | 'danger-ghost';
  type Size = 'md' | 'sm' | 'lg';

  type Props = Omit<HTMLButtonAttributes, 'class'> & {
    block?: boolean;
    class?: string;
    children: Snippet;
    loading?: boolean;
    size: Size;
    variant: Variant;
  };

  let { block = false, class: className = '', children, disabled = false, loading = false, size, variant, ...attributes }: Props = $props();
</script>

<button {...attributes} disabled={disabled || loading} class={`btn ${variant === 'base' ? '' : `btn-${variant}`} ${size === 'md' ? '' : `btn-${size}`} ${block ? 'btn-block' : ''} ${loading ? 'is-loading' : ''} ${className}`}>
  {@render children()}
</button>
