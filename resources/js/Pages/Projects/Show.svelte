<script>
  import Label from "../../Components/Label.svelte";
  import { onMount, tick } from 'svelte';
  import { fade, scale } from 'svelte/transition';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import ProjectCalendar from '../../Components/ProjectCalendar.svelte';
  import ProjectTimeline from '../../Components/ProjectTimeline.svelte';
  import WorkspaceTaskList from '../../Components/WorkspaceTaskList.svelte';
  import { update as taskUpdate } from '../../routes/tasks';
  import { store as taskStore } from '../../routes/projects/tasks';
  import { destroy as projectDestroy, duplicate as projectDuplicate, favorite as projectFavorite, index as projectsIndex, update as projectUpdate } from '../../routes/projects';
  import { update as projectMembersUpdate } from '../../routes/projects/members';
  import { show as teamShow } from '../../routes/teams';
  import { useHttpRequest } from '../../lib/http.js';
  import { consumeNavigationIntent } from '../../lib/navigation-intents.js';
  import { ago, dayBucket, fmtDate, relDate, uid } from '@/src/core/utils.js';
  import { save as saveWorkspaceStore } from '@/src/core/store.js';
  import { S } from '@/src/core/store.js';
  import { ic } from '@/src/core/icons.js';
  import { toast } from '@/src/ui/toast.js';
  import { LABELS, PCOLORS, PSTAT, TEAMS_SEED } from '@/src/core/constants.js';
  import { FT, empty, filePrev, fileType as getFileType, fsize, lbl, prIcon, stIcon } from '@/src/ui/helpers.js';

  const httpRequest = useHttpRequest();
  let { project, projects = [], workspaceData = {}, activeTab = 'board' } = $props();
  const page = usePage();
  let tasks = $state([]);
  $effect(() => { tasks = [...(project.tasks || [])]; });
  function getInitialTab() {
    return activeTab;
  }
  let tab = $state(getInitialTab());
  let savedViewQuery = $state('');
  let savedViewError = $state('');
  let savedFilterOpen = $state(false);
  let savedFilterEditIndex = $state(null);
  let savedFilterPosition = $state({ left: 0, top: 0 });
  let savedValueOpenIndex = $state(null);
  let savedValuePosition = $state({ left: 0, top: 0 });
  let projectViewFilters = $state([]);
  let customSavedViews = $state([]);
  let saveViewOpen = $state(false);
  let saveViewName = $state('');
  let saveViewNameInput = $state(null);
  let saveViewType = $state('list');
  let showTaskForm = $state(false);
  let taskFormStatus = $state('todo');
  let taskFormDueDate = $state('');
  let errorMessage = $state('');
  let isFavorite = $state(false);
  let fileQuery = $state('');
  let fileType = $state('all');
  let fileSort = $state('date');
  let fileView = $state('grid');
  let selectedFile = $state(null);
  let uploadingProjectFiles = $state([]);
  let projectFileRecords = $state([]);
  const projectUploadTimers = new Map();
  let fileMenu = $state(null);
  let fileMenuPosition = $state({ left: 8, top: 8 });
  let fileRenameTarget = $state(null);
  let fileRenameName = $state('');
  let fileDeleteTarget = $state(null);
  let statusMenu = $state(null);
  let tableCellMenu = $state(null);
  let tableCellQuery = $state('');
  let tableTitleEditId = $state(null);
  let tableTitleDraft = $state('');
  let tableEstimateEditId = $state(null);
  let tableEstimateDraft = $state('');
  let projectStatusMenu = $state(null);
  let projectMorePosition = $state({ left: 0, top: 0 });
  let projectMoreOpen = $state(false);
  let projectConfirmation = $state(null);
  let projectConfirmationText = $state('');
  let projectConfirmationBusy = $state(false);
  let membersDialogOpen = $state(false);
  let editDialogOpen = $state(false);
  let projectDialogBusy = $state(false);
  let projectDialogError = $state('');
  let memberDialogError = $state('');
  let memberQuery = $state('');
  let selectedMemberIds = $state([]);
  let projectForm = $state({ name: '', description: '', status: 'active', start_date: '', due_date: '' });
  let drawerTaskId = $state(null);
  let taskSearch = $state('');
  let projectOverviewStatusFilter = $state('');
  let projectListQuery = $state('');
  let projectListGroup = $state('status');
  let projectListSort = $state('manual');
  let projectListSortDirection = $state(1);
  let projectListMenu = $state(null);
  let projectListMenuPosition = $state({ left: 8, top: 8 });
  let projectListFilters = $state([]);
  let projectListFilterEditIndex = $state(null);
  let projectListValueOpenIndex = $state(null);
  function getInitialProjectTablePreferences() {
    const viewKey = `p:${project.source_key || project.id}`;
    const savedView = S.views[viewKey];
    return {
      viewKey,
      hidden: savedView?.hidden || ['start', 'deps', 'created', 'project'],
      columnWidths: { ...(savedView?.colW || {}) },
    };
  }
  const initialProjectTablePreferences = getInitialProjectTablePreferences();
  const projectTableViewKey = initialProjectTablePreferences.viewKey;
  let projectTableHidden = $state(initialProjectTablePreferences.hidden);
  let projectTableColumnWidths = $state(initialProjectTablePreferences.columnWidths);
  const projectTableColumnDefinitions = [
    ['title', 'Task', 314], ['status', 'Status', 132], ['priority', 'Priority', 112],
    ['assignee', 'Assignee', 156], ['due', 'Due date', 112], ['start', 'Start date', 112],
    ['labels', 'Labels', 180], ['deps', 'Dependencies', 150], ['estimate', 'Estimate', 92],
    ['created', 'Created', 112], ['project', 'Project', 156],
  ];
  let projectTableColumns = $derived(projectTableColumnDefinitions
    .filter(([field]) => field === 'title' || !projectTableHidden.includes(field))
    .map(([field, label, width]) => [field, label, projectTableColumnWidths[field] || width]));
  let drawerTask = $derived.by(() => {
    const selectedTask = tasks.find(task => task.id === drawerTaskId);
    return selectedTask
      ? {
        ...selectedTask,
        project: { id: project.id, name: project.name, key: project.key, color: project.color },
        project_id: project.id,
      }
      : null;
  });
  let filteredBoardTasks = $derived(tasks.filter(task => !taskSearch.trim() || `${task.title} ${task.key || ''}`.toLowerCase().includes(taskSearch.trim().toLowerCase())));
  let projectListTasks = $derived.by(() => {
    const filtered = tasks.filter(task => (!projectOverviewStatusFilter || task.status === projectOverviewStatusFilter)
      && (!projectListQuery.trim() || `${task.title} ${task.key || ''}`.toLowerCase().includes(projectListQuery.trim().toLowerCase()))
      && projectListFilters.every(filter => matchesProjectListFilter(task, filter)));
    if (projectListSort === 'manual') return filtered;
    return filtered.sort((first, second) => {
      if (projectListSort === 'title') return first.title.localeCompare(second.title);
      if (projectListSort === 'due' || projectListSort === 'start') return String(first[projectListSort === 'due' ? 'due_date' : 'start_date'] || '9999-12-31').localeCompare(String(second[projectListSort === 'due' ? 'due_date' : 'start_date'] || '9999-12-31'));
      if (projectListSort === 'priority') return ['urgent', 'high', 'medium', 'low', 'none'].indexOf(first.priority) - ['urgent', 'high', 'medium', 'low', 'none'].indexOf(second.priority);
      if (projectListSort === 'status') return columns.findIndex(column => column.id === first.status) - columns.findIndex(column => column.id === second.status);
      if (projectListSort === 'assignee') return (first.assignee?.name || '').localeCompare(second.assignee?.name || '');
      if (projectListSort === 'project') return (first.project?.name || '').localeCompare(second.project?.name || '');
      if (projectListSort === 'estimate') return Number(first.estimate || 0) - Number(second.estimate || 0);
      return new Date(projectListSort === 'created' ? first.created_at : first.updated_at) - new Date(projectListSort === 'created' ? second.created_at : second.updated_at);
    }).map((task, index, sorted) => projectListSortDirection > 0 ? task : sorted[sorted.length - index - 1]);
  });
  let projectListGroups = $derived.by(() => {
    if (projectListGroup === 'none') return [{ id: 'all', name: '', tasks: projectListTasks }];
    if (projectListGroup === 'project') return [{ id: String(project.id), name: project.name, icon: project.icon || 'folder', tasks: projectListTasks }];
    if (projectListGroup === 'assignee') {
      const grouped = new Map();
      for (const task of projectListTasks) {
        const id = String(task.assignee_id || 'unassigned');
        const name = task.assignee?.name || 'Unassigned';
        const group = grouped.get(id) || { id, name, tasks: [] };
        group.tasks.push(task);
        grouped.set(id, group);
      }
      return [...grouped.values()];
    }
      if (projectListGroup === 'priority') return ['urgent', 'high', 'medium', 'low', 'none'].map(id => ({ id, name: ({ urgent: 'Urgent', high: 'High', medium: 'Medium', low: 'Low', none: 'No priority' })[id], tasks: projectListTasks.filter(task => (task.priority || 'none') === id) })).filter(group => group.tasks.length);
      if (projectListGroup === 'due') return [
        { id: 'overdue', name: 'Overdue', tasks: projectListTasks.filter(task => task.due_date && task.status !== 'done' && new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString())) },
        { id: 'upcoming', name: 'Upcoming', tasks: projectListTasks.filter(task => task.due_date && !(task.status !== 'done' && new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString()))) },
        { id: 'nodate', name: 'No due date', tasks: projectListTasks.filter(task => !task.due_date) },
      ].filter(group => group.tasks.length);
      return columns.map(column => ({ ...column, tasks: projectListTasks.filter(task => task.status === column.id) })).filter(column => column.tasks.length);
  });

  onMount(() => {
    const closeStatusMenu = event => {
      if (statusMenu && !event.target.closest('.status-picker')) statusMenu = null;
      if (tableCellMenu && !event.target.closest('[data-table-cell-menu-trigger], [data-table-cell-menu]')) tableCellMenu = null;
      if (projectStatusMenu && !event.target.closest('[data-project-status], [data-project-status-menu]')) projectStatusMenu = null;
      if (projectMoreOpen && !event.target.closest('[data-project-more], [data-project-more-menu]')) projectMoreOpen = false;
      if (savedFilterOpen && !event.target.closest('[data-saved-filter-trigger], [data-saved-filter-builder]')) savedFilterOpen = false;
      if (savedValueOpenIndex !== null && !event.target.closest('[data-saved-filter-value-trigger], [data-saved-filter-values]')) savedValueOpenIndex = null;
      if (projectListMenu && !event.target.closest('[data-project-list-menu-trigger], [data-project-list-menu]')) projectListMenu = null;
      if (fileMenu && !event.target.closest('[data-file-menu-trigger], [data-file-menu]')) fileMenu = null;
    };
    const closeOnEscape = event => {
      if (event.key !== 'Escape') return;
      if (statusMenu) statusMenu = null;
      else if (tableCellMenu) tableCellMenu = null;
      else if (projectStatusMenu) projectStatusMenu = null;
      else if (projectMoreOpen) projectMoreOpen = false;
      else if (projectListMenu) projectListMenu = null;
      else if (fileMenu) fileMenu = null;
      else if (savedFilterOpen) savedFilterOpen = false;
      else if (savedValueOpenIndex !== null) savedValueOpenIndex = null;
      else if (membersDialogOpen) membersDialogOpen = false;
      else if (editDialogOpen) editDialogOpen = false;
    };
    document.addEventListener('pointerdown', closeStatusMenu, true);
    document.addEventListener('keydown', closeOnEscape);
    const projectAction = consumeNavigationIntent('project-action');
    if (projectAction) {
      tick().then(() => {
        if (projectAction.action === 'edit') openProjectSettings();
        if (projectAction.action === 'share') openProjectMembers();
      });
    }
    return () => {
      projectUploadTimers.forEach(timer => window.clearInterval(timer));
      projectUploadTimers.clear();
      document.removeEventListener('pointerdown', closeStatusMenu, true);
      document.removeEventListener('keydown', closeOnEscape);
    };
  });

  $effect(() => { isFavorite = project.is_favorite; });

  const columns = [
    { id: 'backlog', name: 'Backlog', icon: 'circle-dashed' },
    { id: 'todo', name: 'To Do', icon: 'circle' },
    { id: 'progress', name: 'In Progress', icon: 'circle-dot' },
    { id: 'review', name: 'Review', icon: 'circle-check' },
    { id: 'done', name: 'Done', icon: 'check-circle-2' },
  ];
  const savedFilterFields = [
    ['status', 'Status', 'circle-dot'], ['assignee', 'Assignee', 'user'], ['priority', 'Priority', 'signal-high'],
    ['due', 'Due date', 'calendar'], ['project', 'Project', 'folder'], ['labels', 'Labels', 'tag'],
    ['created', 'Created date', 'calendar-plus'], ['updated', 'Updated date', 'calendar-clock'],
  ];

  let completedCount = $derived(tasks.filter(task => task.status === 'done').length);
  let progress = $derived(tasks.length ? Math.round(completedCount / tasks.length * 100) : 0);
  let overdueCount = $derived(tasks.filter(task => task.status !== 'done' && task.due_date && new Date(`${task.due_date.slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString())).length);
  let statusCounts = $derived(columns.map(column => ({ ...column, count: tasks.filter(task => task.status === column.id).length })));
  let comingUp = $derived(tasks.filter(task => task.status !== 'done' && task.due_date).sort((first, second) => new Date(first.due_date) - new Date(second.due_date)).slice(0, 6));
  let sourceProject = $derived((workspaceData.projects || []).find(item => item.database_id === project.id));
  let projectTeam = $derived((page.props.teams || TEAMS_SEED).find(team => team.id === project.team));
  let projectOverviewEvents = $derived((workspaceData.activity || []).filter(item => item.project === sourceProject?.id));
  $effect(() => {
    const originalViewIds = new Set((workspaceData.savedViews || []).map(view => view.id));
    customSavedViews = (S.data.savedViews || []).filter(view => view.project === sourceProject?.id && !originalViewIds.has(view.id));
  });
  let savedViews = $derived([
    ...(workspaceData.savedViews || []).filter(view => view.project === sourceProject?.id),
    ...customSavedViews,
  ]);
  let selectedSavedView = $derived(savedViews.find(view => tab === `v:${view.id}`));
  let savedViewFiltersDraft = $state(null);
  let savedViewFilters = $derived(savedViewFiltersDraft ?? selectedSavedView?.filters ?? []);
  $effect(() => {
    const selectedView = selectedSavedView;
    if (!selectedView) return;
    const viewKey = `sv:${selectedView.id}`;
    S.views[viewKey] ??= {
      filters: structuredClone(selectedView.filters || []),
      sort: { f: 'manual', dir: 1 },
      group: 'status',
      q: '',
      hidden: ['start', 'created', 'deps'],
      colW: {},
      showDone: true,
    };
    savedViewFiltersDraft = structuredClone(S.views[viewKey].filters || selectedView.filters || []);
    savedViewQuery = '';
    savedViewError = '';
  });
  let savedViewTasks = $derived.by(() => tasks.filter(task => {
    const matchesQuery = !savedViewQuery.trim() || `${task.title} ${task.key || ''}`.toLowerCase().includes(savedViewQuery.trim().toLowerCase());
    return matchesQuery && savedViewFilters.every(filter => {
      if (!filter.v?.length) return true;
      let matches;
      if (filter.f === 'labels') matches = (task.labels || []).some(label => filter.v.includes(label));
      else if (filter.f === 'assignee') matches = filter.v.includes(String(task.assignee_id || 'none'));
      else if (filter.f === 'project') matches = filter.v.includes(String(task.project_id));
      else if (filter.f === 'due') {
        if (filter.v.includes('nodate') && !task.due_date) matches = true;
        else if (!task.due_date) matches = false;
        else {
          const due = new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`);
          const today = new Date();
          today.setHours(0, 0, 0, 0);
          const days = Math.floor((due - today) / 86400000);
          matches = filter.v.some(value => value === 'overdue' ? days < 0 && task.status !== 'done'
            : value === 'today' ? days === 0
              : value === 'week' ? days >= 0 && days <= 7
                : value === 'later' && days > 7);
        }
      } else if (filter.f === 'created' || filter.f === 'updated') {
        const timestamp = new Date(filter.f === 'created' ? task.created_at : task.updated_at).getTime();
        matches = Number.isFinite(timestamp) && filter.v.some(value => Date.now() - timestamp <= Number(value) * 86400000);
      } else matches = filter.v.includes(String(task[filter.f]));
      return filter.op === 'not' ? !matches : matches;
    });
  }));
  let savedViewGroups = $derived(columns.map(column => ({
    id: column.id,
    name: column.name,
    icon: column.icon,
    iconType: 'status',
    tasks: savedViewTasks.filter(task => task.status === column.id),
  })));
  let sourceMembers = $derived(workspaceData.members || []);
  let projectMemberOptions = $derived(sourceMembers
    .filter(member => member.database_id)
    .map(member => ({ id: member.database_id, name: member.name, email: member.email, color: member.c, sourceKey: member.id }))
    .filter(member => member.name.toLowerCase().includes(memberQuery.trim().toLowerCase()) || (member.email || '').toLowerCase().includes(memberQuery.trim().toLowerCase())));
  let memberById = $derived(new Map(sourceMembers.map(member => [member.id, member])));
  let memberByDatabaseId = $derived(new Map(sourceMembers.filter(member => member.database_id).map(member => [String(member.database_id), member])));
  let sourceTasks = $derived(workspaceData.tasks || []);
  let sourceTaskById = $derived(new Map(sourceTasks.map(task => [task.id, task])));
  $effect(() => {
    const baseFiles = (workspaceData.files || []).filter(file => file.project === sourceProject?.id);
    const savedFiles = S.data.files || [];
    const savedFilesById = new Map(savedFiles.map(file => [file.id, file]));
    const baseFileIds = new Set(baseFiles.map(file => file.id));
    projectFileRecords = [
      ...baseFiles.map(file => ({ ...(savedFilesById.get(file.id) || file) })),
      ...savedFiles.filter(file => file.project === sourceProject?.id && !baseFileIds.has(file.id)).map(file => ({ ...file })),
    ];
  });
  let projectFiles = $derived.by(() => {
    return projectFileRecords
    .filter(file => file.project === sourceProject?.id && file.name.toLowerCase().includes(fileQuery.toLowerCase()) && (fileType === 'all' || file.type === fileType))
    .sort((first, second) => fileSort === 'name' ? first.name.localeCompare(second.name) : fileSort === 'type' ? first.type.localeCompare(second.type) : second.at - first.at);
  });
  let projectEvents = $derived.by(() => {
    if (!sourceProject) return [];
    const activity = (workspaceData.activity || []).filter(item => item.project === sourceProject.id);
    const comments = (workspaceData.comments || [])
      .filter(comment => sourceTaskById.get(comment.task)?.project === sourceProject.id)
      .map(comment => ({ id: comment.id, by: comment.by, verb: 'commented on', task: comment.task, project: sourceProject.id, at: comment.at, extra: '' }));
    return [...activity, ...comments].sort((first, second) => second.at - first.at);
  });
  let eventGroups = $derived.by(() => {
    const groups = new Map();
    for (const event of projectEvents) {
      const bucket = dayBucket(event.at);
      groups.set(bucket, [...(groups.get(bucket) || []), event]);
    }
    return [...groups.entries()];
  });

  function statusName(value) {
    return columns.find(column => column.id === value)?.name || value;
  }

  function toggleProjectListMenu(event, menu) {
    if (projectListMenu === menu) {
      projectListMenu = null;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = menu === 'filter' ? 560 : menu === 'sort' ? 240 : 180;
    projectListMenuPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.min(bounds.bottom + 4, window.innerHeight - 220),
    };
    projectListMenu = menu;
  }

  function matchesProjectListFilter(task, filter) {
    if (!filter.v?.length) return true;
    let matches;
    if (filter.f === 'labels') matches = (task.labels || []).some(label => filter.v.includes(label));
    else if (filter.f === 'assignee') matches = filter.v.includes(String(task.assignee_id || 'none'));
    else if (filter.f === 'project') matches = filter.v.includes(String(task.project_id));
    else if (filter.f === 'due') {
      if (filter.v.includes('nodate') && !task.due_date) matches = true;
      else if (!task.due_date) matches = false;
      else {
        const due = new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const days = Math.floor((due - today) / 86400000);
        matches = filter.v.some(value => value === 'overdue' ? days < 0 && task.status !== 'done'
          : value === 'today' ? days === 0
            : value === 'week' ? days >= 0 && days <= 7
              : value === 'later' && days > 7);
      }
    } else if (filter.f === 'created' || filter.f === 'updated') {
      const timestamp = new Date(filter.f === 'created' ? task.created_at : task.updated_at).getTime();
      matches = Number.isFinite(timestamp) && filter.v.some(value => Date.now() - timestamp <= Number(value) * 86400000);
    } else matches = filter.v.includes(String(task[filter.f]));
    return filter.op === 'not' ? !matches : matches;
  }

  function addProjectListFilter(field) {
    projectListFilters = [...projectListFilters, { f: field, op: 'is', v: [] }];
    projectListFilterEditIndex = projectListFilters.length - 1;
  }

  function updateProjectListFilter(index, values) {
    projectListFilters = projectListFilters.map((filter, filterIndex) => filterIndex === index ? { ...filter, ...values } : filter);
  }

  function toggleProjectListFilterValue(index, value) {
    const filter = projectListFilters[index];
    const selectedValues = filter.v.includes(value) ? filter.v.filter(item => item !== value) : [...filter.v, value];
    updateProjectListFilter(index, { v: selectedValues });
  }

  function removeProjectListFilter(index) {
    projectListFilters = projectListFilters.filter((_, filterIndex) => filterIndex !== index);
    if (projectListFilterEditIndex === index) projectListFilterEditIndex = null;
    else if (projectListFilterEditIndex > index) projectListFilterEditIndex -= 1;
    if (projectListValueOpenIndex === index) projectListValueOpenIndex = null;
    else if (projectListValueOpenIndex > index) projectListValueOpenIndex -= 1;
  }

  function clearProjectListFilters() {
    projectListFilters = [];
    projectListFilterEditIndex = null;
    projectListValueOpenIndex = null;
  }

  async function toggleStatusMenu(event, task) {
    if (statusMenu?.taskId === task.id) {
      statusMenu = null;
      return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const menuWidth = 200;
    const menuHeight = columns.length * 30 + 8;
    const left = rect.left + menuWidth > window.innerWidth - 8 ? Math.max(8, rect.right - menuWidth) : rect.left;
    const top = rect.bottom + 4 + menuHeight > window.innerHeight - 8 ? Math.max(8, rect.top - 4 - menuHeight) : rect.bottom + 4;
    statusMenu = { taskId: task.id, left, top };
    await tick();
    document.querySelector('.status-picker .pop [aria-checked="true"]')?.focus({ preventScroll: true });
  }

  async function chooseStatus(task, nextStatus) {
    statusMenu = null;
    await changeStatus(task, nextStatus);
  }

  function openTableCellMenu(event, task, field) {
    if (tableCellMenu?.taskId === task.id && tableCellMenu.field === field) {
      tableCellMenu = null;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = field === 'assignee' ? 240 : field === 'labels' || field === 'deps' ? 220 : 200;
    tableCellMenu = {
      taskId: task.id,
      field,
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 300)),
    };
    tableCellQuery = '';
  }

  async function updateTableTask(task, changes, keepMenu = false) {
    if (!keepMenu) tableCellMenu = null;
    try {
      const updated = await httpRequest(taskUpdate(task.id).url, 'PATCH', changes);
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
      return true;
    } catch (error) {
      errorMessage = error.message;
      return false;
    }
  }

  function setTableTaskDate(task, field, value) {
    void updateTableTask(task, { [field === 'due' ? 'due_date' : 'start_date']: value || null });
  }

  function tableQuickDate(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function toggleTableTaskValue(task, field, value) {
    const current = task[field] || [];
    void updateTableTask(task, { [field]: current.includes(value) ? current.filter(item => item !== value) : [...current, value] }, true);
  }

  function saveTableText(task, field) {
    const isTitle = field === 'title';
    const editingId = isTitle ? tableTitleEditId : tableEstimateEditId;
    if (editingId !== task.id) return;
    const value = (isTitle ? tableTitleDraft : tableEstimateDraft).trim();
    if (isTitle) tableTitleEditId = null;
    else tableEstimateEditId = null;
    void updateTableTask(task, { [field]: value || null });
  }

  function saveTableEstimate(task) {
    saveTableText(task, 'estimate');
  }

  function projectStatusName(value) {
    return ({ active: 'In Progress', planning: 'Planning', risk: 'At Risk', hold: 'On Hold', complete: 'Completed', paused: 'On Hold', completed: 'Completed', archived: 'Archived' })[value] || value;
  }

  function openCreateTask(date = '', initialStatus = 'todo') {
    taskFormDueDate = date;
    taskFormStatus = initialStatus;
    showTaskForm = true;
  }

  function taskCreated(createdTask) {
    tasks = [createdTask, ...tasks];
  }

  function taskUpdated(updatedTask) {
    tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }

  async function updateSavedViewTask(task, values) {
    try {
      const updatedTask = await httpRequest(taskUpdate(task.id).url, 'PATCH', values);
      taskUpdated(updatedTask);
    } catch (error) {
      savedViewError = error.message;
    }
  }

  async function updateProjectListTask(task, values) {
    try {
      const updatedTask = await httpRequest(taskUpdate(task.id).url, 'PATCH', values);
      taskUpdated(updatedTask);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  async function createSavedViewTask(title, group) {
    try {
      const createdTask = await httpRequest(taskStore({ project: project.id }).url, 'POST', {
        title,
        status: group.id,
        priority: 'medium',
      });
      taskCreated(createdTask);
    } catch (error) {
      savedViewError = error.message;
    }
  }

  async function createProjectListTask(title, group) {
    try {
      const createdTask = await httpRequest(taskStore({ project: project.id }).url, 'POST', {
        title,
        status: group.id,
        priority: 'medium',
      });
      taskCreated(createdTask);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  function removeSavedViewFilter(index) {
    setSavedViewFilters(savedViewFilters.filter((_, filterIndex) => filterIndex !== index));
    if (savedFilterEditIndex === index) savedFilterEditIndex = null;
    else if (savedFilterEditIndex > index) savedFilterEditIndex -= 1;
    if (savedValueOpenIndex === index) savedValueOpenIndex = null;
    else if (savedValueOpenIndex > index) savedValueOpenIndex -= 1;
  }

  function saveProjectView(event) {
    event.preventDefault();
    const viewName = saveViewName.trim() || 'Untitled view';
    const savedView = {
      id: uid('v'),
      project: sourceProject?.id || project.source_key,
      name: viewName,
      type: saveViewType,
      filters: structuredClone(projectViewFilters),
    };
    S.data.savedViews = [...(S.data.savedViews || []), savedView];
    saveWorkspaceStore();
    customSavedViews = [...customSavedViews, savedView];
    saveViewOpen = false;
    saveViewName = '';
    tab = `v:${savedView.id}`;
  }

  async function openSaveViewDialog() {
    saveViewName = '';
    saveViewType = 'list';
    saveViewOpen = true;
    await tick();
    saveViewNameInput?.focus();
  }

  function savedFilterLabel(filter) {
    const field = ({ status: 'Status', assignee: 'Assignee', priority: 'Priority', due: 'Due date', project: 'Project', labels: 'Labels', created: 'Created date', updated: 'Updated date' })[filter.f] || filter.f;
    const values = (filter.v || []).map(value => ({ urgent: 'Urgent', high: 'High', medium: 'Medium', low: 'Low', none: 'No priority', backlog: 'Backlog', todo: 'To Do', progress: 'In Progress', review: 'Review', done: 'Done', overdue: 'Overdue', today: 'Today', week: 'Next 7 days', later: 'Later', nodate: 'No due date' })[value] || value);
    return `${field} is${filter.op === 'not' ? ' not' : ''} ${values.length ? values.join(', ') : 'any'}`;
  }

  function filterOptions(filter) {
    if (filter.f === 'status') return columns.map(column => ({ id: column.id, name: column.name }));
    if (filter.f === 'priority') return [
      { id: 'urgent', name: 'Urgent' }, { id: 'high', name: 'High' }, { id: 'medium', name: 'Medium' }, { id: 'low', name: 'Low' }, { id: 'none', name: 'No priority' },
    ];
    if (filter.f === 'assignee') return [
      { id: 'none', name: 'Unassigned' },
      ...(workspaceData.members || []).filter(member => member.database_id).map(member => ({ id: String(member.database_id), name: member.name })),
    ];
    if (filter.f === 'project') return projects.map(item => ({ id: String(item.id), name: item.name }));
    if (filter.f === 'labels') return LABELS.map(label => ({ id: label.id, name: label.name }));
    if (filter.f === 'due') return [
      { id: 'overdue', name: 'Overdue' }, { id: 'today', name: 'Today' }, { id: 'week', name: 'Next 7 days' }, { id: 'later', name: 'Later' }, { id: 'nodate', name: 'No due date' },
    ];
    return [{ id: '1', name: 'Last 24 hours' }, { id: '7', name: 'Last 7 days' }, { id: '30', name: 'Last 30 days' }];
  }

  function updateSavedViewFilter(index, values) {
    setSavedViewFilters(savedViewFilters.map((filter, filterIndex) => filterIndex === index ? { ...filter, ...values } : filter));
  }

  function setSavedViewFilters(filters) {
    const copiedFilters = JSON.parse(JSON.stringify(filters));
    savedViewFiltersDraft = copiedFilters;
    if (!selectedSavedView) return;
    const viewKey = `sv:${selectedSavedView.id}`;
    S.views[viewKey] ??= { filters: [], sort: { f: 'manual', dir: 1 }, group: 'status', q: '', hidden: ['start', 'created', 'deps'], colW: {}, showDone: true };
    S.views[viewKey].filters = JSON.parse(JSON.stringify(copiedFilters));
    saveWorkspaceStore();
  }

  function toggleSavedViewFilterValue(index, value) {
    const filter = savedViewFilters[index];
    const currentValues = filter.v || [];
    const selectedValues = currentValues.includes(value) ? currentValues.filter(item => item !== value) : [...currentValues, value];
    updateSavedViewFilter(index, { v: selectedValues });
  }

  async function toggleSavedValuePopover(event, index) {
    if (savedValueOpenIndex === index) {
      savedValueOpenIndex = null;
      return;
    }
    savedFilterOpen = false;
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = 200;
    savedValueOpenIndex = index;
    savedValuePosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: bounds.bottom + 4,
    };
    await tick();
    const popup = document.querySelector('[data-saved-filter-values]');
    if (!popup) return;
    const height = popup.getBoundingClientRect().height;
    savedValuePosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: bounds.bottom + height > window.innerHeight - 8 ? Math.max(8, bounds.top - height - 4) : bounds.bottom + 4,
    };
  }

  function savedFilterOptionIcon(filter, option) {
    if (filter.f === 'status') return stIcon(option.id, 14);
    if (filter.f === 'priority') return prIcon(option.id, 14);
    if (filter.f === 'project') {
      const projectOption = projects.find(item => String(item.id) === String(option.id));
      return `<span class="pdot" style="--c:${PCOLORS[projectOption?.color] || 'var(--gray)'}"></span>`;
    }
    if (filter.f === 'labels') {
      const label = LABELS.find(item => item.id === option.id);
      return `<span class="pdot" style="--c:${label?.c || 'var(--gray)'}"></span>`;
    }
    if (filter.f === 'assignee') {
      const member = workspaceData.members?.find(item => String(item.database_id) === String(option.id));
      return member ? `<span class="av sm" style="--c:${member.c || '#8A867E'}">${member.name.split(' ').map(part => part[0]).join('').slice(0, 2)}</span>` : ic('user-round', 14);
    }
    return ic(filter.f === 'due' || filter.f === 'created' || filter.f === 'updated' ? 'calendar' : 'circle-dot', 14);
  }

  async function toggleSavedFilterBuilder(event, editIndex = null) {
    if (savedFilterOpen) {
      savedFilterOpen = false;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = Math.min(560, window.innerWidth - 16);
    savedValueOpenIndex = null;
    savedFilterPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.max(8, bounds.bottom + 4),
    };
    savedFilterEditIndex = editIndex;
    savedFilterOpen = true;
    await tick();
    const popup = document.querySelector('[data-saved-filter-builder]');
    if (!popup) return;
    const height = popup.getBoundingClientRect().height;
    savedFilterPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: bounds.bottom + height > window.innerHeight - 8 ? Math.max(8, bounds.top - height - 4) : bounds.bottom + 4,
    };
  }

  function addSavedFilter(field) {
    setSavedViewFilters([...savedViewFilters, { f: field, op: 'is', v: [] }]);
    savedFilterEditIndex = savedViewFiltersDraft.length - 1;
    savedFilterOpen = true;
  }

  function clearSavedViewFilters() {
    setSavedViewFilters([]);
    savedViewQuery = '';
    savedFilterOpen = false;
    savedValueOpenIndex = null;
  }

  function taskSnapshot(task) {
    return sourceTasks.find(item => item.database_id === task.id || item.id === task.source_key);
  }

  function taskComments(task) {
    const sourceTask = taskSnapshot(task);
    return sourceTask ? (workspaceData.comments || []).filter(comment => comment.task === sourceTask.id).length : 0;
  }

  function cardSubtasks(task) {
    const sourceTask = taskSnapshot(task);
    return task.subtasks?.length ? task.subtasks : sourceTask?.subtasks || [];
  }

  function cardAttachments(task) {
    const sourceTask = taskSnapshot(task);
    return task.attachments?.length ? task.attachments : sourceTask?.attachments || [];
  }

  function cardLabels(task) {
    const sourceTask = taskSnapshot(task);
    return task.labels?.length ? task.labels : sourceTask?.labels || [];
  }

  function cardKey(task) {
    return task.key || taskSnapshot(task)?.key || task.source_key || '';
  }

  function cardDue(task) {
    const dueDate = task.due_date || taskSnapshot(task)?.due;
    return dueDate ? formatDate(dueDate) : '';
  }

  async function changeStatus(task, nextStatus) {
    if (task.status === nextStatus) return;
    errorMessage = '';
    try {
      const updated = await httpRequest(taskUpdate(task.id).url, 'PATCH', { status: nextStatus });
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function uploadProjectFiles(files) {
    if (!files.length) return;
    if (S.ui.offline) {
      toast("Upload failed — you're offline.", { kind: 'err', action: 'Try again', onAction: () => {} });
      return;
    }
    for (const file of files) {
      const upload = { id: uid('u'), name: file.name, size: fsize(file.size), type: getFileType(file.name), pct: 0 };
      uploadingProjectFiles = [...uploadingProjectFiles, upload];
      const timer = window.setInterval(() => {
        const nextPct = Math.min(100, upload.pct + 18);
        upload.pct = nextPct;
        uploadingProjectFiles = uploadingProjectFiles.map(item => item.id === upload.id ? { ...item, pct: nextPct } : item);
        if (nextPct < 100) return;
        window.clearInterval(timer);
        projectUploadTimers.delete(upload.id);
        uploadingProjectFiles = uploadingProjectFiles.filter(item => item.id !== upload.id);
        const record = {
          id: uid('f'),
          name: upload.name,
          type: upload.type,
          size: upload.size,
          by: workspaceData.me,
          at: Date.now(),
          project: sourceProject?.id,
          task: null,
        };
        S.data.files.unshift(record);
        saveWorkspaceStore();
        projectFileRecords = [record, ...projectFileRecords];
        toast(`Uploaded ${upload.name}`);
      }, 220);
      projectUploadTimers.set(upload.id, timer);
    }
  }

  function handleProjectFileSelection(event) {
    uploadProjectFiles([...event.currentTarget.files]);
    event.currentTarget.value = '';
  }

  function handleProjectFileDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('over');
    uploadProjectFiles([...event.dataTransfer.files]);
  }

  function openFileMenu(event, file) {
    event.stopPropagation();
    if (fileMenu?.id === file.id) {
      fileMenu = null;
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    fileMenuPosition = {
      left: Math.max(8, Math.min(bounds.right - 24, window.innerWidth - 208)),
      top: Math.max(8, Math.min(bounds.bottom + 4, window.innerHeight - 200)),
    };
    fileMenu = file;
  }

  function renameProjectFile(event) {
    event.preventDefault();
    if (!fileRenameTarget || !fileRenameName.trim()) return;
    const file = S.data.files.find(item => item.id === fileRenameTarget.id);
    if (!file) return;
    file.name = fileRenameName.trim();
    file.type = getFileType(file.name);
    projectFileRecords = projectFileRecords.map(item => item.id === file.id ? { ...item, name: file.name, type: file.type } : item);
    saveWorkspaceStore();
    fileRenameTarget = null;
    toast('Changes saved');
  }

  function duplicateProjectFile(file) {
    const originalIndex = S.data.files.findIndex(item => item.id === file.id);
    if (originalIndex < 0) return;
    const duplicate = { ...S.data.files[originalIndex], id: uid('f'), name: file.name.replace(/(\.[^.]+)$/, ' copy$1'), at: Date.now(), by: S.data.me };
    S.data.files.splice(originalIndex + 1, 0, duplicate);
    projectFileRecords = [...projectFileRecords.slice(0, originalIndex + 1), duplicate, ...projectFileRecords.slice(originalIndex + 1)];
    saveWorkspaceStore();
    toast('File duplicated');
  }

  async function copyProjectFileLink(file) {
    const url = `https://gr8rstudio.com/${S.data.ws.url}/file/${file.id}`;
    try {
      await navigator.clipboard.writeText(url);
      toast('Link copied');
    } catch {
      toast(`Copy isn't available here — ${url}`, { kind: 'info', ms: 6000 });
    }
  }

  function deleteProjectFile() {
    if (!fileDeleteTarget) return;
    const deletedFile = { ...fileDeleteTarget };
    const fileName = deletedFile.name;
    S.data.files = S.data.files.filter(file => file.id !== fileDeleteTarget.id);
    if (deletedFile.task) {
      const task = S.data.tasks.find(item => item.id === deletedFile.task);
      if (task) task.attachments = task.attachments.filter(attachment => attachment.name !== fileName);
    }
    projectFileRecords = projectFileRecords.filter(file => file.id !== fileDeleteTarget.id);
    saveWorkspaceStore();
    fileDeleteTarget = null;
    toast(`Deleted ${fileName}`, { action: 'Undo', onAction: () => {
      S.data.files.push(deletedFile);
      if (deletedFile.task) {
        const task = S.data.tasks.find(item => item.id === deletedFile.task);
        if (task && !task.attachments.some(attachment => attachment.name === fileName)) task.attachments.push(deletedFile);
      }
      projectFileRecords = [...projectFileRecords, deletedFile];
      saveWorkspaceStore();
    } });
  }

  function toggleProjectTableColumn(field) {
    projectTableHidden = projectTableHidden.includes(field)
      ? projectTableHidden.filter(hiddenField => hiddenField !== field)
      : [...projectTableHidden, field];
    S.views[projectTableViewKey] ??= { filters: [], sort: { f: 'manual', dir: 1 }, group: 'status', q: '', hidden: [], colW: {}, showDone: true };
    S.views[projectTableViewKey].hidden = [...projectTableHidden];
    saveWorkspaceStore();
  }

  function resetProjectTableColumnWidths() {
    projectTableColumnWidths = {};
    S.views[projectTableViewKey] ??= { filters: [], sort: { f: 'manual', dir: 1 }, group: 'status', q: '', hidden: [...projectTableHidden], colW: {}, showDone: true };
    S.views[projectTableViewKey].colW = {};
    saveWorkspaceStore();
    projectListMenu = null;
  }

  function startProjectTableColumnResize(event, field) {
    event.preventDefault();
    event.stopPropagation();
    const startX = event.clientX;
    const startWidth = projectTableColumns.find(([column]) => column === field)?.[2] || 112;
    const resize = moveEvent => {
      projectTableColumnWidths = {
        ...projectTableColumnWidths,
        [field]: Math.max(64, startWidth + moveEvent.clientX - startX),
      };
    };
    const finish = () => {
      window.removeEventListener('pointermove', resize);
      window.removeEventListener('pointerup', finish);
      S.views[projectTableViewKey] ??= { filters: [], sort: { f: 'manual', dir: 1 }, group: 'status', q: '', hidden: [...projectTableHidden], colW: {}, showDone: true };
      S.views[projectTableViewKey].colW = Object.fromEntries(Object.entries(projectTableColumnWidths).map(([column, width]) => [column, Math.round(width)]));
      saveWorkspaceStore();
    };
    window.addEventListener('pointermove', resize);
    window.addEventListener('pointerup', finish, { once: true });
  }

  function formatLongDate(value) {
    if (!value) return '—';
    return fmtDate(value.slice(0, 10), true);
  }

  function openOverviewStatus(status) {
    projectOverviewStatusFilter = status;
    tab = 'list';
  }

  function openProjectTab(nextTab) {
    if (nextTab !== 'list') projectOverviewStatusFilter = '';
    tab = nextTab;
  }

  function openTaskFromOverview(task) {
    drawerTaskId = task.id;
  }

  function daysUntil(value) {
    if (!value) return null;
    const due = new Date(`${value.slice(0, 10)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.floor((due - today) / 86400000);
  }

  function memberInitials(name) {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  async function toggleFavorite() {
    try {
      const updated = await httpRequest(projectFavorite(project.id).url, 'PATCH', {});
      isFavorite = updated.is_favorite;
      project.is_favorite = updated.is_favorite;
    } catch (error) {
      errorMessage = error.message;
    }
  }

  function openProjectStatusMenu(event) {
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = 190;
    projectStatusMenu = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.max(8, bounds.bottom + 4),
    };
  }

  function toggleProjectMore(event) {
    if (projectMoreOpen) {
      projectMoreOpen = false;
      return;
    }

    const bounds = event.currentTarget.getBoundingClientRect();
    projectMorePosition = {
      left: Math.max(8, Math.min(bounds.right - 200, window.innerWidth - 208)),
      top: Math.max(8, bounds.bottom + 4),
    };
    projectMoreOpen = true;
  }

  async function changeProjectStatus(status) {
    projectStatusMenu = null;
    await updateProject({ status });
  }

  async function updateProject(changes) {
    projectDialogError = '';
    try {
      const updated = await httpRequest(projectUpdate(project.id).url, 'PATCH', changes);
      Object.assign(project, updated);
      return true;
    } catch (error) {
      projectDialogError = error.errors?.name?.[0] || error.message;
      return false;
    }
  }

  function openProjectSettings() {
    projectForm = {
      name: project.name,
      description: project.description || '',
      status: project.status?.value || project.status,
      start_date: project.start_date ? project.start_date.slice(0, 10) : '',
      due_date: project.due_date ? project.due_date.slice(0, 10) : '',
    };
    projectDialogError = '';
    editDialogOpen = true;
    projectMoreOpen = false;
  }

  async function saveProjectSettings(event) {
    event.preventDefault();
    projectDialogBusy = true;
    if (await updateProject(projectForm)) editDialogOpen = false;
    projectDialogBusy = false;
  }

  function openProjectMembers() {
    selectedMemberIds = (project.members || []).map(member => member.id);
    memberQuery = '';
    memberDialogError = '';
    membersDialogOpen = true;
    projectMoreOpen = false;
  }

  function openProjectConfirmation(action) {
    projectMoreOpen = false;
    projectConfirmationText = '';
    projectConfirmation = action;
  }

  async function runProjectMoreAction(action) {
    projectMoreOpen = false;

    if (action === 'duplicate') {
      try {
        const copy = await httpRequest(projectDuplicate(project.id).url, 'POST', {});
        toast(`Duplicated ${project.name}`, {
          action: 'Open',
          onAction: () => router.visit(projectShow(copy.id).url),
        });
        router.reload({ preserveScroll: true });
      } catch (error) {
        errorMessage = error.message;
      }
      return;
    }

    if (action === 'copy') {
      const link = `https://gr8rstudio.com/${S.data.ws.url}/project/${project.key.toLowerCase()}`;
      try {
        await navigator.clipboard.writeText(link);
        toast('Link copied');
      } catch {
        toast(`Copy isn't available here — ${link}`, { kind: 'info', ms: 6000 });
      }
      return;
    }

    if (action === 'move-up' || action === 'move-down') {
      const sourceProjectId = project.source_key;
      const order = S.data.projOrder || [];
      const currentIndex = order.indexOf(sourceProjectId);
      const nextIndex = action === 'move-up' ? currentIndex - 1 : currentIndex + 1;
      if (currentIndex >= 0 && nextIndex >= 0 && nextIndex < order.length) {
        const nextOrder = [...order];
        [nextOrder[currentIndex], nextOrder[nextIndex]] = [nextOrder[nextIndex], nextOrder[currentIndex]];
        S.data.projOrder = nextOrder;
        saveWorkspaceStore();
      }
      return;
    }

    openProjectConfirmation(action);
  }

  async function confirmProjectAction() {
    const action = projectConfirmation;
    if (!action || projectConfirmationBusy) return;
    if (action === 'delete' && projectConfirmationText !== project.name) return;

    projectConfirmationBusy = true;
    errorMessage = '';

    try {
      if (action === 'archive') {
        await httpRequest(projectUpdate(project.id).url, 'PATCH', { status: 'archived' });
        toast(`Archived ${project.name}`);
      } else {
        await httpRequest(projectDestroy(project.id).url, 'DELETE', {});
        toast(`Deleted ${project.name}`);
      }
      projectConfirmation = null;
      router.visit(projectsIndex().url);
    } catch (error) {
      errorMessage = error.message;
    } finally {
      projectConfirmationBusy = false;
    }
  }

  function toggleProjectMember(memberId) {
    selectedMemberIds = selectedMemberIds.includes(memberId)
      ? selectedMemberIds.filter(id => id !== memberId)
      : [...selectedMemberIds, memberId];
  }

  async function saveProjectMembers() {
    memberDialogError = '';
    projectDialogBusy = true;
    try {
      const result = await httpRequest(projectMembersUpdate(project.id).url, 'PUT', { members: selectedMemberIds });
      project.members = result.members;
      membersDialogOpen = false;
    } catch (error) {
      memberDialogError = error.errors?.members?.[0] || error.message;
    } finally {
      projectDialogBusy = false;
    }
  }
</script>

<svelte:head><title>{tab === 'overview' ? 'Overview' : selectedSavedView?.name || statusName(tab)} · {project.name}</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="proj-h">
      <div class="t">
        <span class="picon lg" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}>{@html ic(project.icon || 'folder', 18)}</span>
        <h1>{project.name}</h1>
        <button class="ibtn ibtn-sm" style={isFavorite ? 'color:var(--amber)' : ''} aria-label={isFavorite ? 'Remove from favorites' : 'Add to favorites'} aria-pressed={isFavorite} onclick={toggleFavorite}>{@html ic('star', 15)}</button>
        <button type="button" class="pillbtn bordered" data-project-status aria-label="Project status" aria-haspopup="menu" aria-expanded={!!projectStatusMenu} onclick={openProjectStatusMenu}><span class="pdot" style={`--c:${PSTAT[project.status]?.c || 'var(--gray)'};border-radius:50%;width:7px;height:7px`}></span>{projectStatusName(project.status)}{@html ic('chevron-down', 12)}</button>
        <span class="sp"></span>
        <div class="row" style="gap:4px">
          <button type="button" class="row" style="gap:0" aria-label="Members" onclick={openProjectMembers}>
            <span class="avs">{#each (project.members || []).slice(0, 5) as member (member.id)}<span class="av md" style={`--c:${member.color || '#8A867E'}`} aria-label={member.name}>{memberInitials(member.name)}</span>{/each}{#if (project.members || []).length > 5}<span class="av md more" style="--c:var(--gray)">+{project.members.length - 5}</span>{/if}</span>
          </button>
          <button type="button" class="ibtn ibtn-sm" data-tip="Add member" aria-label="Add member" onclick={openProjectMembers}>{@html ic('user-plus', 15)}</button>
          <button type="button" class="btn btn-secondary btn-sm hide-m" onclick={openProjectMembers}>{@html ic('share-2', 13)}Share</button>
          <button type="button" class="ibtn ibtn-sm" data-tip="Project settings" aria-label="Project settings" onclick={openProjectSettings}>{@html ic('settings', 15)}</button>
          <button type="button" class="ibtn ibtn-sm" data-project-more aria-label="More" aria-expanded={projectMoreOpen} onclick={toggleProjectMore}>{@html ic('ellipsis', 15)}</button>
        </div>
      </div>
      {#if projectStatusMenu}
        <div class="pop enter" data-project-status-menu role="menu" style={`left:${projectStatusMenu.left}px;top:${projectStatusMenu.top}px;width:190px`}>
          {#each Object.entries(PSTAT) as [status, details] (status)}<button type="button" class="mi" role="menuitemradio" aria-checked={project.status === status} onclick={() => changeProjectStatus(status)}><span class="pdot" style={`--c:${details.c};border-radius:50%;width:8px;height:8px`}></span><span>{details.name}</span>{#if project.status === status}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        </div>
      {/if}
      {#if projectMoreOpen}
        <div class="pop enter" data-project-more-menu role="menu" style={`left:${projectMorePosition.left}px;top:${projectMorePosition.top}px;width:200px`}>
          <button type="button" class="mi" role="menuitem" onclick={openProjectSettings}>{@html ic('pencil', 14)}<span>Rename &amp; edit</span></button>
          <button type="button" class="mi" role="menuitem" onclick={openProjectMembers}>{@html ic('share-2', 14)}<span>Share project</span></button>
          <button type="button" class="mi" role="menuitem" onclick={() => { projectMoreOpen = false; toggleFavorite(); }}>{@html ic('star', 14)}<span>{isFavorite ? 'Remove from favorites' : 'Add to favorites'}</span></button>
        </div>
      {/if}
      <nav class="tabs" aria-label="Project views">
        {#each [['overview', 'Overview', 'layout-dashboard'], ['board', 'Board', 'square-kanban'], ['list', 'List', 'list'], ['table', 'Table', 'table-2'], ['calendar', 'Calendar', 'calendar'], ['timeline', 'Timeline', 'chart-gantt'], ['files', 'Files', 'paperclip'], ['activity', 'Activity', 'activity']] as [id, label, icon]}
          <button class="tab" class:on={tab === id} aria-current={tab === id ? 'page' : undefined} onclick={() => openProjectTab(id)}>{@html ic(icon, 14)}{label}</button>
        {/each}
        {#each savedViews as savedView (savedView.id)}
          <button class="tab" class:on={tab === `v:${savedView.id}`} aria-current={tab === `v:${savedView.id}` ? 'page' : undefined} onclick={() => tab = `v:${savedView.id}`}>{@html ic('list-filter', 14)}{savedView.name}</button>
        {/each}
        <button type="button" class="tab" aria-label="Save view" data-tip="Save current filters as a view" onclick={openSaveViewDialog}>{@html ic('plus', 14)}</button>
      </nav>
    </div>

    <div class="pbody">
      {#if errorMessage}<div class="alert danger" role="alert">{errorMessage}</div>{/if}
      {#if selectedSavedView}
        <div class="toolbar" role="toolbar">
          <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={savedViewQuery} placeholder="Search tasks" aria-label="Search tasks" /></div>
          <button type="button" class={`btn btn-ghost ${savedViewFilters.length ? 'on' : ''}`} data-saved-filter-trigger aria-haspopup="dialog" aria-expanded={savedFilterOpen} onclick={event => toggleSavedFilterBuilder(event)}>{@html ic('list-filter', 14)}Filter{#if savedViewFilters.length}<span class="badge accent" style="height:16px;padding:0 5px">{savedViewFilters.length}</span>{/if}</button>
          <button type="button" class="btn btn-ghost">{@html ic('arrow-up-down', 14)}<span class="hide-m">Sort:</span> Manual</button>
          <button type="button" class="btn btn-ghost">{@html ic('rows-3', 14)}<span class="hide-m">Group:</span> Status</button>
          <span class="sp"></span><button type="button" class="btn btn-primary btn-sm" onclick={() => openCreateTask()}>{@html ic('plus', 13)}New task</button>
        </div>
        {#if savedViewFilters.length}<div class="chipsbar">{@html ic('list-filter', 13)}{#each savedViewFilters as filter, index (index)}<span class="chip"><button type="button" data-saved-filter-value-trigger aria-haspopup="menu" aria-expanded={savedValueOpenIndex === index} onclick={event => toggleSavedValuePopover(event, index)}>{@html ic(savedFilterFields.find(([field]) => field === filter.f)?.[2] || 'list-filter', 12)}<b>{savedFilterFields.find(([field]) => field === filter.f)?.[1] || filter.f}</b> {filter.op === 'not' ? 'is not' : 'is'} <span>{filter.v?.length ? filterOptions(filter).filter(option => filter.v.includes(String(option.id))).map(option => option.name).join(', ') : 'any'}</span></button><button class="ibtn" type="button" aria-label="Remove filter" onclick={() => removeSavedViewFilter(index)}>{@html ic('x', 12)}</button></span>{#if index < savedViewFilters.length - 1}<span class="muted">and</span>{/if}{/each}<button type="button" class="btn btn-sm btn-ghost" data-saved-filter-trigger aria-haspopup="dialog" aria-expanded={savedFilterOpen} onclick={event => toggleSavedFilterBuilder(event)}>{@html ic('plus', 12)}Add</button><span class="sp"></span><button type="button" class="btn btn-sm btn-ghost" onclick={clearSavedViewFilters}>Clear all</button></div>{/if}
        {#if savedFilterOpen}
          <div class="pop enter fbuild" data-saved-filter-builder role="dialog" aria-label="Filter by" style={`left:${savedFilterPosition.left}px;top:${savedFilterPosition.top}px;width:${Math.min(560, window.innerWidth - 16)}px;max-width:${Math.min(560, window.innerWidth - 16)}px`}>
            <div class="mh fb-h">Filter by{#if savedViewFilters.length}<button type="button" class="btn btn-sm btn-ghost" onclick={clearSavedViewFilters}>Clear all</button>{/if}</div>
            {#if savedViewFilters.length}
              <div class="fb-rows">
                {#each savedViewFilters as filter, index (index)}
                  <div class="frow">
                    <span class="conj">{index ? 'and' : 'Where'}</span>
                    <select class="select" aria-label="Field" value={filter.f} onchange={event => { updateSavedViewFilter(index, { f: event.currentTarget.value, v: [] }); savedFilterEditIndex = index; }}>
                      {#each savedFilterFields as [field, label] (field)}<option value={field}>{label}</option>{/each}
                    </select>
                    <select class="select" aria-label="Operator" value={filter.op} onchange={event => updateSavedViewFilter(index, { op: event.currentTarget.value })}><option value="is">is</option><option value="not">is not</option></select>
                    <button type="button" class="valbtn" aria-label={`Values for ${savedFilterFields.find(([field]) => field === filter.f)?.[1] || filter.f}`} aria-expanded={savedFilterEditIndex === index} onclick={() => savedFilterEditIndex = savedFilterEditIndex === index ? null : index}><span class="trunc grow">{#if filter.v.length}{filterOptions(filter).filter(option => filter.v.includes(String(option.id))).map(option => option.name).join(', ')}{:else}<span class="faint">Select…</span>{/if}</span>{@html ic('chevron-down', 12)}</button>
                    <button type="button" class="ibtn ibtn-xs" aria-label="Remove filter" onclick={() => removeSavedViewFilter(index)}>{@html ic('trash-2', 13)}</button>
                  </div>
                  {#if savedFilterEditIndex === index}<div style="margin-left:50px;border:1px solid var(--border);border-radius:var(--r);padding:4px;max-height:200px;overflow:auto">{#each filterOptions(filter) as option (option.id)}<label class="mi" style="cursor:pointer;min-height:28px"><input type="checkbox" class="check" checked={filter.v.includes(String(option.id))} onchange={() => toggleSavedViewFilterValue(index, String(option.id))} />{option.name}</label>{/each}</div>{/if}
                {/each}
              </div>
            {:else}<p class="fb-empty">No filters applied. Combine filters to narrow the task list, e.g. Status is In Progress and Assignee is Sarah.</p>{/if}
            <div class="msep"></div><div class="fb-add"><span class="faint">Add filter:</span>{#each savedFilterFields as [field, label, icon] (field)}<button type="button" class="badge" style="cursor:pointer" onclick={() => addSavedFilter(field)}>{@html ic(icon, 11)}{label}</button>{/each}</div>
          </div>
        {/if}
        {#if savedValueOpenIndex !== null && savedViewFilters[savedValueOpenIndex]}
          {@const valueFilter = savedViewFilters[savedValueOpenIndex]}
          <div class="pop enter" data-saved-filter-values role="menu" aria-label={`${savedFilterFields.find(([field]) => field === valueFilter.f)?.[1] || valueFilter.f} ${valueFilter.op === 'not' ? 'is not' : 'is'}`} style={`left:${savedValuePosition.left}px;top:${savedValuePosition.top}px;width:200px`}>
            <div class="mh">{savedFilterFields.find(([field]) => field === valueFilter.f)?.[1] || valueFilter.f} {valueFilter.op === 'not' ? 'is not' : 'is'}</div>
            {#each filterOptions(valueFilter) as option (option.id)}<label class="mi" style="cursor:pointer"><input type="checkbox" class="check" checked={valueFilter.v?.includes(String(option.id)) || false} onchange={() => toggleSavedViewFilterValue(savedValueOpenIndex, String(option.id))} />{@html savedFilterOptionIcon(valueFilter, option)}{option.name}</label>{/each}
          </div>
        {/if}
        {#if savedViewError}<div class="alert danger" role="alert">{savedViewError}</div>{/if}
        <WorkspaceTaskList groups={savedViewGroups} {projects} members={project.members || []} {workspaceData} columns={['status', 'assignee', 'priority', 'due', 'labels']} onOpen={task => drawerTaskId = task.id} onUpdate={updateSavedViewTask} onCreate={createSavedViewTask} />
      {:else if tab === 'overview'}
        <div class="page" style="max-width:1160px;padding-top:22px">
          <div class="grid2">
            <div class="stack">
              <section class="panel"><div class="panel-h"><h2>About</h2><div class="acts"><button type="button" class="btn btn-sm btn-ghost" onclick={openProjectSettings}>{@html ic('pencil', 13)}Edit</button></div></div><div class="panel-b" style="font-size:14px;line-height:1.6;color:var(--text)">{project.description || ''}</div></section>
              <section class="panel"><div class="panel-h"><h2>Progress</h2><div class="acts"><span class="faint" style="font-size:12px">{completedCount} of {tasks.length} tasks done</span></div></div>
                <div class="panel-b">
                  <div class="row" style="align-items:baseline;gap:10px;margin-bottom:10px"><span style="font-size:30px;font-weight:600;letter-spacing:-.03em" class="num">{progress}%</span>{#if project.due_date}<span class="muted">{daysUntil(project.due_date) >= 0 ? `${daysUntil(project.due_date)} days until ${formatDate(project.due_date)}` : `Ended ${formatDate(project.due_date)}`}</span>{/if}{#if overdueCount}<span class="badge red">{@html ic('clock-alert', 11)}{overdueCount} overdue</span>{/if}</div>
                  <div class="stackbar" style="height:8px;margin-bottom:12px">{#each statusCounts as column (column.id)}<i style={`width:${(column.count / Math.max(tasks.length, 1)) * 100}%;background:var(--st-${column.id})`} title={`${column.name}: ${column.count}`}></i>{/each}</div>
                  <div class="row" style="flex-wrap:wrap;gap:14px;font-size:12.5px">{#each statusCounts as column (column.id)}<button type="button" class="row" style="gap:5px" onclick={() => openOverviewStatus(column.id)}>{@html ic(column.icon, 12)}<span class="muted">{column.name}</span><b class="num" style="font-weight:600">{column.count}</b></button>{/each}</div>
                </div>
              </section>
              <section class="panel"><div class="panel-h"><h2>Coming up</h2><div class="acts"><button class="btn btn-sm btn-ghost" onclick={() => { projectOverviewStatusFilter = ''; tab = 'list'; }}>View list</button></div></div>
                {#if comingUp.length}{#each comingUp as task (task.id)}{@const taskAssignee = memberByDatabaseId.get(String(task.assignee_id || ''))}{@const taskDueDate = String(task.due_date || '').slice(0, 10)}<div class="mini" role="button" tabindex="0" onclick={() => openTaskFromOverview(task)} onkeydown={event => { if (event.target === event.currentTarget && ['Enter', ' '].includes(event.key)) { event.preventDefault(); openTaskFromOverview(task); } }}><input type="checkbox" class="check round" checked={task.status === 'done'} aria-label={`${task.status === 'done' ? 'Reopen' : 'Complete'} ${task.title}`} onclick={event => event.stopPropagation()} onchange={() => void changeStatus(task, task.status === 'done' ? 'todo' : 'done')} /><span title={statusName(task.status)}>{@html stIcon(task.status, 13)}</span><span class="tt grow">{task.title}</span>{@html prIcon(task.priority)}<span style="width:74px;text-align:right" class="hide-m"><span class={`due ${task.status !== 'done' && new Date(`${taskDueDate}T00:00:00`) < new Date(new Date().toDateString()) ? 'over' : ''}`}>{relDate(taskDueDate)}</span></span>{#if taskAssignee}<span class="av sm" style={`--c:${taskAssignee.c || '#8A867E'}`} aria-label={taskAssignee.name}>{memberInitials(taskAssignee.name)}</span>{/if}</div>{/each}{:else}<div class="empty sm"><span class="empty-ic">{@html ic('circle-check', 20)}</span><h2>Nothing scheduled</h2><p>Tasks with due dates will show up here.</p></div>{/if}
              </section>
            </div>
            <div class="stack">
              <section class="panel"><div class="panel-h"><h2>Details</h2></div><div class="panel-b"><dl class="kv">
                <dt>{@html ic('circle-dot', 14)}Status</dt><dd><button type="button" class="pillbtn" data-project-status aria-label="Project status" aria-haspopup="menu" aria-expanded={!!projectStatusMenu} onclick={openProjectStatusMenu}><span class="pstatus" style={`--c:${PSTAT[project.status]?.c || 'var(--gray)'}`}><i></i>{projectStatusName(project.status)}</span></button></dd>
                <dt>{@html ic('user', 14)}Lead</dt><dd><span class="pillbtn">{#if project.lead}<span class="av sm" style={`--c:${project.lead.color || '#8A867E'}`}>{memberInitials(project.lead.name)}</span>{project.lead.name}{:else}Unassigned{/if}</span></dd>
                <dt>{@html ic('users', 14)}Team</dt><dd>{#if projectTeam}<Link class="pillbtn" href={teamShow(projectTeam.id).url}>{@html ic(projectTeam.icon || 'users-round', 13)}{projectTeam.name}</Link>{:else}<span class="pillbtn">—</span>{/if}</dd>
                <dt>{@html ic('calendar', 14)}Start</dt><dd><span class="pillbtn num">{formatLongDate(project.start_date)}</span></dd>
                <dt>{@html ic('flag', 14)}Due</dt><dd><span class="pillbtn num">{formatLongDate(project.due_date)}</span></dd>
                <dt>{@html ic('user-plus', 14)}Members</dt><dd><button type="button" class="pillbtn" aria-label="Project members" onclick={openProjectMembers}><span class="avs">{#each (project.members || []).slice(0, 6) as member (member.id)}<span class="av sm" style={`--c:${member.color || '#8A867E'}`} aria-label={member.name}>{memberInitials(member.name)}</span>{/each}{#if (project.members || []).length > 6}<span class="av sm more" style="--c:var(--gray)">+{project.members.length - 6}</span>{/if}</span><span class="faint">{project.members?.length || 0}</span></button></dd>
              </dl></div></section>
              <section class="panel"><div class="panel-h"><h2>Milestones</h2></div><div class="panel-b">
                {#if sourceProject?.milestones?.length}
                  {#each sourceProject.milestones as milestone (milestone.name)}
                    {@const milestoneDate = daysUntil(milestone.date)}
                    <div class="row" style="height:32px;font-size:13px"><span style={`width:9px;height:9px;transform:rotate(45deg);border-radius:2px;flex-shrink:0;margin:0 3px;background:${milestoneDate < 0 ? 'var(--green)' : PCOLORS[sourceProject.color] || PCOLORS.slate}`}></span><span class="grow trunc" class:faint={milestoneDate < 0}>{milestone.name}</span><span class="num muted">{fmtDate(milestone.date)}</span></div>
                  {/each}
                {:else}<span class="faint">No milestones yet</span>{/if}
              </div></section>
              <section class="panel"><div class="panel-h"><h2>Recent activity</h2><div class="acts"><button class="btn btn-sm btn-ghost" onclick={() => tab = 'activity'}>View all</button></div></div><div class="panel-b feed">
                {#if projectOverviewEvents.length}
                  {#each projectOverviewEvents.slice(0, 6) as event (event.id)}
                    {@const actor = memberById.get(event.by)}
                    {@const eventTask = sourceTaskById.get(event.task)}
                    <div class="fitem"><span class="av sm" style={`--c:${actor?.c || '#8A867E'}`} aria-label={actor?.name || 'Someone'}>{actor?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><div class="grow"><b>{event.by === workspaceData.me ? 'You' : actor?.name || 'Someone'}</b><span class="muted"> {event.verb} </span>{#if eventTask}<button type="button" class="obj" onclick={() => eventTask.database_id && (drawerTaskId = eventTask.database_id)}>{eventTask.title}</button>{:else}<span class="obj">{project.name}</span>{/if}{#if event.extra}<span class="muted"> {event.extra}</span>{/if}</div><time>{ago(event.at)}</time></div>
                  {/each}
                {:else}<span class="faint">No activity yet</span>{/if}
              </div></section>
            </div>
          </div>
        </div>
      {:else if tab === 'board'}
        <div class="toolbar" role="toolbar">
          <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={taskSearch} placeholder="Search tasks" aria-label="Search tasks" /></div>
          <button class="btn btn-ghost" type="button" onclick={() => taskSearch = ''}>{@html ic('list-filter', 14)}Filter</button>
          <button class="btn btn-ghost" type="button">{@html ic('arrow-up-down', 14)}<span class="hide-m">Sort:</span> Manual</button>
          <span class="sp"></span>
          <button class="btn btn-primary btn-sm" type="button" onclick={() => openCreateTask()}>{@html ic('plus', 13)}New task</button>
        </div>
        <div class="board">
          {#each columns as column (column.id)}
            {@const columnTasks = filteredBoardTasks.filter(task => task.status === column.id)}
            <section class="bcol">
              <div class="bcol-h">{@html stIcon(column.id, 14)}<span>{column.name}</span><span class="cnt">{columnTasks.length}</span><span class="sp"></span><span class="acts"><button class="ibtn ibtn-xs" aria-label={`Add task to ${column.name}`} onclick={() => openCreateTask('', column.id)}>{@html ic('plus', 14)}</button><button class="ibtn ibtn-xs" aria-label={`Options for ${column.name}`}>{@html ic('ellipsis', 14)}</button></span></div>
              <div class="bcol-b">
                {#each columnTasks as task (task.id)}
                  {@const sourceTask = taskSnapshot(task)}
                  {@const subtasksForCard = cardSubtasks(task)}
                  {@const completedSubtasks = subtasksForCard.filter(subtask => subtask.done).length}
                  {@const attachmentsForCard = cardAttachments(task)}
                  {@const labelsForCard = cardLabels(task)}
                  {@const commentCount = taskComments(task)}
                  <div class="kcard" class:done={task.status === 'done'} role="button" tabindex="0" aria-label={task.title} onclick={() => drawerTaskId = task.id} onkeydown={event => { if (event.target === event.currentTarget && ['Enter', ' '].includes(event.key)) { event.preventDefault(); drawerTaskId = task.id; } }}>
                    {#if labelsForCard.length}<div class="labels">{#each labelsForCard as label (typeof label === 'string' ? label : label.id)}{@html lbl(typeof label === 'string' ? label : label.id)}{/each}</div>{/if}
                    <div class="top">{#if task.status === 'done'}<span class="done-ic" style="margin:2px 7px 0 0;display:inline-flex" aria-label="Done">{@html stIcon('done', 14)}</span>{/if}<button type="button" class="title" aria-label={`Open task ${task.title}`} onclick={() => drawerTaskId = task.id}>{task.title}</button></div>
                    {#if subtasksForCard.length}<div class="subp">{@html ic('list-checks', 12)}<span class="num">{completedSubtasks}/{subtasksForCard.length}</span><span class={`prog ${completedSubtasks === subtasksForCard.length ? 'green' : ''}`} role="progressbar" aria-valuenow={Math.round(completedSubtasks / subtasksForCard.length * 100)} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${completedSubtasks / subtasksForCard.length}`}></i></span></div>{/if}
                    <div class="meta">
                      <span class="key">{cardKey(task)}</span>
                      <button type="button" class="m" title={task.priority} aria-label={`Priority: ${task.priority}`} onclick={event => { event.stopPropagation(); openTableCellMenu(event, task, 'priority'); }}>{@html prIcon(task.priority, 13)}</button>
                      {#if task.due_date || sourceTask?.due}<button type="button" class="m" aria-label={`Due ${cardDue(task)}`} onclick={event => { event.stopPropagation(); openTableCellMenu(event, task, 'due'); }}>{@html ic('calendar', 12)}{cardDue(task)}</button>{/if}
                      {#if task.recurrence || sourceTask?.recur}<span class="m" title="Repeats">{@html ic('repeat', 12)}</span>{/if}
                      {#if commentCount}<span class="m" aria-label={`${commentCount} comments`}>{@html ic('message-square', 12)}{commentCount}</span>{/if}
                      {#if attachmentsForCard.length}<span class="m" aria-label={`${attachmentsForCard.length} attachments`}>{@html ic('paperclip', 12)}{attachmentsForCard.length}</span>{/if}
                      <button type="button" class="m" style="margin-left:auto" aria-label={`Assignee: ${task.assignee?.name || 'Unassigned'}`} onclick={event => { event.stopPropagation(); openTableCellMenu(event, task, 'assignee'); }}><span class="av sm" style={`--c:${task.assignee?.color || task.assignee?.c || '#8A867E'}`} aria-hidden="true">{#if task.assignee?.name}{task.assignee.name.split(' ').map(part => part[0]).join('').slice(0, 2)}{:else}{@html ic('user', 11)}{/if}</span></button>
                    </div>
                    <div class="hacts"><button class="ibtn ibtn-xs" type="button" aria-label={task.status === 'done' ? 'Reopen' : 'Mark complete'} onclick={event => { event.stopPropagation(); changeStatus(task, task.status === 'done' ? 'todo' : 'done'); }}>{@html ic(task.status === 'done' ? 'rotate-ccw' : 'check', 13)}</button><button class="ibtn ibtn-xs" type="button" aria-label="Quick edit" onclick={event => { event.stopPropagation(); drawerTaskId = task.id; }}>{@html ic('pencil', 12)}</button></div>
                  </div>
                {/each}
                {#if columnTasks.length === 0}<div class="faint" style="padding:8px;font-size:12px">No tasks</div>{/if}
                <button class="addcard" onclick={() => openCreateTask('', column.id)}>{@html ic('plus', 14)}Add task</button>
              </div>
            </section>
          {/each}
        </div>
        {#if tableCellMenu}
          {@const boardMenuTask = tasks.find(item => item.id === tableCellMenu.taskId)}
          {#if boardMenuTask && ['priority', 'assignee', 'due'].includes(tableCellMenu.field)}
            <div class="pop floating enter" data-board-card-menu data-table-cell-menu role="menu" aria-label={tableCellMenu.field === 'due' ? 'Due date' : tableCellMenu.field} style={`left:${tableCellMenu.left}px;top:${tableCellMenu.top}px;z-index:50;width:${tableCellMenu.field === 'assignee' ? 240 : 220}px`}>
              {#if tableCellMenu.field === 'priority'}<div class="mh">Priority</div>{#each ['urgent', 'high', 'medium', 'low', 'none'] as priority (priority)}<button type="button" class="mi" role="menuitemradio" aria-checked={boardMenuTask.priority === priority} onclick={() => updateTableTask(boardMenuTask, { priority })}>{@html prIcon(priority, 14)}{priority === 'none' ? 'No priority' : priority[0].toUpperCase() + priority.slice(1)}{#if boardMenuTask.priority === priority}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
              {:else if tableCellMenu.field === 'assignee'}<div class="mh">Assign to</div><div class="pin"><input bind:value={tableCellQuery} placeholder="Assign to…" aria-label="Assign to…" /></div><button type="button" class="mi" role="menuitemradio" aria-checked={!boardMenuTask.assignee_id} onclick={() => updateTableTask(boardMenuTask, { assignee_id: null })}>{@html ic('user-round', 14)}Unassigned{#if !boardMenuTask.assignee_id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{#each sourceMembers.filter(member => member.database_id && member.name.toLowerCase().includes(tableCellQuery.trim().toLowerCase())) as member (member.database_id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(boardMenuTask.assignee_id) === String(member.database_id)} onclick={() => updateTableTask(boardMenuTask, { assignee_id: member.database_id })}><span class="av sm" style={`--c:${member.c || '#8A867E'}`}>{memberInitials(member.name)}</span><span class="trunc">{member.name}</span>{#if String(boardMenuTask.assignee_id) === String(member.database_id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
              {:else}<div class="mh">Due date</div><div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">{#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7], ['In 2 weeks', 14]] as [label, days] (label)}<button type="button" class="badge" onclick={() => setTableTaskDate(boardMenuTask, 'due', tableQuickDate(days))}>{label}</button>{/each}{#if boardMenuTask.due_date}<button type="button" class="badge" onclick={() => setTableTaskDate(boardMenuTask, 'due', '')}>Clear</button>{/if}</div><div class="msep"></div><div class="pin"><input type="date" aria-label="Due date" value={String(boardMenuTask.due_date || '').slice(0, 10)} onchange={event => setTableTaskDate(boardMenuTask, 'due', event.currentTarget.value)} /></div>{/if}
            </div>
          {/if}
        {/if}
      {:else if tab === 'list'}
        <div class="toolbar" role="toolbar">
          <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={projectListQuery} placeholder="Search tasks" aria-label="Search tasks" /></div>
          <button type="button" class={`btn btn-ghost ${projectListFilters.length ? 'on' : ''}`} data-project-list-menu-trigger aria-haspopup="dialog" aria-expanded={projectListMenu === 'filter'} onclick={event => toggleProjectListMenu(event, 'filter')}>{@html ic('list-filter', 14)}Filter{#if projectListFilters.length}<span class="badge accent" style="height:16px;padding:0 5px">{projectListFilters.length}</span>{/if}</button>
          <button type="button" class="btn btn-ghost" data-project-list-menu-trigger aria-haspopup="menu" aria-expanded={projectListMenu === 'sort'} onclick={event => toggleProjectListMenu(event, 'sort')}>{@html ic('arrow-up-down', 14)}<span class="hide-m">Sort:</span> {({ manual: 'Manual', title: 'Title', due: 'Due date', priority: 'Priority', status: 'Status', assignee: 'Assignee', created: 'Created', updated: 'Updated', start: 'Start date', estimate: 'Estimate', project: 'Project' })[projectListSort]}</button>
          <button type="button" class="btn btn-ghost" data-project-list-menu-trigger aria-haspopup="menu" aria-expanded={projectListMenu === 'group'} onclick={event => toggleProjectListMenu(event, 'group')}>{@html ic('rows-3', 14)}<span class="hide-m">Group:</span> {({ status: 'Status', priority: 'Priority', assignee: 'Assignee', project: 'Project', due: 'Due date', none: 'None' })[projectListGroup]}</button>
          <span class="sp"></span><button type="button" class="btn btn-primary btn-sm" onclick={() => openCreateTask()}>{@html ic('plus', 13)}New task</button>
        </div>
        {#if projectListMenu === 'filter'}
          <div class="pop enter fbuild" data-project-list-menu role="dialog" aria-label="Filter by" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:min(560px,calc(100vw - 16px));max-width:min(560px,calc(100vw - 16px));z-index:40`}>
            <div class="mh fb-h">Filter by{#if projectListFilters.length}<button type="button" class="btn btn-sm btn-ghost" onclick={clearProjectListFilters}>Clear all</button>{/if}</div>
            {#if projectListFilters.length}
              <div class="fb-rows">
                {#each projectListFilters as filter, index (index)}
                  <div class="frow">
                    <span class="conj">{index ? 'and' : 'Where'}</span>
                    <select class="select" aria-label="Field" value={filter.f} onchange={event => { updateProjectListFilter(index, { f: event.currentTarget.value, v: [] }); projectListFilterEditIndex = index; }}>
                      {#each savedFilterFields as [field, label] (field)}<option value={field}>{label}</option>{/each}
                    </select>
                    <select class="select" aria-label="Operator" value={filter.op} onchange={event => updateProjectListFilter(index, { op: event.currentTarget.value })}><option value="is">is</option><option value="not">is not</option></select>
                    <button type="button" class="valbtn" aria-label={`Values for ${savedFilterFields.find(([field]) => field === filter.f)?.[1] || filter.f}`} aria-expanded={projectListFilterEditIndex === index} onclick={() => { projectListFilterEditIndex = projectListFilterEditIndex === index ? null : index; projectListValueOpenIndex = null; }}><span class="trunc grow">{#if filter.v.length}{filterOptions(filter).filter(option => filter.v.includes(String(option.id))).map(option => option.name).join(', ')}{:else}<span class="faint">Select…</span>{/if}</span>{@html ic('chevron-down', 12)}</button>
                    <button type="button" class="ibtn ibtn-xs" aria-label="Remove filter" onclick={() => removeProjectListFilter(index)}>{@html ic('trash-2', 13)}</button>
                  </div>
                  {#if projectListFilterEditIndex === index}<div style="margin-left:50px;border:1px solid var(--border);border-radius:var(--r);padding:4px;max-height:200px;overflow:auto">{#each filterOptions(filter) as option (option.id)}<label class="mi" style="cursor:pointer;min-height:28px"><input type="checkbox" class="check" checked={filter.v.includes(String(option.id))} onchange={() => toggleProjectListFilterValue(index, String(option.id))} />{@html savedFilterOptionIcon(filter, option)}{option.name}</label>{/each}</div>{/if}
                {/each}
              </div>
            {:else}<p class="fb-empty">No filters applied. Combine filters to narrow the task list, e.g. Status is In Progress and Assignee is Sarah.</p>{/if}
            <div class="msep"></div><div class="fb-add"><span class="faint">Add filter:</span>{#each savedFilterFields as [field, label, icon] (field)}<button type="button" class="badge" style="cursor:pointer" onclick={() => addProjectListFilter(field)}>{@html ic(icon, 11)}{label}</button>{/each}</div>
          </div>
        {:else if projectListMenu === 'sort'}
          <div class="pop enter" data-project-list-menu role="menu" aria-label="Sort tasks" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:240px;z-index:40`}>
            <div class="mh">Sort by</div>
            {#each [['manual', 'Manual'], ['priority', 'Priority'], ['due', 'Due date'], ['start', 'Start date'], ['title', 'Title'], ['status', 'Status'], ['assignee', 'Assignee'], ['created', 'Created'], ['updated', 'Last updated']] as [value, label] (value)}<button type="button" class="mi" role="menuitemradio" aria-checked={projectListSort === value} onclick={() => projectListSort = value}>{label}{#if projectListSort === value}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            <div class="msep"></div><div class="seg" style="margin:6px"><button type="button" class:on={projectListSortDirection > 0} onclick={() => projectListSortDirection = 1}>{@html ic('arrow-up', 12)}Ascending</button><button type="button" class:on={projectListSortDirection < 0} onclick={() => projectListSortDirection = -1}>{@html ic('arrow-down', 12)}Descending</button></div>
          </div>
        {:else if projectListMenu === 'group'}
          <div class="pop enter" data-project-list-menu role="menu" aria-label="Group tasks" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:180px;z-index:40`}>
            {#each [['status', 'Status'], ['priority', 'Priority'], ['assignee', 'Assignee'], ['project', 'Project'], ['due', 'Due date'], ['none', 'None']] as [value, label] (value)}<button type="button" class="mi" role="menuitemradio" aria-checked={projectListGroup === value} onclick={() => { projectListGroup = value; projectListMenu = null; }}>{label}{#if projectListGroup === value}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
          </div>
        {/if}
        {#if projectListFilters.length}<div class="chipsbar">{@html ic('list-filter', 13)}{#each projectListFilters as filter, index (index)}<span class="chip"><button type="button" aria-label={`Values for ${savedFilterFields.find(([field]) => field === filter.f)?.[1] || filter.f}`} aria-expanded={projectListValueOpenIndex === index} onclick={event => { const bounds = event.currentTarget.getBoundingClientRect(); projectListMenuPosition = { left: Math.max(8, Math.min(bounds.left, window.innerWidth - 208)), top: Math.min(bounds.bottom + 4, window.innerHeight - 220) }; projectListValueOpenIndex = projectListValueOpenIndex === index ? null : index; }}>{@html ic(savedFilterFields.find(([field]) => field === filter.f)?.[2] || 'list-filter', 12)}<b>{savedFilterFields.find(([field]) => field === filter.f)?.[1] || filter.f}</b> {filter.op === 'not' ? 'is not' : 'is'} <span>{filter.v.length ? filterOptions(filter).filter(option => filter.v.includes(String(option.id))).map(option => option.name).join(', ') : 'any'}</span></button><button class="ibtn" type="button" aria-label="Remove filter" onclick={() => removeProjectListFilter(index)}>{@html ic('x', 12)}</button></span>{#if index < projectListFilters.length - 1}<span class="muted">and</span>{/if}{/each}<button type="button" class="btn btn-sm btn-ghost" data-project-list-menu-trigger aria-haspopup="dialog" onclick={event => toggleProjectListMenu(event, 'filter')}>{@html ic('plus', 12)}Add</button><span class="sp"></span><button type="button" class="btn btn-sm btn-ghost" onclick={clearProjectListFilters}>Clear all</button></div>{/if}
        {#if projectListValueOpenIndex !== null && projectListFilters[projectListValueOpenIndex]}
          {@const valueFilter = projectListFilters[projectListValueOpenIndex]}
          <div class="pop enter" data-project-list-menu role="menu" aria-label={`${savedFilterFields.find(([field]) => field === valueFilter.f)?.[1] || valueFilter.f} ${valueFilter.op === 'not' ? 'is not' : 'is'}`} style={`position:fixed;left:${projectListMenuPosition.left}px;top:${projectListMenuPosition.top}px;width:200px;z-index:41`}>
            <div class="mh">{savedFilterFields.find(([field]) => field === valueFilter.f)?.[1] || valueFilter.f} {valueFilter.op === 'not' ? 'is not' : 'is'}</div>
            {#each filterOptions(valueFilter) as option (option.id)}<label class="mi" style="cursor:pointer"><input type="checkbox" class="check" checked={valueFilter.v.includes(String(option.id))} onchange={() => toggleProjectListFilterValue(projectListValueOpenIndex, String(option.id))} />{@html savedFilterOptionIcon(valueFilter, option)}{option.name}</label>{/each}
          </div>
        {/if}
        <div>
          {#if projectOverviewStatusFilter}<div class="row" style="padding-bottom:10px"><span class="faint">Status: {statusName(projectOverviewStatusFilter)}</span><button type="button" class="btn btn-sm btn-ghost" onclick={() => projectOverviewStatusFilter = ''}>Clear filter</button></div>{/if}
          {#if projectListTasks.length}<WorkspaceTaskList groups={projectListGroups} {projects} members={project.members || []} {workspaceData} columns={['status', 'assignee', 'priority', 'due', 'labels']} onOpen={task => drawerTaskId = task.id} onUpdate={updateProjectListTask} onCreate={createProjectListTask} />{:else}<section class="panel"><div class="empty"><span class="empty-ic">{@html ic(projectListQuery || projectListFilters.length ? 'search-x' : 'list-checks', 20)}</span><h2>{projectListQuery || projectListFilters.length ? 'No results found' : 'No tasks here'}</h2><p>{projectListQuery || projectListFilters.length ? 'No tasks match your search or filters.' : 'Add a task to get things moving.'}</p><button class="btn btn-primary btn-sm" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</button></div></section>{/if}
        </div>
      {:else if tab === 'table'}
        <div class="toolbar" role="toolbar">
          <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={projectListQuery} placeholder="Search tasks" aria-label="Search tasks" /></div>
          <button type="button" class={`btn btn-ghost ${projectListFilters.length ? 'on' : ''}`} data-project-list-menu-trigger aria-haspopup="dialog" aria-expanded={projectListMenu === 'filter'} onclick={event => toggleProjectListMenu(event, 'filter')}>{@html ic('list-filter', 14)}Filter{#if projectListFilters.length}<span class="badge accent" style="height:16px;padding:0 5px">{projectListFilters.length}</span>{/if}</button>
          <button type="button" class="btn btn-ghost" data-project-list-menu-trigger aria-haspopup="menu" aria-expanded={projectListMenu === 'sort'} onclick={event => toggleProjectListMenu(event, 'sort')}>{@html ic('arrow-up-down', 14)}<span class="hide-m">Sort:</span> {({ manual: 'Manual', title: 'Title', due: 'Due date', priority: 'Priority', status: 'Status', assignee: 'Assignee', created: 'Created', updated: 'Last updated', start: 'Start date' })[projectListSort]}</button>
          <button type="button" class="btn btn-ghost" data-project-list-menu-trigger aria-haspopup="menu" aria-expanded={projectListMenu === 'group'} onclick={event => toggleProjectListMenu(event, 'group')}>{@html ic('rows-3', 14)}<span class="hide-m">Group:</span> {({ status: 'Status', priority: 'Priority', assignee: 'Assignee', project: 'Project', due: 'Due date', none: 'None' })[projectListGroup]}</button>
          <button type="button" class="btn btn-ghost" data-project-list-menu-trigger aria-haspopup="menu" aria-expanded={projectListMenu === 'columns'} onclick={event => toggleProjectListMenu(event, 'columns')}>{@html ic('columns-3', 14)}Columns</button>
          <span class="sp"></span><button type="button" class="btn btn-primary btn-sm" onclick={() => openCreateTask()}>{@html ic('plus', 13)}New task</button>
        </div>
        {#if projectListMenu === 'filter'}
          <div class="pop enter fbuild" data-project-list-menu role="dialog" aria-label="Filter by" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:min(560px,calc(100vw - 16px));max-width:min(560px,calc(100vw - 16px));z-index:40`}>
            <div class="mh fb-h">Filter by{#if projectListFilters.length}<button type="button" class="btn btn-sm btn-ghost" onclick={clearProjectListFilters}>Clear all</button>{/if}</div>
            {#if projectListFilters.length}<div class="fb-rows">{#each projectListFilters as filter, index (index)}<div class="frow"><span class="conj">{index ? 'and' : 'Where'}</span><select class="select" aria-label="Field" value={filter.f} onchange={event => { updateProjectListFilter(index, { f: event.currentTarget.value, v: [] }); projectListFilterEditIndex = index; }}><option value="status">Status</option><option value="assignee">Assignee</option><option value="priority">Priority</option><option value="due">Due date</option><option value="project">Project</option><option value="labels">Labels</option><option value="created">Created date</option><option value="updated">Updated date</option></select><select class="select" aria-label="Operator" value={filter.op} onchange={event => updateProjectListFilter(index, { op: event.currentTarget.value })}><option value="is">is</option><option value="not">is not</option></select><button type="button" class="valbtn" aria-label={`Values for ${filter.f}`} onclick={() => projectListFilterEditIndex = projectListFilterEditIndex === index ? null : index}><span class="trunc grow">{filter.v.length ? filterOptions(filter).filter(option => filter.v.includes(String(option.id))).map(option => option.name).join(', ') : 'Select…'}</span>{@html ic('chevron-down', 12)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Remove filter" onclick={() => removeProjectListFilter(index)}>{@html ic('trash-2', 13)}</button></div>{#if projectListFilterEditIndex === index}<div style="margin-left:50px;border:1px solid var(--border);border-radius:var(--r);padding:4px;max-height:200px;overflow:auto">{#each filterOptions(filter) as option (option.id)}<label class="mi" style="cursor:pointer;min-height:28px"><input type="checkbox" class="check" checked={filter.v.includes(String(option.id))} onchange={() => toggleProjectListFilterValue(index, String(option.id))} />{option.name}</label>{/each}</div>{/if}{/each}</div>{:else}<p class="fb-empty">No filters applied. Combine filters to narrow the task list.</p>{/if}
            <div class="msep"></div><div class="fb-add"><span class="faint">Add filter:</span>{#each [['status', 'Status'], ['assignee', 'Assignee'], ['priority', 'Priority'], ['due', 'Due date'], ['project', 'Project'], ['labels', 'Labels'], ['created', 'Created date'], ['updated', 'Updated date']] as [field, label] (field)}<button type="button" class="badge" style="cursor:pointer" onclick={() => addProjectListFilter(field)}>{label}</button>{/each}</div>
          </div>
        {:else if projectListMenu === 'sort'}
          <div class="pop enter" data-project-list-menu role="menu" aria-label="Sort tasks" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:240px;z-index:40`}><div class="mh">Sort by</div>{#each [['manual', 'Manual'], ['priority', 'Priority'], ['due', 'Due date'], ['start', 'Start date'], ['title', 'Title'], ['status', 'Status'], ['assignee', 'Assignee'], ['created', 'Created'], ['updated', 'Last updated']] as [value, label] (value)}<button type="button" class="mi" role="menuitemradio" aria-checked={projectListSort === value} onclick={() => projectListSort = value}>{label}{#if projectListSort === value}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}<div class="msep"></div><div class="seg" style="margin:6px"><button type="button" class:on={projectListSortDirection > 0} onclick={() => projectListSortDirection = 1}>{@html ic('arrow-up', 12)}Ascending</button><button type="button" class:on={projectListSortDirection < 0} onclick={() => projectListSortDirection = -1}>{@html ic('arrow-down', 12)}Descending</button></div></div>
        {:else if projectListMenu === 'group'}
          <div class="pop enter" data-project-list-menu role="menu" aria-label="Group tasks" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:180px;z-index:40`}>{#each [['status', 'Status'], ['priority', 'Priority'], ['assignee', 'Assignee'], ['project', 'Project'], ['due', 'Due date'], ['none', 'None']] as [value, label] (value)}<button type="button" class="mi" role="menuitemradio" aria-checked={projectListGroup === value} onclick={() => { projectListGroup = value; projectListMenu = null; }}>{label}{#if projectListGroup === value}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}</div>
        {:else if projectListMenu === 'columns'}
          <div class="pop enter" data-project-list-menu role="menu" aria-label="Table columns" style={`position:fixed;top:${projectListMenuPosition.top}px;left:${projectListMenuPosition.left}px;width:200px;z-index:40`}><div class="mh">Visible columns</div>{#each projectTableColumnDefinitions.slice(1) as [field, label] (field)}<label class="mi" style="cursor:pointer"><input type="checkbox" class="check" checked={!projectTableHidden.includes(field)} onchange={() => toggleProjectTableColumn(field)} />{label}</label>{/each}<div class="msep"></div><button type="button" class="mi" role="menuitem" onclick={resetProjectTableColumnWidths}>{@html ic('rotate-ccw', 14)}Reset widths</button></div>
        {/if}
        {#if projectListFilters.length}<div class="chipsbar">{@html ic('list-filter', 13)}{#each projectListFilters as filter, index (index)}<span class="chip"><button type="button" aria-label={`Values for ${filter.f}`} onclick={event => { const bounds = event.currentTarget.getBoundingClientRect(); projectListMenuPosition = { left: Math.max(8, Math.min(bounds.left, window.innerWidth - 208)), top: Math.min(bounds.bottom + 4, window.innerHeight - 220) }; projectListFilterEditIndex = projectListFilterEditIndex === index ? null : index; projectListMenu = 'filter'; }}>{filter.f} is {filter.v.length ? filterOptions(filter).filter(option => filter.v.includes(String(option.id))).map(option => option.name).join(', ') : 'any'}</button><button type="button" class="ibtn" aria-label="Remove filter" onclick={() => removeProjectListFilter(index)}>{@html ic('x', 12)}</button></span>{/each}<span class="sp"></span><button type="button" class="btn btn-sm btn-ghost" onclick={clearProjectListFilters}>Clear all</button></div>{/if}
        {#if projectListTasks.length}
          {@const tableWidth = projectTableColumns.reduce((sum, column) => sum + column[2], 0)}
          <div class="tbl-wrap"><table class="tbl" style={`width:${tableWidth}px`} aria-label="Tasks table"><colgroup>{#each projectTableColumns as [field, , width] (field)}<col style={`width:${width}px`} />{/each}</colgroup><thead><tr>{#each projectTableColumns as [field, label] (field)}<th class:sticky={field === 'title'} scope="col"><div class="thi" role="button" tabindex="0" onclick={() => !['labels', 'deps'].includes(field) && (projectListSort = field)} onkeydown={event => event.key === 'Enter' && !['labels', 'deps'].includes(field) && (projectListSort = field)}>{label}{#if projectListSort === field}{@html ic(projectListSortDirection > 0 ? 'arrow-up' : 'arrow-down', 11)}{/if}</div><span class="rsz" aria-hidden="true" onpointerdown={event => startProjectTableColumnResize(event, field)}></span></th>{/each}</tr></thead><tbody>
            {#each projectListGroups as group (group.id)}
              {#if projectListGroup !== 'none'}<tr class="grow-row"><td class="sticky" colspan={projectTableColumns.length}><span class="row" style="gap:6px">{#if group.iconType === 'status'}{@html stIcon(group.id, 13)}{:else if group.icon}{@html ic(group.icon, 13)}{/if}{group.name}<span class="faint" style="font-weight:500">{group.tasks.length}</span></span></td></tr>{/if}
              {#each group.tasks as task (task.id)}<tr>
                {#each projectTableColumns as [field] (field)}
                  {#if field === 'title'}<td class="sticky cellbtn" role="button" tabindex="0" title="Double-click to rename" onclick={() => tableTitleEditId !== task.id && (drawerTaskId = task.id)} ondblclick={event => { event.stopPropagation(); tableTitleEditId = task.id; tableTitleDraft = task.title; }} onkeydown={event => event.key === 'Enter' && tableTitleEditId !== task.id && (drawerTaskId = task.id)}>{#if tableTitleEditId === task.id}<input class="inline-in" aria-label="Title" value={tableTitleDraft} oninput={event => tableTitleDraft = event.currentTarget.value} onclick={event => event.stopPropagation()} onkeydown={event => { if (event.key === 'Enter') saveTableText(task, 'title'); if (event.key === 'Escape') tableTitleEditId = null; }} onblur={() => saveTableText(task, 'title')} />{:else}<span class="row" style="gap:8px">{@html stIcon(task.status, 13)}<span class="trunc" style="font-weight:450">{task.title}</span><span class="mono faint" style="font-size:11px;margin-left:auto">{task.key}</span></span>{/if}</td>
                  {:else if field === 'status'}<td class="cellbtn"><div class="status-picker"><button type="button" class="pillbtn" aria-label={`Status: ${statusName(task.status)}`} aria-haspopup="menu" aria-expanded={statusMenu?.taskId === task.id} onclick={event => toggleStatusMenu(event, task)}>{@html stIcon(task.status, 13)}<span>{statusName(task.status)}</span></button>{#if statusMenu?.taskId === task.id}<div class="pop floating enter" role="menu" style={`left:${statusMenu.left}px;top:${statusMenu.top}px`}>{#each columns as option}<button type="button" class="mi" role="menuitemradio" aria-checked={task.status === option.id} onclick={() => chooseStatus(task, option.id)}>{@html stIcon(option.id, 14)}<span class="trunc">{option.name}</span>{#if task.status === option.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}</div>{/if}</div></td>
                  {:else if field === 'priority'}<td class="cellbtn" role="button" tabindex="0" data-table-cell-menu-trigger onclick={event => openTableCellMenu(event, task, 'priority')}>{@html prIcon(task.priority, 13)}<span style="margin-left:6px">{task.priority === 'none' ? '—' : task.priority[0].toUpperCase() + task.priority.slice(1)}</span></td>
                  {:else if field === 'assignee'}<td class="cellbtn" role="button" tabindex="0" data-table-cell-menu-trigger onclick={event => openTableCellMenu(event, task, 'assignee')}>{#if task.assignee}<span class="row" style="gap:6px"><span class="av sm" style={`--c:${task.assignee.color || task.assignee.c || '#8A867E'}`}>{memberInitials(task.assignee.name)}</span><span class="trunc">{task.assignee.name}</span></span>{:else}<span class="faint">Unassigned</span>{/if}</td>
                  {:else if field === 'due'}<td class="cellbtn num" role="button" tabindex="0" data-table-cell-menu-trigger onclick={event => openTableCellMenu(event, task, 'due')}>{task.due_date ? formatDate(task.due_date) : '—'}</td>
                  {:else if field === 'start'}<td class="cellbtn num" role="button" tabindex="0" data-table-cell-menu-trigger onclick={event => openTableCellMenu(event, task, 'start')}>{task.start_date ? formatDate(task.start_date) : '—'}</td>
                  {:else if field === 'labels'}<td class="cellbtn" role="button" tabindex="0" data-table-cell-menu-trigger onclick={event => openTableCellMenu(event, task, 'labels')}><span class="row" style="gap:4px">{#each task.labels || [] as label (label)}{@html lbl(label)}{:else}<span class="faint">—</span>{/each}</span></td>
                  {:else if field === 'deps'}<td class="cellbtn" role="button" tabindex="0" data-table-cell-menu-trigger onclick={event => openTableCellMenu(event, task, 'deps')}>{#if task.dependencies?.length}{#each task.dependencies as dependency (dependency)}{@const dependencyTask = tasks.find(item => item.source_key === dependency || item.key === dependency)}{#if dependencyTask}<span class="depchip" title={dependencyTask.title}>{dependencyTask.key}</span>{:else}<span class="depchip">{dependency}</span>{/if}{/each}{:else}<span class="faint">—</span>{/if}</td>
                  {:else if field === 'estimate'}<td class="cellbtn num" title="Double-click to edit" ondblclick={() => { tableEstimateEditId = task.id; tableEstimateDraft = task.estimate || ''; }}>{#if tableEstimateEditId === task.id}<input class="inline-in" aria-label="Estimate" value={tableEstimateDraft} oninput={event => tableEstimateDraft = event.currentTarget.value} onkeydown={event => { if (event.key === 'Enter') saveTableEstimate(task); if (event.key === 'Escape') tableEstimateEditId = null; }} onblur={() => saveTableEstimate(task)} />{:else if task.estimate}{task.estimate}{:else}<span class="faint">—</span>{/if}</td>
                  {:else if field === 'created'}<td class="num muted">{formatDate(task.created_at)}</td>
                  {:else if field === 'project'}<td class="cellbtn" role="button" tabindex="0" onclick={() => drawerTaskId = task.id}><span class="row" style="gap:6px"><span class="pdot" style={`--c:${PCOLORS[project.color] || PCOLORS.slate}`}></span>{project.name}</span></td>{/if}
                {/each}
                {#if tableCellMenu?.taskId === task.id}
                  <td style="display:contents">
                  <div class="pop floating enter" data-table-cell-menu role="menu" aria-label={tableCellMenu.field === 'due' || tableCellMenu.field === 'start' ? `${tableCellMenu.field} date` : tableCellMenu.field} style={`left:${tableCellMenu.left}px;top:${tableCellMenu.top}px;z-index:50;width:${tableCellMenu.field === 'assignee' ? 240 : 220}px`}>
                    {#if tableCellMenu.field === 'priority'}<div class="mh">Priority</div>{#each ['urgent', 'high', 'medium', 'low', 'none'] as priority (priority)}<button type="button" class="mi" role="menuitemradio" aria-checked={task.priority === priority} onclick={() => updateTableTask(task, { priority })}>{@html prIcon(priority, 14)}{priority === 'none' ? 'No priority' : priority[0].toUpperCase() + priority.slice(1)}{#if task.priority === priority}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
                    {:else if tableCellMenu.field === 'assignee'}<div class="mh">Assign to</div><div class="pin"><input bind:value={tableCellQuery} placeholder="Assign to…" aria-label="Assign to…" /></div><button type="button" class="mi" role="menuitemradio" aria-checked={!task.assignee_id} onclick={() => updateTableTask(task, { assignee_id: null })}>{@html ic('user-round', 14)}Unassigned{#if !task.assignee_id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{#each sourceMembers.filter(member => member.database_id && member.name.toLowerCase().includes(tableCellQuery.trim().toLowerCase())) as member (member.database_id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(task.assignee_id) === String(member.database_id)} onclick={() => updateTableTask(task, { assignee_id: member.database_id })}><span class="av sm" style={`--c:${member.c || '#8A867E'}`}>{memberInitials(member.name)}</span><span class="trunc">{member.name}</span>{#if String(task.assignee_id) === String(member.database_id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
                    {:else if tableCellMenu.field === 'due' || tableCellMenu.field === 'start'}{@const dateField = tableCellMenu.field}<div class="mh">{dateField === 'due' ? 'Due date' : 'Start date'}</div><div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">{#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7], ['In 2 weeks', 14]] as [label, days] (label)}<button type="button" class="badge" onclick={() => setTableTaskDate(task, dateField, tableQuickDate(days))}>{label}</button>{/each}{#if task[dateField === 'due' ? 'due_date' : 'start_date']}<button type="button" class="badge" onclick={() => setTableTaskDate(task, dateField, '')}>Clear</button>{/if}</div><div class="msep"></div><div class="pin"><input type="date" aria-label={dateField === 'due' ? 'Due date' : 'Start date'} value={String(task[dateField === 'due' ? 'due_date' : 'start_date'] || '').slice(0, 10)} onchange={event => setTableTaskDate(task, dateField, event.currentTarget.value)} /></div>
                    {:else if tableCellMenu.field === 'labels'}<div class="mh">Labels</div><div class="pin"><input bind:value={tableCellQuery} placeholder="Filter labels…" aria-label="Filter labels…" /></div>{#each LABELS.filter(label => label.name.toLowerCase().includes(tableCellQuery.trim().toLowerCase())) as label (label.id)}<label class="mi" style="cursor:pointer"><input type="checkbox" class="check" checked={(task.labels || []).includes(label.id)} onchange={() => toggleTableTaskValue(task, 'labels', label.id)} />{label.name}</label>{/each}
                    {:else if tableCellMenu.field === 'deps'}<div class="mh">Dependencies</div><div class="pin"><input bind:value={tableCellQuery} placeholder="Search tasks…" aria-label="Search tasks…" /></div>{#each tasks.filter(item => item.id !== task.id && `${item.key} ${item.title}`.toLowerCase().includes(tableCellQuery.trim().toLowerCase())) as dependency (dependency.id)}<label class="mi" style="cursor:pointer"><input type="checkbox" class="check" checked={(task.dependencies || []).includes(dependency.source_key)} onchange={() => toggleTableTaskValue(task, 'dependencies', dependency.source_key)} />{dependency.key} · {dependency.title}</label>{/each}{/if}
                  </div>
                  </td>
                {/if}
              </tr>{/each}
            {/each}
            <tr><td class="sticky cellbtn" colspan={projectTableColumns.length} style="color:var(--text-3);cursor:pointer" onclick={() => openCreateTask()}><span class="row" style="gap:6px">{@html ic('plus', 14)}New task</span></td></tr>
          </tbody></table></div>
        {:else}<section class="panel"><div class="empty"><span class="empty-ic">{@html ic('search-x', 20)}</span><h2>No results found</h2><p>No tasks match your search or filters.</p><button class="btn btn-secondary btn-sm" onclick={() => { projectListQuery = ''; clearProjectListFilters(); }}>Clear filters</button></div></section>{/if}
      {:else if tab === 'calendar'}
        <ProjectCalendar {tasks} {project} onCreate={openCreateTask} onReschedule={updateTableTask} />
      {:else if tab === 'files'}
        <div class="toolbar" role="toolbar">
          <div class="inwrap">{@html ic('search', 13)}<input class="input search-sm" bind:value={fileQuery} placeholder="Search files" aria-label="Search files" /></div>
          <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={fileType} aria-label="Filter by type"><option value="all">All types</option>{#each Object.entries(FT).filter(([key]) => key !== 'other') as [key, type]}<option value={key}>{type.n}</option>{/each}</select>
          <select class="select" style="height:26px;width:auto;font-size:12px" bind:value={fileSort} aria-label="Sort files"><option value="date">Sort: Newest</option><option value="name">Sort: Name</option><option value="type">Sort: Type</option></select>
          <span class="sp"></span><div class="seg" role="tablist" aria-label="File view"><button type="button" class:on={fileView === 'grid'} aria-label="Grid view" aria-pressed={fileView === 'grid'} onclick={() => fileView = 'grid'}>{@html ic('layout-grid', 13)}</button><button type="button" class:on={fileView === 'list'} aria-label="List view" aria-pressed={fileView === 'list'} onclick={() => fileView = 'list'}>{@html ic('list', 13)}</button></div>
          <label class="btn btn-primary btn-sm" style="cursor:pointer">{@html ic('upload', 13)}Upload<input type="file" multiple hidden aria-label="Upload files" onchange={handleProjectFileSelection} /></label>
        </div>
        <div class="page wide" style="padding-top:16px">
          <label class="dropzone" style="margin-bottom:16px" ondragover={event => { event.preventDefault(); event.currentTarget.classList.add('over'); }} ondragleave={event => event.currentTarget.classList.remove('over')} ondrop={handleProjectFileDrop}>{@html ic('upload-cloud', 18)}<span>Drop files here or <span class="link">browse</span> — up to 250 MB each</span><input type="file" multiple hidden aria-label="Browse files to upload" onchange={handleProjectFileSelection} /></label>
          {#if uploadingProjectFiles.length}
            <div class="col" style="gap:6px;margin-bottom:16px">
              {#each uploadingProjectFiles as upload (upload.id)}
                {@const uploadType = FT[upload.type] || FT.other}
                <div class="upl"><span class="ftype" style={`--c:${uploadType.c}`}>{@html ic(uploadType.i, 14)}</span><div class="grow"><div class="row"><span class="trunc" style="font-weight:500">{upload.name}</span><span class="sp"></span><span class="faint num" style="font-size:11.5px">{upload.pct}%</span></div><div class="prog" style="margin-top:6px"><i style={`--p:${upload.pct / 100}`}></i></div></div></div>
              {/each}
            </div>
          {/if}
          {#if projectFiles.length === 0}
            {@html empty(fileQuery || fileType !== 'all' ? 'search-x' : 'folder-open', fileQuery || fileType !== 'all' ? 'No results found' : 'No files yet', fileQuery || fileType !== 'all' ? 'No files match your search.' : 'Upload designs, documents, and assets to share them with the project.')}
          {:else if fileView === 'grid'}
            <div class="fgrid">
              {#each projectFiles as file (file.id)}
                {@const fileTypeDetails = FT[file.type] || FT.other}
                {@const fileAuthor = memberById.get(file.by)}
                <div class="fcard" role="button" tabindex="0" aria-label={file.name} onclick={() => selectedFile = file} onkeydown={event => event.key === 'Enter' && (selectedFile = file)}>{@html filePrev(file)}<div class="fi"><span class="fn trunc">{file.name}</span><span class="fm">{file.size} · {fileAuthor?.name?.split(' ')[0]} · {ago(file.at)}</span></div><button type="button" class="ibtn ibtn-xs more" aria-label={`File options for ${file.name}`} data-file-menu-trigger onclick={event => openFileMenu(event, file)}>{@html ic('ellipsis', 13)}</button></div>
              {/each}
            </div>
          {:else}
            <div class="panel" style="overflow-x:auto"><table class="perm-t" style="min-width:680px"><thead><tr><th style="padding-left:14px">Name</th><th style="text-align:left">Type</th><th style="text-align:left">Size</th><th style="text-align:left">Uploaded by</th><th style="text-align:left">Date</th><th></th></tr></thead><tbody>
              {#each projectFiles as file (file.id)}
                {@const fileTypeDetails = FT[file.type] || FT.other}
                {@const fileAuthor = memberById.get(file.by)}
                {@const linkedTask = sourceTaskById.get(file.task)}
                <tr><td style="padding-left:14px"><span class="row"><span class="ftype" style={`--c:${fileTypeDetails.c}`}>{@html ic(fileTypeDetails.i, 14)}</span><button type="button" class="link" style="font-weight:500" onclick={() => selectedFile = file}>{file.name}</button>{#if linkedTask}<span class="badge">{@html ic('link', 10)}{linkedTask.key}</span>{/if}</span></td><td style="text-align:left" class="muted">{fileTypeDetails.n}</td><td style="text-align:left" class="num muted">{file.size}</td><td style="text-align:left"><span class="row"><span class="av sm" style={`--c:${fileAuthor?.c || '#8A867E'}`}>{fileAuthor?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span>{fileAuthor?.name}</span></td><td style="text-align:left" class="muted">{ago(file.at)}</td><td><button type="button" class="ibtn ibtn-sm" aria-label={`File options for ${file.name}`} data-file-menu-trigger onclick={event => openFileMenu(event, file)}>{@html ic('ellipsis', 14)}</button></td></tr>
              {/each}
            </tbody></table></div>
          {/if}
        </div>
      {:else if tab === 'activity'}
        <div class="page" style="max-width:780px;padding-top:18px">
          {#if eventGroups.length}
            {#each eventGroups as [bucket, events] (bucket)}
              <div class="day-h">{bucket}</div><div class="feed lined">
                {#each events as event (event.id)}
                  {@const actor = memberById.get(event.by)}
                  {@const eventTask = sourceTaskById.get(event.task)}
                  <div class="fitem"><span class="av sm" style={`--c:${actor?.c || '#8A867E'}`} aria-label={actor?.name || 'Someone'}>{actor?.name?.split(' ').map(part => part[0]).join('').slice(0, 2) || '?'}</span><div class="grow"><b>{event.by === workspaceData.me ? 'You' : actor?.name || 'Someone'}</b><span class="muted"> {event.verb} </span>{#if eventTask}<a class="obj" href={`/projects/${project.id}`}>{eventTask.title}</a>{:else}<span class="obj">{project.name}</span>{/if}{#if event.extra}<span class="muted"> {event.extra}</span>{/if}</div><time>{ago(event.at)}</time></div>
                {/each}
              </div>
            {/each}
          {:else}<div class="empty"><span class="empty-ic">{@html ic('activity', 20)}</span><h2>No activity yet</h2><p>Changes to this project will appear here.</p></div>{/if}
        </div>
      {:else if tab === 'timeline'}
        <ProjectTimeline {tasks} {project} milestones={sourceProject?.milestones || []} sourceTaskOrder={(sourceTasks || []).map(task => task.database_id)} onCreate={openCreateTask} onTask={task => drawerTaskId = task.id} onUpdateTask={updateTableTask} />
      {/if}
    </div>
  </div>

  {#if fileMenu}
    <div class="pop enter" data-file-menu role="menu" tabindex="-1" style={`left:${fileMenuPosition.left}px;top:${fileMenuPosition.top}px`} onpointerdown={event => event.stopPropagation()}>
      <button type="button" class="mi" role="menuitem" onclick={() => { selectedFile = fileMenu; fileMenu = null; }}>{@html ic('eye', 15)}Preview</button>
      <button type="button" class="mi" role="menuitem" onclick={() => { fileRenameTarget = fileMenu; fileRenameName = fileMenu.name; fileMenu = null; }}>{@html ic('pencil', 15)}Rename</button>
      <button type="button" class="mi" role="menuitem" onclick={() => { void copyProjectFileLink(fileMenu); fileMenu = null; }}>{@html ic('link', 15)}Copy link</button>
      <button type="button" class="mi" role="menuitem" onclick={() => { duplicateProjectFile(fileMenu); fileMenu = null; }}>{@html ic('copy', 15)}Duplicate</button>
      <div class="msep"></div>
      <button type="button" class="mi danger" role="menuitem" onclick={() => { fileDeleteTarget = fileMenu; fileMenu = null; }}>{@html ic('trash-2', 15)}Delete</button>
    </div>
  {/if}
  {#if fileRenameTarget}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (fileRenameTarget = null)}>
      <div class="modal sm" transition:scale={{ duration: 240, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="rename-file-title" tabindex="-1">
        <div class="modal-h"><h2 id="rename-file-title">Rename file</h2><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={() => fileRenameTarget = null}>{@html ic('x', 16)}</button></div>
        <form id="rename-file-form" class="modal-b" onsubmit={renameProjectFile}><div class="field"><Label for="rename-file-name">File name</Label><input class="input" id="rename-file-name" bind:value={fileRenameName} required /></div></form>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => fileRenameTarget = null}>Cancel</button><button type="submit" form="rename-file-form" class="btn btn-primary">Save</button></div>
      </div>
    </div>
  {/if}
  {#if fileDeleteTarget}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (fileDeleteTarget = null)}>
      <div class="modal sm" transition:scale={{ duration: 240, start: 0.97 }} role="alertdialog" aria-modal="true" aria-labelledby="delete-file-title" tabindex="-1">
        <div class="modal-b" style="padding-top:18px;gap:10px">
          <div class="row" style="gap:12px;align-items:flex-start"><span class="ftype" style="--c:var(--red);width:34px;height:34px;border-radius:9px">{@html ic('trash-2', 17)}</span><div class="grow"><h2 id="delete-file-title" style="font-size:15px;font-weight:600;margin:4px 0 6px">Delete file?</h2><p class="muted" style="margin:0;font-size:13px;line-height:1.55"><b>{fileDeleteTarget.name}</b> will be removed from the project{fileDeleteTarget.task ? ' and its task' : ''}.</p></div></div>
        </div>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => fileDeleteTarget = null}>Cancel</button><button type="button" class="btn btn-danger" onclick={deleteProjectFile}>Delete file</button></div>
      </div>
    </div>
  {/if}
  {#if editDialogOpen}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (editDialogOpen = false)}>
      <div class="modal" transition:scale={{ duration: 240, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="project-settings-title" tabindex="-1">
        <div class="modal-h"><h2 id="project-settings-title">Project settings</h2><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={() => editDialogOpen = false}>{@html ic('x', 16)}</button></div>
        <form class="modal-b" onsubmit={saveProjectSettings}>
          <div class="field"><Label for="project-settings-name">Name</Label><input class="input" id="project-settings-name" bind:value={projectForm.name} maxlength="120" required /></div>
          <div class="field"><Label for="project-settings-description">Description</Label><textarea class="textarea" id="project-settings-description" bind:value={projectForm.description} rows="3" maxlength="5000"></textarea></div>
          <div class="field"><Label for="project-settings-status">Status</Label><select class="select" id="project-settings-status" bind:value={projectForm.status}>{#each Object.entries(PSTAT) as [status, details]}<option value={status}>{details.name}</option>{/each}</select></div>
          <div class="row" style="gap:10px"><div class="field grow"><Label for="project-settings-start">Start date</Label><input class="input" id="project-settings-start" type="date" bind:value={projectForm.start_date} /></div><div class="field grow"><Label for="project-settings-due">Due date</Label><input class="input" id="project-settings-due" type="date" bind:value={projectForm.due_date} /></div></div>
          {#if projectDialogError}<span class="err" role="alert">{projectDialogError}</span>{/if}
        </form>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => editDialogOpen = false}>Cancel</button><button type="button" class="btn btn-primary" disabled={projectDialogBusy} onclick={() => saveProjectSettings({ preventDefault() {} })}>{projectDialogBusy ? 'Saving…' : 'Save changes'}</button></div>
      </div>
    </div>
  {/if}
  {#if membersDialogOpen}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (membersDialogOpen = false)}>
      <div class="modal" transition:scale={{ duration: 240, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="project-share-title" tabindex="-1">
        <div class="modal-h"><h2 id="project-share-title">Share “{project.name}”</h2><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={() => membersDialogOpen = false}>{@html ic('x', 16)}</button></div>
        <div class="modal-b">
          <div class="field"><Label for="project-member-search">Add people by name or email</Label><input class="input" id="project-member-search" bind:value={memberQuery} placeholder="Search workspace members" /></div>
          <div class="eyebrow" style="margin-bottom:4px">People with access</div>
          {#each projectMemberOptions as member (member.id)}
            {@const isProjectMember = selectedMemberIds.includes(member.id)}
            {@const isProjectOwner = member.id === project.owner_id}
            <label class="row" style="height:44px;gap:10px;cursor:pointer"><span class="av md" style={`--c:${member.color || '#8A867E'}`} aria-hidden="true">{memberInitials(member.name)}</span><span class="grow"><span style="font-weight:500;font-size:13px">{member.name}{isProjectOwner ? ' (you)' : ''}</span><span class="faint" style="display:block;font-size:11.5px">{member.email}{isProjectOwner ? ' · Project lead' : ''}</span></span>{#if isProjectOwner}<span class="muted" style="font-size:12.5px">Full access</span>{:else}<input type="checkbox" class="check" checked={isProjectMember} onchange={() => toggleProjectMember(member.id)} aria-label={`Project access for ${member.name}`} />{/if}</label>
          {/each}
          {#if memberDialogError}<span class="err" role="alert">{memberDialogError}</span>{/if}
          <div style="border-top:1px solid var(--divider);padding-top:12px"><div class="eyebrow" style="margin-bottom:8px">General access</div><div class="row" style="gap:10px"><span class="ftype" style="--c:var(--text-2)">{@html ic(project.is_private ? 'lock' : 'building-2', 14)}</span><div class="grow"><b style="font-weight:500;font-size:13px">{project.is_private ? 'Only people invited' : `Everyone at ${workspaceData.ws?.name || 'Gr8r Studio'}`}</b><div class="faint" style="font-size:11.5px">{project.is_private ? 'Only people listed above can open this project.' : 'Anyone in the workspace can view and comment.'}</div></div></div></div>
        </div>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => membersDialogOpen = false}>Cancel</button><button type="button" class="btn btn-primary" disabled={projectDialogBusy} onclick={saveProjectMembers}>{projectDialogBusy ? 'Saving…' : 'Done'}</button></div>
      </div>
    </div>
  {/if}
  {#if saveViewOpen}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (saveViewOpen = false)}>
      <div class="modal sm" transition:scale={{ duration: 240, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="save-project-view-title" tabindex="-1">
        <div class="modal-h"><div><h2 id="save-project-view-title">Save view</h2><p class="faint" style="margin:3px 0 0;font-size:12px">Save the current filters as a tab on this project.</p></div><button type="button" class="ibtn ibtn-sm" aria-label="Close" onclick={() => saveViewOpen = false}>{@html ic('x', 16)}</button></div>
        <form id="save-project-view-form" class="modal-b" onsubmit={saveProjectView}>
          <div class="field"><Label for="save-project-view-name">View name</Label><input class="input" id="save-project-view-name" bind:this={saveViewNameInput} bind:value={saveViewName} placeholder="e.g. Design review queue" maxlength="80" /></div>
          <div class="field"><Label for="save-project-view-type">Layout</Label><select class="select" id="save-project-view-type" bind:value={saveViewType}><option value="list">List</option><option value="board">Board</option><option value="table">Table</option></select></div>
          {#if projectViewFilters.length}<div class="hint">{projectViewFilters.length} filter{projectViewFilters.length === 1 ? '' : 's'} will be saved with this view.</div>{:else}<div class="alert info">{@html ic('info', 14)}<span>No filters are active — the view will show all tasks. You can add filters after saving.</span></div>{/if}
        </form>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-secondary" onclick={() => saveViewOpen = false}>Cancel</button><button type="submit" class="btn btn-primary" form="save-project-view-form">Save view</button></div>
      </div>
    </div>
  {/if}
  <WorkspaceTaskForm open={showTaskForm} {projects} members={project.members || []} projectOrder={workspaceData.projects?.map(sourceProject => sourceProject.database_id).filter(Boolean) || []} initialProjectId={project.id} initialStatus={taskFormStatus} initialDate={taskFormDueDate} onClose={() => showTaskForm = false} onCreated={taskCreated} />
  {#if drawerTask}<TaskDrawer task={drawerTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={taskUpdated} />{/if}
  {#if selectedFile}
    {@const selectedFileType = FT[selectedFile.type] || FT.other}
    {@const selectedFileAuthor = memberById.get(selectedFile.by)}
    {@const attachedTask = sourceTaskById.get(selectedFile.task)}
    <div class="modal-wrap" transition:fade={{ duration: 180 }} role="presentation" onclick={event => event.target === event.currentTarget && (selectedFile = null)}>
      <div class="modal modal-lg" transition:scale={{ duration: 260, start: 0.97 }} role="dialog" aria-modal="true" aria-labelledby="file-preview-title" tabindex="-1">
        <div class="modal-h"><div><h2 id="file-preview-title">{selectedFile.name}</h2><p class="faint" style="margin:3px 0 0;font-size:12px">{selectedFileType.n} · {selectedFile.size} · Uploaded by {selectedFileAuthor?.name || 'you'} {ago(selectedFile.at)}</p></div><button type="button" class="ibtn" aria-label="Close preview" onclick={() => selectedFile = null}>{@html ic('x', 16)}</button></div>
        <div class="modal-b"><div style="border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden">{@html filePrev(selectedFile).replace('class="fprev"', 'class="fprev" style="aspect-ratio:16/9"')}</div>{#if attachedTask}<div class="row faint" style="font-size:12.5px;margin-top:12px">{@html ic('link', 13)}Attached to <a class="link" href={`/projects/${project.id}`}>{attachedTask.title}</a></div>{/if}</div>
        <div class="modal-f"><span class="sp"></span><button type="button" class="btn btn-primary" onclick={() => selectedFile = null}>Close</button></div>
      </div>
    </div>
  {/if}
</AppLayout>
