<script>
import Select from "../../Components/Select.svelte";
import Dropdown from "../../Components/Dropdown.svelte";
import Button from "../../Components/Button.svelte";
import Input from "../../Components/Input.svelte";
import { onMount } from "svelte";
import { tick } from "svelte";
import { Link, usePage } from "@inertiajs/svelte";
import AppLayout from "../../Layouts/AppLayout.svelte";
import WorkspaceTaskForm from "../../Components/WorkspaceTaskForm.svelte";
import WorkspaceTaskList from "../../Components/WorkspaceTaskList.svelte";
import TaskDrawer from "../../Components/TaskDrawer.svelte";
import {
	index as tasksIndexRoute,
	update as taskUpdate,
} from "../../routes/tasks";
import { store as storeTaskRoute } from "../../routes/projects/tasks";
import { useHttpRequest } from "../../lib/http.js";
import { consumeNavigationIntent } from "../../lib/navigation-intents.js";
import { show as projectShow } from "../../routes/projects";
import { LABELS } from "@/src/core/constants.js";
import { viewOf } from "@/src/shell/view-engine.js";
import { S, save } from "@/src/core/store.js";
import {
	addD,
	diffD,
	fmtDate,
	iso,
	MONL,
	TODAY,
	WD,
} from "@/src/core/utils.js";
import { ic } from "@/src/core/icons.js";
import { lbl, prIcon, prPill, stIcon, stPill } from "@/src/ui/helpers.js";

const httpRequest = useHttpRequest();
let {
	tasks: incomingTasks = [],
	projects = [],
	members = [],
	workspaceData = {},
	defaultFilter = "all",
} = $props();
const savedTaskView = viewOf("tasks");
let tasks = $state([]);
$effect(() => {
	tasks = [...incomingTasks];
});
let query = $state(savedTaskView.q || "");
let filters = $state(
	(savedTaskView.filters || []).map((filter) => ({
		field: filter.f,
		operator: filter.op,
		values: [...(filter.v || [])],
	})),
);
let groupBy = $state(savedTaskView.group || "status");
let sortBy = $state(savedTaskView.sort?.f || "manual");
let sortDirection = $state(savedTaskView.sort?.dir || 1);
let toolbarMenu = $state("");
let valuesOpen = $state(-1);
let menuPosition = $state({ left: 8, top: 8 });
let view = $state(savedTaskView.mode || "list");
let createOpen = $state(false);
let createAssigneeId = $state(null);
let createStatus = $state("todo");
let errorMessage = $state("");
let drawerTaskId = $state(null);
let draggedTaskId = $state(null);
let boardComposerStatus = $state("");
let boardComposerTitle = $state("");
let boardComposerInput = $state(null);
let boardCardMenu = $state(null);
let boardCardMenuPosition = $state({ left: 8, top: 8 });
let selectedTask = $derived(
	tasks.find((task) => task.id === drawerTaskId) || null,
);
const page = usePage();
const tableColumns = [
	{ id: "title", name: "Task", width: 314 },
	{ id: "status", name: "Status", width: 132 },
	{ id: "priority", name: "Priority", width: 112 },
	{ id: "assignee", name: "Assignee", width: 156 },
	{ id: "due", name: "Due date", width: 112 },
	{ id: "start", name: "Start date", width: 112 },
	{ id: "labels", name: "Labels", width: 180 },
	{ id: "deps", name: "Dependencies", width: 150 },
	{ id: "estimate", name: "Estimate", width: 92 },
	{ id: "created", name: "Created", width: 112 },
	{ id: "project", name: "Project", width: 156 },
];
let tableColumnHidden = $state([
	...(savedTaskView.hidden || ["start", "created", "deps"]),
]);
let tableColumnWidths = $state({ ...(savedTaskView.colW || {}) });
let tableGroupCollapsed = $state({});
let tableCellMenu = $state(null);
let tableCellQuery = $state("");
let tableCalendarMonth = $state(
	new Date(TODAY.getFullYear(), TODAY.getMonth(), 1),
);
let editingTableCell = $state(null);
let editingTableValue = $state("");
let editingTableInput = $state(null);
let resizingTableColumn = $state(null);
onMount(() => {
	const intent = consumeNavigationIntent("create-task");
	if (!intent) return;

	const assignee = members.find(
		(member) => member.source_key === intent.assigneeSourceKey,
	);
	openCreateTask(assignee ? String(assignee.id) : "");
});
$effect(() => {
	if (defaultFilter === "open")
		filters = [{ field: "status", operator: "not", values: ["done"] }];
	if (defaultFilter === "done")
		filters = [
			{ field: "status", operator: "is", values: ["done"] },
			{ field: "updated", operator: "is", values: ["7"] },
		];
	if (defaultFilter === "overdue")
		filters = [{ field: "due", operator: "is", values: ["overdue"] }];
});
$effect(() => {
	savedTaskView.mode = view;
	savedTaskView.q = query;
	savedTaskView.group = groupBy;
	savedTaskView.sort = { f: sortBy, dir: sortDirection };
	savedTaskView.filters = filters.map((filter) => ({
		f: filter.field,
		op: filter.operator || "is",
		v: [...filter.values],
	}));
	save();
});

const statuses = [
	{ id: "backlog", name: "Backlog", icon: "circle-dashed" },
	{ id: "todo", name: "To Do", icon: "circle" },
	{ id: "progress", name: "In Progress", icon: "circle-dot" },
	{ id: "review", name: "Review", icon: "circle-check" },
	{ id: "done", name: "Done", icon: "check-circle-2" },
];
const priorities = ["none", "low", "medium", "high", "urgent"];
const filterFields = [
	{ id: "status", name: "Status", icon: "circle-dot" },
	{ id: "assignee", name: "Assignee", icon: "user" },
	{ id: "priority", name: "Priority", icon: "signal-high" },
	{ id: "due", name: "Due date", icon: "calendar" },
	{ id: "project", name: "Project", icon: "folder" },
	{ id: "labels", name: "Labels", icon: "tag" },
	{ id: "created", name: "Created date", icon: "calendar-plus" },
	{ id: "updated", name: "Updated date", icon: "history" },
];
const groupOptions = [
	{ id: "status", name: "Status", icon: "circle-dot" },
	{ id: "priority", name: "Priority", icon: "signal-high" },
	{ id: "assignee", name: "Assignee", icon: "user" },
	{ id: "project", name: "Project", icon: "folder" },
	{ id: "due", name: "Due date", icon: "calendar" },
	{ id: "none", name: "No grouping", icon: "minus" },
];
const sortNames = {
	manual: "Manual",
	priority: "Priority",
	due: "Due date",
	start: "Start date",
	title: "Title",
	status: "Status",
	assignee: "Assignee",
	created: "Created",
	updated: "Last updated",
};
let filterCount = $derived(filters.length);

