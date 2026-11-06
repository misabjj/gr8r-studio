import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::workspace
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
export const workspace = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: workspace.url(options),
    method: 'get',
})

workspace.definition = {
    methods: ["get","head"],
    url: '/settings/workspace',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::workspace
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
workspace.url = (options?: RouteQueryOptions) => {
    return workspace.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::workspace
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
workspace.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: workspace.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::workspace
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
workspace.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: workspace.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::appearance
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
export const appearance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: appearance.url(options),
    method: 'get',
})

appearance.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::appearance
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
appearance.url = (options?: RouteQueryOptions) => {
    return appearance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::appearance
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
appearance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: appearance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::appearance
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
appearance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: appearance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::language
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
export const language = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: language.url(options),
    method: 'get',
})

language.definition = {
    methods: ["get","head"],
    url: '/settings/language',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::language
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
language.url = (options?: RouteQueryOptions) => {
    return language.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::language
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
language.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: language.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::language
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
language.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: language.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::datetime
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
export const datetime = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: datetime.url(options),
    method: 'get',
})

datetime.definition = {
    methods: ["get","head"],
    url: '/settings/datetime',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::datetime
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
datetime.url = (options?: RouteQueryOptions) => {
    return datetime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::datetime
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
datetime.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: datetime.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::datetime
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
datetime.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: datetime.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::members
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
export const members = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: members.url(options),
    method: 'get',
})

members.definition = {
    methods: ["get","head"],
    url: '/settings/members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::members
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
members.url = (options?: RouteQueryOptions) => {
    return members.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::members
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
members.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: members.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::members
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
members.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: members.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::teams
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
export const teams = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: teams.url(options),
    method: 'get',
})

teams.definition = {
    methods: ["get","head"],
    url: '/settings/teams',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::teams
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
teams.url = (options?: RouteQueryOptions) => {
    return teams.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::teams
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
teams.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: teams.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::teams
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
teams.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: teams.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::projects
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
export const projects = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: projects.url(options),
    method: 'get',
})

projects.definition = {
    methods: ["get","head"],
    url: '/settings/projects',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::projects
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
projects.url = (options?: RouteQueryOptions) => {
    return projects.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::projects
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
projects.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: projects.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::projects
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
projects.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: projects.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::permissions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
export const permissions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: permissions.url(options),
    method: 'get',
})

permissions.definition = {
    methods: ["get","head"],
    url: '/settings/permissions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::permissions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
permissions.url = (options?: RouteQueryOptions) => {
    return permissions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::permissions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
permissions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: permissions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::permissions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
permissions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: permissions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::notifEmail
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
export const notifEmail = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifEmail.url(options),
    method: 'get',
})

notifEmail.definition = {
    methods: ["get","head"],
    url: '/settings/notif-email',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::notifEmail
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
notifEmail.url = (options?: RouteQueryOptions) => {
    return notifEmail.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::notifEmail
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
notifEmail.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifEmail.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::notifEmail
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
notifEmail.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifEmail.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::notifPush
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
export const notifPush = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifPush.url(options),
    method: 'get',
})

notifPush.definition = {
    methods: ["get","head"],
    url: '/settings/notif-push',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::notifPush
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
notifPush.url = (options?: RouteQueryOptions) => {
    return notifPush.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::notifPush
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
notifPush.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifPush.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::notifPush
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
notifPush.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifPush.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::notifMentions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
export const notifMentions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifMentions.url(options),
    method: 'get',
})

notifMentions.definition = {
    methods: ["get","head"],
    url: '/settings/notif-mentions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::notifMentions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
notifMentions.url = (options?: RouteQueryOptions) => {
    return notifMentions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::notifMentions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
notifMentions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifMentions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::notifMentions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
notifMentions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifMentions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::notifAssign
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
export const notifAssign = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifAssign.url(options),
    method: 'get',
})

