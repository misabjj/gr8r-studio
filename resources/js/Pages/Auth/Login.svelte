<script>
  import Button from "../../Components/Button.svelte";
  import ErrorMessage from "../../Components/ErrorMessage.svelte";
  import Alert from "../../Components/Alert.svelte";
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import InputPassword from "../../Components/InputPassword.svelte";
  import { useForm } from '@inertiajs/svelte';
  import { register } from '../../routes';
  import { store as loginRoute } from '../../routes/login';
  import { request as passwordRequest } from '../../routes/password';
  import { LOGO } from '@/src/core/icons.js';
  import AuthSocialButtons from '../../Components/AuthSocialButtons.svelte';
  import AuthLayout from '../../Components/AuthLayout.svelte';

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

<AuthLayout actionLabel="Sign up" actionHref={register.url()}>
    <form class="auth-card" onsubmit={submit} novalidate>
      <div style="display:flex;justify-content:center">{@html LOGO(30)}</div>
      <h1>Welcome back</h1>
      <p class="sub">Sign in to Gr8r Studio</p>
      {#if status}<Alert variant="ok" icon="circle-check">{status}</Alert>{/if}
      {#if form.errors.login}<Alert variant="danger" icon="circle-alert">{form.errors.login}</Alert>{/if}
      <div class="field">
        <Label for="l-email">Email</Label>
        <Input size="large" error={!!form.errors.email} id="l-email" type="email" bind:value={form.email} autocomplete="email" />
        {#if form.errors.email}<ErrorMessage message={form.errors.email} />{/if}
      </div>
      <div class="field">
        <div class="row"><Label for="l-pw">Password</Label><span class="sp"></span><a class="link" href={passwordRequest.url()}>Forgot password?</a></div>
        <InputPassword size="large" error={!!form.errors.password} id="l-pw" bind:value={form.password} autocomplete="current-password" revealable />
        {#if form.errors.password}<ErrorMessage message={form.errors.password} />{/if}
      </div>
      <label class="row" style="font-size:13px;gap:8px;cursor:pointer"><input type="checkbox" class="check" bind:checked={form.remember} />Remember me for 30 days</label>
      <Button variant="primary" size="lg" block loading={form.processing} disabled={form.processing}>{form.processing ? 'Signing in…' : 'Sign in'}</Button>
      <div class="or">or</div>
      <AuthSocialButtons />
      <p class="auth-foot">Don't have an account? <a class="link" href={register.url()}>Sign up</a></p>
    </form>
</AuthLayout>
