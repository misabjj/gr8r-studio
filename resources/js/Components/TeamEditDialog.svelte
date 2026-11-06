<script>
  import { fade } from 'svelte/transition';
  import { tick } from 'svelte';
  import { useHttpRequest } from '../lib/http.js';
  import { destroy as deleteTeam, store as createTeam, update as updateTeam } from '../routes/teams';
  import { PCOLORS, PICONS } from '@/src/core/constants.js';
  import { ic } from '@/src/core/icons.js';
  import { toast } from '@/src/ui/toast.js';

  const httpRequest = useHttpRequest();
  let { team, members = [], teams = [], projects = [], isCreate = false, onClose, onUpdated, onDeleted, onCreated } = $props();
  let name = $state('');
  let description = $state('');
  let icon = $state('');
  let color = $state('slate');
  let selectedMemberIds = $state([]);
  let errorMessage = $state('');
  let busy = $state(false);
  let confirmDelete = $state(false);
  let nameInput = $state(null);

  $effect(() => {
    name = isCreate ? '' : team.name;
    description = isCreate ? '' : team.description || '';
    icon = isCreate ? 'users' : team.icon;
    color = Object.entries(PCOLORS).find(([, hex]) => hex === team.color)?.[0] || 'slate';
    selectedMemberIds = members
      .filter(member => member.team === team.id && member.database_id && member.status !== 'deactivated')
      .map(member => String(member.database_id));
    if (isCreate) tick().then(() => nameInput?.focus());
  });

  function initials(memberName) {
    return memberName.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function toggleMember(memberId) {
    const id = String(memberId);
    selectedMemberIds = selectedMemberIds.includes(id)
      ? selectedMemberIds.filter(selectedId => selectedId !== id)
      : [...selectedMemberIds, id];
  }

  async function submit(event) {
    event.preventDefault();
    if (!name.trim()) {
      errorMessage = 'Give the team a name';
      await tick();
      nameInput?.focus();
      return;
    }

    busy = true;
    errorMessage = '';

    try {
      const teamRoute = isCreate ? createTeam() : updateTeam(team.id);
      const savedTeam = await httpRequest(teamRoute.url, isCreate ? 'POST' : 'PATCH', {
        name: name.trim(),
        description: description.trim() || null,
        icon,
        color,
        member_ids: selectedMemberIds.map(Number),
      });

      if (isCreate) {
        onCreated(savedTeam, selectedMemberIds);
        toast(`Created ${savedTeam.name}`);
      } else {
        onUpdated(savedTeam, selectedMemberIds);
        toast(`Saved ${savedTeam.name}`);
      }
    } catch (error) {
      errorMessage = error.errors?.name?.[0] || error.message;
    } finally {
      busy = false;
    }
  }

  async function removeTeam() {
    busy = true;

    try {
      await httpRequest(deleteTeam(team.id).url, 'DELETE', {});
      onDeleted();
      toast(`Deleted ${team.name}`);
    } catch (error) {
      errorMessage = error.message;
      confirmDelete = false;
    } finally {
      busy = false;
    }
  }
</script>

<div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && onClose()}>
  {#if confirmDelete}
    <div class="modal sm" role="dialog" aria-modal="true" aria-labelledby="team-delete-title" tabindex="-1">
      <div class="modal-b" style="padding-top:18px;gap:10px"><div class="row" style="gap:12px;align-items:flex-start"><span class="ftype" style="--c:var(--red);width:34px;height:34px;border-radius:9px">{@html ic('users', 17)}</span><div class="grow"><h2 id="team-delete-title" style="font-size:15px;font-weight:600;margin:4px 0 6px">Delete {team.name}?</h2><p class="muted" style="margin:0;font-size:13px;line-height:1.55">{members.length ? `${members.length} member${members.length === 1 ? '' : 's'} will have no team` : 'The team has no members'}{projects.length ? ` and ${projects.length} project${projects.length === 1 ? '' : 's'} will need a new team` : ''}. People and projects are not deleted.</p></div></div>{#if errorMessage}<div class="err" role="alert">{@html ic('circle-alert', 12)}{errorMessage}</div>{/if}</div>
      <div class="modal-f"><span class="sp"></span><button class="btn btn-secondary" type="button" onclick={() => confirmDelete = false}>Cancel</button><button class="btn btn-danger" type="button" disabled={busy} onclick={removeTeam}>{busy ? 'Deleting…' : 'Delete team'}</button></div>
    </div>
  {:else}
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="team-edit-title" tabindex="-1">
    <div class="modal-h"><div><h2 id="team-edit-title">{isCreate ? 'New team' : 'Edit team'}</h2>{#if isCreate}<div class="muted" style="font-size:12.5px;margin-top:2px">Teams group the people who work on projects together.</div>{/if}</div><button class="ibtn ibtn-sm" type="button" aria-label="Close" onclick={onClose}>{@html ic('x', 16)}</button></div>
    <form id="team-edit-form" class="modal-b" onsubmit={submit}>
      <div class="row" style="gap:10px;align-items:flex-start">
        <span class="picon lg" style={`--c:${PCOLORS[color]};margin-top:22px`} aria-hidden="true">{@html ic(icon, 18)}</span>
        <div class="field grow"><label class="label" for="team-edit-name">Team name</label><input class={`input ${errorMessage ? 'is-error' : ''}`} id="team-edit-name" bind:this={nameInput} bind:value={name} placeholder="e.g. Growth" aria-invalid={!!errorMessage} aria-describedby={errorMessage ? 'team-edit-name-error' : undefined} maxlength="120" />{#if errorMessage}<span id="team-edit-name-error" class="err" role="alert">{@html ic('circle-alert', 12)}{errorMessage}</span>{/if}</div>
      </div>
      <div class="field"><label class="label" for="team-edit-description">What does this team own?</label><input class="input" id="team-edit-description" bind:value={description} placeholder="e.g. Acquisition experiments and lifecycle email" maxlength="500" /></div>
      <div class="row" style="gap:16px;align-items:flex-start;flex-wrap:wrap">
        <div class="field" style="flex:1;min-width:220px"><span class="label" id="team-edit-icon-label">Icon</span><div class="iconpick" role="radiogroup" aria-labelledby="team-edit-icon-label">{#each PICONS as item (item)}<button type="button" role="radio" aria-checked={icon === item} class:on={icon === item} aria-label={item} onclick={() => icon = item}>{@html ic(item, 15)}</button>{/each}</div></div>
        <div class="field"><span class="label" id="team-edit-color-label">Color</span><div class="swatches" role="radiogroup" aria-labelledby="team-edit-color-label" style="max-width:140px">{#each Object.entries(PCOLORS) as [key, hex] (key)}<button type="button" role="radio" aria-checked={color === key} class={`sw ${color === key ? 'on' : ''}`} style={`--c:${hex};width:22px;height:22px`} aria-label={key} onclick={() => color = key}>{#if color === key}{@html ic('check', 12)}{/if}</button>{/each}</div></div>
      </div>
      <fieldset class="field" style="border:0;padding:0;margin:0"><legend class="label" style="margin-bottom:6px">Members <span class="faint" style="font-weight:400">· {selectedMemberIds.length} selected</span></legend>
        <div class="panel" style="max-height:220px;overflow-y:auto;padding:4px">{#each members.filter(member => member.status !== 'deactivated') as member (member.database_id || member.id)}<label class="mi" style="cursor:pointer;min-height:36px"><input type="checkbox" class="check" checked={member.database_id != null && selectedMemberIds.includes(String(member.database_id))} disabled={!member.database_id} onchange={() => toggleMember(member.database_id)} /><span class="av sm" style={`--c:${member.c}`} aria-hidden="true">{initials(member.name)}</span><span class="grow trunc">{member.name}</span><span class="r">{member.team && member.team !== team.id ? teams.find(other => other.id === member.team)?.name || '' : ''}</span></label>{/each}</div>
        <span class="hint">Everyone belongs to one primary team. Adding someone here moves them from their current team.</span>
      </fieldset>
    </form>
    <div class="modal-f">{#if !isCreate}<button class="btn btn-danger-ghost" type="button" onclick={() => { errorMessage = ''; confirmDelete = true; }}>{@html ic('trash-2', 14)}Delete team</button>{/if}<span class="sp"></span><button class="btn btn-secondary" type="button" onclick={onClose}>Cancel</button><button class="btn btn-primary" type="submit" form="team-edit-form" disabled={busy}>{busy ? (isCreate ? 'Creating…' : 'Saving…') : isCreate ? 'Create team' : 'Save changes'}</button></div>
  </div>
  {/if}
</div>
