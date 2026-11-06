<script>
  import Input from "../../Components/Input.svelte";
  import { onMount } from 'svelte';
  import { tick } from 'svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import WorkspaceTaskForm from '../../Components/WorkspaceTaskForm.svelte';
  import WorkspaceTaskList from '../../Components/WorkspaceTaskList.svelte';
  import TaskDrawer from '../../Components/TaskDrawer.svelte';
  import { index as tasksIndexRoute, update as taskUpdate } from '../../routes/tasks';
  import { store as storeTaskRoute } from '../../routes/projects/tasks';
  import { useHttpRequest } from '../../lib/http.js';
  import { consumeNavigationIntent } from '../../lib/navigation-intents.js';
  import { show as projectShow } from '../../routes/projects';
  import { LABELS } from '@/src/core/constants.js';
  import { viewOf } from '@/src/shell/view-engine.js';
  import { S, save } from '@/src/core/store.js';
  import { addD, diffD, fmtDate, iso, MONL, TODAY, WD } from '@/src/core/utils.js';
  import { ic } from '@/src/core/icons.js';
  import { lbl, prIcon, prPill, stIcon, stPill } from '@/src/ui/helpers.js';

  const httpRequest = useHttpRequest();
  let { tasks: incomingTasks = [], projects = [], members = [], workspaceData = {}, defaultFilter = 'all' } = $props();
  const savedTaskView = viewOf('tasks');
  let tasks = $state([]);
  $effect(() => { tasks = [...incomingTasks]; });
  let query = $state(savedTaskView.q || '');
  let filters = $state((savedTaskView.filters || []).map(filter => ({
    field: filter.f,
    operator: filter.op,
    values: [...(filter.v || [])],
  })));
  let groupBy = $state(savedTaskView.group || 'status');
  let sortBy = $state(savedTaskView.sort?.f || 'manual');
  let sortDirection = $state(savedTaskView.sort?.dir || 1);
  let toolbarMenu = $state('');
  let valuesOpen = $state(-1);
  let menuPosition = $state({ left: 8, top: 8 });
  let view = $state(savedTaskView.mode || 'list');
  let createOpen = $state(false);
  let createAssigneeId = $state(null);
  let createStatus = $state('todo');
  let errorMessage = $state('');
  let drawerTaskId = $state(null);
  let draggedTaskId = $state(null);
  let boardComposerStatus = $state('');
  let boardComposerTitle = $state('');
  let boardComposerInput = $state(null);
  let boardCardMenu = $state(null);
  let boardCardMenuPosition = $state({ left: 8, top: 8 });
  let selectedTask = $derived(tasks.find(task => task.id === drawerTaskId) || null);
  const page = usePage();
  const tableColumns = [
    { id: 'title', name: 'Task', width: 314 },
    { id: 'status', name: 'Status', width: 132 },
    { id: 'priority', name: 'Priority', width: 112 },
    { id: 'assignee', name: 'Assignee', width: 156 },
    { id: 'due', name: 'Due date', width: 112 },
    { id: 'start', name: 'Start date', width: 112 },
    { id: 'labels', name: 'Labels', width: 180 },
    { id: 'deps', name: 'Dependencies', width: 150 },
    { id: 'estimate', name: 'Estimate', width: 92 },
    { id: 'created', name: 'Created', width: 112 },
    { id: 'project', name: 'Project', width: 156 },
  ];
  let tableColumnHidden = $state([...(savedTaskView.hidden || ['start', 'created', 'deps'])]);
  let tableColumnWidths = $state({ ...(savedTaskView.colW || {}) });
  let tableGroupCollapsed = $state({});
  let tableCellMenu = $state(null);
  let tableCellQuery = $state('');
  let tableCalendarMonth = $state(new Date(TODAY.getFullYear(), TODAY.getMonth(), 1));
  let editingTableCell = $state(null);
  let editingTableValue = $state('');
  let editingTableInput = $state(null);
  let resizingTableColumn = $state(null);
  onMount(() => {
    const intent = consumeNavigationIntent('create-task');
    if (!intent) return;

    const assignee = members.find(member => member.source_key === intent.assigneeSourceKey);
    openCreateTask(assignee ? String(assignee.id) : '');
  });
  $effect(() => {
    if (defaultFilter === 'open') filters = [{ field: 'status', operator: 'not', values: ['done'] }];
    if (defaultFilter === 'done') filters = [
      { field: 'status', operator: 'is', values: ['done'] },
      { field: 'updated', operator: 'is', values: ['7'] },
    ];
    if (defaultFilter === 'overdue') filters = [{ field: 'due', operator: 'is', values: ['overdue'] }];
  });
  $effect(() => {
    savedTaskView.mode = view;
    savedTaskView.q = query;
    savedTaskView.group = groupBy;
    savedTaskView.sort = { f: sortBy, dir: sortDirection };
    savedTaskView.filters = filters.map(filter => ({
      f: filter.field,
      op: filter.operator || 'is',
      v: [...filter.values],
    }));
    save();
  });

  const statuses = [
    { id: 'backlog', name: 'Backlog', icon: 'circle-dashed' },
    { id: 'todo', name: 'To Do', icon: 'circle' },
    { id: 'progress', name: 'In Progress', icon: 'circle-dot' },
    { id: 'review', name: 'Review', icon: 'circle-check' },
    { id: 'done', name: 'Done', icon: 'check-circle-2' },
  ];
  const priorities = ['none', 'low', 'medium', 'high', 'urgent'];
  const filterFields = [
    { id: 'status', name: 'Status', icon: 'circle-dot' },
    { id: 'assignee', name: 'Assignee', icon: 'user' },
    { id: 'priority', name: 'Priority', icon: 'signal-high' },
    { id: 'due', name: 'Due date', icon: 'calendar' },
    { id: 'project', name: 'Project', icon: 'folder' },
    { id: 'labels', name: 'Labels', icon: 'tag' },
    { id: 'created', name: 'Created date', icon: 'calendar-plus' },
    { id: 'updated', name: 'Updated date', icon: 'history' },
  ];
  const groupOptions = [
    { id: 'status', name: 'Status', icon: 'circle-dot' },
    { id: 'priority', name: 'Priority', icon: 'signal-high' },
    { id: 'assignee', name: 'Assignee', icon: 'user' },
    { id: 'project', name: 'Project', icon: 'folder' },
    { id: 'due', name: 'Due date', icon: 'calendar' },
    { id: 'none', name: 'No grouping', icon: 'minus' },
  ];
  const sortNames = {
    manual: 'Manual', priority: 'Priority', due: 'Due date', start: 'Start date',
    title: 'Title', status: 'Status', assignee: 'Assignee', created: 'Created', updated: 'Last updated',
  };
  let filterCount = $derived(filters.length);

  let visibleTasks = $derived.by(() => {
    const sourceTaskOrder = new Map((workspaceData.tasks || [])
      .map((task, index) => [String(task.database_id || task.id), index]));
    const filtered = tasks.filter(task => {
      const matchesQuery = !query || `${task.title} ${task.key || ''}`.toLowerCase().includes(query.toLowerCase());
      return matchesQuery && filters.every(filter => {
        if (!filter.values.length) return true;
        const matches = filter.values.some(value => matchesFilter(task, filter.field, value));
        return filter.operator === 'not' ? !matches : matches;
      });
    });
    return filtered.sort((first, second) => {
      if (sortBy === 'manual') {
        return ((sourceTaskOrder.get(String(first.id)) ?? Number.MAX_SAFE_INTEGER)
          - (sourceTaskOrder.get(String(second.id)) ?? Number.MAX_SAFE_INTEGER)) * sortDirection;
      }
      if (sortBy === 'title') return first.title.localeCompare(second.title) * sortDirection;
      if (sortBy === 'due') return (first.due_date || '9999').localeCompare(second.due_date || '9999') * sortDirection;
      if (sortBy === 'priority') return (priorities.indexOf(second.priority) - priorities.indexOf(first.priority)) * sortDirection;
      if (sortBy === 'start') return (first.start_date || '9999').localeCompare(second.start_date || '9999') * sortDirection;
      if (sortBy === 'status') return (statuses.findIndex(item => item.id === first.status) - statuses.findIndex(item => item.id === second.status)) * sortDirection;
      if (sortBy === 'assignee') return (first.assignee?.name || 'zzzz').localeCompare(second.assignee?.name || 'zzzz') * sortDirection;
      if (sortBy === 'updated') return (new Date(first.updated_at || 0) - new Date(second.updated_at || 0)) * sortDirection;
      return (new Date(first.created_at || 0) - new Date(second.created_at || 0)) * sortDirection;
    });
  });

  function matchesFilter(task, field, value) {
    if (field === 'status') {
      if (value === 'open') return task.status !== 'done';
      if (value === 'overdue') return isOverdue(task);
      return task.status === value;
    }
    if (field === 'assignee') return value === 'none' ? !task.assignee_id : String(task.assignee_id) === String(value);
    if (field === 'priority') return task.priority === value;
    if (field === 'project') return String(task.project_id) === String(value);
    if (field === 'labels') return (task.labels || []).includes(value);
    if (field === 'due') {
      if (value === 'nodate') return !task.due_date;
      if (!task.due_date) return false;
      if (value === 'overdue') return isOverdue(task);
      const delta = dayDifference(task.due_date);
      return value === 'today' ? delta === 0 : value === 'week' ? delta >= 0 && delta <= 7 : delta > 7;
    }
    const timestamp = new Date(field === 'created' ? task.created_at || 0 : task.updated_at || 0).getTime();
    return Date.now() - timestamp <= Number(value) * 86400000;
  }

  function dayDifference(value) {
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.floor((date.getTime() - today.getTime()) / 86400000);
  }

  function addFilter(field) {
    filters = [...filters, { field, operator: 'is', values: [] }];
    valuesOpen = filters.length - 1;
  }

  function removeFilter(index) {
    filters = filters.filter((_, filterIndex) => filterIndex !== index);
    valuesOpen = -1;
  }

  function clearFilters() {
    filters = [];
    query = '';
    valuesOpen = -1;
    toolbarMenu = '';
  }

  function sortByField(field) {
    if (sortBy === field) sortDirection *= -1;
    else {
      sortBy = field;
      sortDirection = 1;
    }
  }

  function filterOptions(field) {
    if (field === 'status') return statuses.map(status => ({ id: status.id, name: status.name, iconType: 'status' }));
    if (field === 'assignee') return [
      { id: 'none', name: 'Unassigned' },
      ...members.map(member => {
        const sourceMember = (workspaceData.members || []).find(item => String(item.database_id) === String(member.id));
        return {
          id: String(member.id),
          name: `${member.name}${String(member.id) === String(page.props.auth?.user?.id) ? ' (you)' : ''}`,
          member: { ...member, color: sourceMember?.c },
        };
      }),
    ];
    if (field === 'priority') return priorities.map(priority => ({ id: priority, name: priority === 'none' ? 'No priority' : priority[0].toUpperCase() + priority.slice(1), iconType: 'priority' }));
    if (field === 'project') return projects
      .filter(project => !project.is_private && !['complete', 'completed', 'archived'].includes(project.status))
      .map(project => ({ id: String(project.id), name: project.name, project }));
    if (field === 'labels') return LABELS.map(label => ({ id: label.id, name: label.name, color: label.c }));
    if (field === 'created' || field === 'updated') return [
      { id: '1', name: 'Last 24 hours' }, { id: '7', name: 'Last 7 days' }, { id: '30', name: 'Last 30 days' },
    ];
    return [
      { id: 'overdue', name: 'Overdue' }, { id: 'today', name: 'Today' },
      { id: 'week', name: 'Next 7 days' }, { id: 'later', name: 'Later' }, { id: 'nodate', name: 'No due date' },
    ];
  }

  function toggleToolbarMenu(menu, event) {
    if (toolbarMenu === menu) {
      toolbarMenu = '';
      return;
    }
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = menu === 'filter' ? 560 : menu === 'columns' ? 220 : 240;
    menuPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.min(bounds.bottom + 4, window.innerHeight - 380),
    };
    toolbarMenu = menu;
    if (menu !== 'filter') {
      valuesOpen = -1;
    }
  }

  function openFilterValues(index, event) {
    const bounds = event.currentTarget.getBoundingClientRect();
    menuPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - 560 - 8)),
      top: Math.min(bounds.bottom + 4, window.innerHeight - 380),
    };
    toolbarMenu = 'filter';
    valuesOpen = index;
  }

  function closeToolbarMenu() {
    toolbarMenu = '';
    valuesOpen = -1;
  }

  function handleWindowPointerdown(event) {
    if (!event.target.closest('[data-task-menu], [data-task-trigger]')) closeToolbarMenu();
    if (!event.target.closest('[data-board-card-menu], [data-board-card-trigger]')) boardCardMenu = null;
    if (!event.target.closest('[data-table-cell-menu], [data-table-cell-trigger]')) tableCellMenu = null;
  }

  function handleWindowKeydown(event) {
    if (event.key === 'Escape') {
      closeToolbarMenu();
      boardCardMenu = null;
      tableCellMenu = null;
    }
  }

  let visibleTableColumns = $derived(tableColumns.filter(column => column.id === 'title' || !tableColumnHidden.includes(column.id)));

  function toggleTableColumn(columnId) {
    if (columnId === 'title') return;
    tableColumnHidden = tableColumnHidden.includes(columnId)
      ? tableColumnHidden.filter(hiddenColumn => hiddenColumn !== columnId)
      : [...tableColumnHidden, columnId];
    savedTaskView.hidden = tableColumnHidden;
    save();
  }

  function tableColumnWidth(column) {
    return tableColumnWidths[column.id] || column.width;
  }

  function startTableColumnResize(event, column) {
    event.preventDefault();
    event.stopPropagation();
    resizingTableColumn = {
      id: column.id,
      startX: event.clientX,
      startWidth: event.currentTarget.parentElement.getBoundingClientRect().width,
    };
  }

  function resizeTableColumn(event) {
    if (!resizingTableColumn) return;
    const { id, startX, startWidth } = resizingTableColumn;
    tableColumnWidths = { ...tableColumnWidths, [id]: Math.max(64, startWidth + event.clientX - startX) };
  }

  function finishTableColumnResize() {
    if (!resizingTableColumn) return;
    savedTaskView.colW = Object.fromEntries(Object.entries(tableColumnWidths).map(([id, width]) => [id, Math.round(width)]));
    resizingTableColumn = null;
    save();
  }

  function tableSortField(columnId) {
    sortByField(['labels', 'deps'].includes(columnId) ? 'manual' : columnId);
  }

  async function startTableCellEdit(task, field) {
    editingTableCell = { taskId: task.id, field };
    editingTableValue = field === 'title' ? task.title : task.estimate || '';
    drawerTaskId = null;
    await tick();
    editingTableInput?.focus();
    editingTableInput?.select();
  }

  async function commitTableCell(task) {
    const field = editingTableCell?.field;
    if (!field || String(editingTableCell.taskId) !== String(task.id)) return;
    const value = editingTableValue.trim();
    editingTableCell = null;
    if (field === 'title' && !value) return;
    if (value !== String(field === 'title' ? task.title : task.estimate || '')) {
      await updateTask(task, { [field]: value || null });
    }
  }

  function openTableCellMenu(task, field, event) {
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = field === 'labels' ? 220 : 200;
    tableCellMenu = {
      taskId: task.id,
      field,
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.min(bounds.bottom + 4, window.innerHeight - 320),
    };
    tableCellQuery = '';
    if (field === 'due' || field === 'start') {
      const selectedDate = field === 'due' ? task.due_date : task.start_date;
      const date = selectedDate ? new Date(`${String(selectedDate).slice(0, 10)}T00:00:00`) : TODAY;
      tableCalendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    }
  }

  function updateTableCell(task, field, value) {
    tableCellMenu = null;
    const fieldNames = {
      due: 'due_date',
      start: 'start_date',
      assignee: 'assignee_id',
      project: 'project_id',
    };
    updateTask(task, { [fieldNames[field] || field]: value });
  }

  function toggleTableCellLabel(task, labelId) {
    const labels = task.labels || [];
    updateTask(task, { labels: labels.includes(labelId)
      ? labels.filter(existingLabel => existingLabel !== labelId)
      : [...labels, labelId] });
  }

  function toggleTableCellDependency(task, dependencyId) {
    const dependencies = task.dependencies || [];
    updateTask(task, { dependencies: dependencies.includes(dependencyId)
      ? dependencies.filter(existingDependency => existingDependency !== dependencyId)
      : [...dependencies, dependencyId] });
  }

  function shiftTableCalendarMonth(amount) {
    tableCalendarMonth = new Date(tableCalendarMonth.getFullYear(), tableCalendarMonth.getMonth() + amount, 1);
  }

  let tableCalendarDays = $derived.by(() => {
    const firstDay = new Date(tableCalendarMonth.getFullYear(), tableCalendarMonth.getMonth(), 1);
    const weekStart = Number(S.prefs.weekStart ?? 1);
    const offset = (firstDay.getDay() - weekStart + 7) % 7;
    return Array.from({ length: 42 }, (_, index) => addD(firstDay, index - offset));
  });

  let tableCalendarWeekdays = $derived.by(() => {
    const weekStart = Number(S.prefs.weekStart ?? 1);
    return Array.from({ length: 7 }, (_, index) => WD[(index + weekStart) % 7].slice(0, 2));
  });

  let tableDependencyCandidates = $derived.by(() => {
    if (!tableCellMenu || tableCellMenu.field !== 'deps') return [];
    const task = tasks.find(item => String(item.id) === String(tableCellMenu.taskId));
    const projectSourceKey = workspaceData.projects?.find(project => String(project.database_id) === String(task?.project_id))?.id;

    return (workspaceData.tasks || []).filter(sourceTask => sourceTask.project === projectSourceKey
      && sourceTask.id !== task?.source_key
      && !sourceTask.archived
      && `${sourceTask.key || ''} ${sourceTask.title}`.toLowerCase().includes(tableCellQuery.trim().toLowerCase()));
  });

  function toggleTableGroup(groupId) {
    const key = `tasks:${groupId}`;
    tableGroupCollapsed = { ...tableGroupCollapsed, [key]: !tableGroupCollapsed[key] };
  }

  function tableGroupIsCollapsed(groupId) {
    return Boolean(tableGroupCollapsed[`tasks:${groupId}`]);
  }

  function tableTaskDate(value) {
    if (!value) return '';
    return formatDate(String(value).slice(0, 10));
  }

  function updateFilter(index, changes) {
    filters = filters.map((filter, filterIndex) => filterIndex === index ? { ...filter, ...changes } : filter);
  }

  function toggleFilterValue(index, value) {
    const values = filters[index].values;
    updateFilter(index, { values: values.includes(String(value)) ? values.filter(item => item !== String(value)) : [...values, String(value)] });
  }

  function initials(name = '') {
    return name.split(' ').map(part => part[0]).join('').slice(0, 2);
  }

  function taskCommentsCount(task) {
    return (workspaceData.comments || []).filter(comment => comment.task === task.source_key).length;
  }

  function taskSubtaskProgress(task) {
    const subtasks = task.subtasks || [];
    const completed = subtasks.filter(subtask => subtask.done).length;
    return { completed, total: subtasks.length, percentage: subtasks.length ? Math.round(completed / subtasks.length * 100) : 0 };
  }

  function dueLabel(task) {
    if (!task.due_date) return '';
    const delta = dayDifference(task.due_date);
    if (delta === 0) return 'Today';
    if (delta === 1) return 'Tomorrow';
    if (delta === -1) return 'Yesterday';
    return new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function dueClass(task) {
    if (task.status === 'done' || !task.due_date) return '';
    const delta = dayDifference(task.due_date);
    return delta < 0 ? 'over' : delta <= 1 ? 'soon' : '';
  }

  function handleBoardCardKeydown(event, task) {
    if (event.target.closest('button, a')) return;
    if (event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    drawerTaskId = task.id;
  }

  function handleBoardDrop(event, status) {
    event.preventDefault();
    const task = tasks.find(item => String(item.id) === String(draggedTaskId));
    if (task && task.status !== status) updateTask(task, { status });
    draggedTaskId = null;
  }

  function openBoardCardMenu(event, task, field) {
    event.stopPropagation();
    const bounds = event.currentTarget.getBoundingClientRect();
    const width = field === 'due' ? 312 : 220;
    if (field === 'due') {
      const date = task.due_date ? new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`) : TODAY;
      tableCalendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    }
    boardCardMenuPosition = {
      left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
      top: Math.min(bounds.bottom + 4, window.innerHeight - (field === 'due' ? 400 : 300)),
    };
    boardCardMenu = { taskId: task.id, field };
  }

  function updateBoardCard(task, values) {
    boardCardMenu = null;
    updateTask(task, values);
  }

  function updateBoardCardDue(task, value) {
    updateBoardCard(task, { due_date: value });
  }

  async function startBoardComposer(status) {
    boardComposerStatus = status;
    boardComposerTitle = '';
    await tick();
    boardComposerInput?.focus();
  }

  async function commitBoardComposer(status) {
    const title = boardComposerTitle.trim();
    if (!title) return;
    const group = statuses.find(item => item.id === status);
    if (group) await createInlineTask(title, group);
    boardComposerStatus = '';
    boardComposerTitle = '';
  }

  function handleBoardComposerKeydown(event, status) {
    if (event.key === 'Enter') {
      event.preventDefault();
      commitBoardComposer(status);
    } else if (event.key === 'Escape') {
      boardComposerStatus = '';
      boardComposerTitle = '';
    }
  }

  function changeGroup(group) {
    groupBy = group;
  }

  function taskGroups() {
    if (groupBy === 'none') return [{ id: 'all', name: 'All tasks', tasks: visibleTasks }];
    if (groupBy === 'project') {
      const sourceOrder = new Map((workspaceData.projects || []).map((project, index) => [String(project.database_id || project.id), index]));
      return [...projects].sort((first, second) => (sourceOrder.get(String(first.id)) ?? 9999) - (sourceOrder.get(String(second.id)) ?? 9999))
        .map(project => ({ id: String(project.id), name: project.name, icon: 'folder', iconType: 'project', tasks: visibleTasks.filter(task => String(task.project_id) === String(project.id)) }))
        .filter(group => group.tasks.length);
    }
    if (groupBy === 'assignee') return [
      ...members.map(member => ({ id: String(member.id), name: member.name, member, iconType: 'member', tasks: visibleTasks.filter(task => String(task.assignee_id) === String(member.id)) })),
      { id: 'none', name: 'Unassigned', icon: 'user-round', iconType: 'icon', tasks: visibleTasks.filter(task => !task.assignee_id) },
    ].filter(group => group.tasks.length);
    if (groupBy === 'priority') return ['urgent', 'high', 'medium', 'low', 'none'].map(priority => ({ id: priority, name: priority === 'none' ? 'No priority' : priority[0].toUpperCase() + priority.slice(1), iconType: 'priority', tasks: visibleTasks.filter(task => task.priority === priority) }));
    if (groupBy === 'due') {
      const groups = [
        { id: 'overdue', name: 'Overdue', icon: 'calendar' },
        { id: 'today', name: 'Today', icon: 'calendar' },
        { id: 'week', name: 'Next 7 days', icon: 'calendar' },
        { id: 'later', name: 'Later', icon: 'calendar' },
        { id: 'nodate', name: 'No due date', icon: 'calendar' },
      ];
      return groups.map(group => ({ ...group, iconType: 'icon', tasks: visibleTasks.filter(task => matchesFilter(task, 'due', group.id)) })).filter(group => group.tasks.length);
    }
    return statuses.map(item => ({ ...item, iconType: 'status', name: item.name, tasks: visibleTasks.filter(task => task.status === item.id) }));
  }

  function isOverdue(task) {
    return task.status !== 'done' && task.due_date && new Date(`${task.due_date.slice(0, 10)}T00:00:00`) < new Date(new Date().toDateString());
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function openCreateTask(initialAssigneeId = '', initialStatus = 'todo') {
    createAssigneeId = initialAssigneeId || null;
    createStatus = initialStatus;
    createOpen = true;
  }

  function taskCreated(task) {
    tasks = [task, ...tasks];
  }

  async function updateTask(task, values) {
    errorMessage = '';
    try {
      const updated = await httpRequest(taskUpdate(task.id).url, 'PATCH', values);
      tasks = tasks.map(item => item.id === updated.id ? { ...item, ...updated } : item);
    } catch (error) {
      errorMessage = error.message;
    }
  }

  async function createInlineTask(title, group) {
    const isStatusGroup = statuses.some(status => status.id === group.id);
    const firstSourceProject = workspaceData.projects?.[0];
    const projectId = isStatusGroup
      ? firstSourceProject?.database_id || projects.find(project => !['complete', 'completed', 'archived'].includes(project.status))?.id
      : Number(group.id);
    if (!projectId) return;

    try {
      const task = await httpRequest(storeTaskRoute({ project: projectId }).url, 'POST', {
        title,
        status: isStatusGroup ? group.id : 'todo',
        priority: 'none',
        assignee_id: null,
        due_date: null,
        labels: [],
        subtasks: [],
        attachments: [],
        recurrence: null,
      });
      tasks = [task, ...tasks];
    } catch (error) {
      errorMessage = error.errors?.title?.[0] || error.message;
    }
  }

  function taskUpdated(updatedTask) {
    tasks = tasks.map(task => task.id === updatedTask.id ? { ...task, ...updatedTask } : task);
  }
</script>

<svelte:window onpointerdown={handleWindowPointerdown} onkeydown={handleWindowKeydown} onpointermove={resizeTableColumn} onpointerup={finishTableColumnResize} />

<svelte:head><title>Tasks · Gr8r Studio</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="ph"><div><h1>Tasks</h1><p>Every task across {projects.length} projects.</p></div><div class="acts"><button class="btn btn-primary" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</button></div></div>
    {#if errorMessage}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errorMessage}</span></div>{/if}
    <div class="toolbar" role="toolbar">
      <div class="inwrap">{@html ic('search', 13)}<Input class="search-sm" bind:value={query} placeholder="Search tasks" aria-label="Search tasks" /></div>
      <button class="btn btn-ghost" data-task-trigger type="button" aria-label="Filter tasks" aria-expanded={toolbarMenu === 'filter'} onclick={event => toggleToolbarMenu('filter', event)}>{@html ic('list-filter', 13)}Filter{#if filterCount}<span class="badge">{filterCount}</span>{/if}</button>
      <button class="btn btn-ghost" data-task-trigger type="button" aria-label={`Sort tasks: ${sortNames[sortBy]}`} aria-expanded={toolbarMenu === 'sort'} onclick={event => toggleToolbarMenu('sort', event)}>{@html ic('arrow-up-down', 13)}Sort: {sortNames[sortBy]}</button>
      {#if view !== 'board'}<button class="btn btn-ghost" data-task-trigger aria-expanded={toolbarMenu === 'group'} onclick={event => toggleToolbarMenu('group', event)}>{@html ic('list-filter', 13)}Group: {groupBy === 'none' ? 'None' : groupBy[0].toUpperCase() + groupBy.slice(1)}</button>{/if}
      {#if view === 'table'}<button class="btn btn-ghost" data-task-trigger aria-label="Columns" aria-expanded={toolbarMenu === 'columns'} onclick={event => toggleToolbarMenu('columns', event)}>{@html ic('columns-3', 13)}Columns</button>{/if}
      <span class="sp"></span>
      <div class="seg" role="tablist" aria-label="Task view"><button class:on={view === 'list'} aria-pressed={view === 'list'} onclick={() => view = 'list'}>{@html ic('list', 13)}<span class="hide-m">List</span></button><button class:on={view === 'board'} aria-pressed={view === 'board'} onclick={() => view = 'board'}>{@html ic('square-kanban', 13)}<span class="hide-m">Board</span></button><button class:on={view === 'table'} aria-pressed={view === 'table'} onclick={() => view = 'table'}>{@html ic('table-2', 13)}<span class="hide-m">Table</span></button></div>
    </div>
    {#if filters.length}
      <div class="chipsbar">{@html ic('list-filter', 13)}
        {#each filters as filter, index (index)}
          {@const field = filterFields.find(item => item.id === filter.field)}
          {@const names = filter.values.map(value => filterOptions(filter.field).find(option => String(option.id) === String(value))?.name || value)}
          <span class="chip"><button style="display:inline-flex;gap:5px;align-items:center" onclick={event => openFilterValues(index, event)}>{@html ic(field?.icon || 'list-filter', 12)}<b>{field?.name || filter.field}</b> {filter.operator === 'not' ? 'is not' : 'is'} <span>{names.length ? names.length > 2 ? `${names.length} selected` : names.join(', ') : 'any'}</span></button><button class="ibtn" aria-label="Remove filter" onclick={() => removeFilter(index)}>{@html ic('x', 12)}</button></span>
          {#if index < filters.length - 1}<span class="faint">and</span>{/if}
        {/each}
        <button class="btn btn-sm btn-ghost" data-task-trigger onclick={event => toggleToolbarMenu('filter', event)}>{@html ic('plus', 12)}Add</button><span class="sp"></span><button class="btn btn-sm btn-ghost" onclick={clearFilters}>Clear all</button>
      </div>
    {/if}
    {#if toolbarMenu === 'filter'}
      <div class="pop floating fbuild enter" data-task-menu role="dialog" aria-label="Filter by" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;max-width:560px`}>
        <div class="mh fb-h">Filter by{#if filters.length}<button class="btn btn-sm btn-ghost" onclick={clearFilters}>Clear all</button>{/if}</div>
        {#if filters.length}
          <div class="fb-rows">
            {#each filters as filter, index (index)}
              {@const fieldName = filterFields.find(field => field.id === filter.field)?.name || filter.field}
              {@const selectedNames = filter.values.map(value => filterOptions(filter.field).find(option => String(option.id) === String(value))?.name || value)}
              <div class="frow">
                <span class="conj">{index ? 'and' : 'Where'}</span>
                <select class="select" aria-label="Field" value={filter.field} onchange={event => { updateFilter(index, { field: event.currentTarget.value, values: [] }); valuesOpen = index; }}>
                  {#each filterFields as field (field.id)}<option value={field.id}>{field.name}</option>{/each}
                </select>
                <select class="select" aria-label="Operator" value={filter.operator || 'is'} onchange={event => updateFilter(index, { operator: event.currentTarget.value })}><option value="is">is</option><option value="not">is not</option></select>
                <button class="valbtn" aria-expanded={valuesOpen === index} onclick={() => { valuesOpen = valuesOpen === index ? -1 : index; }}><span class="trunc grow">{#if selectedNames.length}{selectedNames.join(', ')}{:else}<span class="faint">Select…</span>{/if}</span>{@html ic('chevron-down', 12)}</button>
                <button class="ibtn ibtn-xs" aria-label="Remove filter" onclick={() => removeFilter(index)}>{@html ic('trash-2', 13)}</button>
              </div>
              {#if valuesOpen === index}
                <div style="margin-left:50px;border:1px solid var(--border);border-radius:var(--r);padding:4px;max-height:200px;overflow:auto">
                  {#each filterOptions(filter.field) as option (option.id)}
                    <label class="mi" style="cursor:pointer;min-height:28px"><input type="checkbox" class="check" checked={filter.values.includes(String(option.id))} onchange={() => toggleFilterValue(index, option.id)} />{#if option.iconType === 'status'}{@html stIcon(option.id, 13)}{:else if option.iconType === 'priority'}{@html prIcon(option.id, 13)}{:else if option.member}<span class="av sm" style={`--c:${option.member.color || 'var(--gray)'}`}>{initials(option.member.name)}</span>{:else if option.project}<span class="pdot" style={`--c:var(--${option.project.color || 'indigo'})`}></span>{:else if option.color}<span class="pdot" style={`--c:${option.color}`}></span>{/if}{option.name}</label>
                  {/each}
                </div>
              {/if}
            {/each}
          </div>
        {:else}
          <p class="fb-empty">No filters applied. Combine filters to narrow the task list, e.g. Status is In Progress and Assignee is Sarah.</p>
        {/if}
        <div class="msep"></div><div class="fb-add"><span class="faint">Add filter:</span>{#each filterFields as field (field.id)}<button class="badge" onclick={() => addFilter(field.id)}>{@html ic(field.icon, 11)}{field.name}</button>{/each}</div>
      </div>
    {:else if toolbarMenu === 'sort'}
      <div class="pop floating enter" data-task-menu role="menu" aria-label="Sort tasks" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:240px`}>
        <div class="mh">Sort by</div>
        {#each Object.entries(sortNames) as [field, name] (field)}<button class="mi" role="menuitem" onclick={() => { sortBy = field; toolbarMenu = ''; }}>{name}{#if sortBy === field}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
        <div class="msep"></div><div style="padding:4px 6px"><div class="seg" style="width:100%"><button class:on={sortDirection > 0} style="flex:1;justify-content:center" onclick={() => sortDirection = 1}>{@html ic('arrow-up', 12)}Ascending</button><button class:on={sortDirection < 0} style="flex:1;justify-content:center" onclick={() => sortDirection = -1}>{@html ic('arrow-down', 12)}Descending</button></div></div>
      </div>
    {:else if toolbarMenu === 'group'}
      <div class="pop floating enter" data-task-menu role="menu" aria-label="Group tasks" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`}>
        <div class="mh">Group by</div>
        {#each groupOptions as option (option.id)}<button class="mi" role="menuitem" onclick={() => { groupBy = option.id; toolbarMenu = ''; }}>{@html ic(option.icon, 15)}{option.name}{#if groupBy === option.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      </div>
    {:else if toolbarMenu === 'columns'}
      <div class="pop floating enter" data-task-menu role="menu" aria-label="Visible columns" style={`left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`}>
        <div class="mh">Visible columns</div>
        {#each tableColumns.filter(column => column.id !== 'title') as column (column.id)}
          <button class="mi" role="menuitemcheckbox" aria-checked={!tableColumnHidden.includes(column.id)} onclick={() => toggleTableColumn(column.id)}><input class="check" type="checkbox" checked={!tableColumnHidden.includes(column.id)} tabindex="-1" />{column.name}</button>
        {/each}
        <div class="msep"></div><button class="mi" onclick={() => { tableColumnWidths = {}; savedTaskView.colW = {}; save(); }}>Reset column widths</button>
      </div>
    {/if}
    {#if boardCardMenu}{@const task = tasks.find(item => item.id === boardCardMenu.taskId)}{#if task}<div class="pop floating enter" data-board-card-menu role="menu" aria-label={boardCardMenu.field === 'priority' ? 'Priority' : boardCardMenu.field === 'due' ? 'Due date' : 'Assignee'} style={`left:${boardCardMenuPosition.left}px;top:${boardCardMenuPosition.top}px;${boardCardMenu.field === 'due' ? 'min-width:0' : 'width:220px'}`}>
      {#if boardCardMenu.field === 'priority'}<div class="mh">Priority</div>{#each priorities as priority (priority)}<button type="button" class="mi" role="menuitemradio" aria-checked={task.priority === priority} onclick={() => updateBoardCard(task, { priority })}>{@html prIcon(priority, 14)}<span>{priority === 'none' ? 'No priority' : priority[0].toUpperCase() + priority.slice(1)}</span>{#if task.priority === priority}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
      {:else if boardCardMenu.field === 'due'}
        {@const currentDate = String(task.due_date || '').slice(0, 10)}
        <div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">{#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7 - ((TODAY.getDay() + 6) % 7)], ['In 2 weeks', 14]] as [shortcut, offset] (shortcut)}<button type="button" class="badge" style="cursor:pointer" onclick={() => updateBoardCardDue(task, iso(addD(TODAY, offset)))}>{shortcut}</button>{/each}{#if currentDate}<button type="button" class="badge" style="cursor:pointer;color:var(--red)" onclick={() => updateBoardCardDue(task, null)}>Clear</button>{/if}</div>
        <div class="msep"></div>
        <div style="padding:6px 6px 2px;width:252px"><div class="row" style="margin-bottom:6px"><b style="font-weight:600;font-size:13px;padding-left:4px">{MONL[tableCalendarMonth.getMonth()]} {tableCalendarMonth.getFullYear()}</b><span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Previous month" onclick={() => shiftTableCalendarMonth(-1)}>{@html ic('chevron-left', 14)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Next month" onclick={() => shiftTableCalendarMonth(1)}>{@html ic('chevron-right', 14)}</button></div>
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:11px">{#each tableCalendarWeekdays as weekday}<span class="faint" style="padding:3px 0">{weekday}</span>{/each}{#each tableCalendarDays as day (iso(day))}{@const dateValue = iso(day)}{@const isSelected = dateValue === currentDate}{@const isToday = diffD(day, TODAY) === 0}<button type="button" class="num" aria-label={fmtDate(dateValue, true)} style={`height:30px;border-radius:6px;font-size:12px;${isSelected ? 'background:var(--accent);color:var(--on-accent);font-weight:600' : isToday ? 'color:var(--accent);font-weight:600;box-shadow:inset 0 0 0 1px var(--accent-line)' : day.getMonth() !== tableCalendarMonth.getMonth() ? 'color:var(--text-3)' : ''}`} onclick={() => updateBoardCardDue(task, dateValue)}>{day.getDate()}</button>{/each}</div>
        </div>
      {:else}<div class="mh">Assign to</div><button type="button" class="mi" role="menuitemradio" aria-checked={!task.assignee_id} onclick={() => updateBoardCard(task, { assignee_id: null })}>{@html ic('user-round', 14)}Unassigned{#if !task.assignee_id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{#each members as member (member.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(task.assignee_id) === String(member.id)} onclick={() => updateBoardCard(task, { assignee_id: member.id })}><span class="av sm" style={`--c:${member.color || 'var(--gray)'}`}>{initials(member.name)}</span><span class="trunc">{member.name}</span>{#if String(task.assignee_id) === String(member.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}{/if}
    </div>{/if}{/if}
    {#if view === 'board'}
      <div class="board">{#each statuses as item (item.id)}{@const columnTasks = visibleTasks.filter(task => task.status === item.id)}<section class="bcol" aria-label={item.name} ondragover={event => event.preventDefault()} ondrop={event => handleBoardDrop(event, item.id)}><div class="bcol-h">{@html stIcon(item.id, 14)}<span>{item.name}</span><span class="cnt">{columnTasks.length}</span><span class="sp"></span><button class="ibtn ibtn-xs" aria-label={`Add task to ${item.name}`} onclick={() => startBoardComposer(item.id)}>{@html ic('plus', 13)}</button></div><div class="bcol-b">{#each columnTasks as task (task.id)}{@const progress = taskSubtaskProgress(task)}{@const assignee = task.assignee || members.find(member => String(member.id) === String(task.assignee_id))}{@const sourceMember = (workspaceData.members || []).find(member => String(member.database_id) === String(task.assignee_id))}
        <!-- svelte-ignore a11y_no_noninteractive_element_to_interactive_role -->
        <article class="kcard" class:done={task.status === 'done'} draggable="true" role="button" tabindex="0" aria-label={`Open task ${task.title}`} onclick={event => { if (!event.target.closest('button, a')) drawerTaskId = task.id; }} onkeydown={event => handleBoardCardKeydown(event, task)} ondragstart={event => { draggedTaskId = task.id; event.dataTransfer?.setData('text/plain', String(task.id)); if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'; }} ondragend={() => draggedTaskId = null}>
          {#if task.labels?.length}<div class="labels">{#each task.labels as labelId (labelId)}{@const label = LABELS.find(option => option.id === labelId)}{#if label}<span class="lbl" style={`--c:${label.c}`}><i></i>{label.name}</span>{/if}{/each}</div>{/if}
          <div class="top">{#if task.status === 'done'}<span class="done-ic" style="margin:2px 7px 0 0;display:inline-flex" aria-label="Done">{@html stIcon('done', 14)}</span>{/if}<span class="title">{task.title}</span></div>
          {#if progress.total}<div class="subp">{@html ic('list-checks', 12)}<span class="num">{progress.completed}/{progress.total}</span><span class={`prog ${progress.completed === progress.total ? 'green' : ''}`} role="progressbar" aria-valuenow={progress.percentage} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${progress.percentage / 100}`}></i></span></div>{/if}
          <div class="meta"><Link class="m" href={projectShow(task.project_id).url} aria-label={`Project ${task.project?.name}`}><span class="pdot" style={`--c:var(--${task.project?.color || 'indigo'})`}></span></Link><span class="key">{task.key}</span><button type="button" class="m" data-board-card-trigger aria-label={`Priority: ${task.priority}`} title={`Priority: ${task.priority}`} onclick={event => openBoardCardMenu(event, task, 'priority')}>{@html prIcon(task.priority, 13)}</button>{#if task.due_date}<button type="button" class={`m due ${dueClass(task)}`} data-board-card-trigger aria-label={`Due date: ${dueLabel(task)}`} title="Due date" onclick={event => openBoardCardMenu(event, task, 'due')}>{@html ic('calendar', 12)}{dueLabel(task)}</button>{/if}{#if task.recurrence}<span class="m" title={`Repeats ${task.recurrence.toLowerCase()}`} aria-label={`Repeats ${task.recurrence.toLowerCase()}`}>{@html ic('repeat', 12)}</span>{/if}{#if taskCommentsCount(task)}<span class="m" aria-label={`${taskCommentsCount(task)} comments`}>{@html ic('message-square', 12)}{taskCommentsCount(task)}</span>{/if}{#if task.attachments?.length}<span class="m" aria-label={`${task.attachments.length} attachments`}>{@html ic('paperclip', 12)}{task.attachments.length}</span>{/if}<button type="button" class="m" style="margin-left:auto" data-board-card-trigger aria-label="Assignee" title={assignee?.name || 'Unassigned'} onclick={event => openBoardCardMenu(event, task, 'assignee')}>{#if assignee}<span class="av sm" style={`--c:${assignee.color || sourceMember?.c || 'var(--gray)'}`}>{initials(assignee.name)}</span>{:else}<span class="av empty sm">{@html ic('user', 11)}</span>{/if}</button></div>
          <div class="hacts"><button type="button" class="ibtn ibtn-xs" aria-label={task.status === 'done' ? 'Reopen' : 'Mark complete'} title={task.status === 'done' ? 'Reopen' : 'Mark complete'} onclick={event => { event.stopPropagation(); updateTask(task, { status: task.status === 'done' ? 'todo' : 'done' }); }}>{@html ic(task.status === 'done' ? 'rotate-ccw' : 'check', 13)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Quick edit" title="Quick edit" onclick={event => { event.stopPropagation(); drawerTaskId = task.id; }}>{@html ic('pencil', 12)}</button><button type="button" class="ibtn ibtn-xs" aria-label="More" title="More" onclick={event => { event.stopPropagation(); drawerTaskId = task.id; }}>{@html ic('ellipsis', 13)}</button></div>
        </article>{/each}{#if boardComposerStatus === item.id}<div class="composer"><textarea bind:this={boardComposerInput} bind:value={boardComposerTitle} placeholder="Task name" rows="2" aria-label={`New task in ${item.name}`} onkeydown={event => handleBoardComposerKeydown(event, item.id)}></textarea><div class="row"><span class="faint" style="font-size:11px">Enter to add · Esc to cancel</span><span class="sp"></span><button type="button" class="btn btn-sm btn-ghost" onclick={() => { boardComposerStatus = ''; boardComposerTitle = ''; }}>Cancel</button><button type="button" class="btn btn-sm btn-primary" onclick={() => commitBoardComposer(item.id)}>Add</button></div></div>{:else}<button class="addcard" onclick={() => startBoardComposer(item.id)}>{@html ic('plus', 14)}Add task</button>{/if}</div></section>{/each}</div>
    {:else if view === 'table'}
      {#if !visibleTasks.length}
        <div class="empty"><span class="empty-ic">{@html ic('search-x', 20)}</span><h2>No results found</h2><p>No tasks match these filters.</p><button class="btn btn-secondary btn-sm" onclick={clearFilters}>Clear filters</button></div>
      {:else}
        <div class="tbl-wrap" data-keep="tbl:tasks">
          <table class="tbl" style={`width:${visibleTableColumns.reduce((total, column) => total + tableColumnWidth(column), 0)}px`} aria-label="Tasks table">
            <colgroup>{#each visibleTableColumns as column (column.id)}<col data-col={column.id} style={`width:${tableColumnWidth(column)}px`} />{/each}</colgroup>
            <thead><tr>
              {#each visibleTableColumns as column (column.id)}
                <th class:sticky={column.id === 'title'} scope="col"><div class="thi" role="button" tabindex="0" onclick={() => tableSortField(column.id)} onkeydown={event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); tableSortField(column.id); } }}>{column.name}{#if sortBy === column.id}{@html ic(sortDirection > 0 ? 'arrow-up' : 'arrow-down', 11)}{/if}</div><span class="rsz" aria-hidden="true" onpointerdown={event => startTableColumnResize(event, column)}></span></th>
              {/each}
            </tr></thead>
            <tbody>
              {#each taskGroups() as group (group.id)}
                {#if groupBy !== 'none'}
                  <tr class="grow-row"><td class="sticky" colspan={visibleTableColumns.length}><span class="row" style="gap:6px"><button type="button" class="ibtn ibtn-xs" aria-expanded={!tableGroupIsCollapsed(group.id)} aria-label="Toggle group" onclick={() => toggleTableGroup(group.id)}>{@html ic(tableGroupIsCollapsed(group.id) ? 'chevron-right' : 'chevron-down', 13)}</button>{#if group.iconType === 'status'}{@html stIcon(group.id, 14)}{:else if group.iconType === 'priority'}{@html prIcon(group.id, 14)}{:else if group.iconType === 'member'}<span class="av sm" style={`--c:${group.member.color || 'var(--gray)'}`}>{initials(group.name)}</span>{:else if group.iconType === 'project'}<span class="pdot" style={`--c:var(--${projects.find(project => String(project.id) === String(group.id))?.color || 'indigo'})`}></span>{:else if group.icon}{@html ic(group.icon, 14)}{/if}{group.name}<span class="faint" style="font-weight:500">{group.tasks.length}</span></span></td></tr>
                {/if}
                {#if !tableGroupIsCollapsed(group.id)}
                  {#each group.tasks as task (task.id)}
                    <tr>
                      {#each visibleTableColumns as column (column.id)}
                        {@const project = task.project || projects.find(item => String(item.id) === String(task.project_id))}
                        {@const assignee = task.assignee || members.find(item => String(item.id) === String(task.assignee_id))}
                        {@const isEditing = editingTableCell?.taskId === task.id && editingTableCell?.field === column.id}
                        <td class:sticky={column.id === 'title'} class:cellbtn={['status', 'priority', 'assignee', 'due', 'start', 'labels', 'deps', 'project'].includes(column.id)} class:editing={isEditing}>
                          {#if isEditing}
                            <input bind:this={editingTableInput} bind:value={editingTableValue} aria-label={column.id === 'title' ? 'Title' : 'Estimate'} onkeydown={event => { if (event.key === 'Enter') { event.preventDefault(); commitTableCell(task); } if (event.key === 'Escape') editingTableCell = null; }} onblur={() => commitTableCell(task)} />
                          {:else if column.id === 'title'}
                            <button type="button" class="table-title" title="Double-click to rename" aria-label={`Open task ${task.title}`} onclick={() => drawerTaskId = task.id} ondblclick={event => { event.preventDefault(); startTableCellEdit(task, 'title'); }}><span class="row" style="gap:8px">{@html stIcon(task.status, 13)}<span class="trunc" style="font-weight:450">{task.title}</span><span class="mono faint" style="font-size:11px;margin-left:auto">{task.key}</span></span></button>
                          {:else if column.id === 'status'}
                            <button type="button" class="table-cell-button" data-table-cell-trigger aria-label={`Status: ${statuses.find(item => item.id === task.status)?.name || task.status}`} onclick={event => openTableCellMenu(task, 'status', event)}>{@html stPill(task.status)}</button>
                          {:else if column.id === 'priority'}
                            <button type="button" class="table-cell-button" data-table-cell-trigger aria-label={`Priority: ${task.priority === 'none' ? 'No priority' : task.priority}`} onclick={event => openTableCellMenu(task, 'priority', event)}>{@html prPill(task.priority)}</button>
                          {:else if column.id === 'assignee'}
                            <button type="button" class="table-cell-button" data-table-cell-trigger aria-label={`Assignee: ${assignee?.name || 'Unassigned'}`} onclick={event => openTableCellMenu(task, 'assignee', event)}><span class="row" style="gap:6px">{#if assignee}<span class="av sm" style={`--c:${assignee.color || 'var(--gray)'}`}>{initials(assignee.name)}</span>{:else}<span class="av empty sm">{@html ic('user', 11)}</span>{/if}<span class="trunc">{assignee?.name || 'Unassigned'}</span></span></button>
                          {:else if column.id === 'due' || column.id === 'start'}
                            <button type="button" class="table-cell-button num" data-table-cell-trigger aria-label={`${column.name}: ${tableTaskDate(column.id === 'due' ? task.due_date : task.start_date) || 'Set date'}`} onclick={event => openTableCellMenu(task, column.id, event)}>{#if tableTaskDate(column.id === 'due' ? task.due_date : task.start_date)}{tableTaskDate(column.id === 'due' ? task.due_date : task.start_date)}{:else}<span class="faint">—</span>{/if}</button>
                          {:else if column.id === 'labels'}
                            <button type="button" class="table-cell-button" data-table-cell-trigger aria-label="Labels" onclick={event => openTableCellMenu(task, 'labels', event)}><span class="row" style="gap:4px">{@html (task.labels || []).map(lbl).join('') || '<span class="faint">—</span>'}</span></button>
                          {:else if column.id === 'deps'}
                            {@const dependencies = task.dependencies || task.deps || []}
                            <button type="button" class="table-cell-button" data-table-cell-trigger aria-label="Dependencies" onclick={event => openTableCellMenu(task, 'deps', event)}>{#if dependencies.length}{#each dependencies as dependency (dependency.id || dependency)}{@const dependencyKey = dependency.id || dependency}{@const dependencyTask = (workspaceData.tasks || []).find(sourceTask => sourceTask.id === dependencyKey)}{#if dependencyTask}<span class="depchip" title={dependencyTask.title}>{dependencyTask.key}</span>{/if}{/each}{:else}<span class="faint">—</span>{/if}</button>
                          {:else if column.id === 'estimate'}
                            <button type="button" class="table-cell-button num" title="Click to edit" aria-label={`Estimate: ${task.estimate || 'none'}`} data-table-cell-trigger onclick={event => openTableCellMenu(task, 'estimate', event)}>{#if task.estimate}{task.estimate}{:else}<span class="faint">—</span>{/if}</button>
                          {:else if column.id === 'created'}
                            <span class="num muted">{tableTaskDate(task.created_at)}</span>
                          {:else if column.id === 'project'}
                            <button type="button" class="table-cell-button" data-table-cell-trigger aria-label={`Project: ${project?.name || 'None'}`} onclick={event => openTableCellMenu(task, 'project', event)}><span class="row" style="gap:6px"><span class="pdot" style={`--c:var(--${project?.color || 'indigo'})`}></span><span class="trunc">{project?.name || '—'}</span></span></button>
                          {/if}
                        </td>
                      {/each}
                    </tr>
                  {/each}
                {/if}
              {/each}
              <tr><td class="sticky cellbtn" colspan={visibleTableColumns.length} style="color:var(--text-3)"><button type="button" class="table-cell-button" onclick={() => openCreateTask()}><span class="row" style="gap:6px">{@html ic('plus', 14)}New task</span></button></td></tr>
            </tbody>
          </table>
        </div>
        {#if tableCellMenu}{@const menuTask = tasks.find(item => String(item.id) === String(tableCellMenu.taskId))}{#if menuTask}
          <div class="pop floating enter" data-table-cell-menu role="menu" style={`left:${tableCellMenu.left}px;top:${tableCellMenu.top}px;width:${['labels', 'deps', 'project'].includes(tableCellMenu.field) ? 240 : 200}px`}>
            {#if tableCellMenu.field === 'status'}<div class="mh">Status</div>{#each statuses as option (option.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={menuTask.status === option.id} onclick={() => updateTableCell(menuTask, 'status', option.id)}>{@html stIcon(option.id, 14)}<span>{option.name}</span>{#if menuTask.status === option.id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {:else if tableCellMenu.field === 'priority'}<div class="mh">Priority</div>{#each priorities as option (option)}<button type="button" class="mi" role="menuitemradio" aria-checked={menuTask.priority === option} onclick={() => updateTableCell(menuTask, 'priority', option)}>{@html prIcon(option, 14)}<span>{option === 'none' ? 'No priority' : option[0].toUpperCase() + option.slice(1)}</span>{#if menuTask.priority === option}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {:else if tableCellMenu.field === 'assignee'}<div class="mh">Assignee</div><div class="pin"><input bind:value={tableCellQuery} placeholder="Assign to…" aria-label="Assign to…" /></div><button type="button" class="mi" role="menuitemradio" aria-checked={!menuTask.assignee_id} onclick={() => updateTableCell(menuTask, 'assignee_id', null)}>{@html ic('user-round', 14)}<span>Unassigned</span>{#if !menuTask.assignee_id}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{#each members.filter(member => member.name.toLowerCase().includes(tableCellQuery.trim().toLowerCase())) as member (member.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(menuTask.assignee_id) === String(member.id)} onclick={() => updateTableCell(menuTask, 'assignee_id', member.id)}><span class="av sm" style={`--c:${member.color || 'var(--gray)'}`}>{initials(member.name)}</span><span class="trunc">{member.name}{String(member.id) === String(page.props.auth?.user?.id) ? ' (you)' : ''}</span>{#if String(menuTask.assignee_id) === String(member.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {:else if tableCellMenu.field === 'labels'}<div class="mh">Labels</div>{#each LABELS as labelOption (labelOption.id)}<button type="button" class="mi" role="menuitemcheckbox" aria-checked={(menuTask.labels || []).includes(labelOption.id)} onclick={() => toggleTableCellLabel(menuTask, labelOption.id)}><span class="pdot" style={`--c:${labelOption.c}`}></span><span>{labelOption.name}</span>{#if (menuTask.labels || []).includes(labelOption.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {:else if tableCellMenu.field === 'due' || tableCellMenu.field === 'start'}
              {@const currentDate = String(tableCellMenu.field === 'due' ? menuTask.due_date || '' : menuTask.start_date || '').slice(0, 10)}
              <div class="row" style="gap:4px;padding:4px;flex-wrap:wrap">{#each [['Today', 0], ['Tomorrow', 1], ['Next week', 7 - ((TODAY.getDay() + 6) % 7)], ['In 2 weeks', 14]] as [shortcut, offset] (shortcut)}<button type="button" class="badge" style="cursor:pointer" onclick={() => updateTableCell(menuTask, tableCellMenu.field, iso(addD(TODAY, offset)))}>{shortcut}</button>{/each}{#if currentDate}<button type="button" class="badge" style="cursor:pointer;color:var(--red)" onclick={() => updateTableCell(menuTask, tableCellMenu.field, null)}>Clear</button>{/if}</div>
              <div class="msep"></div>
              <div style="padding:6px 6px 2px;width:252px"><div class="row" style="margin-bottom:6px"><b style="font-weight:600;font-size:13px;padding-left:4px">{MONL[tableCalendarMonth.getMonth()]} {tableCalendarMonth.getFullYear()}</b><span class="sp"></span><button type="button" class="ibtn ibtn-xs" aria-label="Previous month" onclick={() => shiftTableCalendarMonth(-1)}>{@html ic('chevron-left', 14)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Next month" onclick={() => shiftTableCalendarMonth(1)}>{@html ic('chevron-right', 14)}</button></div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:11px">{#each tableCalendarWeekdays as weekday}<span class="faint" style="padding:3px 0">{weekday}</span>{/each}{#each tableCalendarDays as day (iso(day))}{@const dateValue = iso(day)}{@const isSelected = dateValue === currentDate}{@const isToday = diffD(day, TODAY) === 0}<button type="button" class="num" aria-label={fmtDate(dateValue, true)} style={`height:30px;border-radius:6px;font-size:12px;${isSelected ? 'background:var(--accent);color:var(--on-accent);font-weight:600' : isToday ? 'color:var(--accent);font-weight:600;box-shadow:inset 0 0 0 1px var(--accent-line)' : day.getMonth() !== tableCalendarMonth.getMonth() ? 'color:var(--text-3)' : ''}`} onclick={() => updateTableCell(menuTask, tableCellMenu.field, dateValue)}>{day.getDate()}</button>{/each}</div>
              </div>
            {:else if tableCellMenu.field === 'deps'}<div class="mh">Dependencies</div><div class="pin"><input bind:value={tableCellQuery} placeholder="Blocked by…" aria-label="Blocked by…" /></div>{#each tableDependencyCandidates as blockedTask (blockedTask.id)}<button type="button" class="mi" role="menuitemcheckbox" aria-checked={(menuTask.dependencies || []).includes(blockedTask.id)} onclick={() => toggleTableCellDependency(menuTask, blockedTask.id)}>{@html stIcon(blockedTask.status, 14)}<span class="trunc">{blockedTask.key} {blockedTask.title}</span>{#if (menuTask.dependencies || []).includes(blockedTask.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}{#if !tableDependencyCandidates.length}<div class="mi faint" style="cursor:default">No matches</div>{/if}
            {:else if tableCellMenu.field === 'estimate'}<div class="mh">Estimate</div>{#each ['30m', '1h', '2h', '4h', '1d', '2d', '3d', '5d', '8d'] as estimate (estimate)}<button type="button" class="mi" role="menuitemradio" aria-checked={menuTask.estimate === estimate} onclick={() => updateTableCell(menuTask, 'estimate', estimate)}>{@html ic('timer', 14)}<span>{estimate}</span>{#if menuTask.estimate === estimate}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}{#if menuTask.estimate}<div class="msep"></div><button type="button" class="mi" onclick={() => updateTableCell(menuTask, 'estimate', null)}>{@html ic('x', 14)}<span>Clear estimate</span></button>{/if}
            {:else if tableCellMenu.field === 'project'}<div class="mh">Project</div>{#each projects.filter(project => !project.is_private && !['complete', 'completed', 'archived'].includes(project.status)) as project (project.id)}<button type="button" class="mi" role="menuitemradio" aria-checked={String(menuTask.project_id) === String(project.id)} onclick={() => updateTableCell(menuTask, 'project', project.id)}><span class="pdot" style={`--c:var(--${project.color || 'indigo'})`}></span><span class="trunc">{project.name}</span>{#if String(menuTask.project_id) === String(project.id)}<span class="ck">{@html ic('check', 14)}</span>{/if}</button>{/each}
            {/if}
          </div>
        {/if}{/if}
      {/if}
    {:else if !visibleTasks.length}
      <div class="list-wrap"><div class="empty"><span class="empty-ic">{@html ic('list-checks', 20)}</span><h2>No tasks here</h2><p>Add a task to get things moving.</p><button class="btn btn-primary btn-sm" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</button></div></div>
    {:else}
      <WorkspaceTaskList groups={taskGroups()} {projects} {members} {workspaceData} selectedSort={sortBy} selectedSortDirection={sortDirection} onOpen={task => drawerTaskId = task.id} onUpdate={updateTask} onSort={sortByField} onCreate={createInlineTask} />
    {/if}
  </div>

  <WorkspaceTaskForm open={createOpen} {projects} {members} projectOrder={workspaceData.projects?.map(project => project.database_id).filter(Boolean) || []} initialAssignee={createAssigneeId} initialStatus={createStatus} onClose={() => createOpen = false} onCreated={taskCreated} />
  {#if selectedTask}<TaskDrawer task={selectedTask} {workspaceData} onClose={() => drawerTaskId = null} onUpdated={taskUpdated} />{/if}
</AppLayout>

<style>
  .table-title,
  .table-cell-button {
    display: block;
    width: 100%;
    height: 35px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: left;
  }

  .table-title {
    cursor: pointer;
  }

  .table-title:hover,
  .table-cell-button:hover {
    color: var(--text);
  }

  .table-title .row {
    min-width: 0;
    width: 100%;
  }

  .table-cell-button .row {
    min-width: 0;
  }
</style>
