<script lang="ts">
import DropdownItemView from "./DropdownItem.svelte";
import type { DropdownActionHandler, DropdownConfig } from "../types/dropdown";

type Props = {
	config: DropdownConfig;
	onAction?: DropdownActionHandler;
};

let { config, onAction }: Props = $props();
</script>

<!-- The role is configured as a menu or dialog by callers, so this wrapper can receive focus. -->
<!-- svelte-ignore a11y_no_noninteractive_tabindex -->
<div
  {...config.dataAttributes}
  class={config.className}
  id={config.id}
  role={config.role}
  aria-label={config.label}
  aria-modal={config.ariaModal}
  tabindex={config.tabIndex}
  style={config.style}
  onpointerdown={event => {
    if (config.stopPointerDownPropagation) {
      event.stopPropagation();
    }
  }}
>
  {#each config.items ?? [] as item (item.id)}
    <DropdownItemView {item} {onAction} />
  {/each}
</div>