let visibleTasks = $derived.by(() => {
	const sourceTaskOrder = new Map(
		(workspaceData.tasks || []).map((task, index) => [
			String(task.database_id || task.id),
			index,
		]),
	);
	const filtered = tasks.filter((task) => {
		const matchesQuery =
			!query ||
			`${task.title} ${task.key || ""}`
				.toLowerCase()
				.includes(query.toLowerCase());
		return (
			matchesQuery &&
			filters.every((filter) => {
				if (!filter.values.length) return true;
				const matches = filter.values.some((value) =>
					matchesFilter(task, filter.field, value),
				);
				return filter.operator === "not" ? !matches : matches;
			})
		);
	});
	return filtered.sort((first, second) => {
		if (sortBy === "manual") {
			return (
				((sourceTaskOrder.get(String(first.id)) ?? Number.MAX_SAFE_INTEGER) -
					(sourceTaskOrder.get(String(second.id)) ?? Number.MAX_SAFE_INTEGER)) *
				sortDirection
			);
		}
		if (sortBy === "title")
			return first.title.localeCompare(second.title) * sortDirection;
		if (sortBy === "due")
			return (
				(first.due_date || "9999").localeCompare(second.due_date || "9999") *
				sortDirection
			);
		if (sortBy === "priority")
			return (
				(priorities.indexOf(second.priority) -
					priorities.indexOf(first.priority)) *
				sortDirection
			);
		if (sortBy === "start")
			return (
				(first.start_date || "9999").localeCompare(
					second.start_date || "9999",
				) * sortDirection
			);
		if (sortBy === "status")
			return (
				(statuses.findIndex((item) => item.id === first.status) -
					statuses.findIndex((item) => item.id === second.status)) *
				sortDirection
			);
		if (sortBy === "assignee")
			return (
				(first.assignee?.name || "zzzz").localeCompare(
					second.assignee?.name || "zzzz",
				) * sortDirection
			);
		if (sortBy === "updated")
			return (
				(new Date(first.updated_at || 0) - new Date(second.updated_at || 0)) *
				sortDirection
			);
		return (
			(new Date(first.created_at || 0) - new Date(second.created_at || 0)) *
			sortDirection
		);
	});
});

function matchesFilter(task, field, value) {
	if (field === "status") {
		if (value === "open") return task.status !== "done";
		if (value === "overdue") return isOverdue(task);
		return task.status === value;
	}
	if (field === "assignee")
		return value === "none"
			? !task.assignee_id
			: String(task.assignee_id) === String(value);
	if (field === "priority") return task.priority === value;
	if (field === "project") return String(task.project_id) === String(value);
	if (field === "labels") return (task.labels || []).includes(value);
	if (field === "due") {
		if (value === "nodate") return !task.due_date;
		if (!task.due_date) return false;
		if (value === "overdue") return isOverdue(task);
		const delta = dayDifference(task.due_date);
		return value === "today"
			? delta === 0
			: value === "week"
				? delta >= 0 && delta <= 7
				: delta > 7;
	}
	const timestamp = new Date(
		field === "created" ? task.created_at || 0 : task.updated_at || 0,
	).getTime();
	return Date.now() - timestamp <= Number(value) * 86400000;
}

function dayDifference(value) {
	const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	return Math.floor((date.getTime() - today.getTime()) / 86400000);
}

function addFilter(field) {
	filters = [...filters, { field, operator: "is", values: [] }];
	valuesOpen = filters.length - 1;
}

function removeFilter(index) {
	filters = filters.filter((_, filterIndex) => filterIndex !== index);
	valuesOpen = -1;
}

function clearFilters() {
	filters = [];
	query = "";
	valuesOpen = -1;
	toolbarMenu = "";
}

function sortByField(field) {
	if (sortBy === field) sortDirection *= -1;
	else {
		sortBy = field;
		sortDirection = 1;
	}
}

function filterOptions(field) {
	if (field === "status")
		return statuses.map((status) => ({
			id: status.id,
			name: status.name,
			iconType: "status",
		}));
	if (field === "assignee")
		return [
			{ id: "none", name: "Unassigned" },
			...members.map((member) => {
				const sourceMember = (workspaceData.members || []).find(
					(item) => String(item.database_id) === String(member.id),
				);
				return {
					id: String(member.id),
					name: `${member.name}${String(member.id) === String(page.props.auth?.user?.id) ? " (you)" : ""}`,
					member: { ...member, color: sourceMember?.c },
				};
			}),
		];
	if (field === "priority")
		return priorities.map((priority) => ({
			id: priority,
			name:
				priority === "none"
					? "No priority"
					: priority[0].toUpperCase() + priority.slice(1),
			iconType: "priority",
		}));
	if (field === "project")
		return projects
			.filter(
				(project) =>
					!project.is_private &&
					!["complete", "completed", "archived"].includes(project.status),
			)
			.map((project) => ({
				id: String(project.id),
				name: project.name,
				project,
			}));
	if (field === "labels")
		return LABELS.map((label) => ({
			id: label.id,
			name: label.name,
			color: label.c,
		}));
	if (field === "created" || field === "updated")
		return [
			{ id: "1", name: "Last 24 hours" },
			{ id: "7", name: "Last 7 days" },
			{ id: "30", name: "Last 30 days" },
		];
	return [
		{ id: "overdue", name: "Overdue" },
		{ id: "today", name: "Today" },
		{ id: "week", name: "Next 7 days" },
		{ id: "later", name: "Later" },
		{ id: "nodate", name: "No due date" },
	];
}

function toggleToolbarMenu(menu, event) {
	if (toolbarMenu === menu) {
		toolbarMenu = "";
		return;
	}
	const bounds = event.currentTarget.getBoundingClientRect();
	const width = menu === "filter" ? 560 : menu === "columns" ? 220 : 240;
	menuPosition = {
		left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
		top: Math.min(bounds.bottom + 4, window.innerHeight - 380),
	};
	toolbarMenu = menu;
	if (menu !== "filter") {
		valuesOpen = -1;
	}
}

function openFilterValues(index, event) {
	const bounds = event.currentTarget.getBoundingClientRect();
	menuPosition = {
		left: Math.max(8, Math.min(bounds.left, window.innerWidth - 560 - 8)),
		top: Math.min(bounds.bottom + 4, window.innerHeight - 380),
	};
	toolbarMenu = "filter";
	valuesOpen = index;
}

function closeToolbarMenu() {
	toolbarMenu = "";
	valuesOpen = -1;
}

function handleWindowPointerdown(event) {
	if (!event.target.closest("[data-task-menu], [data-task-trigger]"))
		closeToolbarMenu();
	if (
		!event.target.closest("[data-board-card-menu], [data-board-card-trigger]")
	)
		boardCardMenu = null;
	if (
		!event.target.closest("[data-table-cell-menu], [data-table-cell-trigger]")
	)
		tableCellMenu = null;
}

function handleWindowKeydown(event) {
	if (event.key === "Escape") {
		closeToolbarMenu();
		boardCardMenu = null;
		tableCellMenu = null;
	}
}

let visibleTableColumns = $derived(
	tableColumns.filter(
		(column) => column.id === "title" || !tableColumnHidden.includes(column.id),
	),
);

function toggleTableColumn(columnId) {
	if (columnId === "title") return;
	tableColumnHidden = tableColumnHidden.includes(columnId)
		? tableColumnHidden.filter((hiddenColumn) => hiddenColumn !== columnId)
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
	tableColumnWidths = {
		...tableColumnWidths,
		[id]: Math.max(64, startWidth + event.clientX - startX),
	};
}

function finishTableColumnResize() {
	if (!resizingTableColumn) return;
	savedTaskView.colW = Object.fromEntries(
		Object.entries(tableColumnWidths).map(([id, width]) => [
			id,
			Math.round(width),
		]),
	);
	resizingTableColumn = null;
	save();
}

function tableSortField(columnId) {
	sortByField(["labels", "deps"].includes(columnId) ? "manual" : columnId);
}

async function startTableCellEdit(task, field) {
	editingTableCell = { taskId: task.id, field };
	editingTableValue = field === "title" ? task.title : task.estimate || "";
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
	if (field === "title" && !value) return;
	if (value !== String(field === "title" ? task.title : task.estimate || "")) {
		await updateTask(task, { [field]: value || null });
	}
}

