<script>
  import Button from "../../Components/Button.svelte";
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import { onMount, tick } from 'svelte';
  import { fade } from 'svelte/transition';
  import { Link, router } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import * as settingsRoutes from '../../routes/settings';
  import { index as membersIndex, show as memberShow } from '../../routes/members';
  import { index as tasksIndex } from '../../routes/tasks';
  import { index as projectsIndex } from '../../routes/projects';
  import { show as teamShow } from '../../routes/teams';
  import { setNavigationIntent } from '../../lib/navigation-intents.js';
  import { seed } from '@/src/data/seed.js';
  import { S, save, teamsList } from '@/src/core/store.js';
  import { applyPrefs } from '@/src/core/theme.js';
  import { uid } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';

  let { section: routeSection = 'appearance' } = $props();
  let section = $state('appearance');
  const settingsUrl = (key) => {
    const routeKey = key.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
    const route = key === '2fa' ? settingsRoutes.method2fa : settingsRoutes[routeKey];
    return route.url();
  };
  let settingsModule = $state(null);
  let inviteOpen = $state(false);
  let inviteDraft = $state('');
  let inviteRole = $state('Member');
  let inviteTeam = $state('');
  let inviteError = $state('');
  let inviteBusy = $state(false);
  let inviteEmailInput = $state(null);
  let memberPopup = $state(null);
  let removeCandidate = $state(null);
  let confirmation = $state(null);
  let confirmationText = $state('');
  let navigation = $derived(settingsModule?.SET_NAV || []);
  let revision = $state(0);
  let content = $derived.by(() => {
    revision;
    if (!settingsModule) return '';
    const item = navigation.flatMap(([, items]) => items).find(([key]) => key === section);
    return settingsModule.settingsBody(section, item?.[1] || 'Appearance');
  });

  $effect(() => {
    section = routeSection;
  });

  onMount(() => {
    applyPrefs();
    const root = document.querySelector('#settings-body');
    root?.addEventListener('click', handleClick);
    root?.addEventListener('change', handleChange);
    root?.addEventListener('input', handleInput);
    root?.addEventListener('submit', handleSubmit);
    const onPointerDown = event => {
      if (!event.target.closest('.settings-popup, [data-a="pop"], [data-a="ctxBtn"]')) memberPopup = null;
    };
    const onKeyDown = event => {
      if (event.key === 'Escape') {
        memberPopup = null;
        inviteOpen = false;
        removeCandidate = null;
        confirmation = null;
      }
    };
    window.addEventListener('pointerdown', onPointerDown);
    window.addEventListener('keydown', onKeyDown);
    import('@/src/pages/settings.js').then(module => {
      settingsModule = module;
    });

    return () => {
      root?.removeEventListener('click', handleClick);
      root?.removeEventListener('change', handleChange);
      root?.removeEventListener('input', handleInput);
      root?.removeEventListener('submit', handleSubmit);
      window.removeEventListener('pointerdown', onPointerDown);
      window.removeEventListener('keydown', onKeyDown);
    };
  });

  function refresh() {
    revision += 1;
  }

  function showToast(message, kind = 'ok', duration = 3000) {
    const host = document.querySelector('#toasts');
    if (!host) return;
    const item = document.createElement('div');
    item.className = 'toast';
    item.setAttribute('role', 'status');
    const icon = document.createElement('span');
    icon.innerHTML = ic(kind === 'err' ? 'circle-alert' : kind === 'info' ? 'info' : 'circle-check', 15);
    const text = document.createElement('span');
    text.className = 'grow';
    text.textContent = message;
    item.append(icon, text);
    host.append(item);
    window.setTimeout(() => item.remove(), duration);
  }

  function setPreference(key, value) {
    S.prefs[key] = key === 'weekStart' ? Number(value) : value;
    save();
    applyPrefs();
    refresh();
  }

  async function openInviteDialog() {
    inviteDraft = '';
    inviteRole = 'Member';
    inviteTeam = String(teamsList()[0]?.id || '');
    inviteError = '';
    inviteOpen = true;
    await tick();
    inviteEmailInput?.focus();
  }

  function closeInviteDialog() {
    inviteOpen = false;
    inviteError = '';
  }

  async function copyInviteLink() {
    const link = `https://gr8rstudio.com/join/${S.data.ws.url}-7f3k2`;
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
        if (S.data.members.some(member => member.email === email)) continue;
        S.data.members.push({
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
      refresh();
      inviteBusy = false;
      closeInviteDialog();
      showToast(`${emails.length} invitation${emails.length > 1 ? 's' : ''} sent`);
    }, 600);
  }

  function openMemberPopup(control, kind) {
    const bounds = control.getBoundingClientRect();
    memberPopup = {
      kind,
      memberId: control.dataset.id,
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - 208)),
      top: Math.max(8, bounds.bottom + 4),
    };
  }

  function changeMemberRole(role) {
    const member = S.data.members.find(item => item.id === memberPopup?.memberId);
    if (!member) return;
    member.role = role;
    member.title = role;
    save();
    memberPopup = null;
    refresh();
    showToast(`${member.name} is now ${role === 'Admin' ? 'an' : 'a'} ${role}`);
  }

  function runMemberAction(action) {
    const popup = memberPopup;
    const member = S.data.members.find(item => item.id === popup?.memberId);
    if (!member) return;
    memberPopup = null;

    if (action === 'view') {
      router.visit(memberShow(member.id).url);
    } else if (action === 'assign') {
      setNavigationIntent({ type: 'create-task', assigneeSourceKey: member.id });
      router.visit(tasksIndex().url);
    } else if (action === 'copy') {
      navigator.clipboard.writeText(member.email).then(() => showToast('Email copied')).catch(() => showToast(member.email, 'info', 6000));
    } else if (action === 'role') {
      memberPopup = { ...popup, kind: 'role' };
    } else if (action === 'resend') {
      showToast(`Invite resent to ${member.email}`);
    } else if (action === 'remove') {
      removeCandidate = member;
    }
  }

  function removeMember() {
    const member = removeCandidate;
    if (!member) return;
    S.data.members = S.data.members.filter(item => item.id !== member.id);
    S.data.tasks.forEach(task => { if (task.assignee === member.id) task.assignee = null; });
    S.data.projects.forEach(project => { project.members = project.members.filter(id => id !== member.id); });
    save();
    removeCandidate = null;
    refresh();
    showToast(`Removed ${member.name}`);
  }

  function askConfirmation(details) {
    confirmationText = '';
    confirmation = details;
  }

  function confirmAction() {
    if (!confirmation || (confirmation.typeName && confirmationText !== confirmation.typeName)) return;
    const { action, value } = confirmation;
    confirmation = null;
    confirmationText = '';

    if (action === 'revokeAll') {
      S.data.sessions = S.data.sessions.filter(session => session.cur);
      save();
      refresh();
      showToast('Signed out of other sessions');
    } else if (action === 'disable2fa') {
      S.data.tfa = false;
      save();
      refresh();
      showToast('Two-factor authentication turned off');
    } else if (action === 'changePlan') {
      S.data.plan = value;
      save();
      refresh();
      showToast(`You're now on the ${value} plan`);
    } else if (action === 'deleteWorkspace') {
      S.data = seed();
      S.views = {};
      save();
      refresh();
      showToast('Workspace deleted. Demo data was restored for this prototype.', 'info', 5000);
    }
  }

  function handleClick(event) {
    const control = event.target.closest('[data-a]');
    if (!control) return;

    const { a: action, k: key, v: value } = control.dataset;

    if (action === 'invite') {
      void openInviteDialog();
    } else if (action === 'go' && control.dataset.r === 'members') {
      router.visit(membersIndex().url);
    } else if (action === 'go' && control.dataset.r === 'team') {
      router.visit(teamShow(control.dataset.id).url);
    } else if (action === 'go' && control.dataset.r === 'archive') {
      router.visit(projectsIndex().url);
    } else if (action === 'go' && control.dataset.r === 'settings') {
      router.visit(settingsUrl(control.dataset.sec || 'appearance'));
    } else if (action === 'pop' && control.dataset.pop === 'role') {
      openMemberPopup(control, 'role');
    } else if (action === 'ctxBtn' && control.dataset.ctx === 'member') {
      openMemberPopup(control, 'member');
    } else if (action === 'newTeam') {
      setNavigationIntent({ type: 'create-team' });
      router.visit(membersIndex({ query: { tab: 'teams' } }).url);
    } else if (action === 'setup2fa') {
      S.ui.tfaSetup = true;
      S.ui.errors = {};
      refresh();
    } else if (action === 'cancel2fa') {
      S.ui.tfaSetup = false;
      refresh();
    } else if (action === 'disable2fa') {
      askConfirmation({ action: 'disable2fa', title: 'Turn off two-factor authentication?', body: 'Your account will be protected by your password only.', ok: 'Turn off', icon: 'shield-off', danger: true });
    } else if (action === 'revokeSession') {
      S.data.sessions = S.data.sessions.filter(session => session.id !== control.dataset.id);
      save();
      refresh();
      showToast('Session signed out');
    } else if (action === 'revokeAll') {
      askConfirmation({ action: 'revokeAll', title: 'Sign out of all other sessions?', body: "You'll stay signed in on this device. Other devices will need to sign in again.", ok: 'Sign out others', icon: 'log-out', danger: true });
    } else if (action === 'changePlan') {
      const plan = value;
      const price = { Free: 0, Team: 12, Business: 24 }[plan];
      askConfirmation({ action: 'changePlan', value: plan, title: `${plan === 'Free' ? 'Downgrade' : 'Upgrade'} to ${plan}?`, body: `${S.data.members.length} members × $${price}/month = $${S.data.members.length * price}/month, prorated from today and billed to Visa ending 4242.`, ok: plan === 'Free' ? 'Downgrade' : `Upgrade to ${plan}`, icon: 'gem' });
    } else if (action === 'delWorkspace') {
      askConfirmation({ action: 'deleteWorkspace', title: `Delete ${S.data.ws.name}?`, body: 'Every project, task, file, and comment in this workspace will be permanently deleted for all members.', ok: 'Delete workspace', icon: 'trash-2', danger: true, typeName: S.data.ws.name });
    } else if (action === 'setTheme') {
      setPreference('theme', value);
    } else if (action === 'setPref') {
      setPreference(key, value);
    } else if (action === 'wsColor') {
      S.data.ws.c = value;
      save();
      refresh();
    } else if (action === 'avColor') {
      const member = S.data.members.find(item => item.id === S.data.me);
      if (member) member.c = value;
      save();
      refresh();
    } else if (action === 'toastInfo') {
      showToast(control.dataset.msg || '', 'info');
    }
  }

  function handleChange(event) {
    const control = event.target;
    const { in: inputAction, k: key } = control.dataset;

    if (inputAction === 'prefSel') {
      setPreference(key, control.value);
      showToast('Preference saved', 'ok', 1500);
    } else if (inputAction === 'prefToggle') {
      S.prefs[key] = control.checked;
      save();
      applyPrefs();
    } else if (inputAction === 'npToggle') {
      S.data.notifPrefs[key] = control.checked;
      save();
      showToast('Notification preferences saved', 'ok', 1500);
    }
  }

  function handleInput(event) {
    const control = event.target;
    if (control.dataset.in !== 'pwStrength') return;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = settingsModule?.strengthMeter(control.value) || '';
    const current = control.parentElement.querySelector('.strength');
    if (current) current.parentElement.replaceWith(wrapper.firstElementChild || document.createTextNode(''));
    else if (wrapper.firstElementChild) control.after(wrapper.firstElementChild);
  }

  function handleSubmit(event) {
    const form = event.target.closest('form[data-submit]');
    if (!form) return;
    event.preventDefault();

    if (form.dataset.submit === 'saveWorkspace') {
      const name = form.querySelector('#ws-name').value.trim();
      const url = form.querySelector('#ws-url').value.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-');
      if (!name) {
        showToast("Workspace name can't be empty", 'err');
        return;
      }
      const previousName = S.data.ws.name;
      S.data.ws.name = name;
      S.data.ws.url = url || S.data.ws.url;
      const workspace = S.data.workspaces.find(item => item.name === previousName);
      if (workspace) workspace.name = name;
      save();
      refresh();
      showToast('Workspace settings saved');
    } else if (form.dataset.submit === 'saveProfile') {
      const name = form.querySelector('#pf-name').value.trim();
      if (!name) {
        S.ui.errors.pname2 = 'Enter your name';
        refresh();
        return;
      }
      S.ui.errors.pname2 = null;
      const member = S.data.members.find(item => item.id === S.data.me);
      S.prefs.name = name;
      S.prefs.title = form.querySelector('#pf-title').value.trim();
      if (member) {
        member.name = name;
        member.title = S.prefs.title;
      }
      save();
      refresh();
      showToast('Profile updated');
    } else if (form.dataset.submit === 'savePassword') {
      const currentPassword = form.querySelector('#pw-cur').value;
      const newPassword = form.querySelector('#pw-new').value;
      const confirmation = form.querySelector('#pw-conf').value;
      S.ui.errors = {};
      if (!currentPassword) S.ui.errors.pwCur = 'Enter your current password';
      if (newPassword.length < 10 || !/[0-9\W]/.test(newPassword)) S.ui.errors.pwNew = 'Use at least 10 characters, including a number or symbol';
      if (newPassword !== confirmation) S.ui.errors.pwConf = "Passwords don't match";
      if (Object.keys(S.ui.errors).length) {
        refresh();
        return;
      }
      S.ui.pwDone = true;
      S.ui.pwNew = '';
      refresh();
      showToast('Password updated');
    } else if (form.dataset.submit === 'verify2fa') {
      const code = form.querySelector('#tfa-code').value.trim();
      if (!/^\d{6}$/.test(code)) {
        S.ui.errors.tfa = 'Enter the 6-digit code from your app';
        refresh();
        return;
      }
      S.ui.errors = {};
      S.data.tfa = true;
      S.ui.tfaSetup = false;
      save();
      refresh();
      showToast('Two-factor authentication enabled');
    }
  }
