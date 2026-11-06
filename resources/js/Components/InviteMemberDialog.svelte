<script>
  import Button from "./Button.svelte";
  import Label from "./Label.svelte";
  import { onMount, tick } from 'svelte';
  import { fade } from 'svelte/transition';
  import { D, save, teamsList } from '@/src/core/store.js';
  import { uid } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';

  let { onClose = () => {} } = $props();
  let emailInput = $state(null);
  let inviteDraft = $state('');
  let inviteRole = $state('Member');
  let inviteTeam = $state(String(teamsList()[0]?.id || ''));
  let inviteError = $state('');
  let inviteBusy = $state(false);

  onMount(async () => {
    await tick();
    emailInput?.focus();
  });

  function showToast(message, kind = 'ok', duration = 3000) {
    const host = document.querySelector('#toasts');
    if (!host) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    toast.textContent = message;
    if (kind === 'info') toast.classList.add('info');
    host.append(toast);
    window.setTimeout(() => toast.remove(), duration);
  }

  async function copyInviteLink() {
    const link = `https://gr8rstudio.com/join/${D().ws.url}-7f3k2`;
    try {
      await navigator.clipboard.writeText(link);
      showToast('Invite link copied');
    } catch {
      showToast(`Invite link: ${link}`, 'info', 6000);
    }
  }

  function submitInvites(event) {
    event.preventDefault();
    const emails = inviteDraft.split(/[\s,;]+/).map(email => email.trim()).filter(Boolean);
    const invalidEmail = emails.find(email => !/^\S+@\S+\.\S+$/.test(email));

    if (!emails.length) {
      inviteError = 'Enter at least one email address';
      return;
    }

    if (invalidEmail) {
      inviteError = `${invalidEmail} isn't a valid email address`;
      return;
    }

    inviteError = '';
    inviteBusy = true;
    window.setTimeout(() => {
      for (const email of emails) {
        if (D().members.some(member => member.email === email)) continue;

        D().members.push({
          id: uid('m'),
          name: email.split('@')[0].replace(/[._-]+/g, ' ').replace(/\b\w/g, character => character.toUpperCase()),
          email,
          role: inviteRole,
          team: inviteTeam,
          title: inviteRole,
          c: '#6B7280',
          status: 'invited',
          last: null,
          tz: '—',
        });
      }

      save();
      inviteBusy = false;
      onClose();
      showToast(`${emails.length} invitation${emails.length > 1 ? 's' : ''} sent`);
    }, 600);
  }
</script>

<svelte:window onkeydown={event => event.key === 'Escape' && onClose()} />

<div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && onClose()}>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="invite-dialog-title" tabindex="-1">
    <div class="modal-h">
      <div>
        <h2 id="invite-dialog-title">Invite to {D().ws.name}</h2>
        <div class="muted" style="font-size:12.5px;margin-top:2px">Invited people get an email with a link to join.</div>
      </div>
      <button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={onClose}>{@html ic('x', 16)}</button>
    </div>
    <form id="invite-dialog-form" class="modal-b" onsubmit={submitInvites}>
      <div class="field">
        <Label for="invite-dialog-emails">Email addresses</Label>
        <textarea bind:this={emailInput} bind:value={inviteDraft} class={`textarea ${inviteError ? 'is-error' : ''}`} id="invite-dialog-emails" placeholder="name@company.com, another@company.com" rows="3" aria-invalid={!!inviteError} aria-describedby={inviteError ? 'invite-dialog-error' : 'invite-dialog-hint'}></textarea>
        {#if inviteError}
          <span class="err" id="invite-dialog-error" role="alert">{@html ic('circle-alert', 12)}{inviteError}</span>
        {:else}
          <span class="hint" id="invite-dialog-hint">Separate multiple addresses with commas.</span>
        {/if}
      </div>
      <div class="row" style="gap:12px">
        <div class="field grow">
          <Label for="invite-dialog-role">Role</Label>
          <select class="select" id="invite-dialog-role" bind:value={inviteRole}><option>Member</option><option>Admin</option><option>Guest</option></select>
        </div>
        <div class="field grow">
          <Label for="invite-dialog-team">Team</Label>
          <select class="select" id="invite-dialog-team" bind:value={inviteTeam}>{#each teamsList() as team (team.id)}<option value={team.id}>{team.name}</option>{/each}</select>
        </div>
      </div>
      <div class="alert info">{@html ic('info', 14)}<span><b>Guests</b> can only see projects they're added to and can't create projects.</span></div>
    </form>
    <div class="modal-f">
      <Button type="button" variant="ghost" size="md" onclick={copyInviteLink}>{@html ic('link', 14)}Copy invite link</Button>
      <span class="sp"></span>
      <Button type="button" variant="secondary" size="md" onclick={onClose}>Cancel</Button>
      <Button type="submit" variant="primary" size="md" form="invite-dialog-form" disabled={inviteBusy}>{inviteBusy ? 'Sending…' : 'Send invites'}</Button>
    </div>
  </div>
</div>
