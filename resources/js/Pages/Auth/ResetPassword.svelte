<script>
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import PasswordInput from "../../Components/PasswordInput.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { update as resetPassword } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';
  import AuthThemeToggle from '../../Components/AuthThemeToggle.svelte';
  import { strengthMeter } from '../../lib/password.js';

  let { email: initialEmail = '', token } = $props();
  const form = useForm(() => ({ token, email: initialEmail, password: '', password_confirmation: '' }));

  async function submit(event) {
    event.preventDefault();
    form.transform(data => ({ ...data, email: data.email.trim() })).post(resetPassword().url);
  }
</script>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row"><AuthThemeToggle /></div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Choose a new password</h1>
      <p class="sub">for {initialEmail}</p>
        <input type="hidden" name="token" value={form.token} />
      <div class="field">
        <Label for="r-pw">New password</Label>
        <PasswordInput size="large" error={!!form.errors.password} id="r-pw" bind:value={form.password} autocomplete="new-password" />
        {@html strengthMeter(form.password)}
        {#if form.errors.password}<span class="err">{@html ic('circle-alert', 12)}{form.errors.password}</span>{/if}
      </div>
      <div class="field">
        <Label for="r-pw2">Confirm password</Label>
        <PasswordInput size="large" error={!!form.errors.password_confirmation} id="r-pw2" bind:value={form.password_confirmation} autocomplete="new-password" />
        {#if form.errors.password_confirmation}<span class="err">{@html ic('circle-alert', 12)}{form.errors.password_confirmation}</span>{/if}
        {#if form.errors.email}<span class="err">{@html ic('circle-alert', 12)}{form.errors.email}</span>{/if}
      </div>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={form.processing} disabled={form.processing}>{form.processing ? 'Resetting…' : 'Reset password'}</button>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
