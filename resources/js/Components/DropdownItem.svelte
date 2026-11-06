<script lang="ts">
import { Link } from "@inertiajs/svelte";
import Select from "./Select.svelte";
import DropdownItemView from "./DropdownItem.svelte";
import { ic, wsLogo } from "../../../src/core/icons.js";
import { prIcon, stIcon } from "../../../src/ui/helpers.js";
import type {
	DropdownAction,
	DropdownActionContext,
	DropdownActionHandler,
	DropdownElement,
	DropdownElementEvent,
	DropdownItem,
	DropdownLink,
} from "../types/dropdown";

type Props = {
	item: DropdownItem;
	onAction?: DropdownActionHandler;
};

let { item, onAction }: Props = $props();

function activate(
	item: DropdownAction | DropdownLink,
	event?: MouseEvent,
): void {
	if (!item.action) {
		return;
	}

	if (item.stopPropagation) {
		event?.stopPropagation();
	}

	const bounds =
		event?.currentTarget instanceof Element
			? event.currentTarget.getBoundingClientRect()
			: undefined;
	onAction?.(item.action, item, {
		event: "click",
		targetBounds: bounds
			? {
					bottom: bounds.bottom,
					left: bounds.left,
					right: bounds.right,
					top: bounds.top,
					width: bounds.width,
				}
			: undefined,
	});
}

function activateLink(event: MouseEvent, item: DropdownLink): void {
	if (item.disabled) {
		event.preventDefault();
		return;
	}

	activate(item, event);
}

function itemClass(item: DropdownAction | DropdownLink): string {
	return ["mi", item.className, item.danger ? "danger" : undefined]
		.filter(Boolean)
		.join(" ");
}

function dispatchElementEvent(
	eventType: DropdownElementEvent,
	item: DropdownElement,
	event: Event,
): void {
	if (item.stopPropagation) {
		event.stopPropagation();
	}

	if (eventType === "submit") {
		event.preventDefault();
	}

	const action = item.events?.[eventType];
	if (!action) {
		return;
	}

	const context: DropdownActionContext = { event: eventType };
	const target = event.currentTarget;

	if (target instanceof Element) {
		const bounds = target.getBoundingClientRect();
		context.targetBounds = {
			bottom: bounds.bottom,
			left: bounds.left,
			right: bounds.right,
			top: bounds.top,
			width: bounds.width,
		};
	}

	if (target instanceof HTMLInputElement) {
		context.value = target.value;
		if (target.type === "checkbox" || target.type === "radio") {
			context.checked = target.checked;
		}
	} else if (
		target instanceof HTMLSelectElement ||
		target instanceof HTMLTextAreaElement
	) {
		context.value = target.value;
	}

	if (event instanceof KeyboardEvent) {
		context.key = event.key;
	}

	onAction?.(action, item, context);
}
</script>

