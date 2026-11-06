<script>
  import Input from "./Input.svelte";
  import { fade } from 'svelte/transition';
  import { cubicOut } from 'svelte/easing';
  import { tick } from 'svelte';
  import { usePage } from '@inertiajs/svelte';
  import { store as taskStore } from '../routes/projects/tasks';
  import { update as taskUpdate } from '../routes/tasks';
  import { useHttpRequest } from '../lib/http.js';
  import { ic } from '@/src/core/icons.js';
  import { S } from '@/src/core/store.js';
  import { LABELS, PCOLORS } from '@/src/core/constants.js';
  import { addD, diffD, fmtDate, iso, MONL, TODAY, WD } from '@/src/core/utils.js';
  import { FT, fileType, fsize, prIcon, stIcon } from '@/src/ui/helpers.js';

  const httpRequest = useHttpRequest();
  let {
    open = false,
    projects = [],
    members = [],
    projectOrder = [],
    initialProjectId = null,
    initialStatus = 'todo',
    initialDate = '',
    initialStartDate = '',
    initialAssignee = null,
    editingTask = null,
    onClose,
    onCreated,
    onUpdated,
  } = $props();
  const page = usePage();
  let title = $state('');
  let description = $state('');
  let projectId = $state('');
  let assigneeId = $state('');
  let status = $state('todo');
  let priority = $state('medium');
  let dueDate = $state('');
  let startDate = $state('');
  let labels = $state([]);
  let subtasks = $state([]);
  let subtaskTitle = $state('');
  let attachments = $state([]);
  let recurrence = $state('');
  let createMore = $state(false);
  let busy = $state(false);
  let errorMessage = $state('');
  let openMenu = $state('');
  let projectQuery = $state('');
  let labelQuery = $state('');
  let assigneeQuery = $state('');
  let calendarMonth = $state(new Date(TODAY.getFullYear(), TODAY.getMonth(), 1));
  let menuPosition = $state({ left: 0, top: 0 });
  let titleInput = $state(null);
  let menuTrigger = $state(null);
  let dialogOpener = null;
  let wasOpen = false;

  const statuses = [
    { id: 'backlog', name: 'Backlog' },
    { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' },
    { id: 'review', name: 'Review' },
    { id: 'done', name: 'Done' },
  ];
  const priorities = [
    { id: 'urgent', name: 'Urgent' },
    { id: 'high', name: 'High' },
    { id: 'medium', name: 'Medium' },
    { id: 'low', name: 'Low' },
    { id: 'none', name: 'No priority' },
  ];
  const recurrenceOptions = ['Does not repeat', 'Daily', 'Weekly', 'Every 2 weeks', 'Monthly'];

  function taskModalTransition() {
    return {
      duration: 260,
      easing: cubicOut,
      css: progress => `opacity:${progress};transform:translateY(${(1 - progress) * 8}px) scale(${0.97 + progress * 0.03});`,
    };
  }

  let selectedProject = $derived(projects.find(project =>
    [project.id, project.database_id].some(id => id != null && String(id) === projectId)));
  function projectDatabaseId(project) {
    return String(project.database_id ?? project.id);
  }
  function canAccessProject(project) {
    const projectIds = new Set([project.id, project.database_id, project.source_key]
      .filter(value => value != null)
      .map(String));
    const sourceProject = (page.props.workspaceData?.projects || []).find(item =>
      [item.id, item.database_id].some(value => value != null && projectIds.has(String(value))));
    const isPrivate = project.is_private ?? project.private ?? sourceProject?.private ?? false;

    if (!isPrivate) return true;

    const userId = String(page.props.auth?.user?.id || '');
    const memberKey = String(page.props.workspaceData?.me || '');
    const projectMembers = [...(project.members || []), ...(sourceProject?.members || [])];

    return projectMembers.some(member => {
      const memberId = typeof member === 'object' ? member.id : member;
      const sourceKey = typeof member === 'object' ? member.source_key : undefined;

      return [memberId, sourceKey]
        .filter(value => value != null)
        .map(String)
        .some(value => value === userId || value === memberKey);
    });
  }
  let effectiveProjectOrder = $derived.by(() => {
    if (projectOrder.length) return projectOrder;

    return (page.props.workspaceData?.projects || [])
      .map(project => project.database_id || project.id)
      .filter(Boolean);
  });
  let accessibleProjects = $derived(projects.filter(canAccessProject));
  let orderedProjects = $derived.by(() => {
    const sourceOrder = new Map(effectiveProjectOrder.map((id, index) => [String(id), index]));
    return [...accessibleProjects].sort((first, second) =>
      (sourceOrder.get(projectDatabaseId(first)) ?? Number.MAX_SAFE_INTEGER)
        - (sourceOrder.get(projectDatabaseId(second)) ?? Number.MAX_SAFE_INTEGER));
  });
  let defaultProjectId = $derived.by(() => {
    const sourceProject = (page.props.workspaceData?.projects || []).find(project =>
      project.database_id
      && !['complete', 'completed', 'archived'].includes(project.status)
      && orderedProjects.some(availableProject => projectDatabaseId(availableProject) === String(project.database_id)));

    return sourceProject?.database_id
      ?? orderedProjects.find(project => !['complete', 'completed', 'archived'].includes(project.status))?.database_id
      ?? orderedProjects.find(project => !['complete', 'completed', 'archived'].includes(project.status))?.id
      ?? orderedProjects[0]?.database_id
      ?? orderedProjects[0]?.id
      ?? '';
  });
  let filteredProjects = $derived(orderedProjects
    .filter(project => !['complete', 'completed', 'archived'].includes(project.status))
    .filter(project => project.name.toLowerCase().includes(projectQuery.trim().toLowerCase()))
    .sort((first, second) => {
      const firstId = String(first.database_id ?? first.id);
      const secondId = String(second.database_id ?? second.id);
      return secondId === projectId ? 1 : firstId === projectId ? -1 : 0;
    }));
  let availableMembers = $derived.by(() => {
    const workspaceMembers = (page.props.workspaceData?.members || [])
      .filter(member => member.database_id)
      .map(member => ({
        id: member.database_id,
        source_key: member.id,
        name: member.name,
        color: member.c,
        status: member.status,
      }));

    if (workspaceMembers.length) return workspaceMembers;
    if (members.length) return members;

    return page.props.auth?.user ? [page.props.auth.user] : [];
  });
  let orderedMembers = $derived.by(() => {
    const sourceOrder = new Map((page.props.workspaceData?.members || []).map((member, index) => [String(member.id), index]));
    return [...availableMembers]
      .filter(member => member.status !== 'deactivated')
      .sort((first, second) => (sourceOrder.get(String(first.source_key || '')) ?? Number.MAX_SAFE_INTEGER)
        - (sourceOrder.get(String(second.source_key || '')) ?? Number.MAX_SAFE_INTEGER));
  });
  let assignableMembers = $derived(orderedMembers.filter(member => member.name.toLowerCase().includes(assigneeQuery.trim().toLowerCase())));
  let filteredLabels = $derived(LABELS.filter(label => label.name.toLowerCase().includes(labelQuery.trim().toLowerCase())));
  let selectedAssignee = $state(null);
  let selectedStatus = $derived(statuses.find(item => item.id === status));
  let selectedPriority = $derived(priorities.find(item => item.id === priority));
  function memberColor(member) {
    return member?.color
      || page.props.workspaceData?.members?.find(item => String(item.id) === String(member?.source_key))?.c
      || 'var(--gray)';
  }
  let calendarDays = $derived.by(() => {
    const first = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), 1);
    const weekStart = Number(S.prefs.weekStart ?? 1);
    const offset = (first.getDay() - weekStart + 7) % 7;
    return Array.from({ length: 42 }, (_, index) => addD(first, index - offset));
  });
  let calendarWeekdays = $derived.by(() => {
    const weekStart = Number(S.prefs.weekStart ?? 1);
    return Array.from({ length: 7 }, (_, index) => WD[(index + weekStart) % 7].slice(0, 2));
  });

  $effect(() => {
    if (!open) {
      openMenu = '';
      wasOpen = false;
      return;
    }

    if (!wasOpen) {
      dialogOpener = document.activeElement instanceof HTMLElement && document.activeElement !== document.body
        ? document.activeElement
        : null;
      wasOpen = true;
    }

    title = editingTask?.title || '';
    description = editingTask?.description?.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() || '';
    projectId = String(editingTask?.project_id ?? initialProjectId ?? defaultProjectId);
    const currentMember = availableMembers.find(member =>
      String(member.source_key || '') === String(page.props.workspaceData?.me || ''))
      || availableMembers.find(member => String(member.id) === String(page.props.auth?.user?.id || ''))
      || page.props.auth?.user;
    const selectedMemberId = editingTask?.assignee_id ?? initialAssignee;
    const selectedMember = selectedMemberId == null
      ? currentMember
      : availableMembers.find(member => String(member.id) === String(selectedMemberId)
        || String(member.source_key || '') === String(selectedMemberId));
    assigneeId = String(selectedMember?.id ?? selectedMemberId ?? '');
    selectedAssignee = selectedMember || null;
    status = editingTask?.status || initialStatus;
    priority = editingTask?.priority || 'medium';
    dueDate = editingTask?.due_date?.slice(0, 10) || initialDate;
    startDate = editingTask?.start_date?.slice(0, 10) || initialStartDate;
    labels = [...(editingTask?.labels || [])];
    subtasks = [...(editingTask?.subtasks || [])];
    subtaskTitle = '';
    attachments = [];
    recurrence = editingTask?.recurrence || '';
    createMore = Boolean(S.ui.taskCreateMore);
    errorMessage = '';
    focusTitle();
  });

  async function focusTitle() {
    await tick();
    titleInput?.focus();
  }

  function restoreDialogFocus() {
    if (dialogOpener?.isConnected) dialogOpener.focus({ preventScroll: true });
    dialogOpener = null;
  }

  async function showMenu(menu, event) {
    menuTrigger = event.currentTarget;
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = popupWidth(menu);
    const left = bounds.left + width <= window.innerWidth - 8
      ? bounds.left
      : Math.max(8, bounds.left + bounds.width - width);
    menuPosition = { left, top: Math.max(8, bounds.bottom + 4) };
    if (menu === 'project') projectQuery = '';
    if (menu === 'labels') labelQuery = '';
    if (menu === 'assignee') assigneeQuery = '';
    if (menu === 'due' || menu === 'start') {
      const selectedDate = menu === 'due' ? dueDate : startDate;
      const date = selectedDate ? new Date(`${selectedDate}T00:00:00`) : TODAY;
      calendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    }
    openMenu = openMenu === menu ? '' : menu;
    if (!openMenu) return;

    await tick();
    const popup = document.querySelector('[data-task-form-popup]');
    if (!popup) return;

    const focusTarget = popup.querySelector('input')
      || popup.querySelector('[aria-checked="true"]')
      || popup.querySelector('[aria-pressed="true"]')
      || popup.querySelector('button');
    focusTarget?.focus({ preventScroll: true });
    const popupBounds = popup.getBoundingClientRect();
    const actualLeft = bounds.left + popupBounds.width <= window.innerWidth - 8
      ? bounds.left
      : Math.max(8, bounds.left + bounds.width - popupBounds.width);
    const top = bounds.bottom + popupBounds.height > window.innerHeight - 8
      ? Math.max(8, bounds.top - popupBounds.height - 4)
      : bounds.bottom + 4;
    menuPosition = { left: actualLeft, top };
  }

  function popupWidth(menu) {
    if (menu === 'due' || menu === 'start') return 312;
    return 200;
  }

  function closeMenu() {
    const trigger = menuTrigger;
    openMenu = '';
    tick().then(() => trigger?.focus({ preventScroll: true }));
  }

  function handleWindowPointerdown(event) {
    const { target } = event;
    if (!openMenu || !(target instanceof Element) || target.closest('[data-task-form-popup], [data-task-form-trigger]')) return;
    openMenu = '';
  }

  function handleKeydown(event) {
    if (event.key === 'Tab' && open) {
      const dialog = document.querySelector('[role="dialog"][aria-labelledby="workspace-new-task-title"]');
      const focusable = [...(dialog?.querySelectorAll(
        'a[href],button:not([disabled]),input:not([type="hidden"]):not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"]),[contenteditable="true"]',
      ) || [])].filter(element => element.offsetParent !== null);
      const index = focusable.indexOf(document.activeElement);

      if (focusable.length && (event.shiftKey ? index <= 0 : index === focusable.length - 1 || index === -1)) {
        event.preventDefault();
        focusable[event.shiftKey ? focusable.length - 1 : 0].focus();
        return;
      }
    }

    if (open && openMenu && ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
      const items = [...(document.querySelector('[data-task-form-popup]')?.querySelectorAll(
        '[role="menuitemradio"]:not(:disabled),[role="menuitemcheckbox"]:not(:disabled),button:not(:disabled)',
      ) || [])].filter(element => element.offsetParent !== null);

      if (items.length) {
        event.preventDefault();
        const index = items.indexOf(document.activeElement);
        const nextIndex = event.key === 'Home'
          ? 0
          : event.key === 'End'
            ? items.length - 1
            : event.key === 'ArrowDown'
              ? (index + 1) % items.length
              : (index - 1 + items.length) % items.length;
        items[nextIndex].focus();
        return;
      }
    }

    if (event.key === 'Escape') {
      if (openMenu) {
        closeMenu();
        return;
      }
      onClose();
      return;
    }

    if (open && openMenu === 'status' && /^[1-5]$/.test(event.key)) {
      status = statuses[Number(event.key) - 1].id;
      closeMenu();
      return;
    }

    if (open && (event.metaKey || event.ctrlKey) && event.key === 'Enter' && !busy) {
      event.preventDefault();
      titleInput?.form?.requestSubmit();
    }
  }

  function chooseProject(project) {
    projectId = String(project.database_id ?? project.id);
    closeMenu();
  }

  function chooseAssignee(member) {
    assigneeId = member ? String(member.id) : '';
    selectedAssignee = member;
    closeMenu();
  }

  function setTaskDate(date) {
    if (openMenu === 'due') dueDate = date;
    else startDate = date;
    closeMenu();
  }

  function shiftCalendarMonth(amount) {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + amount, 1);
  }

  function toggleLabel(label) {
    labels = labels.includes(label)
      ? labels.filter(selectedLabel => selectedLabel !== label)
      : [...labels, label];
  }

  function addSubtask(event) {
    event.preventDefault();
    const name = subtaskTitle.trim();
    if (!name) return;
    subtasks = [...subtasks, { id: crypto.randomUUID(), title: name, done: false }];
    subtaskTitle = '';
  }

  function handleSubtaskKeydown(event) {
    if (event.key !== 'Enter') return;
    addSubtask(event);
  }

  function addAttachments(event) {
    const selectedFiles = [...event.currentTarget.files];
    attachments = [
      ...attachments,
      ...selectedFiles.map(file => ({
        id: crypto.randomUUID(),
        name: file.name,
        type: fileType(file.name),
        size: fsize(file.size),
        by: page.props.auth?.user?.id,
        at: new Date().toISOString(),
      })),
    ];
    event.currentTarget.value = '';
  }

  function resetForNextTask() {
    title = '';
    description = '';
    subtasks = [];
    subtaskTitle = '';
    attachments = [];
    errorMessage = '';
  }

  async function submit(event) {
    event.preventDefault();
    if (!title.trim()) {
      errorMessage = 'Give the task a name';
      await focusTitle();
      return;
    }
    const targetProjectId = selectedProject?.database_id ?? projectId;
    if (!targetProjectId) return;

    busy = true;
    errorMessage = '';

    try {
      const taskData = {
        title: title.trim(),
        description: description.trim() || null,
        status,
        priority,
        ...(editingTask ? { project_id: targetProjectId } : {}),
        due_date: dueDate || null,
        start_date: startDate && dueDate && startDate > dueDate ? dueDate : startDate || null,
        assignee_id: assigneeId || null,
        labels,
        subtasks,
        recurrence: recurrence || null,
      };

      if (editingTask) {
        const updatedTask = await httpRequest(taskUpdate(editingTask.id).url, 'PATCH', taskData);
        onUpdated(updatedTask);
        onClose();
        return;
      }

      const task = await httpRequest(taskStore({ project: targetProjectId }).url, 'POST', {
        ...taskData,
        attachments,
      });

      onCreated(task);
      if (createMore) {
        resetForNextTask();
      await focusTitle();
        return;
      }

      onClose();
    } catch (error) {
      errorMessage = error.errors?.title?.[0] || error.message;
    } finally {
      busy = false;
    }
  }