function openTableCellMenu(task, field, event) {
	const bounds = event.currentTarget.getBoundingClientRect();
	const width = field === "labels" ? 220 : 200;
	tableCellMenu = {
		taskId: task.id,
		field,
		left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
		top: Math.min(bounds.bottom + 4, window.innerHeight - 320),
	};
	tableCellQuery = "";
	if (field === "due" || field === "start") {
		const selectedDate = field === "due" ? task.due_date : task.start_date;
		const date = selectedDate
			? new Date(`${String(selectedDate).slice(0, 10)}T00:00:00`)
			: TODAY;
		tableCalendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
	}
}

function updateTableCell(task, field, value) {
	tableCellMenu = null;
	const fieldNames = {
		due: "due_date",
		start: "start_date",
		assignee: "assignee_id",
		project: "project_id",
	};
	updateTask(task, { [fieldNames[field] || field]: value });
}

function toggleTableCellLabel(task, labelId) {
	const labels = task.labels || [];
	updateTask(task, {
		labels: labels.includes(labelId)
			? labels.filter((existingLabel) => existingLabel !== labelId)
			: [...labels, labelId],
	});
}

function toggleTableCellDependency(task, dependencyId) {
	const dependencies = task.dependencies || [];
	updateTask(task, {
		dependencies: dependencies.includes(dependencyId)
			? dependencies.filter(
					(existingDependency) => existingDependency !== dependencyId,
				)
			: [...dependencies, dependencyId],
	});
}

function shiftTableCalendarMonth(amount) {
	tableCalendarMonth = new Date(
		tableCalendarMonth.getFullYear(),
		tableCalendarMonth.getMonth() + amount,
		1,
	);
}

let tableCalendarDays = $derived.by(() => {
	const firstDay = new Date(
		tableCalendarMonth.getFullYear(),
		tableCalendarMonth.getMonth(),
		1,
	);
	const weekStart = Number(S.prefs.weekStart ?? 1);
	const offset = (firstDay.getDay() - weekStart + 7) % 7;
	return Array.from({ length: 42 }, (_, index) =>
		addD(firstDay, index - offset),
	);
});

let tableCalendarWeekdays = $derived.by(() => {
	const weekStart = Number(S.prefs.weekStart ?? 1);
	return Array.from({ length: 7 }, (_, index) =>
		WD[(index + weekStart) % 7].slice(0, 2),
	);
});

let tableDependencyCandidates = $derived.by(() => {
	if (!tableCellMenu || tableCellMenu.field !== "deps") return [];
	const task = tasks.find(
		(item) => String(item.id) === String(tableCellMenu.taskId),
	);
	const projectSourceKey = workspaceData.projects?.find(
		(project) => String(project.database_id) === String(task?.project_id),
	)?.id;

	return (workspaceData.tasks || []).filter(
		(sourceTask) =>
			sourceTask.project === projectSourceKey &&
			sourceTask.id !== task?.source_key &&
			!sourceTask.archived &&
			`${sourceTask.key || ""} ${sourceTask.title}`
				.toLowerCase()
				.includes(tableCellQuery.trim().toLowerCase()),
	);
});

function toggleTableGroup(groupId) {
	const key = `tasks:${groupId}`;
	tableGroupCollapsed = {
		...tableGroupCollapsed,
		[key]: !tableGroupCollapsed[key],
	};
}

function tableGroupIsCollapsed(groupId) {
	return Boolean(tableGroupCollapsed[`tasks:${groupId}`]);
}

function tableTaskDate(value) {
	if (!value) return "";
	return formatDate(String(value).slice(0, 10));
}

function updateFilter(index, changes) {
	filters = filters.map((filter, filterIndex) =>
		filterIndex === index ? { ...filter, ...changes } : filter,
	);
}

function toggleFilterValue(index, value) {
	const values = filters[index].values;
	updateFilter(index, {
		values: values.includes(String(value))
			? values.filter((item) => item !== String(value))
			: [...values, String(value)],
	});
}

function taskDropdownElement(id, tag, options = {}) {
	return { id, type: "element", tag, ...options };
}

function taskDropdownAction(id, action, payload, label, options = {}) {
	return { id, type: "action", action, payload, label, ...options };
}

function filterOptionLeadingItem(option, id) {
	if (option.iconType === "status")
		return taskDropdownElement(`${id}-graphic`, "span", {
			graphic: { type: "status", value: option.id, size: 13 },
		});
	if (option.iconType === "priority")
		return taskDropdownElement(`${id}-graphic`, "span", {
			graphic: { type: "priority", value: option.id, size: 13 },
		});
	if (option.member)
		return taskDropdownElement(`${id}-graphic`, "span", {
			graphic: {
				type: "avatar",
				initials: initials(option.member.name),
				color: option.member.color || "var(--gray)",
			},
		});
	if (option.project)
		return taskDropdownElement(`${id}-graphic`, "span", {
			className: "pdot",
			style: `--c:var(--${option.project.color || "indigo"})`,
		});
	if (option.color)
		return taskDropdownElement(`${id}-graphic`, "span", {
			className: "pdot",
			style: `--c:${option.color}`,
		});
	return taskDropdownElement(`${id}-graphic`, "span", {
		icon: "circle-dot",
		iconSize: 13,
	});
}

function filterValueItems(index, filter = filters[index]) {
	if (!filter) return [];
	return filterOptions(filter.field).map((option) => {
		const id = `filter-value-${index}-${option.id}`;
		return taskDropdownElement(id, "label", {
			className: "mi",
			style: "cursor:pointer;min-height:28px",
			items: [
				taskDropdownElement(`${id}-checkbox`, "input", {
					elementType: "checkbox",
					className: "check",
					checked: filter.values.includes(String(option.id)),
					payload: `${index}|${option.id}`,
					events: { change: "toggle-filter-value" },
				}),
				filterOptionLeadingItem(option, id),
				taskDropdownElement(`${id}-label`, "span", { text: option.name }),
			],
		});
	});
}