{#snippet itemContent(item: DropdownAction | DropdownLink)}
  {#if item.graphic?.type === 'dot'}
    <span class="pdot" style={`--c:${item.graphic.color};width:${item.graphic.size ?? 8}px;height:${item.graphic.size ?? 8}px;border-radius:50%`}></span>
  {:else if item.graphic?.type === 'avatar'}
    <span class="av sm" style={`--c:${item.graphic.color}`}>{item.graphic.initials}</span>
  {:else if item.graphic?.type === 'workspace'}
    {@html wsLogo({ name: item.graphic.name, c: item.graphic.color, brand: item.graphic.brand }, item.graphic.size ?? 20)}
  {:else if item.graphic?.type === 'status'}
    {@html stIcon(item.graphic.value, item.graphic.size ?? 14)}
  {:else if item.graphic?.type === 'priority'}
    {@html prIcon(item.graphic.value, item.graphic.size ?? 14)}
  {:else if item.icon}
    {@html ic(item.icon, item.iconSize ?? 15)}
  {/if}
  {item.label}
  {#if item.trailing}<span class="r">{item.trailing}</span>{/if}
  {#if item.shortcut}<span class="r"><kbd>{item.shortcut}</kbd></span>{/if}
  {#if item.checked}<span class="ck">{@html ic('check', 14)}</span>{/if}
{/snippet}

{#snippet elementContent(item: DropdownElement)}
  {#if item.graphic?.type === 'dot'}
    <span class="pdot" style={`--c:${item.graphic.color};width:${item.graphic.size ?? 8}px;height:${item.graphic.size ?? 8}px;border-radius:50%`}></span>
  {:else if item.graphic?.type === 'avatar'}
    <span class="av sm" style={`--c:${item.graphic.color}`}>{item.graphic.initials}</span>
  {:else if item.graphic?.type === 'workspace'}
    {@html wsLogo({ name: item.graphic.name, c: item.graphic.color, brand: item.graphic.brand }, item.graphic.size ?? 20)}
  {:else if item.graphic?.type === 'status'}
    {@html stIcon(item.graphic.value, item.graphic.size ?? 14)}
  {:else if item.graphic?.type === 'priority'}
    {@html prIcon(item.graphic.value, item.graphic.size ?? 14)}
  {:else if item.icon}
    {@html ic(item.icon, item.iconSize ?? 14)}
  {/if}
  {item.text}
  {#each item.items ?? [] as child (child.id)}
    <DropdownItemView item={child} {onAction} />
  {/each}
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
    onclick={event => activate(item, event)}
  >
    {@render itemContent(item)}
  </button>
{:else if item.type === 'link' && (item.external || item.disabled)}
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
{:else if item.type === 'link'}
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
{:else if item.tag === 'input'}
  <input
    {...item.attributes}
    id={item.elementId}
    class={item.className}
    type={item.elementType ?? 'text'}
    aria-label={item.ariaLabel}
    placeholder={item.placeholder}
    value={item.value}
    checked={item.checked}
    disabled={item.disabled}
    style={item.style}
    onclick={event => dispatchElementEvent('click', item, event)}
    oninput={event => dispatchElementEvent('input', item, event)}
    onchange={event => dispatchElementEvent('change', item, event)}
    onkeydown={event => dispatchElementEvent('keydown', item, event)}
    onfocus={event => dispatchElementEvent('focus', item, event)}
    onblur={event => dispatchElementEvent('blur', item, event)}
    onpointerdown={event => dispatchElementEvent('pointerdown', item, event)}
  />
{:else if item.tag === 'textarea'}
  <textarea
    {...item.attributes}
    id={item.elementId}
    class={item.className}
    aria-label={item.ariaLabel}
    placeholder={item.placeholder}
    value={item.value}
    disabled={item.disabled}
    style={item.style}
    oninput={event => dispatchElementEvent('input', item, event)}
    onchange={event => dispatchElementEvent('change', item, event)}
    onkeydown={event => dispatchElementEvent('keydown', item, event)}
    onfocus={event => dispatchElementEvent('focus', item, event)}
    onblur={event => dispatchElementEvent('blur', item, event)}
  ></textarea>
{:else if item.tag === 'select'}
  <Select
    {...item.attributes}
    id={item.elementId}
    class={item.className}
    aria-label={item.ariaLabel}
    value={String(item.value ?? '')}
    onchange={event => dispatchElementEvent('change', item, event)}
  >
    {#each item.items ?? [] as child (child.id)}
      <DropdownItemView item={child} {onAction} />
    {/each}
  </Select>
{:else if item.tag === 'option'}
  <option
    {...item.attributes}
    id={item.elementId}
    class={item.className}
    value={item.value}
    selected={item.selected}
    disabled={item.disabled}
    style={item.style}
  >
    {@render elementContent(item)}
  </option>
{:else}
  <svelte:element
    this={item.tag}
    {...item.attributes}
    id={item.elementId}
    class={item.className}
    role={item.role}
    aria-label={item.ariaLabel}
    aria-checked={item.ariaChecked}
    aria-expanded={item.ariaExpanded}
    aria-pressed={item.ariaPressed}
    title={item.title}
    placeholder={item.placeholder}
    value={item.value}
    style={item.style}
    onclick={(event: Event) => dispatchElementEvent('click', item, event)}
    onchange={(event: Event) => dispatchElementEvent('change', item, event)}
    onkeydown={(event: Event) => dispatchElementEvent('keydown', item, event)}
    onfocus={(event: Event) => dispatchElementEvent('focus', item, event)}
    onblur={(event: Event) => dispatchElementEvent('blur', item, event)}
    onpointerdown={(event: Event) => dispatchElementEvent('pointerdown', item, event)}
  >
    {@render elementContent(item)}
  </svelte:element>
{/if}
