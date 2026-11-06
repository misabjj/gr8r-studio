<script>
  import { useForm } from '@inertiajs/svelte';
  import { tick } from 'svelte';
  import { register } from '../../routes';
  import { store as loginRoute } from '../../routes/login';
  import { request as passwordRequest } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';
  import AuthSocialButtons from '../../Components/AuthSocialButtons.svelte';
  import AuthThemeToggle from '../../Components/AuthThemeToggle.svelte';

  let { status = null } = $props();
  let showPassword = $state(false);
  const form = useForm({ email: 'hello@gr8rstudio.com', password: '', remember: true });

  async function togglePasswordVisibility() {
    showPassword = !showPassword;
    await tick();
    document.getElementById('l-pw')?.focus();
  }

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
        <label class="label" for="l-email">Email</label>
        <input class="input input-lg" class:is-error={!!form.errors.email} id="l-email" type="email" bind:value={form.email} autocomplete="email" aria-invalid={!!form.errors.email} />
        {#if form.errors.email}<span class="err">{@html ic('circle-alert', 12)}{form.errors.email}</span>{/if}
      </div>
      <div class="field">
        <div class="row"><label class="label" for="l-pw">Password</label><span class="sp"></span><a class="link" href={passwordRequest.url()}>Forgot password?</a></div>
        <div class="inwrap">
          <input class="input input-lg auth-password-input" id="l-pw" type={showPassword ? 'text' : 'password'} bind:value={form.password} autocomplete="current-password" style="padding-right:38px" aria-invalid={!!form.errors.password} />
          <button type="button" class="ibtn ibtn-sm" style="position:absolute;right:5px" onclick={togglePasswordVisibility} aria-label={showPassword ? 'Hide password' : 'Show password'}>{@html ic(showPassword ? 'eye-off' : 'eye', 15)}</button>
        </div>
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
