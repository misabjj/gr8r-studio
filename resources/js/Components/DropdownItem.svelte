<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import DropdownItemView from './DropdownItem.svelte';
  import { ic } from '../../../src/core/icons.js';
  import type { DropdownAction, DropdownActionHandler, DropdownItem, DropdownLink } from '../types/dropdown';

  type Props = {
    item: DropdownItem;
    onAction?: DropdownActionHandler;
  };

  let { item, onAction }: Props = $props();

  function activate(item: DropdownAction | DropdownLink): void {
    if (item.action) onAction?.(item.action, item);
  }

  function activateLink(event: MouseEvent, item: DropdownLink): void {
    if (item.disabled) {
      event.preventDefault();
      return;
    }

    activate(item);
  }

  function itemClass(item: DropdownAction | DropdownLink): string {
    return ['mi', item.className, item.danger ? 'danger' : undefined].filter(Boolean).join(' ');
  }
</script>

{#snippet itemContent(item: DropdownAction | DropdownLink)}
  {#if item.icon}{@html ic(item.icon, item.iconSize ?? 15)}{/if}
  {item.label}
  {#if item.trailing}<span class="r">{item.trailing}</span>{/if}
  {#if item.shortcut}<span class="r"><kbd>{item.shortcut}</kbd></span>{/if}
  {#if item.checked}<span class="ck">{@html ic('check', 14)}</span>{/if}
{/snippet}

{#if item.type === 'heading'}
  <div class={item.className ?? 'mh'} style={item.style}>{item.label}</div>
{:else if item.type === 'separator'}
  <div class="msep" role="separator"></div>
{:else if item.type === 'group'}
  <div class={item.className} style={item.style}>
    {#if item.label}<div class="mh">{item.label}</div>{/if}
    {#each item.items as child (child.id)}
      <DropdownItemView item={child} {onAction} />
    {/each}
  </div>
{:else if item.type === 'text'}
  <div class={item.className ?? 'mi faint'} style={item.style} aria-disabled="true">{item.label}</div>
{:else if item.type === 'action'}
  <button
    class={itemClass(item)}
    type="button"
    role={item.role ?? 'menuitem'}
    aria-checked={item.role === 'menuitemradio' || item.role === 'menuitemcheckbox' ? item.checked : undefined}
    aria-label={item.ariaLabel}
    title={item.title}
    disabled={item.disabled}
    style={item.style}
    onclick={() => activate(item)}
  >
    {@render itemContent(item)}
  </button>
{:else if item.external || item.disabled}
  <a
    class={itemClass(item)}
    href={item.href}
    role={item.role ?? 'menuitem'}
    aria-label={item.ariaLabel}
    aria-checked={item.role === 'menuitemradio' || item.role === 'menuitemcheckbox' ? item.checked : undefined}
    aria-disabled={item.disabled || undefined}
    tabindex={item.disabled ? -1 : undefined}
    title={item.title}
    target={item.target}
    rel={item.rel}
    style={item.style}
    onclick={event => activateLink(event, item)}
  >
    {@render itemContent(item)}
  </a>
{:else}
  <Link
    class={itemClass(item)}
    href={item.href}
    role={item.role ?? 'menuitem'}
    aria-label={item.ariaLabel}
    aria-checked={item.role === 'menuitemradio' || item.role === 'menuitemcheckbox' ? item.checked : undefined}
    title={item.title}
    target={item.target}
    rel={item.rel}
    style={item.style}
    onclick={() => activate(item)}
  >
    {@render itemContent(item)}
  </Link>
{/if}
