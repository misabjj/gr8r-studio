<script>
  import Dropdown from "./Dropdown.svelte";
  import Select from "./Select.svelte";
  import Button from "./Button.svelte";
  import Input from "./Input.svelte";
  import Textarea from "./Textarea.svelte";
  import DOMPurify from 'dompurify';
  import { fade, scale } from 'svelte/transition';
  import { Link, router } from '@inertiajs/svelte';
  import { archive as archiveTaskRoute, destroy as deleteTaskRoute, favorite as taskFavorite, update as taskUpdate } from '../routes/tasks';
  import { show as projectShow } from '../routes/projects';
  import { useHttpRequest } from '../lib/http.js';
  import { store as storeTask } from '../routes/projects/tasks';
  import { ic } from '@/src/core/icons.js';
  import { filePrev, fmtComment, FT, fsize, fileType, lbl, prIcon, prPill, progBar, stIcon, stPill } from '@/src/ui/helpers.js';
  import { LABELS, PCOLORS } from '@/src/core/constants.js';
  import { addD, ago, diffD, fmtDate, iso, MONL, relDate, TODAY, uid, WD } from '@/src/core/utils.js';
  import { S, save as saveWorkspaceStore } from '@/src/core/store.js';
  import { tick } from 'svelte';
  import { toast } from '@/src/ui/toast.js';
  import WorkspaceTaskForm from './WorkspaceTaskForm.svelte';

  const httpRequest = useHttpRequest();
  let { task, workspaceData = {}, onClose, onUpdated } = $props();
  let full = $state(false);
  let entering = $state(true);
  let activeTab = $state('comments');
  let title = $state('');
  let description = $state('');
  let subtasks = $state([]);
  let subtaskTitle = $state('');
  let selectedSubtaskId = $state(null);
  let busy = $state(false);
  let errorMessage = $state('');
  let descriptionEditor = $state(null);
  let propertyMenu = $state('');
  let propertyMenuPosition = $state({ left: 0, top: 0 });
  let propertyMenuTrigger = $state(null);
  let taskMenuOpen = $state(false);
  let editOpen = $state(false);
  let confirmDeleteOpen = $state(false);
  let taskMenuPosition = $state({ left: 0, top: 0 });
  let selectedAttachment = $state(null);
  let attachmentInput = $state(null);
  let reactionMenuCommentId = $state(null);
  let reactionMenuPosition = $state({ left: 0, top: 0 });
  let commentDraft = $state('');
  let commentInput = $state(null);
  let mentionQuery = $state(null);
  let mentionRange = $state(null);
  let projectQuery = $state('');
  let assigneeQuery = $state('');
  let labelQuery = $state('');
  let dependencyQuery = $state('');
  let calendarMonth = $state(new Date(TODAY.getFullYear(), TODAY.getMonth(), 1));

  const descriptionOptions = {
    ALLOWED_TAGS: ['a', 'b', 'br', 'em', 'h4', 'i', 'li', 'ol', 'p', 'strong', 'u', 'ul'],
    ALLOWED_ATTR: ['href', 'rel', 'target'],
  };

  function sanitizeDescription(html) {
    return DOMPurify.sanitize(html, descriptionOptions);
  }

  $effect(() => {
    title = task.title;
    description = sanitizeDescription(task.description || '');
    subtasks = [...(task.subtasks || [])];
    attachments = [...(task.attachments || [])];
    if (descriptionEditor) descriptionEditor.innerHTML = description;
  });

  let taskComments = $state([]);
  let taskActivity = $state([]);
  $effect(() => {
    const sourceComments = workspaceData.comments || [];
    const sourceCommentIds = new Set(sourceComments.map(comment => comment.id));
    const localComments = (S.data.comments || []).filter(comment => !sourceCommentIds.has(comment.id));
    taskComments = [...sourceComments, ...localComments].filter(comment => comment.task === task.source_key);

    const sourceActivity = (workspaceData.activity || []).filter(activity => activity.task === task.source_key);
    const localCommentKeys = new Set(localComments.map(comment => `${comment.task}:${comment.by}:${comment.at}`));
    const localActivity = (S.data.activity || []).filter(activity => activity.task === task.source_key
      && activity.verb === 'commented on'
      && localCommentKeys.has(`${activity.task}:${activity.by}:${activity.at}`));
    taskActivity = [...sourceActivity, ...localActivity];
  });
  let membersBySourceKey = $derived(new Map((workspaceData.members || []).map(member => [member.id, member])));
  let membersByDatabaseId = $derived(new Map((workspaceData.members || []).map(member => [String(member.database_id), member])));
  let selectedSubtask = $derived(subtasks.find(subtask => subtask.id === selectedSubtaskId) || null);
  let labels = $derived(task.labels || []);
  let attachments = $state([]);
  let sourceTasks = $derived(workspaceData.tasks || []);
  let sourceTaskById = $derived(new Map(sourceTasks.map(sourceTask => [sourceTask.id, sourceTask])));
  let sourceProjectId = $derived(workspaceData.projects?.find(project => String(project.database_id) === String(task.project_id))?.id);
  let blockedByTasks = $derived((task.dependencies || []).map(dependency => sourceTaskById.get(dependency)).filter(Boolean));
  let dependencyCandidates = $derived(sourceTasks
    .filter(sourceTask => sourceTask.project === sourceProjectId && sourceTask.id !== task.source_key && !sourceTask.archived)
    .filter(sourceTask => `${sourceTask.key} ${sourceTask.title}`.toLowerCase().includes(dependencyQuery.trim().toLowerCase())));
  let filteredLabels = $derived(LABELS.filter(label => label.name.toLowerCase().includes(labelQuery.trim().toLowerCase())));
  let mentionCandidates = $derived(mentionQuery == null
    ? []
    : (workspaceData.members || []).filter(member => member.status !== 'deactivated'
      && member.name.toLowerCase().includes(mentionQuery.toLowerCase())));

  const statuses = [
    { id: 'backlog', name: 'Backlog' },
    { id: 'todo', name: 'To Do' },
    { id: 'progress', name: 'In Progress' },
    { id: 'review', name: 'Review' },
    { id: 'done', name: 'Done' },
  ];
  const priorities = ['none', 'low', 'medium', 'high', 'urgent'];
  const recurrenceOptions = ['Does not repeat', 'Daily', 'Weekly', 'Every 2 weeks', 'Monthly'];
  const estimateOptions = ['30m', '1h', '2h', '4h', '1d', '2d', '3d', '5d', '8d'];
  const assignableMembers = $derived((workspaceData.members || [])
    .filter(member => member.status !== 'deactivated' && member.database_id)
    .filter(member => member.name.toLowerCase().includes(assigneeQuery.trim().toLowerCase())));
  const assignableProjects = $derived((workspaceData.projects || [])
    .filter(project => project.database_id && !['complete', 'completed', 'archived'].includes(project.status))
    .filter(project => !project.private || project.members?.includes(workspaceData.me))
    .filter(project => project.name.toLowerCase().includes(projectQuery.trim().toLowerCase())));
  const calendarDays = $derived.by(() => {
    const first = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), 1);
    const weekStart = Number(S.prefs.weekStart ?? 1);
    const offset = (first.getDay() - weekStart + 7) % 7;
    return Array.from({ length: 42 }, (_, index) => addD(first, index - offset));
  });
  const calendarWeekdays = $derived.by(() => {
    const weekStart = Number(S.prefs.weekStart ?? 1);
    return Array.from({ length: 7 }, (_, index) => WD[(index + weekStart) % 7].slice(0, 2));
  });

  function formatDate(value) {
    if (!value) return 'Set date';
    return relDate(value.slice(0, 10));
  }

  function formatStartDate(value) {
    if (!value) return 'Set date';
    return fmtDate(value.slice(0, 10));
  }

  async function openPropertyMenu(menu, event) {
    propertyMenuTrigger = event.currentTarget;
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = menu === 'due' || menu === 'start' ? 312 : menu === 'project' ? 220 : 200;
    const left = Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8));
    propertyMenu = propertyMenu === menu ? '' : menu;
    if (!propertyMenu) return;

    if (menu === 'assignee') assigneeQuery = '';
    if (menu === 'project') projectQuery = '';
    if (menu === 'labels') labelQuery = '';
    if (menu === 'dependencies') dependencyQuery = '';
    if (menu === 'due' || menu === 'start') {
      const selectedDate = menu === 'due' ? task.due_date : task.start_date;
      const date = selectedDate ? new Date(`${selectedDate.slice(0, 10)}T00:00:00`) : TODAY;
      calendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    }

    propertyMenuPosition = { left, top: Math.max(8, bounds.bottom + 4) };
    await tick();
    const popup = document.querySelector('[data-drawer-property-popup]');
    if (!popup) return;
    const focusTarget = popup.querySelector('input')
      || popup.querySelector('[aria-checked="true"]')
      || popup.querySelector('button');
    focusTarget?.focus({ preventScroll: true });
    const height = popup.getBoundingClientRect().height;
    const top = bounds.bottom + height > window.innerHeight - 8
      ? Math.max(8, bounds.top - height - 4)
      : bounds.bottom + 4;
    propertyMenuPosition = { left, top };
  }

  function closePropertyMenu() {
    const trigger = propertyMenuTrigger;
    propertyMenu = '';
    tick().then(() => trigger?.focus({ preventScroll: true }));
  }

  function handleWindowPointerdown(event) {
    const { target } = event;
    if (taskMenuOpen && target instanceof Element && !target.closest('[data-drawer-task-menu], [data-drawer-task-menu-trigger]')) taskMenuOpen = false;
    if (reactionMenuCommentId && target instanceof Element && !target.closest('[data-comment-reaction-menu], [data-comment-reaction-trigger]')) reactionMenuCommentId = null;
    if (!propertyMenu || !(target instanceof Element) || target.closest('[data-drawer-property-popup], [data-drawer-property-trigger]')) return;
    propertyMenu = '';
  }

  function handlePropertyKeydown(event) {
    if (selectedAttachment && event.key === 'Escape') {
      selectedAttachment = null;
      return;
    }
    if (taskMenuOpen && event.key === 'Escape') {
      taskMenuOpen = false;
      return;
    }
    if (!propertyMenu) return;
    if (event.key === 'Escape') {
      closePropertyMenu();
      return;
    }

    if (propertyMenu === 'status' && /^[1-5]$/.test(event.key)) {
      void saveTaskProperty('status', statuses[Number(event.key) - 1].id);
    }
  }

  async function saveTaskProperty(field, value) {
    const updated = await updateTask({ [field]: value });
    closePropertyMenu();
    return updated;
  }

  function toggleLabel(labelId) {
    const nextLabels = labels.includes(labelId)
      ? labels.filter(currentLabel => currentLabel !== labelId)
      : [...labels, labelId];
    void updateTask({ labels: nextLabels });
  }

  function toggleDependency(dependencyId) {
    const dependencies = task.dependencies || [];
    const nextDependencies = dependencies.includes(dependencyId)
      ? dependencies.filter(currentDependency => currentDependency !== dependencyId)
      : [...dependencies, dependencyId];
    void updateTask({ dependencies: nextDependencies });
  }

  function selectDate(date) {
    const field = propertyMenu === 'due' ? 'due_date' : 'start_date';
    void saveTaskProperty(field, date || null);
  }

  function shiftCalendarMonth(amount) {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + amount, 1);
  }

  function formatTime(value) {
    const timestamp = typeof value === 'number' ? value : new Date(value).getTime();

    return Number.isFinite(timestamp) ? ago(timestamp) : '';
  }

  function openAttachment(attachment) {
    selectedAttachment = attachment;
  }

  function handleAttachmentKeydown(event, attachment) {
    if (event.target !== event.currentTarget || !['Enter', ' '].includes(event.key)) return;
    event.preventDefault();
    openAttachment(attachment);
  }

  function removeAttachment(attachment) {
    const nextAttachments = attachments.filter(item => item.id !== attachment.id);
    const localTask = S.data.tasks.find(item => item.id === task.source_key);
    const at = Date.now();
    if (localTask) {
      localTask.attachments = nextAttachments;
      localTask.updated = at;
    }
    S.data.files = (S.data.files || []).filter(item => item.id !== attachment.id);
    const activity = { id: uid('a'), by: workspaceData.me, verb: 'removed a file from', task: task.source_key, project: sourceProjectId, at, extra: '' };
    S.data.activity.unshift(activity);
    attachments = nextAttachments;
    taskActivity = [activity, ...taskActivity];
    saveWorkspaceStore();
    onUpdated({ ...task, attachments: nextAttachments });
    toast(`Removed ${attachment.name}`);
  }

  function addAttachments(event) {
    const selectedFiles = [...event.currentTarget.files];
    const at = Date.now();
    const newAttachments = selectedFiles.map(file => ({
      id: uid('a'),
      name: file.name,
      type: fileType(file.name),
      size: fsize(file.size),
      by: workspaceData.me,
      at,
    }));
    const nextAttachments = [...attachments, ...newAttachments];
    const localTask = S.data.tasks.find(item => item.id === task.source_key);
    if (localTask) {
      localTask.attachments = nextAttachments;
      localTask.updated = at;
    }
    S.data.files = [...(S.data.files || []), ...newAttachments.map(attachment => ({ ...attachment, project: sourceProjectId, task: task.source_key }))];
    attachments = nextAttachments;
    saveWorkspaceStore();
    onUpdated({ ...task, attachments: nextAttachments });
    event.currentTarget.value = '';
    if (newAttachments.length) toast(`Uploaded ${newAttachments[0].name}`);
  }

  function updateCommentDraft(event) {
    commentDraft = event.currentTarget.value;
    const textBeforeCaret = commentDraft.slice(0, event.currentTarget.selectionStart);
    const match = textBeforeCaret.match(/(?:^|\s)@([A-Za-z]*)$/);
    mentionQuery = match ? match[1] : null;
    mentionRange = match
      ? { start: event.currentTarget.selectionStart - match[1].length - 1, end: event.currentTarget.selectionStart }
      : null;
  }

  function startMention() {
    const separator = commentDraft && !commentDraft.endsWith(' ') ? ' ' : '';
    commentDraft += `${separator}@`;
    mentionQuery = '';
    mentionRange = { start: commentDraft.length - 1, end: commentDraft.length };
    tick().then(() => {
      commentInput?.focus();
      commentInput?.setSelectionRange(commentDraft.length, commentDraft.length);
    });
  }

  function insertMention(member) {
    const range = mentionRange || { start: commentDraft.length, end: commentDraft.length };
    commentDraft = `${commentDraft.slice(0, range.start)}@${member.name} ${commentDraft.slice(range.end)}`;
    mentionQuery = null;
    mentionRange = null;
    tick().then(() => {
      commentInput?.focus();
      commentInput?.setSelectionRange(commentDraft.length, commentDraft.length);
    });
  }

  function handleCommentKeydown(event) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
      event.preventDefault();
      postComment(event);
    }
  }

  function postComment(event) {
    event.preventDefault();
    const text = commentDraft.trim();
    if (!text) return;

    const at = Date.now();
    const comment = { id: uid('c'), task: task.source_key, by: workspaceData.me, at, text, re: {} };
    const activity = { id: uid('a'), by: workspaceData.me, verb: 'commented on', task: task.source_key, project: sourceProjectId, at, extra: '' };
    const localTask = S.data.tasks.find(item => item.id === task.source_key);
    if (localTask) localTask.updated = at;
    S.data.comments = [...(S.data.comments || []), comment];
    S.data.activity.unshift(activity);
    saveWorkspaceStore();
    taskComments = [...taskComments, comment];
    taskActivity = [activity, ...taskActivity];
    commentDraft = '';
    mentionQuery = null;
    toast('Comment posted');
  }

  function deleteComment(comment) {
    if (comment.by !== workspaceData.me) return;

    S.data.comments = (S.data.comments || []).filter(item => item.id !== comment.id);
    taskComments = taskComments.filter(item => item.id !== comment.id);
    saveWorkspaceStore();
    toast('Comment deleted');
  }

  function toggleReaction(comment, emoji) {
    const existingComment = (S.data.comments || []).find(item => item.id === comment.id) || comment;
    const reactions = { ...(existingComment.re || {}) };
    const members = reactions[emoji] || [];
    reactions[emoji] = members.includes(workspaceData.me)
      ? members.filter(memberId => memberId !== workspaceData.me)
      : [...members, workspaceData.me];
    const updatedComment = { ...existingComment, re: reactions };
    const isStored = (S.data.comments || []).some(item => item.id === comment.id);
    S.data.comments = isStored
      ? S.data.comments.map(item => item.id === comment.id ? updatedComment : item)
      : [...(S.data.comments || []), updatedComment];
    taskComments = taskComments.map(item => item.id === comment.id ? updatedComment : item);
    saveWorkspaceStore();
    reactionMenuCommentId = null;
  }

  async function toggleReactionMenu(event, comment) {
    if (reactionMenuCommentId === comment.id) {
      reactionMenuCommentId = null;
      return;
    }

    const bounds = event.currentTarget.getBoundingClientRect();
    reactionMenuPosition = { left: Math.max(8, Math.min(bounds.left, window.innerWidth - 200)), top: bounds.bottom + 4 };
    reactionMenuCommentId = comment.id;
    await tick();
    const menu = document.querySelector('[data-comment-reaction-menu]');
    if (!menu) return;
    const menuBounds = menu.getBoundingClientRect();
    reactionMenuPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - menuBounds.width - 8)),
      top: menuBounds.bottom > window.innerHeight - 8 ? Math.max(8, bounds.top - menuBounds.height - 4) : bounds.bottom + 4,
    };
  }

  async function updateTask(values) {
    busy = true;
    errorMessage = '';
    try {
      const updated = await httpRequest(taskUpdate(task.id).url, 'PATCH', values);
      onUpdated({ ...task, ...updated, project: updated.project || task.project, assignee: updated.assignee || task.assignee });
      return true;
    } catch (error) {
      errorMessage = error.errors?.title?.[0] || error.message;
      return false;
    } finally {
      busy = false;
    }
  }

  async function saveSubtasks(nextSubtasks) {
    const previousSubtasks = subtasks;
    subtasks = nextSubtasks;
    if (!await updateTask({ subtasks: nextSubtasks })) subtasks = previousSubtasks;
  }

  function toggleSubtask(subtask) {
    return saveSubtasks(subtasks.map(item => item.id === subtask.id ? { ...item, done: !item.done } : item));
  }

  function addSubtask(event) {
    event.preventDefault();
    const nextTitle = subtaskTitle.trim();
    if (!nextTitle) return;
    subtaskTitle = '';
    void saveSubtasks([...subtasks, { id: crypto.randomUUID(), title: nextTitle, done: false }]);
  }

  function updateSubtask(subtaskId, updates) {
    return saveSubtasks(subtasks.map(item => item.id === subtaskId ? { ...item, ...updates } : item));
  }

  function openSubtask(subtaskId) {
    selectedSubtaskId = subtaskId;
  }

  function removeSubtask(subtaskId) {
    selectedSubtaskId = null;
    void saveSubtasks(subtasks.filter(item => item.id !== subtaskId));
  }

  async function convertSubtask(subtask) {
    if (!task.project_id) return;
    busy = true;
    errorMessage = '';
    try {
      const assigneeId = membersBySourceKey.get(subtask.assignee)?.database_id || task.assignee_id || null;
      await httpRequest(storeTask({ project: task.project_id }).url, 'POST', {
        title: subtask.title,
        description: subtask.note || null,
        status: subtask.done ? 'done' : 'todo',
        priority: task.priority,
        due_date: subtask.due || null,
        start_date: null,
        assignee_id: assigneeId,
        labels: task.labels || [],
      });
      await saveSubtasks(subtasks.filter(item => item.id !== subtask.id));
      selectedSubtaskId = null;
    } catch (error) {
      errorMessage = error.message;
    } finally {
      busy = false;
    }
  }

  function moveSubtask(subtask, direction) {
    const index = subtasks.findIndex(item => item.id === subtask.id);
    const nextIndex = index + direction;
    if (nextIndex < 0 || nextIndex >= subtasks.length) return;
    const reordered = [...subtasks];
    [reordered[index], reordered[nextIndex]] = [reordered[nextIndex], reordered[index]];
    void saveSubtasks(reordered);
    selectedSubtaskId = reordered[nextIndex].id;
  }

  async function saveTitle() {
    const nextTitle = title.trim();
    if (nextTitle && nextTitle !== task.title) await updateTask({ title: nextTitle });
    else title = task.title;
  }

  async function saveDescription() {
    if (description !== (task.description || '')) await updateTask({ description });
  }

  function updateDescription(event) {
    const editor = event.currentTarget;
    description = sanitizeDescription(editor.innerHTML);
    if (editor.innerHTML !== description) editor.innerHTML = description;
  }

  async function formatDescription(event, command) {
    event.preventDefault();

    const editor = descriptionEditor;
    if (!editor) return;

    const [action, argument] = command.split(':');
    if (action === 'createLink') {
      const selectedText = String(window.getSelection());
      if (!/^https?:\/\//.test(selectedText)) {
        toast('Select a URL starting with https:// to turn it into a link', { kind: 'info' });
        return;
      }

      document.execCommand(action, false, selectedText);
    } else {
      document.execCommand(action, false, argument ? `<${argument}>` : null);
    }

    updateDescription({ currentTarget: editor });
    await saveDescription();
  }

  async function toggleFavorite() {
    busy = true;
    errorMessage = '';
    try {
      const updated = await httpRequest(taskFavorite(task.id).url, 'PATCH', {});
      onUpdated({ ...task, ...updated, project: task.project, assignee: task.assignee });
    } catch (error) {
      errorMessage = error.message;
    } finally {
      busy = false;
    }
  }

  function openTaskMenu(event) {
    event.stopPropagation();
    if (taskMenuOpen) {
      taskMenuOpen = false;
      return;
    }

    const bounds = event.currentTarget.getBoundingClientRect();
    taskMenuPosition = {
      left: Math.max(8, Math.min(bounds.right - 220, window.innerWidth - 228)),
      top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 320)),
    };
    taskMenuOpen = true;
  }

  function chooseTaskMenuAction(event, action) {
    event.stopPropagation();
    taskMenuOpen = false;

    if (action === 'complete') {
      void updateTask({ status: task.status === 'done' ? 'todo' : 'done' });
      return;
    }

    if (action === 'open') return;

    if (action === 'edit') {
      editOpen = true;
      return;
    }

    if (action === 'status' || action === 'assignee' || action === 'priority' || action === 'project') {
      openPropertyMenu(action, event);
      return;
    }

    if (action === 'favorite') void toggleFavorite();
    if (action === 'copy') void copyLink();
    if (action === 'duplicate') void duplicateTask();
    if (action === 'archive') void archiveTask();
    if (action === 'delete') confirmDeleteOpen = true;
  }

  async function duplicateTask() {
    busy = true;
    errorMessage = '';
    try {
      await httpRequest(storeTask({ project: task.project_id }).url, 'POST', {
        title: `${task.title} (copy)`,
        description: task.description || null,
        status: task.status,
        priority: task.priority,
        due_date: task.due_date?.slice(0, 10) || null,
        start_date: task.start_date?.slice(0, 10) || null,
        assignee_id: task.assignee_id || null,
        labels: task.labels || [],
        subtasks: task.subtasks || [],
        attachments: (task.attachments || []).map(attachment => ({
          id: attachment.id,
          name: attachment.name,
          type: attachment.type || fileType(attachment.name),
          size: attachment.size,
          by: Number(attachment.by)
            || workspaceData.members?.find(member => member.id === attachment.by)?.database_id
            || workspaceData.members?.find(member => member.id === workspaceData.me)?.database_id,
          at: attachment.at,
        })).filter(attachment => attachment.by),
        recurrence: task.recurrence || null,
      });
      toast('Task duplicated');
      router.reload({ preserveScroll: true });
    } catch (error) {
      errorMessage = error.message;
    } finally {
      busy = false;
    }
  }

  async function archiveTask() {
    busy = true;
    errorMessage = '';
    try {
      await httpRequest(archiveTaskRoute(task.id).url, 'PATCH', {});
      toast(`Archived “${task.title}”`);
      onClose();
      router.reload({ preserveScroll: true });
    } catch (error) {
      errorMessage = error.message;
    } finally {
      busy = false;
    }
  }

  async function deleteTask() {
    busy = true;
    errorMessage = '';
    try {
      await httpRequest(deleteTaskRoute(task.id).url, 'DELETE', {});
      confirmDeleteOpen = false;
      toast(`Deleted “${task.title}”`);
      onClose();
      router.reload({ preserveScroll: true });
    } catch (error) {
      errorMessage = error.message;
    } finally {
      busy = false;
    }
  }

  function updateEditedTask(updatedTask) {
    onUpdated({ ...task, ...updatedTask, project: updatedTask.project || task.project, assignee: updatedTask.assignee || task.assignee });
  }

  async function copyLink() {
    const sourceTask = sourceTaskById.get(task.source_key);
    const workspaceUrl = workspaceData.ws?.url;
    const taskKey = sourceTask?.key || task.key;

    if (!workspaceUrl || !taskKey) {
      errorMessage = 'Could not find this task link';
      return;
    }

    try {
      await navigator.clipboard.writeText(`https://gr8rstudio.com/${workspaceUrl}/task/${taskKey}`);
      toast('Link copied');
    } catch {
      toast(`Copy isn't available here — https://gr8rstudio.com/${workspaceUrl}/task/${taskKey}`, { kind: 'info', ms: 6000 });
    }
  }

  function finishEntrance(event) {
    if (event.target === event.currentTarget) entering = false;
  }
