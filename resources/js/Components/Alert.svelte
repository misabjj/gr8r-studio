<script lang="ts">
  import type { Snippet } from 'svelte';
  import { ic } from '../../../src/core/icons.js';

  type Variant = 'danger' | 'info' | 'ok';
  type Props = {
    children: Snippet;
    class?: string;
    role?: 'alert' | 'status';
    variant: Variant;
  };

  let {
    children,
    class: className = '',
    role,
    variant,
  }: Props = $props();

  const icons: Record<Variant, string> = {
    danger: 'circle-alert',
    info: 'info',
    ok: 'circle-check',
  };
  const defaultRole = $derived(role ?? (variant === 'danger' ? 'alert' : variant === 'ok' ? 'status' : undefined));
</script>

<div class={`alert ${variant} ${className}`} role={defaultRole}>
  {@html ic(icons[variant], variant === 'info' ? 14 : 15)}<span>{@render children()}</span>
</div>
