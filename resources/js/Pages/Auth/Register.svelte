<script>
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { store as registerRoute } from '../../routes/register';
  import { login } from '../../routes';
  import { ic, LOGO } from '@/src/core/icons.js';
  import AuthSocialButtons from '../../Components/AuthSocialButtons.svelte';
  import AuthThemeToggle from '../../Components/AuthThemeToggle.svelte';
  import { strengthMeter } from '../../lib/password.js';

  const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

  async function submit(event) {
    event.preventDefault();
    form.transform(data => ({ ...data, name: data.name.trim(), email: data.email.trim(), password_confirmation: data.password })).post(registerRoute().url);
  }
</script>

<svelte:head>
  <title>Create account · Gr8r Studio</title>
</svelte:head>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row">
      <a class="btn btn-ghost btn-sm" href={login.url()}>Log in</a>
      <AuthThemeToggle />
    </div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Create your account</h1>
      <p class="sub">Free for up to 3 members. No credit card required.</p>
      {#if errors.register}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.register}</span></div>{/if}
      <AuthSocialButtons />
      <div class="or">or sign up with email</div>
      <div class="field">
        <Label for="r-name">Full name</Label>
        <Input variant="large" error={!!form.errors.name} id="r-name" type="text" bind:value={form.name} autocomplete="name" maxlength="255" required aria-invalid={!!form.errors.name} />
        {#if form.errors.name}<span class="err">{@html ic('circle-alert', 12)}{form.errors.name}</span>{/if}
      </div>
      <div class="field">
        <Label for="r-email">Work email</Label>
        <Input variant="large" error={!!form.errors.email} id="r-email" type="email" bind:value={form.email} autocomplete="email" required aria-invalid={!!form.errors.email} />
        {#if form.errors.email}<span class="err">{@html ic('circle-alert', 12)}{form.errors.email}</span>{/if}
      </div>
      <div class="field">
        <Label for="s-pw">Password</Label>
        <Input variant="large" error={!!form.errors.password} id="s-pw" type="password" bind:value={form.password} autocomplete="new-password" required aria-invalid={!!form.errors.password} />
        {@html strengthMeter(form.password)}
        {#if form.errors.password}<span class="err">{@html ic('circle-alert', 12)}{form.errors.password}</span>{:else}<span class="hint">At least 10 characters with a number or symbol.</span>{/if}
      </div>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={form.processing} disabled={form.processing}>{form.processing ? 'Creating account…' : 'Create account'}</button>
      <p class="hint" style="text-align:center">By creating an account you agree to the Terms and Privacy Policy.</p>
      <p class="auth-foot">Already have an account? <a class="link" href={login.url()}>Sign in</a></p>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