</script>

{#if full}<button type="button" class={`drawer-scrim full ${entering ? 'enter' : ''}`} style="border:0;padding:0;cursor:default" aria-label="Close task details" onclick={onClose}></button>{/if}
<div class={`drawer ${entering ? 'enter' : ''} ${full ? 'full' : ''}`} role="dialog" aria-modal={full} aria-labelledby="task-drawer-title" tabindex="-1" onanimationend={finishEntrance}>
  <div class="drawer-h">
    {#if task.project}<Link class="pillbtn" href={projectShow(task.project_id).url} onclick={onClose}><span class="pdot" style={`--c:var(--${task.project.color || 'indigo'})`}></span>{task.project.name}</Link>{/if}
    <span class="faint">/</span><span class="mono faint" style="font-size:11.5px;padding:0 6px">{task.key || task.source_key}</span>
    <span class="sp"></span>
    <Button variant={task.status === 'done' ? 'secondary' : 'ghost'} size="sm" disabled={busy} onclick={() => updateTask({ status: task.status === 'done' ? 'todo' : 'done' })}>{@html ic(task.status === 'done' ? 'circle-check' : 'circle', 14)}<span class="hide-m">{task.status === 'done' ? 'Completed' : 'Mark complete'}</span></Button>
    <button class="ibtn ibtn-sm" aria-label={task.is_favorite ? 'Remove favorite' : 'Favorite'} aria-pressed={task.is_favorite} disabled={busy} onclick={toggleFavorite}>{@html ic('star', 15)}</button>
    <button class="ibtn ibtn-sm" aria-label="Copy task link" onclick={copyLink}>{@html ic('link', 15)}</button>
    <button class="ibtn ibtn-sm hide-m" aria-label={full ? 'Show side panel' : 'Show full page'} aria-pressed={full} onclick={() => full = !full}>{@html ic(full ? 'minimize-2' : 'maximize-2', 15)}</button>
    <button class="ibtn ibtn-sm" type="button" aria-label="More" aria-haspopup="menu" aria-expanded={taskMenuOpen} data-drawer-task-menu-trigger onclick={openTaskMenu}>{@html ic('ellipsis', 15)}</button>
    <button class="ibtn ibtn-sm" aria-label="Close" onclick={onClose}>{@html ic('x', 16)}</button>
  </div>
  <div class="drawer-b">
    {#if errorMessage}<div class="alert danger" role="alert">{@html ic('circle-alert', 14)}<span>{errorMessage}</span></div>{/if}
    {#if selectedSubtask}
      {@const subtaskIndex = subtasks.findIndex(subtask => subtask.id === selectedSubtask.id)}
      <div class="subview">
        <button type="button" class="pillbtn" style="margin:-4px 0 14px -7px;font-size:12.5px;color:var(--text-2)" onclick={() => selectedSubtaskId = null}>{@html ic('arrow-left', 14)}<span class="trunc" style="max-width:420px">{task.title}</span></button>
        <div class="row" style="gap:10px;align-items:flex-start">
          <input type="checkbox" class="check round" style="margin-top:9px" checked={selectedSubtask.done} disabled={busy} aria-label={selectedSubtask.done ? 'Mark subtask not done' : 'Mark subtask done'} onchange={() => toggleSubtask(selectedSubtask)} />
          <input class="ttl-edit" aria-label="Subtask title" value={selectedSubtask.title} disabled={busy} style={selectedSubtask.done ? 'text-decoration:line-through;color:var(--text-3)' : ''} onblur={event => event.currentTarget.value.trim() && event.currentTarget.value.trim() !== selectedSubtask.title && updateSubtask(selectedSubtask.id, { title: event.currentTarget.value.trim() })} />
        </div>
        <div class="faint" style="font-size:12px;margin:4px 0 0 26px">Subtask {subtaskIndex + 1} of {subtasks.length} · {selectedSubtask.done ? 'Done' : 'Open'}</div>
        <dl class="kv" style="margin-top:16px">
          <dt>{@html ic('user', 14)}Assignee</dt><dd><Select class="select" aria-label="Subtask assignee" value={selectedSubtask.assignee || ''} disabled={busy} onchange={event => updateSubtask(selectedSubtask.id, { assignee: event.currentTarget.value || null })}><option value="">Unassigned</option>{#each workspaceData.members || [] as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</Select></dd>
          <dt>{@html ic('calendar', 14)}Due date</dt><dd><Input class="" type="date" aria-label="Subtask due date" value={selectedSubtask.due || ''} disabled={busy} onchange={event => updateSubtask(selectedSubtask.id, { due: event.currentTarget.value || null })} /></dd>
          <dt>{@html ic('folder', 14)}Parent</dt><dd><button type="button" class="pillbtn" onclick={() => selectedSubtaskId = null}><span class="mono faint" style="font-size:11px">{task.key}</span>{task.title}</button></dd>
        </dl>
        <section class="dsec"><div class="dsec-h"><h3>Notes</h3></div><Textarea aria-label="Subtask notes" rows="4" placeholder="Add details, links, or acceptance criteria…" value={selectedSubtask.note || ''} disabled={busy} onblur={event => updateSubtask(selectedSubtask.id, { note: event.currentTarget.value })} /></section>
        <div class="dsec row" style="gap:6px;flex-wrap:wrap">
          <Button type="button" variant="secondary" size="sm" disabled={busy} onclick={() => convertSubtask(selectedSubtask)}>{@html ic('arrow-up-right', 13)}Convert to task</Button>
          <span class="sp"></span>
          <Button type="button" variant="ghost" size="sm" disabled={subtaskIndex === 0} aria-label="Previous subtask" onclick={() => moveSubtask(selectedSubtask, -1)}>{@html ic('chevron-left', 14)}Previous</Button>
          <Button type="button" variant="ghost" size="sm" disabled={subtaskIndex === subtasks.length - 1} aria-label="Next subtask" onclick={() => moveSubtask(selectedSubtask, 1)}>Next{@html ic('chevron-right', 14)}</Button>
          <Button type="button" variant="danger-ghost" size="sm" disabled={busy} onclick={() => removeSubtask(selectedSubtask.id)}>{@html ic('trash-2', 13)}Delete</Button>
        </div>
      </div>
    {:else}
    {#if task.status === 'done'}<div class="alert ok" style="margin-bottom:12px">{@html ic('circle-check', 15)}<span>Task completed. <button class="link" onclick={() => updateTask({ status: 'todo' })}>Reopen task</button></span></div>{/if}
    <input class="ttl-edit" id="task-drawer-title" aria-label="Task title" bind:value={title} onblur={saveTitle} onkeydown={event => event.key === 'Enter' && event.currentTarget.blur()} />

    <dl class="kv" style={`margin-top:12px;${full ? 'grid-template-columns:112px minmax(0,1fr) 112px minmax(0,1fr)' : ''}`}>
      <dt>{@html ic('circle-dot', 14)}Status</dt><dd><button type="button" class="pillbtn" data-drawer-property-trigger aria-label={`Status: ${statuses.find(item => item.id === task.status)?.name || task.status}`} aria-expanded={propertyMenu === 'status'} disabled={busy} onclick={event => openPropertyMenu('status', event)}>{@html stPill(task.status)}</button></dd>
      <dt>{@html ic('signal-high', 14)}Priority</dt><dd><button type="button" class={`pillbtn ${task.priority === 'none' ? 'empty' : ''}`} data-drawer-property-trigger aria-label={`Priority: ${task.priority === 'none' ? 'No priority' : task.priority}`} aria-expanded={propertyMenu === 'priority'} disabled={busy} onclick={event => openPropertyMenu('priority', event)}>{@html prPill(task.priority)}</button></dd>
      <dt>{@html ic('user', 14)}Assignee</dt><dd><button type="button" class={`pillbtn ${task.assignee ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Assignee" aria-expanded={propertyMenu === 'assignee'} disabled={busy} onclick={event => openPropertyMenu('assignee', event)}>{#if task.assignee}<span class="av sm" style={`--c:${membersByDatabaseId.get(String(task.assignee.id))?.c || '#8A867E'}`}>{task.assignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{/if}<span class="trunc">{task.assignee?.name || 'Unassigned'}</span></button></dd>
      <dt>{@html ic('calendar', 14)}Due date</dt><dd><button type="button" class={`pillbtn ${task.due_date ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Due date" aria-expanded={propertyMenu === 'due'} disabled={busy} onclick={event => openPropertyMenu('due', event)}>{@html ic('calendar', 13)}<span>{formatDate(task.due_date)}</span></button></dd>
      <dt>{@html ic('calendar-arrow-up', 14)}Start date</dt><dd><button type="button" class={`pillbtn ${task.start_date ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Start date" aria-expanded={propertyMenu === 'start'} disabled={busy} onclick={event => openPropertyMenu('start', event)}>{@html ic('calendar-arrow-up', 13)}<span>{formatStartDate(task.start_date)}</span></button></dd>
      <dt>{@html ic('folder', 14)}Project</dt><dd>{#if task.project}<button type="button" class="pillbtn" data-drawer-property-trigger aria-label="Project" aria-expanded={propertyMenu === 'project'} disabled={busy} onclick={event => openPropertyMenu('project', event)}><span class="pdot" style={`--c:${PCOLORS[task.project.color] || PCOLORS.indigo};border-radius:50%;width:8px;height:8px`}></span><span class="trunc">{task.project.name}</span></button>{:else}<span class="pillbtn">—</span>{/if}</dd>
      <dt>{@html ic('tag', 14)}Labels</dt><dd><button type="button" class={`pillbtn ${labels.length ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Labels" aria-expanded={propertyMenu === 'labels'} disabled={busy} style="flex-wrap:wrap;height:auto;min-height:26px;padding:3px 7px" onclick={event => openPropertyMenu('labels', event)}>{#if labels.length}{@html labels.map(lbl).join('')}{:else}{@html ic('tag', 13)}Add labels{/if}</button></dd>
      <dt>{@html ic('repeat', 14)}Repeat</dt><dd><button type="button" class={`pillbtn ${task.recurrence ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Repeat" aria-expanded={propertyMenu === 'recurrence'} disabled={busy} onclick={event => openPropertyMenu('recurrence', event)}>{@html ic('repeat', 13)}{task.recurrence || 'Does not repeat'}</button></dd>
      <dt>{@html ic('timer', 14)}Estimate</dt><dd><button type="button" class={`pillbtn ${task.estimate ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Estimate" aria-expanded={propertyMenu === 'estimate'} disabled={busy} onclick={event => openPropertyMenu('estimate', event)}>{@html ic('timer', 13)}{task.estimate || 'Add estimate'}</button></dd>
      <dt>{@html ic('git-branch', 14)}Blocked by</dt><dd><button type="button" class={`pillbtn ${blockedByTasks.length ? '' : 'empty'}`} data-drawer-property-trigger aria-label="Blocked by" aria-expanded={propertyMenu === 'dependencies'} disabled={busy} style="height:auto;min-height:26px;flex-wrap:wrap" onclick={event => openPropertyMenu('dependencies', event)}>{#if blockedByTasks.length}{#each blockedByTasks as blockedTask (blockedTask.id)}<span class="depchip mono" style="font-size:11px;padding:1px 5px;border-radius:4px;background:var(--surface-3)">{blockedTask.key}</span><span class="trunc" style="max-width:160px">{blockedTask.title}</span>{/each}{:else}{@html ic('git-branch', 13)}None{/if}</button></dd>
    </dl>

    <section class="dsec">
      <div class="dsec-h">
        <h3 id="task-description-label">Description</h3>
        <div class="rte-tb" role="toolbar" aria-label="Formatting" aria-controls="task-description-editor">
          <button type="button" class="ibtn ibtn-xs" aria-label="Bold" title="Bold" onmousedown={event => event.preventDefault()} onclick={event => formatDescription(event, 'bold')}>{@html ic('bold', 13)}</button>
          <button type="button" class="ibtn ibtn-xs" aria-label="Italic" title="Italic" onmousedown={event => event.preventDefault()} onclick={event => formatDescription(event, 'italic')}>{@html ic('italic', 13)}</button>
          <button type="button" class="ibtn ibtn-xs" aria-label="Bulleted list" title="Bulleted list" onmousedown={event => event.preventDefault()} onclick={event => formatDescription(event, 'insertUnorderedList')}>{@html ic('list', 13)}</button>
          <button type="button" class="ibtn ibtn-xs" aria-label="Numbered list" title="Numbered list" onmousedown={event => event.preventDefault()} onclick={event => formatDescription(event, 'insertOrderedList')}>{@html ic('list-ordered', 13)}</button>
          <button type="button" class="ibtn ibtn-xs" aria-label="Heading" title="Heading" onmousedown={event => event.preventDefault()} onclick={event => formatDescription(event, 'formatBlock:h4')}>{@html ic('heading', 13)}</button>
          <button type="button" class="ibtn ibtn-xs" aria-label="Link" title="Link" onmousedown={event => event.preventDefault()} onclick={event => formatDescription(event, 'createLink')}>{@html ic('link', 13)}</button>
        </div>
      </div>
      <div class="rte">
        <div class="rte-body" id="task-description-editor" bind:this={descriptionEditor} contenteditable="true" data-ph="Add a description…" role="textbox" aria-multiline="true" aria-labelledby="task-description-label" oninput={updateDescription} onblur={saveDescription}></div>
      </div>
    </section>

    <section class="dsec"><div class="dsec-h"><h3>Subtasks</h3><span class="cnt num">{subtasks.filter(subtask => subtask.done).length}/{subtasks.length}</span>{#if subtasks.length}<span style="width:90px;display:flex">{@html progBar(Math.round((subtasks.filter(subtask => subtask.done).length / subtasks.length) * 100), subtasks.every(subtask => subtask.done) ? 'green' : '')}</span>{/if}</div>
      {#each subtasks as subtask (subtask.id)}{@const subtaskMember = membersBySourceKey.get(subtask.assignee)}<div class="subt" class:done={subtask.done}>
        <input type="checkbox" class="check" checked={subtask.done} disabled={busy} aria-label={`${subtask.done ? 'Reopen' : 'Complete'} subtask ${subtask.title}`} onchange={() => toggleSubtask(subtask)} />
        <button type="button" class="s" onclick={() => openSubtask(subtask.id)}>{subtask.title}</button>
        {#if subtask.due}<span class={`due ${!subtask.done && new Date(`${subtask.due}T00:00:00`) < new Date(new Date().toDateString()) ? 'over' : ''}`}>{@html ic('calendar', 12)}{relDate(subtask.due)}</span>{/if}
        {#if subtaskMember}<span class="av sm" style={`--c:${subtaskMember.c}`} aria-label={subtaskMember.name}>{subtaskMember.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{/if}
        <button type="button" class="ibtn ibtn-xs x" aria-label="Open subtask details" tabindex="-1" onclick={() => openSubtask(subtask.id)}>{@html ic('chevron-right', 14)}</button>
      </div>{/each}
      <div class="subt" style="color:var(--text-3)">{@html ic('plus', 15)}<input class="inline-in" bind:value={subtaskTitle} placeholder="Add subtask" aria-label="Add subtask" style="font-size:13.5px" onkeydown={event => event.key === 'Enter' && addSubtask(event)} /></div>
    </section>

    <section class="dsec"><div class="dsec-h"><h3>Attachments</h3><span class="cnt">{attachments.length}</span><div class="acts"><Button type="button" variant="ghost" size="sm" onclick={() => attachmentInput?.click()}>{@html ic('upload', 13)}Upload</Button><input bind:this={attachmentInput} type="file" multiple hidden onchange={addAttachments} /></div></div>
      {#if attachments.length}<div class="att">{#each attachments as attachment (attachment.id)}{@const typeDetails = FT[attachment.type] || FT.other}<div class="attc" role="button" tabindex="0" aria-label={attachment.name} onclick={() => openAttachment(attachment)} onkeydown={event => handleAttachmentKeydown(event, attachment)}>{@html filePrev(attachment)}<div class="fi"><div class="trunc" style="font-weight:500">{attachment.name}</div><div class="faint">{typeDetails.n} · {attachment.size}</div></div><button type="button" class="ibtn ibtn-xs x" aria-label={`Remove attachment ${attachment.name}`} onclick={event => { event.stopPropagation(); removeAttachment(attachment); }}>{@html ic('x', 12)}</button></div>{/each}</div>{:else}<button type="button" class="dropzone" style="padding:12px" onclick={() => attachmentInput?.click()}>{@html ic('paperclip', 15)}<span>Drop files or click to attach</span></button>{/if}
    </section>

    <section class="dsec"><div class="tabs" style="margin-bottom:6px" role="tablist" aria-label="Task conversation"><button class="tab" class:on={activeTab === 'comments'} role="tab" aria-selected={activeTab === 'comments'} onclick={() => activeTab = 'comments'}>Comments <span class="cnt">{taskComments.length}</span></button><button class="tab" class:on={activeTab === 'activity'} role="tab" aria-selected={activeTab === 'activity'} onclick={() => activeTab = 'activity'}>Activity <span class="cnt">{taskActivity.length}</span></button></div>
      {#if activeTab === 'comments'}
        {#if taskComments.length}{#each taskComments as comment (comment.id)}{@const member = membersBySourceKey.get(comment.by)}<article class="cmt"><span class="av md" style={`--c:${member?.c || '#8A867E'}`}>{member?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><div class="body"><div class="who"><b>{member?.name || 'Someone'}</b><time>{formatTime(comment.at)}</time>{#if comment.by === workspaceData.me}<span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Delete comment" title="Delete" onclick={() => deleteComment(comment)}>{@html ic('trash-2', 12)}</button>{/if}</div><div class="txt">{@html fmtComment(comment.text)}</div><div class="reacts">{#each Object.entries(comment.re || {}).filter(([, reactions]) => reactions.length) as [emoji, reactions]}<button type="button" class={`react ${reactions.includes(workspaceData.me) ? 'mine' : ''}`} aria-label={`${emoji} ${reactions.length}`} title={reactions.map(memberId => membersBySourceKey.get(memberId)?.name || memberId).join(', ')} onclick={() => toggleReaction(comment, emoji)}>{emoji}<span class="num">{reactions.length}</span></button>{/each}<button type="button" class="react" data-comment-reaction-trigger aria-label="Add reaction" aria-expanded={reactionMenuCommentId === comment.id} style="color:var(--text-3)" onclick={event => toggleReactionMenu(event, comment)}>{@html ic('smile-plus', 13)}</button></div></div></article>{/each}{:else}<p class="faint" style="font-size:13px;margin:10px 0">No comments yet. Start the conversation.</p>{/if}
        <div class="row" style="align-items:flex-start;gap:10px;margin-top:8px"><span class="av md" style={`--c:${workspaceData.members?.find(member => member.id === workspaceData.me)?.c || '#8A867E'}`}>{workspaceData.members?.find(member => member.id === workspaceData.me)?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><div class="grow" style="position:relative"><form class="cbox" onsubmit={postComment}><textarea bind:this={commentInput} bind:value={commentDraft} oninput={updateCommentDraft} onkeydown={handleCommentKeydown} placeholder="Leave a comment… type @ to mention" aria-label="Comment" rows="2"></textarea><div class="row"><button type="button" class="ibtn ibtn-xs" aria-label="Mention" title="Mention someone" onclick={startMention}>{@html ic('at-sign', 14)}</button><span class="sp"></span><span class="faint hide-m" style="font-size:11px">⌘+Enter</span><Button type="submit" variant="primary" size="sm">Comment</Button></div></form>{#if mentionQuery != null && mentionCandidates.length}<Dropdown class="pop enter" role="menu" aria-label="Mention someone" style="position:absolute;bottom:calc(100% + 4px);left:0">{#each mentionCandidates as member (member.id)}<button type="button" class="mi" role="menuitem" onclick={() => insertMention(member)}><span class="av sm" style={`--c:${member.c || '#8A867E'}`}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>{member.name}<span class="r">{member.title}</span></button>{/each}</Dropdown>{/if}</div></div>
      {:else}
        {#if taskActivity.length}{#each taskActivity as activity (activity.id)}{@const member = membersBySourceKey.get(activity.by)}<div class="act-line"><span class="ico"><span class="av sm" style={`--c:${member?.c || '#8A867E'}`}>{member?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span></span><span class="grow"><b>{activity.by === workspaceData.me ? 'You' : member?.name || 'Someone'}</b> {activity.verb.replace(/ of$/, '')} {activity.extra}</span><time class="faint" style="font-size:11.5px">{formatTime(activity.at)}</time></div>{/each}{/if}<div class="act-line"><span class="ico">{@html ic('plus', 13)}</span><span class="grow">Task created</span><time class="faint" style="font-size:11.5px">{formatTime(sourceTaskById.get(task.source_key)?.created)}</time></div>
      {/if}
    </section>
    {/if}
  </div>
  {#if taskMenuOpen}
    <Dropdown class="pop enter" data-drawer-task-menu role="menu" aria-label="Task actions" style={`left:${taskMenuPosition.left}px;top:${taskMenuPosition.top}px;width:220px`}>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'open')}>{@html ic('panel-right-open', 15)}Open</button>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'edit')}>{@html ic('pencil', 15)}Edit<span class="r"><kbd>E</kbd></span></button>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'complete')}>{@html ic(task.status === 'done' ? 'rotate-ccw' : 'circle-check', 15)}{task.status === 'done' ? 'Reopen' : 'Mark complete'}</button>
      <div class="msep"></div>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'status')}>{@html ic('circle-dot', 15)}Status…</button>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'assignee')}>{@html ic('user', 15)}Assign to…</button>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'priority')}>{@html ic('signal-high', 15)}Priority…</button>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'project')}>{@html ic('folder-input', 15)}Move to project…</button>
      <div class="msep"></div>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'favorite')}>{@html ic('star', 15)}{task.is_favorite ? 'Remove from favorites' : 'Add to favorites'}</button>
      <button class="mi" type="button" role="menuitem" onclick={event => chooseTaskMenuAction(event, 'copy')}>{@html ic('link', 15)}Copy link</button>
      <button class="mi" type="button" role="menuitem" disabled={busy} onclick={event => chooseTaskMenuAction(event, 'duplicate')}>{@html ic('copy', 15)}Duplicate</button>
      <button class="mi" type="button" role="menuitem" disabled={busy} onclick={event => chooseTaskMenuAction(event, 'archive')}>{@html ic('archive', 15)}Archive</button>
      <div class="msep"></div>
      <button class="mi danger" type="button" role="menuitem" disabled={busy} onclick={event => chooseTaskMenuAction(event, 'delete')}>{@html ic('trash-2', 15)}Delete<span class="r"><kbd>Del</kbd></span></button>
    </Dropdown>
  {/if}
  {#if reactionMenuCommentId}
    <Dropdown class="pop enter" data-comment-reaction-menu role="menu" aria-label="Add a reaction" style={`left:${reactionMenuPosition.left}px;top:${reactionMenuPosition.top}px;min-width:0`}>
      <div class="row" style="gap:2px;padding:2px">{#each ['👍', '🎉', '❤️', '👀', '🚀', '✅'] as emoji (emoji)}<button type="button" class="ibtn" role="menuitem" aria-label={`React ${emoji}`} style="font-size:16px" onclick={() => { const comment = taskComments.find(item => item.id === reactionMenuCommentId); if (comment) toggleReaction(comment, emoji); }}>{emoji}</button>{/each}</div>
    </Dropdown>
  {/if}
  {#if selectedAttachment}
    {@const typeDetails = FT[selectedAttachment.type] || FT.other}
    {@const author = membersBySourceKey.get(selectedAttachment.by)}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (selectedAttachment = null)}>
      <div class="modal lg" transition:scale={{ duration: 260, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="task-file-preview-title" tabindex="-1">
        <div class="modal-h"><div><h2 id="task-file-preview-title">{selectedAttachment.name}</h2><p class="faint" style="margin:3px 0 0;font-size:12px">{typeDetails.n} · {selectedAttachment.size} · Uploaded by {author?.name || 'you'} {formatTime(selectedAttachment.at)}</p></div><button type="button" class="ibtn" aria-label="Close preview" onclick={() => selectedAttachment = null}>{@html ic('x', 16)}</button></div>
        <div class="modal-b"><div style="border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden">{@html filePrev(selectedAttachment).replace('class="fprev"', 'class="fprev" style="aspect-ratio:16/9"')}</div><div class="row faint" style="font-size:12.5px;margin-top:12px">{@html ic('link', 13)}Attached to {task.title}</div></div>
        <div class="modal-f"><span class="sp"></span><Button type="button" variant="primary" size="md" onclick={() => selectedAttachment = null}>Close</Button></div>
      </div>
    </div>
  {/if}
  {#if propertyMenu}
    <Dropdown class="pop enter" data-drawer-property-popup role="menu" style={`left:${propertyMenuPosition.left}px;top:${propertyMenuPosition.top}px;${propertyMenu === 'due' || propertyMenu === 'start' ? 'min-width:0' : `width:${propertyMenu === 'project' ? 220 : 200}px`}`}>
      {#if propertyMenu === 'project'}
        <div class="pin"><input bind:value={projectQuery} placeholder="Move to project…" aria-label="Move to project…" /></div>
        {#each assignableProjects as project (project.database_id)}
          <button class="mi" type="button" role="menuitemradio" aria-checked={String(task.project_id) === String(project.database_id)} onclick={() => void saveTaskProperty('project_id', project.database_id)}>
            <span class="pdot" style={`--c:${PCOLORS[project.color] || PCOLORS.indigo};border-radius:50%;width:8px;height:8px`}></span><span class="trunc">{project.name}</span>
            {#if String(task.project_id) === String(project.database_id)}<span class="ck">{@html ic('check', 14)}</span>{/if}
          </button>
        {/each}
        {#if !assignableProjects.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
      {:else if propertyMenu === 'status'}
        {#each statuses as item, index (item.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={task.status === item.id} onclick={() => void saveTaskProperty('status', item.id)}>{@html stIcon(item.id, 14)}<span>{item.name}</span><span class="r"><kbd>{index + 1}</kbd></span>{#if task.status === item.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      {:else if propertyMenu === 'priority'}
        {#each priorities as priority (priority)}<button type="button" class="mi" role="menuitemradio" aria-checked={task.priority === priority} onclick={() => void saveTaskProperty('priority', priority)}>{@html prIcon(priority, 14)}<span>{priority === 'none' ? 'No priority' : priority[0].toUpperCase() + priority.slice(1)}</span>{#if task.priority === priority}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      {:else if propertyMenu === 'assignee'}
        <div class="pin"><input bind:value={assigneeQuery} placeholder="Assign to…" aria-label="Assign to…" /></div>
        <button type="button" class="mi" role="menuitemradio" aria-checked={!task.assignee_id} onclick={() => void saveTaskProperty('assignee_id', null)}>{@html ic('user-round', 14)}<span>Unassigned</span>{#if !task.assignee_id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>
        {#each assignableMembers as member (member.database_id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(task.assignee_id) === String(member.database_id)} onclick={() => void saveTaskProperty('assignee_id', member.database_id)}><span class="av sm" style={`--c:${member.c || '#8A867E'}`}>{member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span><span class="trunc">{member.name}{member.id === workspaceData.me ? ' (you)' : ''}</span>{#if String(task.assignee_id) === String(member.database_id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        {#if !assignableMembers.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
      {:else if propertyMenu === 'labels'}
        <div class="pin"><input bind:value={labelQuery} placeholder="Filter labels…" aria-label="Filter labels…" /></div>
        {#each filteredLabels as label (label.id)}<button type="button" class="mi" role="menuitemcheckbox" aria-checked={labels.includes(label.id)} onclick={() => toggleLabel(label.id)}><span class="pdot" style={`--c:${label.c};border-radius:50%;width:8px;height:8px`}></span><span>{label.name}</span>{#if labels.includes(label.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        {#if !filteredLabels.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
      {:else if propertyMenu === 'recurrence'}
        {#each recurrenceOptions as option (option)}<button type="button" class="mi" role="menuitemradio" aria-checked={(task.recurrence || 'Does not repeat') === option} onclick={() => void saveTaskProperty('recurrence', option === 'Does not repeat' ? null : option)}>{@html ic('repeat', 14)}<span>{option}</span>{#if (task.recurrence || 'Does not repeat') === option}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      {:else if propertyMenu === 'estimate'}
        {#each estimateOptions as estimate (estimate)}<button type="button" class="mi" role="menuitemradio" aria-checked={task.estimate === estimate} onclick={() => void saveTaskProperty('estimate', estimate)}>{@html ic('timer', 14)}<span>{estimate}</span>{#if task.estimate === estimate}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        <div class="msep"></div><button type="button" class="mi" onclick={() => void saveTaskProperty('estimate', null)}>{@html ic('x', 14)}<span>Clear estimate</span></button>
      {:else if propertyMenu === 'dependencies'}
        <div class="pin"><input bind:value={dependencyQuery} placeholder="Blocked by…" aria-label="Blocked by…" /></div>
        {#each dependencyCandidates as blockedTask (blockedTask.id)}<button type="button" class="mi" role="menuitemcheckbox" aria-checked={(task.dependencies || []).includes(blockedTask.id)} onclick={() => toggleDependency(blockedTask.id)}>{@html stIcon(blockedTask.status, 14)}<span class="trunc">{blockedTask.key} {blockedTask.title}</span>{#if (task.dependencies || []).includes(blockedTask.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        {#if !dependencyCandidates.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
      {:else if propertyMenu === 'due' || propertyMenu === 'start'}
        {@const currentDate = propertyMenu === 'due' ? task.due_date?.slice(0, 10) || '' : task.start_date?.slice(0, 10) || ''}
        <div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">
          {#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7 - ((TODAY.getDay() + 6) % 7)], ['In 2 weeks', 14]] as [shortcut, offset] (shortcut)}<button type="button" class="badge" style="cursor:pointer" onclick={() => selectDate(iso(addD(TODAY, offset)))}>{shortcut}</button>{/each}
          {#if currentDate}<button type="button" class="badge" style="cursor:pointer;color:var(--red)" onclick={() => selectDate('')}>Clear</button>{/if}
        </div>
        <div class="msep"></div>
        <div style="padding:6px 6px 2px;width:252px">
          <div class="row" style="margin-bottom:6px"><b style="font-weight:600;font-size:13px;padding-left:4px">{MONL[calendarMonth.getMonth()]} {calendarMonth.getFullYear()}</b><span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Previous month" onclick={() => shiftCalendarMonth(-1)}>{@html ic('chevron-left', 14)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Next month" onclick={() => shiftCalendarMonth(1)}>{@html ic('chevron-right', 14)}</button></div>
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:11px">{#each calendarWeekdays as weekday}<span class="faint" style="padding:3px 0">{weekday}</span>{/each}
            {#each calendarDays as day (iso(day))}
              {@const dayIso = iso(day)}
              {@const isSelected = dayIso === currentDate}
              {@const isToday = diffD(day, TODAY) === 0}
              <button type="button" class="num" aria-label={fmtDate(dayIso, true)} aria-pressed={isSelected} style={`height:30px;border-radius:6px;font-size:12px;${isSelected ? 'background:var(--accent);color:var(--on-accent);font-weight:600' : isToday ? 'color:var(--accent);font-weight:600;box-shadow:inset 0 0 0 1px var(--accent-line)' : day.getMonth() !== calendarMonth.getMonth() ? 'color:var(--text-3)' : ''}`} onclick={() => selectDate(dayIso)}>{day.getDate()}</button>
            {/each}
          </div>
        </div>
      {/if}
    </Dropdown>
  {/if}
</div>
{#if editOpen}
  <WorkspaceTaskForm
    open={editOpen}
    projects={workspaceData.projects || []}
    members={workspaceData.members || []}
    projectOrder={(workspaceData.projects || []).map(project => project.database_id).filter(Boolean)}
    initialProjectId={task.project_id}
    initialStatus={task.status}
    initialDate={task.due_date || ''}
    initialStartDate={task.start_date || ''}
    initialAssignee={task.assignee_id}
    editingTask={task}
    onClose={() => editOpen = false}
    onUpdated={updateEditedTask}
  />
{/if}
{#if confirmDeleteOpen}
  <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (confirmDeleteOpen = false)}>
    <div class="modal sm" transition:scale={{ duration: 260, start: 0.97 }} role="alertdialog" aria-modal="true" aria-labelledby="delete-task-title" aria-describedby="delete-task-description">
      <div class="modal-h"><div><h2 id="delete-task-title">Delete task?</h2><p id="delete-task-description" class="muted" style="font-size:12.5px;margin:4px 0 0"><b>{task.title}</b>{subtasks.length ? `, its ${subtasks.length} subtasks,` : ''} and {taskComments.length} comment{taskComments.length === 1 ? '' : 's'} will be permanently deleted.</p></div><button class="ibtn ibtn-sm" type="button" aria-label="Close" onclick={() => confirmDeleteOpen = false}>{@html ic('x', 16)}</button></div>
      <div class="modal-f"><span class="sp"></span><Button variant="secondary" size="md" type="button" disabled={busy} onclick={() => confirmDeleteOpen = false}>Cancel</Button><Button variant="danger" size="md" type="button" disabled={busy} onclick={deleteTask}>{busy ? 'Deleting…' : 'Delete task'}</Button></div>
    </div>
  </div>
{/if}
<svelte:window onpointerdown={handleWindowPointerdown} onkeydown={handlePropertyKeydown} />
