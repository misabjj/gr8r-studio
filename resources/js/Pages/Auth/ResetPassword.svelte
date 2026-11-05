<script>
  import { update as resetPassword } from '../../routes/password';
  import { ic, LOGO } from '@/src/core/icons.js';

  let { email: initialEmail = '', token } = $props();
  let password = $state('');
  let passwordConfirmation = $state('');
  let busy = $state(false);
  let errors = $state({});

  let strength = $derived.by(() => {
    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/\d/.test(password) && /[a-z]/i.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    const colors = ['var(--red)', 'var(--red)', 'var(--amber)', 'var(--green)', 'var(--green)'];
    const labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'];
    return { score, color: colors[score], label: labels[score] };
  });

  async function submit(event) {
    event.preventDefault();
    busy = true;
    errors = {};

    try {
      const response = await fetch(resetPassword().url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
          token,
          email: initialEmail.trim(),
          password,
          password_confirmation: passwordConfirmation,
        }),
      });

      if (response.ok && response.redirected) {
        window.location.assign(response.url);
        return;
      }

      const result = await response.json();
      errors = result.errors || { form: result.message || 'We could not reset your password. Request a new link and try again.' };
    } catch {
      errors = { form: 'We could not reach the server. Check your connection and try again.' };
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head>
  <title>Reset password · Gr8r Studio</title>
</svelte:head>

<div class="auth">
  <div class="auth-top">
    <span class="brand">{@html LOGO(22)}</span>
    <div class="row"><button class="ibtn" type="button" aria-label="Toggle theme">{@html ic('moon', 16)}</button></div>
  </div>
  <div class="auth-c">
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Choose a new password</h1>
      <p class="sub">for {initialEmail}</p>
      {#if errors.form}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.form}</span></div>{/if}
      {#if errors.email}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errors.email[0]}</span></div>{/if}
      <input type="hidden" name="token" value={token} />
      <div class="field">
        <label class="label" for="rp-password">New password</label>
        <input class="input input-lg" class:is-error={!!errors.password} id="rp-password" type="password" bind:value={password} autocomplete="new-password" minlength="8" required aria-invalid={!!errors.password} />
        {#if password}<div class="row" style="gap:8px"><div class="strength grow" aria-hidden="true">{#each [0, 1, 2, 3] as segment}<i class:on={segment < strength.score} style={`--c:${strength.color}`}></i>{/each}</div><span style={`font-size:11.5px;color:${strength.color};width:60px;text-align:right`}>{strength.label}</span></div>{/if}
        {#if errors.password}<span class="err">{@html ic('circle-alert', 12)}{errors.password[0]}</span>{/if}
      </div>
      <div class="field">
        <label class="label" for="rp-password-confirmation">Confirm password</label>
        <input class="input input-lg" id="rp-password-confirmation" type="password" bind:value={passwordConfirmation} autocomplete="new-password" required />
      </div>
      <button class="btn btn-primary btn-lg btn-block" class:is-loading={busy} disabled={busy}>{busy ? 'Updating password…' : 'Reset password'}</button>
    </form>
  </div>
  <div class="auth-foot" style="padding:18px;font-size:12px"><span class="faint">© 2026 Gr8r Studio · Terms · Privacy</span></div>
</div>
