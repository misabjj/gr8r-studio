<script>
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import PasswordInput from "../../Components/PasswordInput.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { register } from '../../routes';
  import { store as loginRoute } from '../../routes/login';
  import { request as passwordRequest } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';
  import AuthSocialButtons from '../../Components/AuthSocialButtons.svelte';
  import AuthThemeToggle from '../../Components/AuthThemeToggle.svelte';

  let { status = null } = $props();
  const form = useForm({ email: 'hello@gr8rstudio.com', password: '', remember: true });

  async function submit(event) {
    event.preventDefault();
    form.transform(data => ({ ...data, email: data.email.trim() })).post(loginRoute().url);
  }
</script>

<svelte:head>
  <title>Gr8r Studio</title>
</svelte:head>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row">
      <a class="btn btn-ghost btn-sm" href={register.url()}>Sign up</a>
      <AuthThemeToggle />
    </div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Welcome back</h1>
      <p class="sub">Sign in to Gr8r Studio</p>
      {#if status}<div class="alert ok" role="status">{@html ic('circle-check', 15)}<span>{status}</span></div>{/if}
      {#if form.errors.login}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{form.errors.login}</span></div>{/if}
      <div class="field">
        <Label for="l-email">Email</Label>
        <Input size="large" error={!!form.errors.email} id="l-email" type="email" bind:value={form.email} autocomplete="email" />
        {#if form.errors.email}<span class="err">{@html ic('circle-alert', 12)}{form.errors.email}</span>{/if}
      </div>
      <div class="field">
        <div class="row"><Label for="l-pw">Password</Label><span class="sp"></span><a class="link" href={passwordRequest.url()}>Forgot password?</a></div>
        <PasswordInput size="large" error={!!form.errors.password} id="l-pw" bind:value={form.password} autocomplete="current-password" revealable />
        {#if form.errors.password}<span class="err">{@html ic('circle-alert', 12)}{form.errors.password}</span>{/if}
      </div>
      <label class="row" style="font-size:13px;gap:8px;cursor:pointer"><input type="checkbox" class="check" bind:checked={form.remember} />Remember me for 30 days</label>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={form.processing} disabled={form.processing}>{form.processing ? 'Signing in…' : 'Sign in'}</button>
      <div class="or">or</div>
      <AuthSocialButtons />
      <p class="auth-foot">Don't have an account? <a class="link" href={register.url()}>Sign up</a></p>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
