<script>
  import { store as registerRoute } from '../../routes/register';
  import { login } from '../../routes';
  import { ic, LOGO } from '@/src/core/icons.js';
  import AuthSocialButtons from '../../Components/AuthSocialButtons.svelte';

  let name = $state('');
  let email = $state('');
  let password = $state('');
  let busy = $state(false);
  let errors = $state({});

  function strengthMeter(value) {
    let score = 0;
    if (value.length >= 8) score++;
    if (value.length >= 12) score++;
    if (/[0-9]/.test(value) && /[a-zA-Z]/.test(value)) score++;
    if (/[^a-zA-Z0-9]/.test(value)) score++;
    const color = ['var(--red)', 'var(--red)', 'var(--amber)', 'var(--green)', 'var(--green)'][score];
    const label = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'][score];
    return value
      ? `<div class="row" style="gap:8px"><div class="strength grow" aria-hidden="true">${[0, 1, 2, 3].map(index => `<i class="${index < score ? 'on' : ''}" style="--c:${color}"></i>`).join('')}</div><span style="font-size:11.5px;color:${color};width:60px;text-align:right">${label}</span></div>`
      : '';
  }

  async function submit(event) {
    event.preventDefault();
    errors = {};
    busy = true;

    try {
      const response = await fetch(registerRoute().url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
          name: name.trim(),
          email: email.trim(),
          password,
          password_confirmation: password,
        }),
      });

      if (response.ok && response.redirected) {
        window.location.assign(response.url);
        return;
      }

      const result = await response.json();
      errors = result.errors || { register: result.message || 'We could not create your account. Try again.' };
    } catch {
      errors = { register: 'We could not reach the server. Check your connection and try again.' };
    } finally {
      busy = false;
    }
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
      <button class="ibtn" type="button" aria-label="Toggle theme">{@html ic('moon', 16)}</button>
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
        <label class="label" for="r-name">Full name</label>
        <input class="input input-lg" class:is-error={!!errors.name} id="r-name" type="text" bind:value={name} autocomplete="name" maxlength="255" required aria-invalid={!!errors.name} />
        {#if errors.name}<span class="err">{@html ic('circle-alert', 12)}{errors.name[0]}</span>{/if}
      </div>
      <div class="field">
        <label class="label" for="r-email">Work email</label>
        <input class="input input-lg" class:is-error={!!errors.email} id="r-email" type="email" bind:value={email} autocomplete="email" required aria-invalid={!!errors.email} />
        {#if errors.email}<span class="err">{@html ic('circle-alert', 12)}{errors.email[0]}</span>{/if}
      </div>
      <div class="field">
        <label class="label" for="r-password">Password</label>
        <input class="input input-lg" class:is-error={!!errors.password} id="r-password" type="password" bind:value={password} autocomplete="new-password" required aria-invalid={!!errors.password} />
        {@html strengthMeter(password)}
        {#if errors.password}<span class="err">{@html ic('circle-alert', 12)}{errors.password[0]}</span>{:else}<span class="hint">At least 10 characters with a number or symbol.</span>{/if}
      </div>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={busy} disabled={busy}>{busy ? 'Creating account…' : 'Create account'}</button>
      <p class="hint" style="text-align:center">By creating an account you agree to the Terms and Privacy Policy.</p>
      <p class="auth-foot">Already have an account? <a class="link" href={login.url()}>Sign in</a></p>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