</script>

<svelte:head><title>Settings · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="set">
    <nav class="set-nav" aria-label="Settings">
      {#each navigation as [group, items]}
        <div class="gh">{group}</div>
        {#each items as [key, label, icon]}
          <Link class={`sitem ${section === key ? 'on' : ''}`} href={settingsUrl(key)}>{@html ic(icon, 15)}<span>{label}</span></Link>
        {/each}
      {/each}
    </nav>
    <div class="set-body" id="settings-body">
      <div class="set-in">{@html content}</div>
    </div>
  </div>
  {#if memberPopup}
    {@const member = S.data.members.find(item => item.id === memberPopup.memberId)}
    {#if member}
      <div class="pop settings-popup enter" role="menu" tabindex="-1" style={`left:${memberPopup.left}px;top:${memberPopup.top}px;width:200px`} onpointerdown={event => event.stopPropagation()}>
        {#if memberPopup.kind === 'role'}
          {#each ['Admin', 'Member', 'Guest'] as role}
            <button class="mi" type="button" role="menuitemradio" aria-checked={member.role === role} onclick={() => changeMemberRole(role)}>{@html ic('shield', 14)}<span>{role}</span>{#if member.role === role}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
          {/each}
        {:else}
          <button class="mi" type="button" role="menuitem" onclick={() => runMemberAction('view')}>{@html ic('user', 15)}View profile</button>
          <button class="mi" type="button" role="menuitem" onclick={() => runMemberAction('assign')}>{@html ic('plus', 15)}Assign a task</button>
          <button class="mi" type="button" role="menuitem" onclick={() => runMemberAction('copy')}>{@html ic('mail', 15)}Copy email</button>
          {#if member.role !== 'Owner' && member.id !== S.data.me}
            <button class="mi" type="button" role="menuitem" onclick={() => runMemberAction('role')}>{@html ic('shield', 15)}Change role…</button>
            {#if member.status === 'invited'}<button class="mi" type="button" role="menuitem" onclick={() => runMemberAction('resend')}>{@html ic('send', 15)}Resend invite</button>{/if}
            <div class="msep"></div>
            <button class="mi danger" type="button" role="menuitem" onclick={() => runMemberAction('remove')}>{@html ic('user-minus', 15)}Remove from workspace</button>
          {/if}
        {/if}
      </div>
    {/if}
  {/if}
  {#if inviteOpen}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && closeInviteDialog()}>
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="settings-invite-title" tabindex="-1">
        <div class="modal-h"><div><h2 id="settings-invite-title">Invite to {S.data.ws.name}</h2><div class="muted" style="font-size:12.5px;margin-top:2px">Invited people get an email with a link to join.</div></div><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={closeInviteDialog}>{@html ic('x', 16)}</button></div>
        <form id="settings-invite-form" class="modal-b" data-submit="submitInvite" onsubmit={submitInvites}>
          <div class="field"><Label for="settings-invite-emails">Email addresses</Label><textarea class={`textarea ${inviteError ? 'is-error' : ''}`} id="settings-invite-emails" bind:this={inviteEmailInput} bind:value={inviteDraft} placeholder="name@company.com, another@company.com" rows="3" aria-invalid={!!inviteError} aria-describedby={inviteError ? 'settings-invite-error' : 'settings-invite-hint'}></textarea>{#if inviteError}<span class="err" id="settings-invite-error" role="alert">{@html ic('circle-alert', 12)}{inviteError}</span>{:else}<span class="hint" id="settings-invite-hint">Separate multiple addresses with commas.</span>{/if}</div>
          <div class="row" style="gap:12px"><div class="field grow"><Label for="settings-invite-role">Role</Label><select class="select" id="settings-invite-role" bind:value={inviteRole}><option>Member</option><option>Admin</option><option>Guest</option></select></div><div class="field grow"><Label for="settings-invite-team">Team</Label><select class="select" id="settings-invite-team" bind:value={inviteTeam}>{#each teamsList() as team (team.id)}<option value={team.id}>{team.name}</option>{/each}</select></div></div>
          <div class="alert info">{@html ic('info', 14)}<span><b>Guests</b> can only see projects they're added to and can't create projects.</span></div>
        </form>
        <div class="modal-f"><Button type="button" variant="ghost" size="md" onclick={copyInviteLink}>{@html ic('link', 14)}Copy invite link</Button><span class="sp"></span><Button type="button" variant="secondary" size="md" onclick={closeInviteDialog}>Cancel</Button><Button type="submit" variant="primary" size="md" form="settings-invite-form" disabled={inviteBusy}>{inviteBusy ? 'Sending…' : 'Send invites'}</Button></div>
      </div>
    </div>
  {/if}
  {#if removeCandidate}
    {@const openTaskCount = S.data.tasks.filter(task => task.assignee === removeCandidate.id && task.status !== 'done').length}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (removeCandidate = null)}>
      <div class="modal sm" role="alertdialog" aria-modal="true" aria-labelledby="settings-remove-member-title" tabindex="-1">
        <div class="modal-b" style="padding-top:18px;gap:10px"><div class="row" style="gap:12px;align-items:flex-start"><span class="ftype" style="--c:var(--red);width:34px;height:34px;border-radius:9px">{@html ic('user-minus', 17)}</span><div class="grow"><h2 id="settings-remove-member-title" style="font-size:15px;font-weight:600;margin:4px 0 6px">Remove {removeCandidate.name}?</h2><p class="muted" style="margin:0;font-size:13px;line-height:1.55">{removeCandidate.name} will lose access to {S.data.ws.name} immediately.{#if openTaskCount} Their <b>{openTaskCount} open task{openTaskCount > 1 ? 's' : ''}</b> will become unassigned.{/if}</p></div></div></div>
        <div class="modal-f"><span class="sp"></span><Button type="button" variant="secondary" size="md" onclick={() => removeCandidate = null}>Cancel</Button><Button type="button" variant="danger" size="md" onclick={removeMember}>Remove member</Button></div>
      </div>
    </div>
  {/if}
  {#if confirmation}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (confirmation = null)}>
      <div class="modal sm" role="alertdialog" aria-modal="true" aria-labelledby="settings-confirm-title" tabindex="-1">
        <div class="modal-b" style="padding-top:18px;gap:10px"><div class="row" style="gap:12px;align-items:flex-start"><span class="ftype" style={`--c:${confirmation.danger ? 'var(--red)' : 'var(--amber)'};width:34px;height:34px;border-radius:9px`}>{@html ic(confirmation.icon || (confirmation.danger ? 'trash-2' : 'archive'), 17)}</span><div class="grow"><h2 id="settings-confirm-title" style="font-size:15px;font-weight:600;margin:4px 0 6px">{confirmation.title}</h2><p class="muted" style="margin:0;font-size:13px;line-height:1.55">{confirmation.body}</p></div></div>
          {#if confirmation.typeName}<div class="field" style="margin-top:6px"><Label for="settings-confirm-input" style="font-weight:400;color:var(--text-2)">Type <b style="color:var(--text);font-weight:600">{confirmation.typeName}</b> to confirm</Label><Input class="" id="settings-confirm-input" bind:value={confirmationText} autocomplete="off" /></div>{/if}
        </div>
        <div class="modal-f"><span class="sp"></span><Button type="button" variant="secondary" size="md" onclick={() => confirmation = null}>Cancel</Button><Button type="button" variant={confirmation.danger ? 'danger' : 'primary'} size="md" disabled={!!confirmation.typeName && confirmationText !== confirmation.typeName} onclick={confirmAction}>{confirmation.ok}</Button></div>
      </div>
    </div>
  {/if}
</AppLayout>
