<script>
  import { onMount } from 'svelte';
  import { router } from '@inertiajs/svelte';
  import { store as storeProject, show as showProject } from '../../routes/projects';
  import { home } from '../../routes';
  import { useHttpRequest } from '../../lib/http.js';
  import { S, save } from '@/src/core/store.js';
  import { ic } from '@/src/core/icons.js';

  const httpRequest = useHttpRequest();
  let authModule = $state(null);
  let templates = $state([]);
  let revision = $state(0);
  let busy = $state(false);
  let errorMessage = $state('');
  let content = $derived.by(() => {
    revision;
    return authModule?.renderOnboarding() || '';
  });

  onMount(() => {
    const requestedStep = Number(new URLSearchParams(window.location.search).get('step'));
    S.ui.onb = Number.isInteger(requestedStep) && requestedStep >= 0 && requestedStep <= 5 ? requestedStep : 0;
    S.ui.auth = 'onboarding';
    S.ui.errors.ws = null;
    const root = document.querySelector('#onboarding-root');
    root?.addEventListener('click', handleClick);
    root?.addEventListener('input', handleInput);
    Promise.all([import('@/src/pages/auth.js'), import('@/src/overlays/modals.js')]).then(([auth, modalModule]) => {
      authModule = auth;
      templates = modalModule.TEMPLATES;
    });
    return () => {
      root?.removeEventListener('click', handleClick);
      root?.removeEventListener('input', handleInput);
    };
  });

  function refresh() {
    revision += 1;
  }

  function showToast(message, kind = 'info') {
    const host = document.querySelector('#toasts');
    if (!host) return;
    const item = document.createElement('div');
    item.className = 'toast';
    item.setAttribute('role', 'status');
    const icon = document.createElement('span');
    icon.innerHTML = ic(kind === 'err' ? 'circle-alert' : kind === 'ok' ? 'circle-check' : 'info', 15);
    const text = document.createElement('span');
    text.className = 'grow';
    text.textContent = message;
    item.append(icon, text);
    host.append(item);
    window.setTimeout(() => item.remove(), 3000);
  }

  function handleInput(event) {
    const element = event.target;
    const onboarding = S.ui.onbData;
    if (element.dataset.in === 'onbWs') {
      onboarding.ws = element.value;
      if (!onboarding.urlTouched) {
        onboarding.url = element.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        const urlInput = document.querySelector('#o-url');
        if (urlInput) urlInput.value = onboarding.url;
      }
      S.ui.errors.ws = null;
    } else if (element.dataset.in === 'onbUrl') {
      onboarding.url = element.value.toLowerCase().replace(/[^a-z0-9-]+/g, '-');
      onboarding.urlTouched = true;
      element.value = onboarding.url;
    } else if (element.dataset.in === 'onbProj') {
      onboarding.proj = element.value;
      onboarding.projTouched = true;
    } else if (element.dataset.in === 'onbInv') {
      const invites = (onboarding.invites || '').split(',');
      invites[Number(element.dataset.i)] = element.value.trim();
      onboarding.invites = invites.join(',');
    }
  }

  function handleClick(event) {
    const control = event.target.closest('[data-a]');
    if (!control) return;
    const { a: action, k: key, v: value } = control.dataset;
    const onboarding = S.ui.onbData;

    if (action === 'onbSet') {
      onboarding[key] = value;
      if (key === 'tmpl') {
        const template = templates.find(item => item.id === value);
        if (template && !onboarding.projTouched) onboarding.proj = template.name === 'Website' ? 'Website Launch' : template.name;
      }
      refresh();
    } else if (action === 'onbBack') {
      S.ui.onb = Math.max(0, S.ui.onb - 1);
      refresh();
    } else if (action === 'onbNext') {
      if (S.ui.onb === 1 && !onboarding.ws.trim()) {
        S.ui.errors.ws = 'Name your workspace to continue';
        refresh();
        return;
      }
      if (S.ui.onb === 3 && !onboarding.proj.trim()) {
        showToast('Give your project a name', 'err');
        return;
      }
      if (S.ui.onb === 4 && control.dataset.skip) onboarding.invites = '';
      if (S.ui.onb === 4 && !control.dataset.skip) {
        const invalidInvite = (onboarding.invites || '').split(',').find(email => email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
        if (invalidInvite) {
          showToast(`${invalidInvite} isn't a valid email address`, 'err');
          return;
        }
      }
      S.ui.onb += 1;
      refresh();
    } else if (action === 'onbExit') {
      S.ui.auth = null;
      router.visit(home().url);
    } else if (action === 'copyInviteLink') {
      navigator.clipboard?.writeText(`${window.location.origin}/register`).then(() => showToast('Invite link copied', 'ok')).catch(() => showToast('Could not copy invite link', 'err'));
    } else if (action === 'onbFinish') {
      finishOnboarding();
    }
  }

  async function finishOnboarding() {
    if (busy) return;
    busy = true;
    errorMessage = '';
    const onboarding = S.ui.onbData;
    const projectName = onboarding.proj.trim() || 'Website Launch';
    const key = projectName.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8) || 'PROJECT';
    try {
      const project = await httpRequest(storeProject().url, 'POST', {
        name: projectName,
        key,
        description: `Created during setup from the ${templates.find(item => item.id === onboarding.tmpl)?.name || 'project'} template.`,
      });
      S.prefs.defaultTab = ({ kanban: 'board', list: 'list', timeline: 'timeline', table: 'table' })[onboarding.team] || 'board';
      save();
      S.ui.auth = null;
      router.visit(showProject(project.id).url);
    } catch (error) {
      errorMessage = error.message || 'We could not finish setting up your workspace.';
      showToast(errorMessage, 'err');
    } finally {
      busy = false;
    }
  }
</script>

<svelte:head><title>Set up your workspace</title></svelte:head>

<div id="onboarding-root" aria-busy={busy}>
  {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}
  {@html content}
</div>