</script>

<svelte:window onkeydown={handleKeydown} onpointerdown={handleWindowPointerdown} />

{#if open}
  <div class="modal-wrap" transition:fade={{ duration: 180 }} onoutroend={restoreDialogFocus} role="presentation" onclick={event => event.target === event.currentTarget && onClose()}>
    <div class="modal" transition:taskModalTransition role="dialog" aria-modal="true" aria-labelledby="workspace-new-task-title" tabindex="-1">
      <div class="modal-h"><h2 id="workspace-new-task-title">{editingTask ? 'Edit task' : 'New task'}</h2><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={onClose}>{@html ic('x', 16)}</button></div>
      <form id="workspace-task-form" class="modal-b" style="gap:12px" onsubmit={submit}>
          <div class="field">
            <label class="sr" for="workspace-task-title">Task name</label>
            <Input variant="large" error={!!errorMessage} id="workspace-task-title" bind:element={titleInput} bind:value={title} oninput={() => title.trim() && (errorMessage = '')} placeholder="Task name" aria-invalid={!!errorMessage} aria-describedby={errorMessage ? 'workspace-task-title-error' : undefined} maxlength="255" style="font-size:15px;font-weight:500" />
            {#if errorMessage}<span id="workspace-task-title-error" class="err" role="alert">{@html ic('circle-alert', 12)}{errorMessage}</span>{/if}
          </div>
          <div class="field">
            <label class="sr" for="workspace-task-description">Description</label>
            <textarea class="textarea" id="workspace-task-description" bind:value={description} placeholder="Add a description… (optional)" rows="3" maxlength="10000"></textarea>
          </div>
          <div class="row" style="flex-wrap:wrap;gap:6px">
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="Project" onclick={event => showMenu('project', event)}><span class="pdot" style={`--c:${PCOLORS[selectedProject?.color] || 'var(--gray)'};border-radius:50%;width:8px;height:8px`}></span><span class="trunc">{selectedProject?.name || 'Project'}</span></button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="Status" onclick={event => showMenu('status', event)}>{@html stIcon(status, 13)}{selectedStatus?.name || 'Status'}</button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="Assignee" onclick={event => showMenu('assignee', event)}>{#if selectedAssignee}<span class="av sm" style={`--c:${memberColor(selectedAssignee)}`}>{selectedAssignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{:else}{@html ic('user-round', 13)}{/if}<span class="trunc">{selectedAssignee?.name || 'Assignee'}</span></button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="Priority" onclick={event => showMenu('priority', event)}>{@html prIcon(priority, 13)}{selectedPriority?.name || 'Priority'}</button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="due" onclick={event => showMenu('due', event)}>{@html ic('calendar', 13)}{dueDate ? `Due ${fmtDate(dueDate)}` : 'Due date'}</button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="start" onclick={event => showMenu('start', event)}>{@html ic('calendar-arrow-up', 13)}{startDate ? `Starts ${fmtDate(startDate)}` : 'Start date'}</button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="Labels" onclick={event => showMenu('labels', event)}>{#if labels.length}{#each labels as labelId (labelId)}{@const label = LABELS.find(item => item.id === labelId)}<span class="lbl" style={`--c:${label?.c || 'var(--gray)'}`}><i></i>{label?.name || labelId}</span>{/each}{:else}{@html ic('tag', 13)}Labels{/if}</button>
            <button type="button" class="pillbtn bordered" data-task-form-trigger aria-label="Repeat" onclick={event => showMenu('recurrence', event)}>{@html ic('repeat', 13)}{recurrence || 'Repeat'}</button>
          </div>
          <div class="field">
            <span class="label">Subtasks</span>
            {#each subtasks as subtask, index (subtask.id)}
              <div class="subt" style="padding:0 4px">{@html ic('circle', 13)}<span class="s">{subtask.title}</span><button type="button" class="ibtn ibtn-xs x" style="opacity:1" aria-label={`Remove ${subtask.title}`} onclick={() => subtasks = subtasks.filter((_, subtaskIndex) => subtaskIndex !== index)}>{@html ic('x', 12)}</button></div>
            {/each}
            <div class="inwrap">{@html ic('plus', 14)}<Input class="" bind:value={subtaskTitle} onkeydown={handleSubtaskKeydown} placeholder="Add a subtask and press Enter" /></div>
          </div>
          <div class="field">
            <span class="label">Attachments</span>
            {#if attachments.length}<div class="col" style="gap:4px">{#each attachments as attachment (attachment.id)}<div class="upl" style="padding:6px 10px"><span class="ftype" style={`--c:${FT[attachment.type]?.c || FT.other.c};width:22px;height:22px`}>{@html ic(FT[attachment.type]?.i || FT.other.i, 12)}</span><span class="grow trunc">{attachment.name}</span><span class="faint" style="font-size:11.5px">{attachment.size}</span><button type="button" class="ibtn ibtn-xs" aria-label={`Remove ${attachment.name}`} onclick={() => attachments = attachments.filter(file => file.id !== attachment.id)}>{@html ic('x', 12)}</button></div>{/each}</div>{/if}
            <label class="dropzone" style="padding:10px">{@html ic('paperclip', 14)}<span>Attach files</span><input type="file" multiple hidden onchange={addAttachments} /></label>
          </div>
      </form>
      <div class="modal-f">
          {#if !editingTask}<label class="row" style="gap:8px;font-size:12.5px;color:var(--text-2);cursor:pointer"><input type="checkbox" class="toggle" bind:checked={createMore} onchange={() => S.ui.taskCreateMore = createMore} />Create more</label>{/if}
          <span class="sp"></span><span class="faint hide-m" style="font-size:11.5px"><kbd>⌘</kbd> <kbd>↵</kbd></span>
          <button type="button" class="btn btn-secondary" onclick={onClose}>Cancel</button>
          <button class="btn btn-primary" type="submit" form="workspace-task-form" disabled={busy || !projects.length}>{busy ? (editingTask ? 'Saving…' : 'Creating…') : (editingTask ? 'Save changes' : 'Create task')}</button>
      </div>
      {#if openMenu}
        <div class="pop enter" data-task-form-popup role="menu" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;${openMenu === 'due' || openMenu === 'start' ? 'min-width:0' : `width:${popupWidth(openMenu)}px`}`}>
          {#if openMenu === 'project'}
            <div class="pin"><input bind:value={projectQuery} placeholder="Move to project…" aria-label="Move to project…" /></div>
            {#each filteredProjects as project (project.id)}<button class="mi" role="menuitemradio" aria-checked={projectDatabaseId(project) === projectId} onclick={() => chooseProject(project)}><span class="pdot" style={`--c:${PCOLORS[project.color] || 'var(--gray)'};border-radius:50%;width:8px;height:8px`}></span><span class="trunc">{project.name}</span>{#if projectDatabaseId(project) === projectId}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {#if !filteredProjects.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
          {:else if openMenu === 'status'}
            {#each statuses as item, index (item.id)}<button class="mi" role="menuitemradio" aria-checked={status === item.id} onclick={() => { status = item.id; closeMenu(); }}>{@html stIcon(item.id, 14)}<span>{item.name}</span><span class="r"><kbd>{index + 1}</kbd></span>{#if status === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
          {:else if openMenu === 'assignee'}
            <div class="pin"><input bind:value={assigneeQuery} placeholder="Assign to…" aria-label="Assign to…" /></div>
            <button class="mi" role="menuitemradio" aria-checked={!assigneeId} onclick={() => chooseAssignee(null)}>{@html ic('user-round', 14)}<span>Unassigned</span>{#if !assigneeId}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
            {#each assignableMembers as member (member.id)}<button class="mi" role="menuitemradio" aria-checked={String(member.id) === assigneeId} onclick={() => chooseAssignee(member)}><span class="av sm" style={`--c:${memberColor(member)}`}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span><span class="trunc">{member.name}{String(member.id) === String(page.props.auth?.user?.id) ? ' (you)' : ''}</span>{#if String(member.id) === assigneeId}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {#if !assignableMembers.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
          {:else if openMenu === 'priority'}
            {#each priorities as item (item.id)}<button class="mi" role="menuitemradio" aria-checked={priority === item.id} onclick={() => { priority = item.id; closeMenu(); }}>{@html prIcon(item.id, 14)}<span>{item.name}</span>{#if priority === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
          {:else if openMenu === 'due' || openMenu === 'start'}
            {@const currentDate = openMenu === 'due' ? dueDate : startDate}
            <div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">
              {#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7 - ((TODAY.getDay() + 6) % 7)], ['In 2 weeks', 14]] as [shortcut, offset] (shortcut)}
                <button type="button" class="badge" style="cursor:pointer" onclick={() => setTaskDate(iso(addD(TODAY, offset)))}>{shortcut}</button>
              {/each}
              {#if currentDate}<button type="button" class="badge" style="cursor:pointer;color:var(--red)" onclick={() => setTaskDate('')}>Clear</button>{/if}
            </div>
            <div class="msep"></div>
            <div style="padding:6px 6px 2px;width:252px">
              <div class="row" style="margin-bottom:6px"><b style="font-weight:600;font-size:13px;padding-left:4px">{MONL[calendarMonth.getMonth()]} {calendarMonth.getFullYear()}</b><span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Previous month" onclick={() => shiftCalendarMonth(-1)}>{@html ic('chevron-left', 14)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Next month" onclick={() => shiftCalendarMonth(1)}>{@html ic('chevron-right', 14)}</button></div>
              <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:11px">{#each calendarWeekdays as weekday}<span class="faint" style="padding:3px 0">{weekday}</span>{/each}
                {#each calendarDays as day (iso(day))}
                  {@const dayIso = iso(day)}
                  {@const isSelected = dayIso === currentDate}
                  {@const isToday = diffD(day, TODAY) === 0}
                  <button type="button" class="num" aria-label={fmtDate(dayIso, true)} style={`height:30px;border-radius:6px;font-size:12px;${isSelected ? 'background:var(--accent);color:var(--on-accent);font-weight:600' : isToday ? 'color:var(--accent);font-weight:600;box-shadow:inset 0 0 0 1px var(--accent-line)' : day.getMonth() !== calendarMonth.getMonth() ? 'color:var(--text-3)' : ''}`} onclick={() => setTaskDate(dayIso)}>{day.getDate()}</button>
                {/each}
              </div>
            </div>
          {:else if openMenu === 'labels'}
            <div class="pin"><input bind:value={labelQuery} placeholder="Filter labels…" aria-label="Filter labels…" /></div>
            {#each filteredLabels as label (label.id)}<button class="mi" role="menuitemcheckbox" aria-checked={labels.includes(label.id)} onclick={() => toggleLabel(label.id)}><span class="pdot" style={`--c:${label.c};border-radius:50%;width:8px;height:8px`}></span><span>{label.name}</span>{#if labels.includes(label.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {#if !filteredLabels.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
          {:else if openMenu === 'recurrence'}
            {#each recurrenceOptions as option (option)}<button class="mi" role="menuitemradio" aria-checked={(recurrence || 'Does not repeat') === option} onclick={() => { recurrence = option === 'Does not repeat' ? '' : option; closeMenu(); }}>{@html ic('repeat', 14)}<span>{option}</span>{#if (recurrence || 'Does not repeat') === option}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
          {/if}
        </div>
      {/if}
    </div>
  </div>
{/if}
