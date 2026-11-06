<script lang="ts">
  import { tick } from 'svelte';
  import type { HTMLInputAttributes } from 'svelte/elements';
  import Input from './Input.svelte';
  import { ic } from '../../../src/core/icons.js';
  import { strengthMeter as getStrengthMeter } from '../lib/password.js';

  type Size = 'default' | 'large';

  type Props = Omit<HTMLInputAttributes, 'class' | 'size' | 'type' | 'value'> & {
    class?: string;
    error?: boolean;
    size?: Size;
    revealable?: boolean;
    strengthMeter?: boolean;
    value?: string;
  };

  let { class: className = '', error = false, size = 'default', revealable = false, strengthMeter = false, value = $bindable(''), ...attributes }: Props = $props();
  let visible = $state(false);
  let inputElement: HTMLInputElement | null = $state(null);

  async function toggleVisibility() {
    visible = !visible;
    await tick();
    inputElement?.focus();
  }
</script>

{#if revealable}
  <div class="inwrap">
    <Input {...attributes} class={`auth-password-input ${className}`} size={size} error={error} type={visible ? 'text' : 'password'} style="padding-right:38px" bind:value bind:element={inputElement} />
    <button type="button" class="ibtn ibtn-sm" style="position:absolute;right:5px" onclick={toggleVisibility} aria-label={visible ? 'Hide password' : 'Show password'}>{@html ic(visible ? 'eye-off' : 'eye', 15)}</button>
  </div>
{:else}
  <Input {...attributes} class={className} size={size} error={error} type="password" bind:value />
{/if}
{#if strengthMeter}{@html getStrengthMeter(value)}{/if}
