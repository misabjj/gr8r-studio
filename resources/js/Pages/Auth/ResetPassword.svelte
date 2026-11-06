<script lang="ts">
  import Button from "../../Components/Button.svelte";
  import ErrorMessage from "../../Components/ErrorMessage.svelte";
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import InputPassword from "../../Components/InputPassword.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { update as resetPassword } from '../../routes/password';
  import { ic, LOGO } from '../../../../src/core/icons.js';
  import AuthLayout from '../../Components/AuthLayout.svelte';

  let { email: initialEmail = '', token }: { email?: string; token: string } = $props();
  const form = useForm(() => ({ token, email: initialEmail, password: '', password_confirmation: '' }));

  function submit(event: SubmitEvent): void {
    event.preventDefault();
    form.transform(data => ({ ...data, email: data.email.trim() })).post(resetPassword().url);
  }
</script>

<AuthLayout>
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Choose a new password</h1>
      <p class="sub">for {initialEmail}</p>
        <input type="hidden" name="token" value={form.token} />
      <div class="field">
        <Label for="r-pw">New password</Label>
        <InputPassword size="large" strengthMeter error={!!form.errors.password} id="r-pw" bind:value={form.password} autocomplete="new-password" />
        {#if form.errors.password}<ErrorMessage message={form.errors.password} />{/if}
      </div>
      <div class="field">
        <Label for="r-pw2">Confirm password</Label>
        <InputPassword size="large" error={!!form.errors.password_confirmation} id="r-pw2" bind:value={form.password_confirmation} autocomplete="new-password" />
        {#if form.errors.password_confirmation}<ErrorMessage message={form.errors.password_confirmation} />{/if}
        {#if form.errors.email}<ErrorMessage message={form.errors.email} />{/if}
      </div>
      <Button variant="primary" size="lg" block loading={form.processing} disabled={form.processing}>{form.processing ? 'Resetting…' : 'Reset password'}</Button>
    </form>
</AuthLayout>
