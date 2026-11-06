<script lang="ts">
  import type { Snippet } from 'svelte';
  import type { HTMLAttributes } from 'svelte/elements';
  import DropdownItemView from './DropdownItem.svelte';
  import type { DropdownActionHandler, DropdownConfig } from '../types/dropdown';

  type Props = Omit<HTMLAttributes<HTMLDivElement>, 'class'> & {
    children?: Snippet;
    class?: string;
    config?: DropdownConfig;
    onAction?: DropdownActionHandler;
  };

  let {
    children,
    class: className = 'pop',
    config = {},
    onAction,
    ...attributes
  }: Props = $props();

  function mergeClasses(...classNames: Array<string | undefined>): string {
    return [...new Set(classNames.flatMap(value => value?.split(/\s+/) ?? []))].join(' ');
  }
</script>

<!-- The role is configured as a menu or dialog by callers, so this wrapper can receive focus. -->
<!-- svelte-ignore a11y_no_noninteractive_tabindex -->
<div
  {...attributes}
  {...config.dataAttributes}
  class={mergeClasses(className, config.className)}
  id={config.id ?? attributes.id}
  role={config.role ?? attributes.role}
  aria-label={config.label ?? attributes['aria-label']}
  aria-modal={config.ariaModal ?? attributes['aria-modal']}
  tabindex={config.tabIndex ?? attributes.tabindex}
  style={config.style ?? attributes.style}
>
  {#each config.items ?? [] as item (item.id)}
    <DropdownItemView {item} {onAction} />
  {/each}
  {@render children?.()}
</div>
