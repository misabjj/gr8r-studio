import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceNotificationReadStateController::markAllRead
* @see app/Http/Controllers/WorkspaceNotificationReadStateController.php:28
* @route '/notifications/read-all'
*/
export const markAllRead = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAllRead.url(options),
    method: 'patch',
})

markAllRead.definition = {
    methods: ["patch"],
    url: '/notifications/read-all',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\WorkspaceNotificationReadStateController::markAllRead
* @see app/Http/Controllers/WorkspaceNotificationReadStateController.php:28
* @route '/notifications/read-all'
*/
markAllRead.url = (options?: RouteQueryOptions) => {
    return markAllRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceNotificationReadStateController::markAllRead
* @see app/Http/Controllers/WorkspaceNotificationReadStateController.php:28
* @route '/notifications/read-all'
*/
markAllRead.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: markAllRead.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\WorkspaceNotificationReadStateController::update
* @see app/Http/Controllers/WorkspaceNotificationReadStateController.php:13
* @route '/notifications/{notification}/read'
*/
export const update = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/notifications/{notification}/read',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\WorkspaceNotificationReadStateController::update
* @see app/Http/Controllers/WorkspaceNotificationReadStateController.php:13
* @route '/notifications/{notification}/read'
*/
update.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

    if (Array.isArray(args)) {
        args = {
            notification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notification: args.notification,
    }

    return update.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceNotificationReadStateController::update
* @see app/Http/Controllers/WorkspaceNotificationReadStateController.php:13
* @route '/notifications/{notification}/read'
*/
update.patch = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

const WorkspaceNotificationReadStateController = { markAllRead, update }

export default WorkspaceNotificationReadStateController