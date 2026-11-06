export type DropdownActionRole =
	| "menuitem"
	| "menuitemcheckbox"
	| "menuitemradio";

type DropdownItemBase = {
	id: string;
	className?: string;
	graphic?: DropdownGraphic;
	label: string;
	payload?: DropdownPayload;
	stopPropagation?: boolean;
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

export type DropdownElementTag =
	| "a"
	| "b"
	| "button"
	| "div"
	| "fieldset"
	| "input"
	| "kbd"
	| "label"
	| "legend"
	| "li"
	| "ol"
	| "option"
	| "p"
	| "section"
	| "select"
	| "small"
	| "span"
	| "strong"
	| "textarea"
	| "time"
	| "ul";

export type DropdownElementEvent =
	| "blur"
	| "change"
	| "click"
	| "focus"
	| "input"
	| "keydown"
	| "pointerdown"
	| "submit";

export type DropdownPayload = string | number | boolean | null;

export type DropdownGraphic =
	| {
			color: string;
			size?: number;
			type: "dot";
	  }
	| {
			color: string;
			initials: string;
			size?: number;
			type: "avatar";
	  }
	| {
			size?: number;
			type: "priority" | "status";
			value: string;
	  }
	| {
			brand?: boolean;
			color: string;
			name: string;
			size?: number;
			type: "workspace";
	  };

export type DropdownElement = {
	ariaChecked?: boolean;
	ariaExpanded?: boolean;
	ariaLabel?: string;
	ariaPressed?: boolean;
	attributes?: Record<string, boolean | number | string | undefined>;
	checked?: boolean;
	className?: string;
	disabled?: boolean;
	elementId?: string;
	elementType?: string;
	events?: Partial<Record<DropdownElementEvent, string>>;
	graphic?: DropdownGraphic;
	icon?: string;
	iconSize?: number;
	id: string;
	items?: DropdownItem[];
	payload?: DropdownPayload;
	placeholder?: string;
	role?: string;
	selected?: boolean;
	style?: string;
	stopPropagation?: boolean;
	tag: DropdownElementTag;
	text?: string;
	title?: string;
	type: "element";
	value?: string | number;
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
	| DropdownLink
	| DropdownElement;

export type DropdownActionContext = {
	checked?: boolean;
	event: DropdownElementEvent;
	key?: string;
	targetBounds?: {
		bottom: number;
		left: number;
		right: number;
		top: number;
		width: number;
	};
	value?: string | number;
};

export type DropdownActionSource =
	| DropdownAction
	| DropdownElement
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
	stopPointerDownPropagation?: boolean;
	tabIndex?: number;
};

export type DropdownActionHandler = (
	action: string,
	item: DropdownActionSource,
	context?: DropdownActionContext,
) => void;
