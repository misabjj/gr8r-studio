<script>
  import Label from "../../Components/Label.svelte";
  import Input from "../../Components/Input.svelte";
  import { fade, scale } from 'svelte/transition';
  import { onMount, tick } from 'svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { show as memberShow } from '../../routes/members';
  import { index as tasksIndex } from '../../routes/tasks';
  import { consumeNavigationIntent, setNavigationIntent } from '../../lib/navigation-intents.js';
  import { show as teamShow } from '../../routes/teams';
  import TeamEditDialog from '../../Components/TeamEditDialog.svelte';
  import { ago, minsAgo, uid } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { ROLES } from '@/src/core/constants.js';
  import { D, save } from '@/src/core/store.js';

  let { workspaceData = {}, teams = [] } = $props();
  const page = usePage();
  let query = $state('');
  let role = $state('all');
  let tab = $state('members');
  let roleMenuMemberId = $state(null);
  let roleMenuPosition = $state({ left: 8, top: 8 });
  let memberMenuId = $state(null);
  let memberMenuPosition = $state({ left: 8, top: 8 });
  let roleOverrides = $state(Object.fromEntries(D().members.map(member => [member.id, member.role])));
  let localOnlyMembers = $state(D().members.filter(member => !(workspaceData.members || []).some(sourceMember => sourceMember.id === member.id)));
  let removedMemberIds = $state([]);
  let removeCandidate = $state(null);
  let inviteOpen = $state(false);
  let inviteDraft = $state('');
  let inviteRole = $state('Member');
  let inviteTeam = $state('');
  let inviteError = $state('');
  let inviteBusy = $state(false);
  let inviteEmailInput = $state(null);
  let teamDialogOpen = $state(false);

  $effect(() => {
    const localMemberIds = new Set(D().members.map(member => member.id));
    removedMemberIds = (workspaceData.members || []).filter(member => !localMemberIds.has(member.id)).map(member => member.id);
  });

  let members = $derived([...(workspaceData.members || []), ...localOnlyMembers].filter(member => !removedMemberIds.includes(member.id)));
  let projects = $derived(workspaceData.projects || []);
  let tasks = $derived(workspaceData.tasks || []);
  let teamById = $derived(new Map(teams.map(team => [team.id, team])));
  let isAdmin = $derived(['Owner', 'Admin'].includes(page.props.auth?.user?.role));
  let filteredMembers = $derived(members.filter(member =>
    (role === 'all' || memberRole(member) === role)
      && (!query.trim() || member.name.toLowerCase().includes(query.trim().toLowerCase()) || member.email.toLowerCase().includes(query.trim().toLowerCase())),
  ));

  const permissions = [
    ['View projects & tasks', 1, 1, 1, 1],
    ['Comment on tasks', 1, 1, 1, 1],
    ['Create and edit tasks', 1, 1, 1, 0],
    ['Create projects', 1, 1, 1, 0],
    ['Invite members', 1, 1, 0, 0],
    ['Manage roles', 1, 1, 0, 0],
    ['Workspace settings', 1, 1, 0, 0],
    ['Billing', 1, 0, 0, 0],
    ['Delete workspace', 1, 0, 0, 0],
  ];

  function assignTaskToMember(memberId) {
    memberMenuId = null;
    setNavigationIntent({ type: 'create-task', assigneeSourceKey: memberId });
    router.visit(tasksIndex().url);
  }

  async function openInviteDialog() {
    inviteDraft = '';
    inviteError = '';
    inviteRole = 'Member';
    inviteTeam = String(teams[0]?.id || '');
    inviteOpen = true;
    await tick();
    inviteEmailInput?.focus();
  }

  function closeInviteDialog() {
    inviteOpen = false;
    inviteError = '';
  }

  function showToast(message) {
    const host = document.querySelector('#toasts');
    if (!host) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    toast.textContent = message;
    host.append(toast);
    window.setTimeout(() => toast.remove(), 3000);
  }

  async function copyInviteLink() {
    const url = `https://gr8rstudio.com/join/${workspaceData.ws?.url || 'gr8r'}-7f3k2`;
    try {
      await navigator.clipboard.writeText(url);
      showToast('Invite link copied');
    } catch {
      showToast('Could not copy invite link');
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
      const newMembers = [];
      for (const email of emails) {
        if (D().members.some(member => member.email === email)) continue;
        const member = {
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
        };
        D().members.push(member);
        newMembers.push(member);
      }
      save();
      localOnlyMembers = [...localOnlyMembers, ...newMembers];
      inviteBusy = false;
      inviteOpen = false;
      showToast(`${emails.length} invitation${emails.length > 1 ? 's' : ''} sent`);
    }, 600);
  }

  function initials(name) {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function memberRole(member) {
    return roleOverrides[member.id] || member.role;
  }

  function openCreateTeamDialog() {
    tab = 'teams';
    teamDialogOpen = true;
  }

  onMount(() => {
    if (consumeNavigationIntent('create-team')) openCreateTeamDialog();
    const requestedTab = new URL(page.url, window.location.origin).searchParams.get('tab');
    if (requestedTab === 'teams') tab = 'teams';

    const onPointerDown = event => {
      if (!event.target.closest('.member-role-control')) roleMenuMemberId = null;
      if (!event.target.closest('.member-options-control')) memberMenuId = null;
    };
    const onKeyDown = event => {
      if (event.key === 'Escape') {
        roleMenuMemberId = null;
        memberMenuId = null;
        if (inviteOpen) closeInviteDialog();
      }
    };
    window.addEventListener('gr8r:create-team', openCreateTeamDialog);
    document.addEventListener('pointerdown', onPointerDown, true);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('pointerdown', onPointerDown, true);
      document.removeEventListener('keydown', onKeyDown);
      window.removeEventListener('gr8r:create-team', openCreateTeamDialog);
    };
  });

  async function toggleRoleMenu(event, member) {
    if (roleMenuMemberId === member.id) {
      roleMenuMemberId = null;
      return;
    }

    const trigger = event.currentTarget.getBoundingClientRect();
    roleMenuMemberId = member.id;
    roleMenuPosition = { left: trigger.left, top: trigger.bottom + 4 };
    await tick();
    const menu = document.querySelector('#member-role-menu');
    if (!menu) return;
    const bounds = menu.getBoundingClientRect();
    const left = bounds.right > innerWidth - 8 ? Math.max(8, trigger.right - bounds.width) : trigger.left;
    const top = bounds.bottom > innerHeight - 8 ? Math.max(8, trigger.top - 4 - bounds.height) : trigger.bottom + 4;
    roleMenuPosition = { left, top };
  }

  async function toggleMemberMenu(event, member) {
    if (memberMenuId === member.id) {
      memberMenuId = null;
      return;
    }

    const trigger = event.currentTarget.getBoundingClientRect();
    memberMenuId = member.id;
    memberMenuPosition = { left: trigger.left, top: trigger.bottom + 4 };
    await tick();
    const menu = document.querySelector('#member-options-menu');
    if (!menu) return;
    const bounds = menu.getBoundingClientRect();
    const left = bounds.right > innerWidth - 8 ? Math.max(8, trigger.right - bounds.width) : trigger.left;
    const top = bounds.bottom > innerHeight - 8 ? Math.max(8, trigger.top - 4 - bounds.height) : trigger.bottom + 4;
    memberMenuPosition = { left, top };
  }

  function chooseRole(member, selectedRole) {
    const localMember = D().members.find(item => item.id === member.id);
    if (localMember) {
      localMember.role = selectedRole;
      save();
    }
    roleOverrides = { ...roleOverrides, [member.id]: selectedRole };
    roleMenuMemberId = null;
  }

  function requestRemove(member) {
    removeCandidate = member;
    memberMenuId = null;
  }

  function confirmRemoveMember() {
    if (!removeCandidate) return;
    const memberId = removeCandidate.id;
    D().members = D().members.filter(member => member.id !== memberId);
    D().tasks.forEach(task => {
      if (task.assignee === memberId) task.assignee = null;
    });
    D().projects.forEach(project => {
      project.members = project.members.filter(id => id !== memberId);
    });
    save();
    removedMemberIds = [...removedMemberIds, memberId];
    removeCandidate = null;
  }

  function resendInvite(member) {
    memberMenuId = null;
    const host = document.querySelector('#toasts');
    if (!host) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    toast.textContent = `Invite resent to ${member.email}`;
    host.append(toast);
    window.setTimeout(() => toast.remove(), 3000);
  }

  async function copyEmail(member) {
    await navigator.clipboard.writeText(member.email);
    memberMenuId = null;
  }

  function activeProjects(memberId) {
    return projects.filter(project => !project.archived && project.status !== 'complete' && project.members?.includes(memberId)).length;
  }

  function openTasks(memberId) {
    return tasks.filter(task => !task.archived && task.assignee === memberId && task.status !== 'done').length;
  }

  function lastActive(member) {
    if (member.last == null) return '—';
    if (member.last < 5) return 'Online';
    return ago(minsAgo(member.last));
  }

  function handleTeamCreated() {
    teamDialogOpen = false;
    router.reload({ preserveScroll: true, preserveState: true });
  }