function filterDropdownItems() {
	const items = [
		taskDropdownElement("filter-heading", "div", {
			className: "mh fb-h",
			items: [
				taskDropdownElement("filter-heading-label", "span", {
					text: "Filter by",
				}),
				...(filters.length
					? [
							taskDropdownElement("filter-clear", "button", {
								className: "btn btn-ghost btn-sm",
								text: "Clear all",
								events: { click: "clear-filters" },
							}),
						]
					: []),
			],
		}),
	];

	if (filters.length) {
		items.push(
			taskDropdownElement("filter-rows", "div", {
				className: "fb-rows",
				items: filters.flatMap((filter, index) => {
					const names = filter.values.map(
						(value) =>
							filterOptions(filter.field).find(
								(option) => String(option.id) === String(value),
							)?.name || value,
					);
					return [
						taskDropdownElement(`filter-row-${index}`, "div", {
							className: "frow",
							items: [
								taskDropdownElement(`filter-conjunction-${index}`, "span", {
									className: "conj",
									text: index ? "and" : "Where",
								}),
								taskDropdownElement(`filter-field-${index}`, "select", {
									className: "select",
									ariaLabel: "Field",
									value: filter.field,
									payload: index,
									events: { change: "set-filter-field" },
									items: filterFields.map((field) =>
										taskDropdownElement(
											`filter-field-option-${index}-${field.id}`,
											"option",
											{ value: field.id, text: field.name },
										),
									),
								}),
								taskDropdownElement(`filter-operator-${index}`, "select", {
									className: "select",
									ariaLabel: "Operator",
									value: filter.operator || "is",
									payload: index,
									events: { change: "set-filter-operator" },
									items: [
										taskDropdownElement(
											`filter-operator-is-${index}`,
											"option",
											{ value: "is", text: "is" },
										),
										taskDropdownElement(
											`filter-operator-not-${index}`,
											"option",
											{ value: "not", text: "is not" },
										),
									],
								}),
								taskDropdownElement(`filter-values-${index}`, "button", {
									className: "valbtn",
									ariaExpanded: valuesOpen === index,
									payload: index,
									events: { click: "toggle-filter-editor" },
									items: [
										taskDropdownElement(
											`filter-values-label-${index}`,
											"span",
											{
												className: "trunc grow",
												text: names.length ? names.join(", ") : "Select…",
											},
										),
										taskDropdownElement(
											`filter-values-chevron-${index}`,
											"span",
											{ icon: "chevron-down", iconSize: 12 },
										),
									],
								}),
								taskDropdownElement(`filter-remove-${index}`, "button", {
									className: "ibtn ibtn-xs",
									ariaLabel: "Remove filter",
									payload: index,
									icon: "trash-2",
									iconSize: 13,
									events: { click: "remove-filter" },
								}),
							],
						}),
						...(valuesOpen === index
							? [
									taskDropdownElement(`filter-options-${index}`, "div", {
										style:
											"margin-left:50px;border:1px solid var(--border);border-radius:var(--r);padding:4px;max-height:200px;overflow:auto",
										items: filterValueItems(index, filter),
									}),
								]
							: []),
					];
				}),
			}),
		);
	} else {
		items.push(
			taskDropdownElement("filter-empty", "p", {
				className: "fb-empty",
				text: "No filters applied. Combine filters to narrow the task list, e.g. Status is In Progress and Assignee is Sarah.",
			}),
		);
	}

	items.push({ id: "filter-separator", type: "separator" });
	items.push(
		taskDropdownElement("filter-add", "div", {
			className: "fb-add",
			items: [
				taskDropdownElement("filter-add-label", "span", {
					className: "faint",
					text: "Add filter:",
				}),
				...filterFields.map((field) =>
					taskDropdownElement(`filter-add-${field.id}`, "button", {
						className: "badge",
						payload: field.id,
						icon: field.icon,
						iconSize: 11,
						text: field.name,
						events: { click: "add-filter" },
					}),
				),
			],
		}),
	);
	return items;
}

function sortDropdownItems() {
	return [
		taskDropdownElement("sort-heading", "div", {
			className: "mh",
			text: "Sort by",
		}),
		...Object.entries(sortNames).map(([field, name]) =>
			taskDropdownAction(`sort-${field}`, "set-sort", field, name, {
				role: "menuitemradio",
				checked: sortBy === field,
			}),
		),
		{ id: "sort-separator", type: "separator" },
		taskDropdownElement("sort-direction-wrap", "div", {
			style: "padding:4px 6px",
			items: [
				taskDropdownElement("sort-direction-segment", "div", {
					className: "seg",
					style: "width:100%",
					items: [
						taskDropdownElement("sort-ascending", "button", {
							className: sortDirection > 0 ? "on" : "",
							style: "flex:1;justify-content:center",
							icon: "arrow-up",
							iconSize: 12,
							text: "Ascending",
							payload: 1,
							events: { click: "set-sort-direction" },
						}),
						taskDropdownElement("sort-descending", "button", {
							className: sortDirection < 0 ? "on" : "",
							style: "flex:1;justify-content:center",
							icon: "arrow-down",
							iconSize: 12,
							text: "Descending",
							payload: -1,
							events: { click: "set-sort-direction" },
						}),
					],
				}),
			],
		}),
	];
}

function groupDropdownItems() {
	return [
		taskDropdownElement("group-heading", "div", {
			className: "mh",
			text: "Group by",
		}),
		...groupOptions.map((option) =>
			taskDropdownAction(
				`group-${option.id}`,
				"set-group",
				option.id,
				option.name,
				{
					icon: option.icon,
					role: "menuitemradio",
					checked: groupBy === option.id,
				},
			),
		),
	];
}

function columnDropdownItems() {
	return [
		taskDropdownElement("columns-heading", "div", {
			className: "mh",
			text: "Visible columns",
		}),
		...tableColumns
			.filter((column) => column.id !== "title")
			.map((column) =>
				taskDropdownElement(`column-${column.id}`, "button", {
					className: "mi",
					role: "menuitemcheckbox",
					ariaChecked: !tableColumnHidden.includes(column.id),
					payload: column.id,
					events: { click: "toggle-column" },
					items: [
						taskDropdownElement(`column-checkbox-${column.id}`, "input", {
							elementType: "checkbox",
							className: "check",
							checked: !tableColumnHidden.includes(column.id),
							attributes: { tabindex: -1 },
						}),
						taskDropdownElement(`column-label-${column.id}`, "span", {
							text: column.name,
						}),
					],
				}),
			),
		{ id: "columns-separator", type: "separator" },
		taskDropdownAction(
			"reset-column-widths",
			"reset-column-widths",
			null,
			"Reset column widths",
		),
	];
}

function taskDateDropdownItems(task, field, isBoardCard = false) {
	const dateField = field === "due" ? "due_date" : "start_date";
	const currentDate = String(task[dateField] || "").slice(0, 10);
	const datePrefix = isBoardCard ? "board-date" : "table-date";
	const selectDateAction = isBoardCard ? "set-board-date" : "set-table-date";
	return [
		taskDropdownElement(`${datePrefix}-shortcuts`, "div", {
			className: "row",
			style: "gap:4px;padding:4px;flex-wrap:wrap",
			items: [
				...[
					["Today", 0],
					["Tomorrow", 1],
					["Next week", 7 - ((TODAY.getDay() + 6) % 7)],
					["In 2 weeks", 14],
				].map(([shortcut, offset]) =>
					taskDropdownElement(`${datePrefix}-shortcut-${offset}`, "button", {
						className: "badge",
						style: "cursor:pointer",
						text: shortcut,
						payload: iso(addD(TODAY, offset)),
						events: { click: selectDateAction },
					}),
				),
				...(currentDate
					? [
							taskDropdownElement(`${datePrefix}-clear`, "button", {
								className: "badge",
								style: "cursor:pointer;color:var(--red)",
								text: "Clear",
								payload: "",
								events: { click: selectDateAction },
							}),
						]
					: []),
			],
		}),
		{ id: `${datePrefix}-separator`, type: "separator" },
		taskDropdownElement(`${datePrefix}-calendar`, "div", {
			style: "padding:6px 6px 2px;width:252px",
			items: [
				taskDropdownElement(`${datePrefix}-header`, "div", {
					className: "row",
					style: "margin-bottom:6px",
					items: [
						taskDropdownElement(`${datePrefix}-month`, "b", {
							style: "font-weight:600;font-size:13px;padding-left:4px",
							text: `${MONL[tableCalendarMonth.getMonth()]} ${tableCalendarMonth.getFullYear()}`,
						}),
						taskDropdownElement(`${datePrefix}-spacer`, "span", {
							className: "sp",
						}),
						taskDropdownElement(`${datePrefix}-previous`, "button", {
							className: "ibtn ibtn-xs",
							ariaLabel: "Previous month",
							icon: "chevron-left",
							iconSize: 14,
							payload: -1,
							events: { click: "shift-calendar-month" },
						}),
						taskDropdownElement(`${datePrefix}-next`, "button", {
							className: "ibtn ibtn-xs",
							ariaLabel: "Next month",
							icon: "chevron-right",
							iconSize: 14,
							payload: 1,
							events: { click: "shift-calendar-month" },
						}),
					],
				}),
				taskDropdownElement(`${datePrefix}-grid`, "div", {
					style:
						"display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:11px",
					items: [
						...tableCalendarWeekdays.map((weekday, index) =>
							taskDropdownElement(`${datePrefix}-weekday-${index}`, "span", {
								className: "faint",
								style: "padding:3px 0",
								text: weekday,
							}),
						),
						...tableCalendarDays.map((day) => {
							const dateValue = iso(day);
							const isSelected = dateValue === currentDate;
							const isToday = diffD(day, TODAY) === 0;
							return taskDropdownElement(
								`${datePrefix}-day-${dateValue}`,
								"button",
								{
									className: "num",
									ariaLabel: fmtDate(dateValue, true),
									ariaPressed: isSelected,
									style: `height:30px;border-radius:6px;font-size:12px;${isSelected ? "background:var(--accent);color:var(--on-accent);font-weight:600" : isToday ? "color:var(--accent);font-weight:600;box-shadow:inset 0 0 0 1px var(--accent-line)" : day.getMonth() !== tableCalendarMonth.getMonth() ? "color:var(--text-3)" : ""}`,
									text: String(day.getDate()),
									payload: dateValue,
									events: { click: selectDateAction },
								},
							);
						}),
					],
				}),
			],
		}),
	];
}

