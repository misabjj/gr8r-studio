<script lang="ts">
  import type { Snippet } from 'svelte';
  import { ic } from '../../../src/core/icons.js';

  type Variant = 'danger' | 'info' | 'ok';
  type Props = {
    children: Snippet;
    class?: string;
    variant: Variant;
  };

  let {
    children,
    class: className = '',
    variant,
  }: Props = $props();

  const icons: Record<Variant, string> = {
    danger: 'circle-alert',
    info: 'info',
    ok: 'circle-check',
  };
  const roles: Record<Variant, 'alert' | 'status'> = {
    danger: 'alert',
    info: 'status',
    ok: 'status',
  };
</script>

<div class={`alert ${variant} ${className}`} role={roles[variant]}>
  {@html ic(icons[variant], variant === 'info' ? 14 : 15)}<span>{@render children()}</span>
</div>
