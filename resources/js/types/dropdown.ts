export type DropdownActionRole =
	| "menuitem"
	| "menuitemcheckbox"
	| "menuitemradio";

type DropdownItemBase = {
	id: string;
	className?: string;
	label: string;
	style?: string;
};

export type DropdownHeading = DropdownItemBase & {
	type: "heading";
};

export type DropdownSeparator = {
	id: string;
	type: "separator";
};

export type DropdownText = DropdownItemBase & {
	type: "text";
};

export type DropdownGroup = {
	className?: string;
	id: string;
	items: DropdownItem[];
	label?: string;
	style?: string;
	type: "group";
};

type DropdownInteractiveItem = DropdownItemBase & {
	action?: string;
	ariaLabel?: string;
	checked?: boolean;
	disabled?: boolean;
	danger?: boolean;
	icon?: string;
	iconSize?: number;
	role?: DropdownActionRole;
	shortcut?: string;
	title?: string;
	trailing?: string;
};

export type DropdownAction = DropdownInteractiveItem & {
	action: string;
	type: "action";
};

export type DropdownLink = DropdownInteractiveItem & {
	external?: boolean;
	href: string;
	rel?: string;
	target?: string;
	type: "link";
};

export type DropdownItem =
	| DropdownHeading
	| DropdownSeparator
	| DropdownText
	| DropdownGroup
	| DropdownAction
	| DropdownLink;

export type DropdownConfig = {
	ariaModal?: boolean;
	className?: string;
	dataAttributes?: Record<
		`data-${string}`,
		boolean | number | string | undefined
	>;
	id?: string;
	items?: DropdownItem[];
	label?: string;
	role?: string;
	style?: string;
	tabIndex?: number;
};

export type DropdownActionHandler = (
	action: string,
	item: DropdownAction | DropdownLink,
) => void;