function boardCardDropdownItems(task) {
	if (boardCardMenu.field === "priority") {
		return [
			taskDropdownElement("board-priority-heading", "div", {
				className: "mh",
				text: "Priority",
			}),
			...priorities.map((priority) =>
				taskDropdownAction(
					`board-priority-${priority}`,
					"set-board-priority",
					priority,
					priority === "none"
						? "No priority"
						: priority[0].toUpperCase() + priority.slice(1),
					{
						role: "menuitemradio",
						checked: task.priority === priority,
						graphic: { type: "priority", value: priority },
					},
				),
			),
		];
	}
	if (boardCardMenu.field === "due")
		return taskDateDropdownItems(task, "due", true);
	return [
		taskDropdownElement("board-assignee-heading", "div", {
			className: "mh",
			text: "Assign to",
		}),
		taskDropdownAction(
			"board-assignee-none",
			"set-board-assignee",
			null,
			"Unassigned",
			{ role: "menuitemradio", checked: !task.assignee_id, icon: "user-round" },
		),
		...members.map((member) =>
			taskDropdownAction(
				`board-assignee-${member.id}`,
				"set-board-assignee",
				member.id,
				member.name,
				{
					role: "menuitemradio",
					checked: String(task.assignee_id) === String(member.id),
					graphic: {
						type: "avatar",
						initials: initials(member.name),
						color: member.color || "var(--gray)",
					},
				},
			),
		),
	];
}

function tableCellDropdownItems(task) {
	const field = tableCellMenu.field;
	if (field === "status")
		return [
			taskDropdownElement("table-status-heading", "div", {
				className: "mh",
				text: "Status",
			}),
			...statuses.map((option) =>
				taskDropdownAction(
					`table-status-${option.id}`,
					"set-table-cell",
					option.id,
					option.name,
					{
						role: "menuitemradio",
						checked: task.status === option.id,
						graphic: { type: "status", value: option.id },
					},
				),
			),
		];
	if (field === "priority")
		return [
			taskDropdownElement("table-priority-heading", "div", {
				className: "mh",
				text: "Priority",
			}),
			...priorities.map((option) =>
				taskDropdownAction(
					`table-priority-${option}`,
					"set-table-cell",
					option,
					option === "none"
						? "No priority"
						: option[0].toUpperCase() + option.slice(1),
					{
						role: "menuitemradio",
						checked: task.priority === option,
						graphic: { type: "priority", value: option },
					},
				),
			),
		];
	if (field === "assignee")
		return [
			taskDropdownElement("table-assignee-heading", "div", {
				className: "mh",
				text: "Assignee",
			}),
			taskDropdownElement("table-assignee-search-wrap", "div", {
				className: "pin",
				items: [
					taskDropdownElement("table-assignee-search", "input", {
						ariaLabel: "Assign to…",
						placeholder: "Assign to…",
						value: tableCellQuery,
						events: { input: "search-table-cell" },
					}),
				],
			}),
			taskDropdownAction(
				"table-assignee-none",
				"set-table-cell",
				null,
				"Unassigned",
				{
					role: "menuitemradio",
					checked: !task.assignee_id,
					icon: "user-round",
				},
			),
			...members
				.filter((member) =>
					member.name
						.toLowerCase()
						.includes(tableCellQuery.trim().toLowerCase()),
				)
				.map((member) =>
					taskDropdownAction(
						`table-assignee-${member.id}`,
						"set-table-cell",
						member.id,
						`${member.name}${String(member.id) === String(page.props.auth?.user?.id) ? " (you)" : ""}`,
						{
							role: "menuitemradio",
							checked: String(task.assignee_id) === String(member.id),
							graphic: {
								type: "avatar",
								initials: initials(member.name),
								color: member.color || "var(--gray)",
							},
						},
					),
				),
		];
	if (field === "due" || field === "start")
		return taskDateDropdownItems(task, field);
	if (field === "labels")
		return [
			taskDropdownElement("table-labels-heading", "div", {
				className: "mh",
				text: "Labels",
			}),
			...LABELS.map((label) =>
				taskDropdownAction(
					`table-label-${label.id}`,
					"toggle-table-label",
					label.id,
					label.name,
					{
						role: "menuitemcheckbox",
						checked: (task.labels || []).includes(label.id),
						graphic: { type: "dot", color: label.c },
					},
				),
			),
		];
	if (field === "deps")
		return [
			taskDropdownElement("table-dependencies-heading", "div", {
				className: "mh",
				text: "Dependencies",
			}),
			taskDropdownElement("table-dependencies-search-wrap", "div", {
				className: "pin",
				items: [
					taskDropdownElement("table-dependencies-search", "input", {
						ariaLabel: "Blocked by…",
						placeholder: "Blocked by…",
						value: tableCellQuery,
						events: { input: "search-table-cell" },
					}),
				],
			}),
			...tableDependencyCandidates.map((blockedTask) =>
				taskDropdownAction(
					`table-dependency-${blockedTask.id}`,
					"toggle-table-dependency",
					blockedTask.id,
					`${blockedTask.key} ${blockedTask.title}`,
					{
						role: "menuitemcheckbox",
						checked: (task.dependencies || []).includes(blockedTask.id),
						graphic: { type: "status", value: blockedTask.status },
					},
				),
			),
			...(!tableDependencyCandidates.length
				? [
						{
							id: "table-dependencies-empty",
							type: "text",
							className: "mi faint",
							label: "No matches",
						},
					]
				: []),
		];
	if (field === "estimate")
		return [
			taskDropdownElement("table-estimate-heading", "div", {
				className: "mh",
				text: "Estimate",
			}),
			...["30m", "1h", "2h", "4h", "1d", "2d", "3d", "5d", "8d"].map(
				(estimate) =>
					taskDropdownAction(
						`table-estimate-${estimate}`,
						"set-table-cell",
						estimate,
						estimate,
						{
							role: "menuitemradio",
							checked: task.estimate === estimate,
							icon: "timer",
						},
					),
			),
			...(task.estimate
				? [
						{ id: "table-estimate-clear-separator", type: "separator" },
						taskDropdownAction(
							"table-estimate-clear",
							"set-table-cell",
							null,
							"Clear estimate",
							{ icon: "x" },
						),
					]
				: []),
		];
	return [
		taskDropdownElement("table-project-heading", "div", {
			className: "mh",
			text: "Project",
		}),
		...projects
			.filter(
				(project) =>
					!project.is_private &&
					!["complete", "completed", "archived"].includes(project.status),
			)
			.map((project) =>
				taskDropdownAction(
					`table-project-${project.id}`,
					"set-table-cell",
					project.id,
					project.name,
					{
						role: "menuitemradio",
						checked: String(task.project_id) === String(project.id),
						graphic: {
							type: "dot",
							color: `var(--${project.color || "indigo"})`,
						},
					},
				),
			),
	];
}