notifAssign.definition = {
    methods: ["get","head"],
    url: '/settings/notif-assign',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::notifAssign
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
notifAssign.url = (options?: RouteQueryOptions) => {
    return notifAssign.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::notifAssign
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
notifAssign.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifAssign.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::notifAssign
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
notifAssign.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifAssign.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::profile
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/settings/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::profile
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::profile
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::profile
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::preferences
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
export const preferences = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preferences.url(options),
    method: 'get',
})

preferences.definition = {
    methods: ["get","head"],
    url: '/settings/preferences',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::preferences
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
preferences.url = (options?: RouteQueryOptions) => {
    return preferences.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::preferences
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
preferences.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preferences.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::preferences
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
preferences.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: preferences.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::shortcuts
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
export const shortcuts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shortcuts.url(options),
    method: 'get',
})

shortcuts.definition = {
    methods: ["get","head"],
    url: '/settings/shortcuts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::shortcuts
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
shortcuts.url = (options?: RouteQueryOptions) => {
    return shortcuts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::shortcuts
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
shortcuts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shortcuts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::shortcuts
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
shortcuts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shortcuts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::password
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
export const password = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: password.url(options),
    method: 'get',
})

password.definition = {
    methods: ["get","head"],
    url: '/settings/password',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::password
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
password.url = (options?: RouteQueryOptions) => {
    return password.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::password
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
password.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: password.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::password
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
password.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: password.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::sessions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
export const sessions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sessions.url(options),
    method: 'get',
})

sessions.definition = {
    methods: ["get","head"],
    url: '/settings/sessions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::sessions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
sessions.url = (options?: RouteQueryOptions) => {
    return sessions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::sessions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
sessions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sessions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::sessions
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
sessions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sessions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::method2fa
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
export const method2fa = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: method2fa.url(options),
    method: 'get',
})

method2fa.definition = {
    methods: ["get","head"],
    url: '/settings/2fa',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::method2fa
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
method2fa.url = (options?: RouteQueryOptions) => {
    return method2fa.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::method2fa
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
method2fa.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: method2fa.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::method2fa
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
method2fa.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: method2fa.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::plan
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
export const plan = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: plan.url(options),
    method: 'get',
})

plan.definition = {
    methods: ["get","head"],
    url: '/settings/plan',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::plan
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
plan.url = (options?: RouteQueryOptions) => {
    return plan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::plan
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
plan.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: plan.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::plan
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
plan.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: plan.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::payment
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
export const payment = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payment.url(options),
    method: 'get',
})

payment.definition = {
    methods: ["get","head"],
    url: '/settings/payment',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::payment
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
payment.url = (options?: RouteQueryOptions) => {
    return payment.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::payment
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
payment.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payment.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::payment
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
payment.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payment.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::invoices
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
export const invoices = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

invoices.definition = {
    methods: ["get","head"],
    url: '/settings/invoices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::invoices
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
invoices.url = (options?: RouteQueryOptions) => {
    return invoices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::invoices
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
invoices.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::invoices
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
invoices.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoices.url(options),
    method: 'head',
})

const settings = {
    index: Object.assign(index, index),
    workspace: Object.assign(workspace, workspace),
    appearance: Object.assign(appearance, appearance),
    language: Object.assign(language, language),
    datetime: Object.assign(datetime, datetime),
    members: Object.assign(members, members),
    teams: Object.assign(teams, teams),
    projects: Object.assign(projects, projects),
    permissions: Object.assign(permissions, permissions),
    notifEmail: Object.assign(notifEmail, notifEmail),
    notifPush: Object.assign(notifPush, notifPush),
    notifMentions: Object.assign(notifMentions, notifMentions),
    notifAssign: Object.assign(notifAssign, notifAssign),
    profile: Object.assign(profile, profile),
    preferences: Object.assign(preferences, preferences),
    shortcuts: Object.assign(shortcuts, shortcuts),
    password: Object.assign(password, password),
    sessions: Object.assign(sessions, sessions),
    method2fa: Object.assign(method2fa, method2fa),
    plan: Object.assign(plan, plan),
    payment: Object.assign(payment, payment),
    invoices: Object.assign(invoices, invoices),
}

export default settings