<script>
  import { Link } from '@inertiajs/svelte';
  import { register } from '../../routes';
  import { store as loginRoute } from '../../routes/login';
  import { request as passwordRequest } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';

  let { status = null } = $props();
  let email = $state('hello@gr8rstudio.com');
  let password = $state('');
  let remember = $state(true);
  let showPassword = $state(false);
  let busy = $state(false);
  let errors = $state({});

  async function submit(event) {
    event.preventDefault();
    errors = {};
    busy = true;

    try {
      const response = await fetch(loginRoute().url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ email: email.trim(), password, remember }),
      });

      if (response.ok && response.redirected) {
        window.location.assign(response.url);
        return;
      }

      const result = await response.json();
      errors = result.errors || { login: result.message || 'We could not sign you in. Try again.' };
    } catch {
      errors = { login: 'We could not reach the server. Check your connection and try again.' };
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head>
  <title>Gr8r Studio</title>
</svelte:head>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row">
      <button class="ibtn" type="button" aria-label="Toggle theme">{@html ic('moon', 16)}</button>
    </div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Welcome back</h1>
      <p class="sub">Sign in to Gr8r Studio</p>
      {#if status}<div class="alert ok" role="status">{@html ic('circle-check', 15)}<span>{status}</span></div>{/if}
      {#if errors.login}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.login}</span></div>{/if}
      <div class="field">
        <label class="label" for="l-email">Email</label>
        <input class="input input-lg" class:is-error={!!errors.email} id="l-email" type="email" bind:value={email} autocomplete="email" aria-invalid={!!errors.email} />
        {#if errors.email}<span class="err">{@html ic('circle-alert', 12)}{errors.email[0]}</span>{/if}
      </div>
      <div class="field">
        <div class="row"><label class="label" for="l-pw">Password</label><span class="sp"></span><Link class="link" href={passwordRequest().url}>Forgot password?</Link></div>
        <div class="inwrap">
          <input class="input input-lg" id="l-pw" type={showPassword ? 'text' : 'password'} bind:value={password} autocomplete="current-password" style="padding-right:38px" aria-invalid={!!errors.password} />
          <button type="button" class="ibtn ibtn-sm" style="position:absolute;right:5px" onclick={() => showPassword = !showPassword} aria-label={showPassword ? 'Hide password' : 'Show password'}>{@html ic(showPassword ? 'eye-off' : 'eye', 15)}</button>
        </div>
        {#if errors.password}<span class="err">{@html ic('circle-alert', 12)}{errors.password[0]}</span>{/if}
      </div>
      <label class="row" style="font-size:13px;gap:8px;cursor:pointer"><input type="checkbox" class="check" bind:checked={remember} />Remember me for 30 days</label>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={busy} disabled={busy}>{busy ? 'Signing in…' : 'Sign in'}</button>
      <p class="sub" style="margin:0">New to Gr8r Studio? <Link class="link" href={register().url}>Create an account</Link></p>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
