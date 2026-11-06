<script lang="ts">
  import Button from "../../Components/Button.svelte";
  import ErrorMessage from "../../Components/ErrorMessage.svelte";
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import InputPassword from "../../Components/InputPassword.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { store as registerRoute } from '../../routes/register';
  import { login } from '../../routes';
  import { LOGO } from '../../../../src/core/icons.js';
  import AuthSocialButtons from '../../Components/AuthSocialButtons.svelte';
  import AuthLayout from '../../Components/AuthLayout.svelte';
  import OrDivider from "../../Components/OrDivider.svelte";

  const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

  function submit(event: SubmitEvent): void {
    event.preventDefault();
    form.transform(data => ({ ...data, name: data.name.trim(), email: data.email.trim(), password_confirmation: data.password })).post(registerRoute().url);
  }
</script>

<svelte:head>
  <title>Create account</title>
</svelte:head>

<AuthLayout actionLabel="Log in" actionHref={login.url()}>
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Create your account</h1>
      <p class="sub">Free for up to 3 members. No credit card required.</p>
      <AuthSocialButtons />
      <OrDivider>or sign up with email</OrDivider>
      <div class="field">
        <Label for="r-name">Full name</Label>
        <Input size="large" error={!!form.errors.name} id="r-name" type="text" bind:value={form.name} autocomplete="name" maxlength={255} required />
        {#if form.errors.name}<ErrorMessage message={form.errors.name} />{/if}
      </div>
      <div class="field">
        <Label for="r-email">Work email</Label>
        <Input size="large" error={!!form.errors.email} id="r-email" type="email" bind:value={form.email} autocomplete="email" required />
        {#if form.errors.email}<ErrorMessage message={form.errors.email} />{/if}
      </div>
      <div class="field">
        <Label for="s-pw">Password</Label>
        <InputPassword size="large" strengthMeter error={!!form.errors.password} id="s-pw" bind:value={form.password} autocomplete="new-password" required />
        {#if form.errors.password}<ErrorMessage message={form.errors.password} />{:else}<span class="hint">At least 10 characters with a number or symbol.</span>{/if}
      </div>
      <Button variant="primary" size="lg" block loading={form.processing} disabled={form.processing}>{form.processing ? 'Creating account…' : 'Create account'}</Button>
      <p class="hint" style="text-align:center">By creating an account you agree to the Terms and Privacy Policy.</p>
      <p class="auth-foot">Already have an account? <a class="link" href={login.url()}>Sign in</a></p>
    </form>
</AuthLayout>