function handleTaskDropdownAction(action, item, context) {
	if (action === "add-filter" && typeof item.payload === "string")
		addFilter(item.payload);
	if (action === "clear-filters") clearFilters();
	if (
		action === "set-filter-field" &&
		typeof item.payload === "number" &&
		typeof context?.value === "string"
	) {
		updateFilter(item.payload, { field: context.value, values: [] });
		valuesOpen = item.payload;
	}
	if (
		action === "set-filter-operator" &&
		typeof item.payload === "number" &&
		typeof context?.value === "string"
	)
		updateFilter(item.payload, { operator: context.value });
	if (action === "toggle-filter-editor" && typeof item.payload === "number")
		valuesOpen = valuesOpen === item.payload ? -1 : item.payload;
	if (action === "remove-filter" && typeof item.payload === "number")
		removeFilter(item.payload);
	if (action === "toggle-filter-value" && typeof item.payload === "string") {
		const [index, value] = item.payload.split("|");
		toggleFilterValue(Number(index), value);
	}
	if (action === "set-sort" && typeof item.payload === "string") {
		sortBy = item.payload;
		toolbarMenu = "";
	}
	if (action === "set-sort-direction" && typeof item.payload === "number")
		sortDirection = item.payload;
	if (action === "set-group" && typeof item.payload === "string") {
		groupBy = item.payload;
		toolbarMenu = "";
	}
	if (action === "toggle-column" && typeof item.payload === "string")
		toggleTableColumn(item.payload);
	if (action === "reset-column-widths") {
		tableColumnWidths = {};
		savedTaskView.colW = {};
		save();
	}
	if (action === "set-board-priority" && typeof item.payload === "string") {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(boardCardMenu?.taskId),
		);
		if (task) updateBoardCard(task, { priority: item.payload });
	}
	if (action === "set-board-assignee") {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(boardCardMenu?.taskId),
		);
		if (task) updateBoardCard(task, { assignee_id: item.payload });
	}
	if (action === "set-board-date" && typeof item.payload === "string") {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(boardCardMenu?.taskId),
		);
		if (task) updateBoardCardDue(task, item.payload || null);
	}
	if (action === "shift-calendar-month" && typeof item.payload === "number")
		shiftTableCalendarMonth(item.payload);
	if (action === "search-table-cell")
		tableCellQuery = String(context?.value ?? "");
	if (action === "set-table-cell") {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(tableCellMenu?.taskId),
		);
		if (task) updateTableCell(task, tableCellMenu.field, item.payload);
	}
	if (action === "set-table-date" && typeof item.payload === "string") {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(tableCellMenu?.taskId),
		);
		if (task) updateTableCell(task, tableCellMenu.field, item.payload || null);
	}
	if (action === "toggle-table-label" && typeof item.payload === "string") {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(tableCellMenu?.taskId),
		);
		if (task) toggleTableCellLabel(task, item.payload);
	}
	if (
		action === "toggle-table-dependency" &&
		typeof item.payload === "string"
	) {
		const task = tasks.find(
			(candidate) => String(candidate.id) === String(tableCellMenu?.taskId),
		);
		if (task) toggleTableCellDependency(task, item.payload);
	}
}

function initials(name = "") {
	return name
		.split(" ")
		.map((part) => part[0])
		.join("")
		.slice(0, 2);
}

function taskCommentsCount(task) {
	return (workspaceData.comments || []).filter(
		(comment) => comment.task === task.source_key,
	).length;
}

function taskSubtaskProgress(task) {
	const subtasks = task.subtasks || [];
	const completed = subtasks.filter((subtask) => subtask.done).length;
	return {
		completed,
		total: subtasks.length,
		percentage: subtasks.length
			? Math.round((completed / subtasks.length) * 100)
			: 0,
	};
}