</script>

<svelte:head><title>Members · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page wide" style="max-width:1200px">
    <div class="ph">
      <div><h1>Members</h1><p>{members.length} people in {workspaceData.ws?.name || 'your workspace'} · {members.filter(member => member.status === 'invited').length} pending</p></div>
      <div class="acts"><button class="btn btn-primary" type="button" onclick={openInviteDialog}>{@html ic('user-plus', 14)}Invite member</button></div>
    </div>
    <div class="tabs" style="margin-bottom:14px">
      {#each [['members', 'Members'], ['teams', 'Teams'], ['roles', 'Roles & permissions']] as item}
        <button type="button" class="tab" class:on={tab === item[0]} onclick={() => tab = item[0]}>{item[1]}</button>
      {/each}
    </div>

    {#if tab === 'members'}
      <div class="row" style="margin-bottom:12px;gap:8px;flex-wrap:wrap">
        <div class="inwrap">{@html ic('search', 13)}<Input class="search-sm" bind:value={query} placeholder="Search by name or email" aria-label="Search members" /></div>
        <div class="seg">
          {#each ['all', ...ROLES] as item}
            <button type="button" class:on={role === item} onclick={() => role = item}>{item === 'all' ? 'All' : item}</button>
          {/each}
        </div>
      </div>
      <div class="panel" style="overflow-x:auto">
        {#if filteredMembers.length}
          <table class="perm-t" style="min-width:880px">
            <thead><tr><th style="padding-left:14px">Member</th><th style="text-align:left">Role</th><th style="text-align:left">Team</th><th>Active projects</th><th>Tasks</th><th style="text-align:left">Last active</th><th style="text-align:left">Status</th><th></th></tr></thead>
            <tbody>
              {#each filteredMembers as member (member.id)}
                {@const team = teamById.get(member.team)}
                <tr>
                  <td style="padding-left:14px"><Link class="row" style="gap:10px;text-align:left" href={memberShow(member.id).url}><span class="av md" style={`--c:${member.c}`} aria-label={member.name}>{initials(member.name)}</span><span class="col"><b style="font-weight:500">{member.name}{#if member.id === workspaceData.me}<span class="faint" style="font-weight:400"> (you)</span>{/if}</b><span class="faint" style="font-size:12px">{member.email}</span></span></Link></td>
                  <td style="text-align:left">
                    {#if isAdmin && memberRole(member) !== 'Owner'}
                      <div class="member-role-control">
                        <button class="pillbtn bordered" type="button" aria-haspopup="menu" aria-expanded={roleMenuMemberId === member.id} onclick={event => toggleRoleMenu(event, member)}>{memberRole(member)}{@html ic('chevron-down', 12)}</button>
                        {#if roleMenuMemberId === member.id}
                          <div class="pop floating enter" id="member-role-menu" role="menu" aria-label={`Role for ${member.name}`} style={`left:${roleMenuPosition.left}px;top:${roleMenuPosition.top}px;width:300px`}>
                            <div class="mh">Role for {member.name}</div>
                            {#each ['Admin', 'Member', 'Guest'] as option}
                              <button class="mi" type="button" role="menuitemradio" aria-checked={memberRole(member) === option} style="height:auto;padding:7px 8px;align-items:flex-start" onclick={() => chooseRole(member, option)}><span class="grow" style="white-space:normal"><span style="font-weight:500">{option}</span><span class="faint" style="font-size:11.5px;display:block">{option === 'Admin' ? 'Manage members, settings, and all projects' : option === 'Member' ? 'Create projects and work on tasks' : 'Only sees projects they are invited to'}</span></span>{#if memberRole(member) === option}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
                            {/each}
                          </div>
                        {/if}
                      </div>
                    {:else}
                      <span class="pillbtn">{#if memberRole(member) === 'Owner'}{@html ic('crown', 13)}{/if}{memberRole(member)}</span>
                    {/if}
                  </td>
                  <td style="text-align:left"><span class="row">{@html ic(team?.icon || 'users', 13)}{team?.name || '—'}</span></td>
                  <td class="num">{activeProjects(member.id)}</td><td class="num">{openTasks(member.id)}</td>
                  <td style="text-align:left" class="muted">{#if member.last != null && member.last < 5}<span style="color:var(--green)">Online</span>{:else}{lastActive(member)}{/if}</td>
                  <td style="text-align:left">{#if member.status === 'invited'}<span class="badge amber"><span class="dot"></span>Invited</span>{:else if member.status === 'deactivated'}<span class="badge gray">Deactivated</span>{:else}<span class="badge green"><span class="dot"></span>Active</span>{/if}</td>
                  <td><div class="member-options-control"><button class="ibtn ibtn-sm" type="button" aria-label={`Options for ${member.name}`} aria-haspopup="menu" aria-expanded={memberMenuId === member.id} onclick={event => toggleMemberMenu(event, member)}>{@html ic('ellipsis', 14)}</button>
                    {#if memberMenuId === member.id}
                      <div class="pop floating enter" id="member-options-menu" role="menu" aria-label={`Options for ${member.name}`} style={`left:${memberMenuPosition.left}px;top:${memberMenuPosition.top}px;width:220px`}>
                        <Link class="mi" role="menuitem" href={memberShow(member.id).url} onclick={() => memberMenuId = null}>{@html ic('user', 15)}View profile</Link>
                        <button class="mi" type="button" role="menuitem" onclick={() => assignTaskToMember(member.id)}>{@html ic('plus', 15)}Assign a task</button>
                        <button class="mi" type="button" role="menuitem" onclick={() => copyEmail(member)}>{@html ic('mail', 15)}Copy email</button>
                        {#if isAdmin && memberRole(member) !== 'Owner' && member.id !== workspaceData.me}
                          <div class="msep"></div>
                          <button class="mi" type="button" role="menuitem" onclick={event => { memberMenuId = null; toggleRoleMenu(event, member); }}>{@html ic('shield', 15)}Change role…</button>
                          {#if member.status === 'invited'}<button class="mi" type="button" role="menuitem" onclick={() => resendInvite(member)}>{@html ic('send', 15)}Resend invite</button>{/if}
                          <div class="msep"></div>
                          <button class="mi danger" type="button" role="menuitem" onclick={() => requestRemove(member)}>{@html ic('user-minus', 15)}Remove from workspace</button>
                        {/if}
                      </div>
                    {/if}
                  </div></td>
                </tr>
              {/each}
            </tbody>
          </table>
        {:else}
          <div class="empty sm"><span class="empty-ic">{@html ic('search-x', 20)}</span><h2>No results found</h2><p>No members match that search.</p></div>
        {/if}
      </div>
    {:else if tab === 'teams'}
      <div class="row" style="margin-bottom:12px"><span class="muted" style="font-size:13px">{teams.length} teams</span><span class="sp"></span><button class="btn btn-secondary btn-sm" type="button" onclick={() => teamDialogOpen = true}>{@html ic('plus', 13)}New team</button></div>
      <div class="pgrid">
        {#each teams as team (team.id)}
          {@const teamMembers = members.filter(member => member.team === team.id)}
          {@const teamProjects = projects.filter(project => project.team === team.id && !project.archived)}
          <Link class="pcard" href={teamShow(team.id).url}><div class="row"><span class="picon" style={`--c:${team.color}`}>{@html ic(team.icon, 15)}</span><b style="font-weight:600;font-size:14px">{team.name}</b></div><div class="desc">{team.description}</div><div class="foot"><span class="row" style="gap:4px">{@html ic('users', 12)}{teamMembers.length} members</span><span class="row" style="gap:4px">{@html ic('folder', 12)}{teamProjects.length} projects</span><span class="sp"></span><span class="avs">{#each teamMembers.slice(0, 4) as member (member.id)}<span class="av sm" style={`--c:${member.c}`} aria-label={member.name}>{initials(member.name)}</span>{/each}{#if teamMembers.length > 4}<span class="av sm more" style="--c:var(--gray)">+{teamMembers.length - 4}</span>{/if}</span></div></Link>
        {/each}
      </div>
    {:else}
      <div class="panel" style="overflow-x:auto"><table class="perm-t" style="min-width:560px"><thead><tr><th style="padding-left:14px">Permission</th>{#each ROLES as item}<th>{item}</th>{/each}</tr></thead><tbody>{#each permissions as permission}<tr><td style="padding-left:14px">{permission[0]}</td>{#each permission.slice(1) as allowed}{#if allowed}<td><span style="color:var(--green);display:inline-flex" aria-label="Allowed">{@html ic('check', 15)}</span></td>{:else}<td><span class="faint" style="display:inline-flex" aria-label="Not allowed">{@html ic('minus', 15)}</span></td>{/if}{/each}</tr>{/each}</tbody></table></div>
    {/if}
  </div>
</AppLayout>

{#if removeCandidate}
  <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (removeCandidate = null)}>
    <div class="modal sm" transition:scale={{ duration: 260, start: 0.97 }} role="alertdialog" aria-modal="true" aria-labelledby="remove-member-title" aria-describedby="remove-member-description">
      <div class="modal-h"><div><h2 id="remove-member-title">Remove {removeCandidate.name}?</h2><p id="remove-member-description" class="muted" style="font-size:12.5px;margin:4px 0 0">{removeCandidate.name} will lose access to {workspaceData.ws?.name || 'this workspace'} immediately. Tasks assigned to them will become unassigned.</p></div><button class="ibtn ibtn-sm" type="button" aria-label="Close" onclick={() => removeCandidate = null}>{@html ic('x', 16)}</button></div>
      <div class="modal-f"><span class="sp"></span><button class="btn btn-secondary" type="button" onclick={() => removeCandidate = null}>Cancel</button><button class="btn btn-danger" type="button" onclick={confirmRemoveMember}>Remove member</button></div>
    </div>
  </div>
{/if}

{#if teamDialogOpen}
  <TeamEditDialog
    isCreate={true}
    team={{ id: '', name: '', description: '', icon: 'users', color: '#23918A' }}
    {members}
    {teams}
    projects={[]}
    onClose={() => teamDialogOpen = false}
    onCreated={handleTeamCreated}
  />
{/if}

{#if inviteOpen}
  <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && closeInviteDialog()}>
    <div class="modal" transition:scale={{ duration: 260, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="members-invite-title">
      <div class="modal-h"><div><h2 id="members-invite-title">Invite to {workspaceData.ws?.name || 'your workspace'}</h2><div class="muted" style="font-size:12.5px;margin-top:2px">Invited people get an email with a link to join.</div></div><button class="ibtn ibtn-sm" type="button" aria-label="Close" onclick={closeInviteDialog}>{@html ic('x', 16)}</button></div>
      <form class="modal-b" onsubmit={submitInvites}>
        <div class="field"><Label for="members-invite-emails">Email addresses</Label><textarea class="textarea" class:is-error={!!inviteError} id="members-invite-emails" bind:this={inviteEmailInput} bind:value={inviteDraft} placeholder="name@company.com, another@company.com" rows="3" aria-invalid={!!inviteError} aria-describedby="members-invite-hint"></textarea>{#if inviteError}<span class="err" role="alert">{@html ic('circle-alert', 12)}{inviteError}</span>{:else}<span class="hint" id="members-invite-hint">Separate multiple addresses with commas.</span>{/if}</div>
        <div class="row" style="gap:12px"><div class="field grow"><Label for="members-invite-role">Role</Label><select class="select" id="members-invite-role" bind:value={inviteRole}><option>Member</option><option>Admin</option><option>Guest</option></select></div><div class="field grow"><Label for="members-invite-team">Team</Label><select class="select" id="members-invite-team" bind:value={inviteTeam}>{#each teams as team (team.id)}<option value={String(team.id)}>{team.name}</option>{/each}</select></div></div>
        <div class="alert info">{@html ic('info', 14)}<span><b>Guests</b> can only see projects they're added to and can't create projects.</span></div>
      </form>
      <div class="modal-f"><button class="btn btn-ghost" type="button" onclick={copyInviteLink}>{@html ic('link', 14)}Copy invite link</button><span class="sp"></span><button class="btn btn-secondary" type="button" onclick={closeInviteDialog}>Cancel</button><button class="btn btn-primary" type="button" disabled={inviteBusy} onclick={submitInvites}>{inviteBusy ? 'Sending…' : 'Send invites'}</button></div>
    </div>
  </div>
{/if}
