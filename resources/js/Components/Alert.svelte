<script lang="ts">
  import type { Snippet } from 'svelte';
  import { ic } from '../../../src/core/icons.js';

  type Variant = 'danger' | 'info' | 'ok';
  type Props = {
    children: Snippet;
    class?: string;
    icon: string;
    iconSize?: number;
    role?: 'alert' | 'status';
    variant: Variant;
  };

  let {
    children,
    class: className = '',
    icon,
    iconSize = 15,
    role,
    variant,
  }: Props = $props();

  const defaultRole = $derived(role ?? (variant === 'danger' ? 'alert' : variant === 'ok' ? 'status' : undefined));
</script>

<div class={`alert ${variant} ${className}`} role={defaultRole}>
  {@html ic(icon, iconSize)}<span>{@render children()}</span>
</div>