function dueLabel(task) {
	if (!task.due_date) return "";
	const delta = dayDifference(task.due_date);
	if (delta === 0) return "Today";
	if (delta === 1) return "Tomorrow";
	if (delta === -1) return "Yesterday";
	return new Date(
		`${String(task.due_date).slice(0, 10)}T00:00:00`,
	).toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

function dueClass(task) {
	if (task.status === "done" || !task.due_date) return "";
	const delta = dayDifference(task.due_date);
	return delta < 0 ? "over" : delta <= 1 ? "soon" : "";
}

function handleBoardCardKeydown(event, task) {
	if (event.target.closest("button, a")) return;
	if (event.key !== "Enter" && event.key !== " ") return;
	event.preventDefault();
	drawerTaskId = task.id;
}

function handleBoardDrop(event, status) {
	event.preventDefault();
	const task = tasks.find((item) => String(item.id) === String(draggedTaskId));
	if (task && task.status !== status) updateTask(task, { status });
	draggedTaskId = null;
}

function openBoardCardMenu(event, task, field) {
	event.stopPropagation();
	const bounds = event.currentTarget.getBoundingClientRect();
	const width = field === "due" ? 312 : 220;
	if (field === "due") {
		const date = task.due_date
			? new Date(`${String(task.due_date).slice(0, 10)}T00:00:00`)
			: TODAY;
		tableCalendarMonth = new Date(date.getFullYear(), date.getMonth(), 1);
	}
	boardCardMenuPosition = {
		left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
		top: Math.min(
			bounds.bottom + 4,
			window.innerHeight - (field === "due" ? 400 : 300),
		),
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
	boardComposerTitle = "";
	await tick();
	boardComposerInput?.focus();
}

async function commitBoardComposer(status) {
	const title = boardComposerTitle.trim();
	if (!title) return;
	const group = statuses.find((item) => item.id === status);
	if (group) await createInlineTask(title, group);
	boardComposerStatus = "";
	boardComposerTitle = "";
}

function handleBoardComposerKeydown(event, status) {
	if (event.key === "Enter") {
		event.preventDefault();
		commitBoardComposer(status);
	} else if (event.key === "Escape") {
		boardComposerStatus = "";
		boardComposerTitle = "";
	}
}

function changeGroup(group) {
	groupBy = group;
}

function taskGroups() {
	if (groupBy === "none")
		return [{ id: "all", name: "All tasks", tasks: visibleTasks }];
	if (groupBy === "project") {
		const sourceOrder = new Map(
			(workspaceData.projects || []).map((project, index) => [
				String(project.database_id || project.id),
				index,
			]),
		);
		return [...projects]
			.sort(
				(first, second) =>
					(sourceOrder.get(String(first.id)) ?? 9999) -
					(sourceOrder.get(String(second.id)) ?? 9999),
			)
			.map((project) => ({
				id: String(project.id),
				name: project.name,
				icon: "folder",
				iconType: "project",
				tasks: visibleTasks.filter(
					(task) => String(task.project_id) === String(project.id),
				),
			}))
			.filter((group) => group.tasks.length);
	}
	if (groupBy === "assignee")
		return [
			...members.map((member) => ({
				id: String(member.id),
				name: member.name,
				member,
				iconType: "member",
				tasks: visibleTasks.filter(
					(task) => String(task.assignee_id) === String(member.id),
				),
			})),
			{
				id: "none",
				name: "Unassigned",
				icon: "user-round",
				iconType: "icon",
				tasks: visibleTasks.filter((task) => !task.assignee_id),
			},
		].filter((group) => group.tasks.length);
	if (groupBy === "priority")
		return ["urgent", "high", "medium", "low", "none"].map((priority) => ({
			id: priority,
			name:
				priority === "none"
					? "No priority"
					: priority[0].toUpperCase() + priority.slice(1),
			iconType: "priority",
			tasks: visibleTasks.filter((task) => task.priority === priority),
		}));
	if (groupBy === "due") {
		const groups = [
			{ id: "overdue", name: "Overdue", icon: "calendar" },
			{ id: "today", name: "Today", icon: "calendar" },
			{ id: "week", name: "Next 7 days", icon: "calendar" },
			{ id: "later", name: "Later", icon: "calendar" },
			{ id: "nodate", name: "No due date", icon: "calendar" },
		];
		return groups
			.map((group) => ({
				...group,
				iconType: "icon",
				tasks: visibleTasks.filter((task) =>
					matchesFilter(task, "due", group.id),
				),
			}))
			.filter((group) => group.tasks.length);
	}
	return statuses.map((item) => ({
		...item,
		iconType: "status",
		name: item.name,
		tasks: visibleTasks.filter((task) => task.status === item.id),
	}));
}

function isOverdue(task) {
	return (
		task.status !== "done" &&
		task.due_date &&
		new Date(`${task.due_date.slice(0, 10)}T00:00:00`) <
			new Date(new Date().toDateString())
	);
}

function formatDate(value) {
	if (!value) return "";
	return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString(
		"en-US",
		{ month: "short", day: "numeric" },
	);
}

function openCreateTask(initialAssigneeId = "", initialStatus = "todo") {
	createAssigneeId = initialAssigneeId || null;
	createStatus = initialStatus;
	createOpen = true;
}

function taskCreated(task) {
	tasks = [task, ...tasks];
}

async function updateTask(task, values) {
	errorMessage = "";
	try {
		const updated = await httpRequest(taskUpdate(task.id).url, "PATCH", values);
		tasks = tasks.map((item) =>
			item.id === updated.id ? { ...item, ...updated } : item,
		);
	} catch (error) {
		errorMessage = error.message;
	}
}

async function createInlineTask(title, group) {
	const isStatusGroup = statuses.some((status) => status.id === group.id);
	const firstSourceProject = workspaceData.projects?.[0];
	const projectId = isStatusGroup
		? firstSourceProject?.database_id ||
			projects.find(
				(project) =>
					!["complete", "completed", "archived"].includes(project.status),
			)?.id
		: Number(group.id);
	if (!projectId) return;

	try {
		const task = await httpRequest(
			storeTaskRoute({ project: projectId }).url,
			"POST",
			{
				title,
				status: isStatusGroup ? group.id : "todo",
				priority: "none",
				assignee_id: null,
				due_date: null,
				labels: [],
				subtasks: [],
				attachments: [],
				recurrence: null,
			},
		);
		tasks = [task, ...tasks];
	} catch (error) {
		errorMessage = error.errors?.title?.[0] || error.message;
	}
}

function taskUpdated(updatedTask) {
	tasks = tasks.map((task) =>
		task.id === updatedTask.id ? { ...task, ...updatedTask } : task,
	);
}
</script>

<svelte:window onpointerdown={handleWindowPointerdown} onkeydown={handleWindowKeydown} onpointermove={resizeTableColumn} onpointerup={finishTableColumnResize} />

<svelte:head><title>Tasks</title></svelte:head>

<AppLayout>
  <div class="page flush">
    <div class="ph"><div><h1>Tasks</h1><p>Every task across {projects.length} projects.</p></div><div class="acts"><Button variant="primary" size="md" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</Button></div></div>
    {#if errorMessage}<div class="alert danger" role="alert">{@html ic('circle-alert', 15)}<span>{errorMessage}</span></div>{/if}
    <div class="toolbar" role="toolbar">
      <div class="inwrap">{@html ic('search', 13)}<Input class="search-sm" bind:value={query} placeholder="Search tasks" aria-label="Search tasks" /></div>
      <Button variant="ghost" size="md" data-task-trigger type="button" aria-label="Filter tasks" aria-expanded={toolbarMenu === 'filter'} onclick={event => toggleToolbarMenu('filter', event)}>{@html ic('list-filter', 13)}Filter{#if filterCount}<span class="badge">{filterCount}</span>{/if}</Button>
      <Button variant="ghost" size="md" data-task-trigger type="button" aria-label={`Sort tasks: ${sortNames[sortBy]}`} aria-expanded={toolbarMenu === 'sort'} onclick={event => toggleToolbarMenu('sort', event)}>{@html ic('arrow-up-down', 13)}Sort: {sortNames[sortBy]}</Button>
      {#if view !== 'board'}<Button variant="ghost" size="md" data-task-trigger aria-expanded={toolbarMenu === 'group'} onclick={event => toggleToolbarMenu('group', event)}>{@html ic('list-filter', 13)}Group: {groupBy === 'none' ? 'None' : groupBy[0].toUpperCase() + groupBy.slice(1)}</Button>{/if}
      {#if view === 'table'}<Button variant="ghost" size="md" data-task-trigger aria-label="Columns" aria-expanded={toolbarMenu === 'columns'} onclick={event => toggleToolbarMenu('columns', event)}>{@html ic('columns-3', 13)}Columns</Button>{/if}
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
        <Button variant="ghost" size="sm" data-task-trigger onclick={event => toggleToolbarMenu('filter', event)}>{@html ic('plus', 12)}Add</Button><span class="sp"></span><Button variant="ghost" size="sm" onclick={clearFilters}>Clear all</Button>
      </div>
    {/if}
    {#if toolbarMenu === 'filter'}
      <Dropdown
        config={{ className: 'pop floating fbuild enter', dataAttributes: { 'data-task-menu': true }, role: 'dialog', label: 'Filter by', style: `left:${menuPosition.left}px;top:${menuPosition.top}px;max-width:560px`, items: filterDropdownItems() }}
        onAction={handleTaskDropdownAction}
      />
    {:else if toolbarMenu === 'sort'}
      <Dropdown
        config={{ className: 'pop floating enter', dataAttributes: { 'data-task-menu': true }, role: 'menu', label: 'Sort tasks', style: `left:${menuPosition.left}px;top:${menuPosition.top}px;width:240px`, items: sortDropdownItems() }}
        onAction={handleTaskDropdownAction}
      />
    {:else if toolbarMenu === 'group'}
      <Dropdown
        config={{ className: 'pop floating enter', dataAttributes: { 'data-task-menu': true }, role: 'menu', label: 'Group tasks', style: `left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`, items: groupDropdownItems() }}
        onAction={handleTaskDropdownAction}
      />
    {:else if toolbarMenu === 'columns'}
      <Dropdown
        config={{ className: 'pop floating enter', dataAttributes: { 'data-task-menu': true }, role: 'menu', label: 'Visible columns', style: `left:${menuPosition.left}px;top:${menuPosition.top}px;width:220px`, items: columnDropdownItems() }}
        onAction={handleTaskDropdownAction}
      />
    {/if}
    {#if boardCardMenu}{@const task = tasks.find(item => item.id === boardCardMenu.taskId)}{#if task}<Dropdown
      config={{ className: 'pop floating enter', dataAttributes: { 'data-board-card-menu': true }, role: 'menu', label: boardCardMenu.field === 'priority' ? 'Priority' : boardCardMenu.field === 'due' ? 'Due date' : 'Assignee', style: `left:${boardCardMenuPosition.left}px;top:${boardCardMenuPosition.top}px;${boardCardMenu.field === 'due' ? 'min-width:0' : 'width:220px'}`, items: boardCardDropdownItems(task) }}
      onAction={handleTaskDropdownAction}
    />{/if}{/if}
    {#if view === 'board'}
      <div class="board">{#each statuses as item (item.id)}{@const columnTasks = visibleTasks.filter(task => task.status === item.id)}<section class="bcol" aria-label={item.name} ondragover={event => event.preventDefault()} ondrop={event => handleBoardDrop(event, item.id)}><div class="bcol-h">{@html stIcon(item.id, 14)}<span>{item.name}</span><span class="cnt">{columnTasks.length}</span><span class="sp"></span><button class="ibtn ibtn-xs" aria-label={`Add task to ${item.name}`} onclick={() => startBoardComposer(item.id)}>{@html ic('plus', 13)}</button></div><div class="bcol-b">{#each columnTasks as task (task.id)}{@const progress = taskSubtaskProgress(task)}{@const assignee = task.assignee || members.find(member => String(member.id) === String(task.assignee_id))}{@const sourceMember = (workspaceData.members || []).find(member => String(member.database_id) === String(task.assignee_id))}
        <!-- svelte-ignore a11y_no_noninteractive_element_to_interactive_role -->
        <article class="kcard" class:done={task.status === 'done'} draggable="true" role="button" tabindex="0" aria-label={`Open task ${task.title}`} onclick={event => { if (!event.target.closest('button, a')) drawerTaskId = task.id; }} onkeydown={event => handleBoardCardKeydown(event, task)} ondragstart={event => { draggedTaskId = task.id; event.dataTransfer?.setData('text/plain', String(task.id)); if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'; }} ondragend={() => draggedTaskId = null}>
          {#if task.labels?.length}<div class="labels">{#each task.labels as labelId (labelId)}{@const label = LABELS.find(option => option.id === labelId)}{#if label}<span class="lbl" style={`--c:${label.c}`}><i></i>{label.name}</span>{/if}{/each}</div>{/if}
          <div class="top">{#if task.status === 'done'}<span class="done-ic" style="margin:2px 7px 0 0;display:inline-flex" aria-label="Done">{@html stIcon('done', 14)}</span>{/if}<span class="title">{task.title}</span></div>
          {#if progress.total}<div class="subp">{@html ic('list-checks', 12)}<span class="num">{progress.completed}/{progress.total}</span><span class={`prog ${progress.completed === progress.total ? 'green' : ''}`} role="progressbar" aria-valuenow={progress.percentage} aria-valuemin="0" aria-valuemax="100"><i style={`--p:${progress.percentage / 100}`}></i></span></div>{/if}
          <div class="meta"><Link class="m" href={projectShow(task.project_id).url} aria-label={`Project ${task.project?.name}`}><span class="pdot" style={`--c:var(--${task.project?.color || 'indigo'})`}></span></Link><span class="key">{task.key}</span><button type="button" class="m" data-board-card-trigger aria-label={`Priority: ${task.priority}`} title={`Priority: ${task.priority}`} onclick={event => openBoardCardMenu(event, task, 'priority')}>{@html prIcon(task.priority, 13)}</button>{#if task.due_date}<button type="button" class={`m due ${dueClass(task)}`} data-board-card-trigger aria-label={`Due date: ${dueLabel(task)}`} title="Due date" onclick={event => openBoardCardMenu(event, task, 'due')}>{@html ic('calendar', 12)}{dueLabel(task)}</button>{/if}{#if task.recurrence}<span class="m" title={`Repeats ${task.recurrence.toLowerCase()}`} aria-label={`Repeats ${task.recurrence.toLowerCase()}`}>{@html ic('repeat', 12)}</span>{/if}{#if taskCommentsCount(task)}<span class="m" aria-label={`${taskCommentsCount(task)} comments`}>{@html ic('message-square', 12)}{taskCommentsCount(task)}</span>{/if}{#if task.attachments?.length}<span class="m" aria-label={`${task.attachments.length} attachments`}>{@html ic('paperclip', 12)}{task.attachments.length}</span>{/if}<button type="button" class="m" style="margin-left:auto" data-board-card-trigger aria-label="Assignee" title={assignee?.name || 'Unassigned'} onclick={event => openBoardCardMenu(event, task, 'assignee')}>{#if assignee}<span class="av sm" style={`--c:${assignee.color || sourceMember?.c || 'var(--gray)'}`}>{initials(assignee.name)}</span>{:else}<span class="av empty sm">{@html ic('user', 11)}</span>{/if}</button></div>
          <div class="hacts"><button type="button" class="ibtn ibtn-xs" aria-label={task.status === 'done' ? 'Reopen' : 'Mark complete'} title={task.status === 'done' ? 'Reopen' : 'Mark complete'} onclick={event => { event.stopPropagation(); updateTask(task, { status: task.status === 'done' ? 'todo' : 'done' }); }}>{@html ic(task.status === 'done' ? 'rotate-ccw' : 'check', 13)}</button><button type="button" class="ibtn ibtn-xs" aria-label="Quick edit" title="Quick edit" onclick={event => { event.stopPropagation(); drawerTaskId = task.id; }}>{@html ic('pencil', 12)}</button><button type="button" class="ibtn ibtn-xs" aria-label="More" title="More" onclick={event => { event.stopPropagation(); drawerTaskId = task.id; }}>{@html ic('ellipsis', 13)}</button></div>
        </article>{/each}{#if boardComposerStatus === item.id}<div class="composer"><textarea bind:this={boardComposerInput} bind:value={boardComposerTitle} placeholder="Task name" rows="2" aria-label={`New task in ${item.name}`} onkeydown={event => handleBoardComposerKeydown(event, item.id)}></textarea><div class="row"><span class="faint" style="font-size:11px">Enter to add · Esc to cancel</span><span class="sp"></span><Button type="button" variant="ghost" size="sm" onclick={() => { boardComposerStatus = ''; boardComposerTitle = ''; }}>Cancel</Button><Button type="button" variant="primary" size="sm" onclick={() => commitBoardComposer(item.id)}>Add</Button></div></div>{:else}<button class="addcard" onclick={() => startBoardComposer(item.id)}>{@html ic('plus', 14)}Add task</button>{/if}</div></section>{/each}</div>
    {:else if view === 'table'}
      {#if !visibleTasks.length}
        <div class="empty"><span class="empty-ic">{@html ic('search-x', 20)}</span><h2>No results found</h2><p>No tasks match these filters.</p><Button variant="secondary" size="sm" onclick={clearFilters}>Clear filters</Button></div>
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
          <Dropdown
            config={{ className: 'pop floating enter', dataAttributes: { 'data-table-cell-menu': true }, role: 'menu', style: `left:${tableCellMenu.left}px;top:${tableCellMenu.top}px;width:${['labels', 'deps', 'project'].includes(tableCellMenu.field) ? 240 : 200}px`, items: tableCellDropdownItems(menuTask) }}
            onAction={handleTaskDropdownAction}
          />
        {/if}{/if}
      {/if}
    {:else if !visibleTasks.length}
      <div class="list-wrap"><div class="empty"><span class="empty-ic">{@html ic('list-checks', 20)}</span><h2>No tasks here</h2><p>Add a task to get things moving.</p><Button variant="primary" size="sm" onclick={() => openCreateTask()}>{@html ic('plus', 14)}New task</Button></div></div>
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
